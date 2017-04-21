<?php

/**
 * @desc EbayItem Best Matches转化率分析表
 * @author yangsh
 * @since 2017-03-20
 */
class EbayItembestmatches extends EbayModel {

    /** @var int 账号id */
    protected $_accountID;

    protected $_exceptionMsg;

    /*列表需要的字段*/
    public $currency;
    public $item_id_link;
    public $meta_categ_name;
    public $categ_lvl2_name;
    public $categ_lvl3_name;
    public $leaf_categ_name;

	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_itembestmatches';
    }

    /**
     * 设置异常信息
     * @param string $message           
     */
    public function setExceptionMessage($message) {
        $this->_exceptionMsg = $message;
        return $this;
    }

    public function getExceptionMessage() {
        return $this->_exceptionMsg;
    }       

    /**
     * 设置账号ID
     * @param int $accountID
     */
    public function setAccountID($accountID) {
        $this->_accountID = $accountID;
        return $this;
    } 

    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
     * @return array
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * getListByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
     * @return array
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }

    /**
     * @desc 获取Item Best Matches转化率分析数据
     * @param  string $dateRange 如：20170215..20170315
     * @return 
     */
    public function getItemBestMatches($dateRange) {
        $request = new Analytics_GetItemBestMatchesRequest();
        $request->setDateRange($dateRange);
        $request->_pageSize = 500;
        $flag = true;
        $errMsg = '';
        $cntCount = 0;
        while($request->_pageNum <= $request->_pageCount ) {
            echo 'pageNum:'.$request->_pageNum."<br>";
            $response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
            if(!$request->getIfSuccess()) {
                if( ($cntCount++) < 3 ) {
                    sleep(120);
                    continue;
                }
            }
            $cntCount = 0;//恢复0
            $request->_pageNum += 1;//设置下页的值
            $request->_pageCount = isset($response->pageTotal) ? intval($response->pageTotal) : 1;
            if(!$request->getIfSuccess()) {
                $flag = false;
                $errMsg .= $request->getErrorMsg();
                break;
            }
            if(empty($response->data)) {
                break;
            }
            foreach ($response->data as $item) {
                try {
                    $isOk = $this->saveEbayItemBestMatchs($item);
                    if(!$isOk) {
                        throw new Exception($item->itemId .' ## '. "Save EbayItemBestMatchs Failure");
                    }
                } catch (Exception $e) {
                    $flag = false;
                    $errMsg2 = $item->itemId .' ## '. $e->getMessage();
                    $errMsg .= $errMsg2;
                    //记录异常日志 
                    $logModel = new EbayLog();
                    $logModel->getDbConnection()->createCommand()->insert(
                        $logModel->tableName(), array(
                            'account_id'    => $this->_accountID,
                            'event'         => EbayLog::EVENT_ITEM_BEST_MATCHES,
                            'start_time'    => date('Y-m-d H:i:s'),                         
                            'status'        => EbayLog::STATUS_FAILURE,
                            'message'       => mb_strlen($errMsg2)>500 ? mb_substr($errMsg2,0,500) : $errMsg2,
                            'response_time' => date('Y-m-d H:i:s'),
                            'end_time'      => date('Y-m-d H:i:s'),
                            'create_user_id'=> intval(Yii::app()->user->id),
                        )
                    );

                }
            }
        }
        $this->setExceptionMessage($errMsg);
        return $flag;
    }

    /**
     * @desc 保存数据
     * @param  object $item
     * @return boolean
     */
    public function saveEbayItemBestMatchs($item) {
        $data = array(
            'account_id'           => $this->_accountID,
            'best_match_id'        => $item->bestMatchId,
            'user_slctd_id'        => isset($item->userSlctdId) ? $item->userSlctdId : '',
            'user_id'              => isset($item->userId) ? $item->userId : '',
            'item_id'              => isset($item->itemId) ? $item->itemId : '',
            'meta_categ_id'        => isset($item->metaCategId) ? $item->metaCategId : 0,
            'categ_lvl2_id'        => isset($item->categLvl2Id) ? $item->categLvl2Id : 0,
            'categ_lvl3_id'        => isset($item->categLvl3Id) ? $item->categLvl3Id : 0,
            'leaf_categ_id'        => isset($item->leafCategId) ? $item->leafCategId : 0,
            'watch_count'          => isset($item->watchCount) ? $item->watchCount : 0,
            'price'                => floatval($item->price),
            'item_site_id'         => isset($item->itemSiteId) ? $item->itemSiteId : '-1',
            'srp_cnt'              => isset($item->srpCnt) ? $item->srpCnt : 0,
            'top3_cnt'             => isset($item->top3Cnt) ? $item->top3Cnt : 0,
            'top10_cnt'            => isset($item->top10Cnt) ? $item->top10Cnt : 0,
            'top_keyword1'         => isset($item->topKeyword1) ? $item->topKeyword1 : '',
            'top_keyword_traffic1' => isset($item->topKeywordTraffic1) ? $item->topKeywordTraffic1 : 0,
            'top_keyword2'         => isset($item->topKeyword2) ? $item->topKeyword2 : '',
            'top_keyword_traffic2' => isset($item->topKeywordTraffic2) ? $item->topKeywordTraffic2 : 0,
            'top_keyword3'         => isset($item->topKeyword3) ? $item->topKeyword3 : '',
            'top_keyword_traffic3' => isset($item->topKeywordTraffic3) ? $item->topKeywordTraffic3 : 0,
            'vi_nonbot_hits'       => isset($item->viNonbotHits) ? $item->viNonbotHits : 0,
            'vi_srp'               => isset($item->viSrp) ? $item->viSrp : 0,
            'vi_direct_click_ebay' => isset($item->viDirectClickEbay) ? $item->viDirectClickEbay : 0,
            'vi_other_source'      => isset($item->viOtherSource) ? $item->viOtherSource : 0,
            'vi_pc'                => isset($item->viPc) ? $item->viPc : 0,
            'vi_portable'          => isset($item->viPortable) ? $item->viPortable : 0,
            'ck_nonbot_hits'       => isset($item->ckNonbotHits) ? $item->ckNonbotHits : 0,
            'ck_srp'               => isset($item->ckSrp) ? $item->ckSrp : 0,
            'ck_direct_click_ebay' => isset($item->ckSrckDirectClickEbayp) ? $item->ckDirectClickEbay : 0,
            'ck_other_source'      => isset($item->ckOtherSource) ? $item->ckOtherSource : 0,
            'ck_pc'                => isset($item->ckPc) ? $item->ckPc : 0,
            'ck_portable'          => isset($item->ckPortable) ? $item->ckPortable : 0,
            'cal_date'             => isset($item->calDate) ? self::transferDateStrToDateTime($item->calDate) : '0000-00-00 00:00:00',
            'refreshed_date'       => isset($item->refreshedDate) ? self::transferDateStrToDateTime($item->refreshedDate) : '0000-00-00 00:00:00',
            'creation_date'        => isset($item->creationDate) ? self::transferDateStrToDateTime($item->creationDate) : '0000-00-00 00:00:00',
            'update_time'          => date('Y-m-d H:i:s'),
        );
        $info = $this->getOneByCondition('id',"account_id='{$this->_accountID}' and best_match_id='{$item->bestMatchId}'");
        if($info) {
            return $this->getDbConnection()->createCommand()->update($this->tableName(),$data,"id={$info['id']}");
        } else {
            return $this->getDbConnection()->createCommand()->insert($this->tableName(),$data);
        }
    }

    /**
     * @desc 转换成标准时间格式
     * @param  string $dateStr 
     * @return string       
     */
    public static function transferDateStrToDateTime($dateStr) {
        return date('Y-m-d H:i:s', substr($dateStr,0,10));
    }

    /**
     * @desc 属性翻译
     */
    public function attributeLabels()
    {
        return array(
            'num'                  => Yii::t('system', 'No.'),
            'account_id'           => '账号',
            'best_match_id'        => '最佳匹配ID',
            'item_id'              => '刊登号',        
            'meta_categ_name'      => '一级品类',
            'categ_lvl2_name'      => '二级品类',
            'categ_lvl3_name'      => '三级品类',
            'leaf_categ_name'      => '未级品类',
            'watch_count'          => 'watch数量',
            'price'                => '价格',
            'currency'             => '币种',
            'item_site_id'         => '站点',
            'srp_cnt'              => '24小时内前20曝光次数',
            'top3_cnt'             => '24小时内前3曝光次数',
            'top10_cnt'            => '24小时内前10曝光次数',
            'top_keyword1'         => '关键字1',
            'top_keyword_traffic1' => '曝光次数1',
            'top_keyword2'         => '关键字2',
            'top_keyword_traffic2' => '曝光次数2',
            'top_keyword3'         => '关键字3',
            'top_keyword_traffic3' => '曝光次数3',            
            'vi_nonbot_hits'       => '浏览量',
            'vi_srp'               => '来自BM的浏览量',
            'vi_direct_click_ebay' => '来自直接点击的浏览量',
            'vi_other_source'      => '来自其他渠道的浏览量',
            'vi_pc'                => '来自PC端的浏览量',
            'vi_portable'          => '来自移动端的浏览量',
            'ck_nonbot_hits'       => '成交量',
            'ck_srp'               => '来自BM的成交量',          
            'ck_direct_click_ebay' => '来自直接点击的成交量',
            'ck_other_source'      => '来其他渠道的成交量量',
            'ck_pc'                => '来自PC端的成交量',
            'ck_portable'          => '来自移动端的成交量',
            'cal_date'             => '计算时间',
            'update_time'          => '拉取时间',
            'meta_categ_id'        => '一级品类',
            'categ_lvl2_id'        => '二级品类',       
        );
    }

    /**
     * @desc 定义URL
     */
    public static function getIndexNavTabId(){
        return Menu::model()->getIdByUrl('/ebay/ebaysellanalytics/listbestmatches');
    }

    public function getEbaySiteConfig() {
        return $this->getDbConnection()->createCommand()
                ->select('*')
                ->from(EbaySiteConfig::tableName())
                ->where('status=1')
                ->queryAll();
    }    

    public function getEbayAccountOptions($accountId = null){
        $accountOptions = EbayAccount::getIdNamePairs();
        if($accountId !== null) {
            return isset($accountOptions[$accountId])?$accountOptions[$accountId]:'';
        }
        return $accountOptions;
    }

    public function getEbaySiteOptions($siteId=null) {
        $siteOptions = array();
        $ebaySiteConfig = $this->getEbaySiteConfig();
        foreach ($ebaySiteConfig as $value) {
            $siteOptions[$value['site_id']] = $value['site_name'];
        }
        if($siteId !== null) {
            return isset($siteOptions[$siteId])?$siteOptions[$siteId]:'';
        }
        return $siteOptions;
    }

    public function getEbayCategoryListBySite($siteId) {
        $lvl1 = $lvl2 = array();
        $condition = "real_site_id='{$siteId}' and level < 3";
        $res = EbayCategory::model()->getListByCondition("category_id,category_name,level",$condition);
        if(!empty($res)) {
            foreach ($res as $v) {
                $level = $v['level'];
                unset($v['level']);
                if($level == 1) {
                    $lvl1[] = $v;
                } else if($level == 2) {
                    $lvl2[] = $v;
                }
            }
        }
        return array($lvl1,$lvl2);
    }

    public function getEbayCatetoryOptions($siteId,$level=1) {
        if($siteId === null) {
            return array();
        }
        $lvl = array();
        $condition = "real_site_id='{$siteId}' and level={$level}";
        $res = EbayCategory::model()->getListByCondition("category_id,category_name,level",$condition);
        if(!empty($res)) {
            foreach ($res as $v) {
                $lvl[$v['level']][$v['category_id']] = $v['category_name'];
            }
        }
        return isset($lvl[$level]) ? $lvl[$level] : array();
    }    

    private function getEbayCategoryList() {
        $rtn = array();
        $condition = $_REQUEST['item_site_id'] !== null && $_REQUEST['item_site_id'] !== '' ? " real_site_id='{$_REQUEST['item_site_id']}' " : "1";
        $res = EbayCategory::model()->getListByCondition("real_site_id,category_id,category_name",$condition);
        if(!empty($res)) {
            foreach ($res as $v) {
                $rtn[$v['real_site_id'].'-'.$v['category_id']] = $v['category_name'];
            }
        }
        return $rtn;
    }

    /**
     * @return array search filter (name=>label)
     */
    public function filterOptions(){
        $siteId = Yii::app()->request->getParam("item_site_id",null);
        $metaCategId = Yii::app()->request->getParam("meta_categ_id",null);
        $categLvl2Id = Yii::app()->request->getParam("categ_lvl2_id",null);
        $siteId = $siteId === '' ? null : $siteId;
        $metaCategId = $metaCategId === '' ? null : $metaCategId;
        $categLvl2Id = $categLvl2Id === '' ? null : $categLvl2Id; 

        return array(
            array(
                'name'   => 'account_id',
                'type'   => 'dropDownList',
                'search' => '=',
                'data'   => $this->getEbayAccountOptions(),
            ),
            array(
                'name'        => 'item_site_id',
                'type'        => 'dropDownList',
                'search'      => '=',
                'data'        => $this->getEbaySiteOptions(),
                'value'       => $siteId,
                'rel'         => 'selectedTodo',
                'htmlOptions' => array('onchange'=>'getEbayCategoryList(this)'),                
            ),
            array(
                    'name'          => 'meta_categ_id',
                    'type'          => 'dropDownList',
                    'data'          => $this->getEbayCatetoryOptions($siteId,1),
                    'search'        => '=',
                    'value'         => $metaCategId,
                    'htmlOptions'   => array(
                        'id'=>'search_meta_categ_id'
                    ),
            ),   
            array(
                    'name'          => 'categ_lvl2_id',
                    'type'          => 'dropDownList',
                    'data'          => $this->getEbayCatetoryOptions($siteId,2),
                    'search'        => '=',
                    'value'         => $categLvl2Id,
                    'htmlOptions'   => array(
                        'id'=>'search_categ_lvl2_id'
                    ),
            ),                         
            array(
                'name'   => 'item_id',
                'type'   => 'text',
                'search' => 'LIKE',
                'alias'  => 't',
            ),  
            array(
                    'name'          => 'cal_date',
                    'type'          => 'text',
                    'search'        => 'RANGE',
                    'alias'         =>  't',
                    'htmlOptions'   => array(
                            'size' => 4,
                            'class'=>'date',
                            'style'=>'width:80px;'
                    ),
            ),                               
        );
    }

    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions(){
        return array('watch_count','srp_cnt','top3_cnt','top10_cnt','top_keyword_traffic1','top_keyword_traffic2','top_keyword_traffic3','vi_nonbot_hits','vi_srp','vi_direct_click_ebay','vi_other_source','vi_pc','vi_portable','ck_nonbot_hits','ck_srp','ck_direct_click_ebay','ck_other_source','ck_pc','ck_portable','cal_date','update_time');
    }

    /**
     * search SQL
     * @return $array
     */
    protected function _setCDbCriteria(){
        $criteria = new CDbCriteria();
        // $criteria->select = '*';
        
        //查询日期限制,不超过7天
        $endCalDate = date('Y-m-d',strtotime('-2 days'));
        $startCalDate = date('Y-m-d',strtotime($endCalDate)-6*86400);
        if(!isset($_REQUEST['cal_date'])) {
            $criteria->addCondition("cal_date >='{$startCalDate}' and cal_date <='{$endCalDate}' ");
        } else {
            $startCalDate = !empty($_REQUEST['cal_date'][0]) ? $_REQUEST['cal_date'][0] : $startCalDate;
            $endCalDate = !empty($_REQUEST['cal_date'][1]) ? $_REQUEST['cal_date'][1] : $endCalDate;
            $diff = strtotime($endCalDate) - strtotime($startCalDate);
            if(ceil($diff/86400) > 6 || $diff <= 0) {
                $criteria->addCondition(" 1=0 ");
            } else {
                $criteria->addCondition("cal_date >='{$startCalDate}' and cal_date <='{$endCalDate}' ");
            }
        }

        if (isset($_REQUEST['item_site_id']) && $_REQUEST['item_site_id'] !== ''){
            $criteria->addCondition("item_site_id = " . (int)$_REQUEST['item_site_id']);
        }

        if (isset($_REQUEST['account_id']) && $_REQUEST['account_id'] !== ''){
            $criteria->addCondition("account_id = " . (int)$_REQUEST['account_id']);
        }

        if (isset($_REQUEST['meta_categ_id']) && $_REQUEST['meta_categ_id'] !== ''){
            $criteria->addCondition("meta_categ_id = " . (int)$_REQUEST['meta_categ_id']);
        }        

        if (isset($_REQUEST['categ_lvl2_id']) && $_REQUEST['categ_lvl2_id'] !== ''){
            $criteria->addCondition("categ_lvl2_id = " . (int)$_REQUEST['categ_lvl2_id']);
        }    
        return $criteria;
    }

    /**
     * @return $array
     */
    public function search(){
        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 'watch_count',
        );
        $criteria = null;
        $criteria = $this->_setCDbCriteria();
        $dataProvider = parent::search(get_class($this), $sort, array(), $criteria);

        $data = $this->addition($dataProvider->data);
        $dataProvider->setData($data);
        return $dataProvider;
    }

    /**
     * @return $array
     */
    public function addition($data){
        $idNameArr = EbayAccount::getIdNamePairs();
        $ebaySiteConfig = $this->getEbaySiteConfig();
        foreach ($ebaySiteConfig as $value) {
            $siteConfig[$value['site_id']] = $value;
        }
        $ebayCategoryList = $this->getEbayCategoryList();
        foreach ($data as $key => $val) {
            $data[$key]->item_id_link = EbayProduct::getItemlink($val['item_id'], $val['item_site_id']);
            $data[$key]->account_id = isset($idNameArr[$val->account_id]) ? $idNameArr[$val->account_id] : '--';
            $data[$key]->currency = isset($siteConfig[$val['item_site_id']]) ?$siteConfig[$val['item_site_id']]['currency']:'--';

            if($val['meta_categ_id']) {
                $siteCateIdKey = $val['item_site_id'].'-'.$val['meta_categ_id'];
                $data[$key]->meta_categ_name = isset($ebayCategoryList[$siteCateIdKey]) ? $ebayCategoryList[$siteCateIdKey]:'--';
            } else {
                $data[$key]->meta_categ_name = '--';
            }
            
            if($val['categ_lvl2_id']) {
                $siteCateIdKey = $val['item_site_id'].'-'.$val['categ_lvl2_id'];
                $data[$key]->categ_lvl2_name = isset($ebayCategoryList[$siteCateIdKey]) ? $ebayCategoryList[$siteCateIdKey]:'--';
            } else {
                $data[$key]->categ_lvl2_name = '--';
            } 
            
            if($val['categ_lvl3_id']) {
                $siteCateIdKey = $val['item_site_id'].'-'.$val['categ_lvl3_id'];
                $data[$key]->categ_lvl3_name = isset($ebayCategoryList[$siteCateIdKey]) ? $ebayCategoryList[$siteCateIdKey]:'--';
            } else {
                $data[$key]->categ_lvl3_name = '--';
            }                        

            if($val['leaf_categ_id']) {
                $siteCateIdKey = $val['item_site_id'].'-'.$val['leaf_categ_id'];
                $data[$key]->leaf_categ_name = isset($ebayCategoryList[$siteCateIdKey]) ? $ebayCategoryList[$siteCateIdKey]:'--';
            } else {
                $data[$key]->leaf_categ_name = '--';
            }
            //放最后
            $data[$key]->item_site_id = isset($siteConfig[$val['item_site_id']]) ? $siteConfig[$val['item_site_id']]['site_name']:'--';
        }
        return $data;
    }    

}