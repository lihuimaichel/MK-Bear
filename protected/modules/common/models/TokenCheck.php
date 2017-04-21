<?php
/**
 * @desc 检测token是否失效 
 * @author liuj
 * @since 2016-03-30
 */
class TokenCheck extends CommonModel {
	
    /**@var token状态 */
    const STATUS_DEFAULT = 0;       //失败
    const STATUS_SUCCESS = 1;       //成功

    
    /**
     * @desc 获取模型
     * @param system $className
     * @return Ambigous <CActiveRecord, unknown, multitype:>
     */
    public static function model($className = __CLASS__) {
            return parent::model($className);
    }

    /**
     * @desc 设置表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
            return 'ueb_token_check';
    }
	
    /**
     * @desc 保存数据
     * 
     */
    public function addData($data) {
        return $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'id'                        =>      Yii::t('system', 'No.'),
            'account_id'		=>	'账号',
            'platform'                  =>	'平台',
            'time'                      =>	'检测时间',
            'status'			=>	'状态',
            'message'			=>	'检测信息'
        );
    }
    
    /**
     * get search info
     */
    public function search() {
            $sort = new CSort();
            $sort->attributes = array(
                'defaultOrder'  => 'id',
            );
            $dataProvider = parent::search(get_class($this), $sort);
            $data = $this->addtions($dataProvider->data);
            $dataProvider->setData($data);
            return $dataProvider;
    }
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
            $status = Yii::app()->request->getParam('status');
            $platform = Yii::app()->request->getParam('platform');
            $account_id = Yii::app()->request->getParam('account_id');
            $result = array(
                array(
                                'name'=>'status',
                                'type'=>'dropDownList',
                                'search'=>'=',
                                'data'=>$this->getStatusOptions(),
                                'value'=>$status
                ),
                array(
                                'name'=>'platform',
                                'type'=>'dropDownList',
                                'search'=>'=',
                                'data'=>$this->getPlatformOptions(),
                                'htmlOptions'   => array('onchange' => 'getAccount(this)'),
                                'value'=>$platform
                ),
                array(
                                'name'=>'account_id',
                                'type'=>'dropDownList',
                                'search'=>'=',
                                'data'=>$this->getPlatformAccount($platform)
                ),

            );
            return $result;
    }
    

    public function getStatusOptions($status = null){
            $statusOptions = array(
                self::STATUS_DEFAULT    =>  '失败',
                self::STATUS_SUCCESS    =>  '成功',
            );
            if($status !== null)
                return isset($statusOptions[$status])?$statusOptions[$status]:'';
            return $statusOptions;
    }

    public function getPlatformOptions($platform = null){
            $platformOptions = array(
                'aliexpress'        => 'aliexpress',
                'lazada'            => 'lazada',
                'ebay'              => 'ebay',
                'wish'              => 'wish',
                'amazon'            => 'amazon',
                'joom'              => 'joom',
            );
            if($platform !== null)
                    return isset($platformOptions[$platform])?$platformOptions[$platform]:$platform;
            return $platformOptions;
    }
    
    public function addtions($datas){
            if(empty($datas)) return $datas;
            foreach ($datas as &$data){
                //状态
                $data['status'] = $this->getStatusOptions($data['status']);
                //平台
                $data['platform'] = $this->getPlatformOptions($data['platform']);
                //账号名称
                $data['account_id'] = $this->getPlatformAccount(trim($data['platform']), $data['account_id']);
            }
            return $datas;
    }
    
    /**
     * @desc  根据平台获取公司账号
     */
    public function getPlatformAccount($platform,$account_id = null){        
        $arr =array();
        switch($platform){
            case 'lazada':
                $lazada_account = LazadaAccount::model()->getAbleAccountList();
                foreach($lazada_account as $key => $val){
                        $arr[$val['id']] = $val['short_name'];
                }
                if($account_id){
                    if(isset($arr[$account_id]))
                        return $arr[$account_id];
                }else{
                        return $arr;
                }
            break;
            case 'aliexpress':
                $aliexpress_account = AliexpressAccount::model()->getAbleAccountList();
                foreach($aliexpress_account as $key => $val){
                        $arr[$val['id']] = $val['short_name'];
                }
                if($account_id){
                    if(isset($arr[$account_id]))
                        return $arr[$account_id];
                }else{
                        return $arr;
                }
            break;
            case 'amazon':
                $amazon_account = AmazonAccount::model()->getAbleAccountList();
                foreach($amazon_account as $key => $val){
                        $arr[$val['id']] = $val['account_name'];
                }
                if($account_id){
                    if(isset($arr[$account_id]))
                        return $arr[$account_id];
                }else{
                        return $arr;
                }
            break;
            case 'ebay':
                $ebay_account = EbayAccount::model()->getAbleAccountList();
                foreach($ebay_account as $key => $val){
                        $arr[$val['id']] = $val['short_name'];
                }
                if($account_id){
                    if(isset($arr[$account_id]))
                        return $arr[$account_id];
                }else{
                        return $arr;
                }
            break;
            case 'wish':
                $wish_account = WishAccount::model()->getAbleAccountList();
                foreach($wish_account as $key => $val){
                        $arr[$val['id']] = $val['account_name'];
                }
                if($account_id){
                    if(isset($arr[$account_id]))
                        return $arr[$account_id];
                }else{
                        return $arr;
                }
            break;
            case 'joom':
                $joom_account = JoomAccount::model()->getAbleAccountList();
                foreach($joom_account as $key => $val){
                        $arr[$val['id']] = $val['account_name'];
                }
                if($account_id){
                    if(isset($arr[$account_id]))
                        return $arr[$account_id];
                }else{
                        return $arr;
                }
            break;
            default :
            return $arr;
    	}  
    }
}