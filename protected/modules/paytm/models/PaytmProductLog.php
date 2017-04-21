<?php
/**
 * @desc Paytm产品管理日志记录
 * @author AjunLongLive!
 * @since 2017-03-14
 */
class PaytmProductLog extends PaytmModel {
	
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_paytm_product_log';
    }
    
    /**
     * @desc 插入sku修改日志相关数据
     * @param $logSkuArray
     * @return 成功返回:{'status'=>'success','msg'=>''}，失败返回:{'status'=>'failure','msg'=>'失败的原因'}
     */
    public function updateSkuModifyLog($logSkuArray){
        $return = array('status'=>'success','msg'=>'');
        if (isset($logSkuArray) && !empty($logSkuArray)){
                $logSkuArray['modify_time'] = $nowTime.'';
                $isOk = $this->getDbConnection()->createCommand()
                                                ->insert($this->tableName(), $logSkuArray);
                if($isOk) {
                    $pkId = $this->getDbConnection()->getLastInsertID();
                    $return['id'] = $pkId;
                } else {
                    $return['status'] = 'failure';
                }
    
        } else {
            $return['status'] = 'failure';
            $return['msg'] = '传入的更新数据不能为空！';
        }
        return $return;
    }    
    
    
    
    /**
     * @desc UTC时间格式转换
     * @param unknown $UTCTime
     * @return mixed
     */
    public function transferUTCTimeFormat($UTCTime){
        $UTCTime = strtoupper($UTCTime);
        $newUTCTime = str_replace("T", " ", $UTCTime);
        $newUTCTime = str_replace("Z", "", $UTCTime);
        return $newUTCTime;
    }
    
    /**
     * @desc 转换为北京时间
     * @param unknown $UTCTime
     * @return string
     */
    public function transferToLocal($UTCTime){
        return date("Y-m-d H:i:s", strtotime($UTCTime)+8*3600);
    }
    
    
    
    /**
     * get search info
     */
    public function search() {
        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder'  => 'id',
        );
        $dataProvider = parent::search(get_class($this), $sort,'',$this->_setDbCriteria());
        $data = $this->addtions($dataProvider->data);
        $dataProvider->setData($data);
        return $dataProvider;
    }

    /**
     * 解析json，并获取特定的值，同时输出
     * 
     */
    public function displayJsonValue($jsonString,$jsonKey) {
        $array = json_decode($jsonString,1);
        echo $array[$jsonKey];
    }

    /**
     * 解析json，并获取特定的值，同时输出
     *
     */
    public function displaySettingHtml($productID) {
        //modify stock
        $settingHtml = '';
        $settingHtml .= "<input type='button' value='下架' onClick='modifyChildStatus({$productID},\"offline\")' /><br /><br />";
        $settingHtml .= "<input type='button' value='上架' onClick='modifyChildStatus({$productID},\"online\")' /><br />";
        echo $settingHtml;
    }    
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        //$event = Yii::app()->request->getParam('event');
        //$status = Yii::app()->request->getParam('status');
        //$account_id = Yii::app()->request->getParam('account_id');
        $result = array(
            array(
                'name'=>'account_id',
                'type'=>'dropDownList',
                'search'=>'=',
                'data'=>PaytmLog::model()->getAccountList()
            ),
            array(
                'name'=>'status',
                'type'=>'dropDownList',
                'search'=>'=',
                'data'=>array('-1' => ' 下架 ','1' => ' 上架 ')
            ),
            array(
                'name' => 'product_id',
                'type' => 'text',
                'search' => '=',
                //'alias'	=>	'v',
                'htmlOption' => array(
                    
                ),
            ),            
            array(
                'name' => 'sku',
                'type' => 'text',
                'search' => 'LIKE',
                //'alias'	=>	'v',
                'htmlOption' => array(
                    'size' => '22',
                ),
            ),   
            array(
                'name' => 'paytm_sku',
                'type' => 'text',
                'search' => 'LIKE',
                //'alias'	=>	'v',
                'htmlOption' => array(
                    'size' => '22',
                ),
            ),                        
        );
        return $result;
    }
    
    public function addtions($datas){
        if(empty($datas)) return $datas;
        $account_list = PaytmLog::model()->getAccountList();
        foreach ($datas as $key => &$data){
            //print_r($data);
            if (isset($data['account_id'])){
                if(!isset($account_list[$data['account_id']])){
                    continue;
                }
                //账号名称
                $data['account_id'] = $account_list[$data['account_id']];
            }
            //处理子sku的相关数据
            //print_r($data);
            if (isset($data['product_id']) && !empty($data['product_id'])){
                $datas[$key]->detail = array();
                $parentThisArray = array();
                $parentThisArray['product_id'] = $data['product_id'];
                $parentThisArray['inventory']  = $data['inventory'];
                $parentThisArray['price']      = $data['price'];
                $childSkuArr = $this->getSkusDetailFromParams($data['product_id'],$parentThisArray);
                if ($childSkuArr['status'] == 'success'){
                    if (count($childSkuArr['data']) > 0){
                        foreach ($childSkuArr['data'] as $keyChild => $valChild){
                            //modify stock
                            $eachModifyStockText   = "<input type='text' style='width:28px;' id='stock_value_{$valChild['child_product_id']}' />&nbsp;";
                            $eachModifyStockButton = "<input type='button' value='保存' onClick='modifyChildStock({$valChild['child_product_id']})' />";
                            $childSkuArr['data'][$keyChild]['child_modify_stock'] = $eachModifyStockText . $eachModifyStockButton;
                            //modify price
                            $eachModifyPriceText   = "<input type='text' style='width:28px;' id='price_value_{$valChild['child_product_id']}' />&nbsp;";
                            $eachModifyPriceButton = "<input type='button' value='保存' onClick='modifyChildPrice({$valChild['child_product_id']})' />";
                            $childSkuArr['data'][$keyChild]['child_modify_price'] = $eachModifyPriceText . $eachModifyPriceButton;
                            //stock
                            $arrayTemp = json_decode($valChild['child_inventory'],1);
                            $childSkuArr['data'][$keyChild]['child_inventory'] = $arrayTemp['qty'];
                            $childSkuArr['data'][$keyChild]['child_product_id'] = $data['product_id'].','.$valChild['child_product_id'];
                        }
                        $datas[$key]->detail = $childSkuArr['data'];
                        
                    }                    
                }
                //print_r($datas[$key]->detail);
            }
            //print_r($data);
        }
        return $datas;
    }    
    
    /**
     * @desc 设置数据结构
     * @return CDbCriteria
     */
    protected function _setDbCriteria() {
        $criteria = new CDbCriteria();
        $criteria->addCondition("parent_id is null");
        return $criteria;
    }    
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'id'                                      =>    '序号',
            'account_id'                              =>    '账号名称',
            'product_id'                              =>    '产品ID',
            'account_name'		                      =>	'账号名称',
            'sku'                                     =>	'系统sku',
            'paytm_sku'		                          =>	'在线sku',
            'child_sku'                               =>	'系统子sku',
            'child_paytm_sku'		                  =>	'在线子sku',
            'child_id'		                          =>	'',
            'child_product_id'		                  =>	'',
            'child_price'		                      =>	'价格',
            'child_creat_time'		                  =>	'子sku创建时间',
            'child_update_time'		                  =>	'子sku更新时间',
            'child_modify_stock'		              =>	'修改库存',
            'child_modify_price'		              =>	'修改价格',
            'child_inventory'		                  =>	'库存',
            'status'			                      =>	'产品状态',
            'setting'			                      =>	'设置',
            'name'		                              =>	'标题',
            'pay_money_type'			              =>	'货币',
            'creat_time'			                  =>	'创建时间',
            'update_time'			                  =>	'更新时间',
            'system_child_skus'			              =>	'系统子sku',
            'online_child_skus'			              =>	'在线子sku',
            'child_skus_price'			              =>	'子sku价格',
            'stock'			                          =>	'库存',
            'child_skus_creat_time'			          =>	'子sku创建时间',
            'child_skus_update_time'			      =>	'子sku更新时间',
        );
    }

}