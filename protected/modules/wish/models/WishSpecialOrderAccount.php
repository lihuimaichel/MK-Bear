<?php 
/**
 * @desc paypal交易 Model
 * @author Gordon
 */
class WishSpecialOrderAccount extends WishModel{
    
	const STATUS_YES     = 1; //接收
	const STATUS_NO      = 2; //发起
	
	public $status_text;
	public $schedule_status_text;
	public $update_username;
	public $create_username;
    
    public static function model($className = __CLASS__) {
    	return parent::model($className);
    
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return 'ueb_wish_special_order_account';
    }
    
    /**
     * @desc 保存paypal交易信息
     * @param string $transactionID
     * @param string $orderID
     * @param array $param
     */
    public function saveData($param = array()){
        $res = $this->dbConnection->createCommand()->insert(self::tableName(), $param);
        if($res){
        	return $this->dbConnection->getLastInsertID();
        }
        return false;
    }
    
    
    public function updateDataByID($id, $data){
    	return $this->dbConnection->createCommand()->update(self::tableName(), $data, "id=:id", array(':id'=>$id));
    }
    
    public function updateDataByIDs($ids, $data){
    	return $this->dbConnection->createCommand()->update(self::tableName(), $data, array('IN', 'id', $ids));
    }
    
    
    public function checkUniqueByPhone($buyerPhone, $filterId = null){
    	return $this->getDbConnection()->createCommand()
    	->from(self::tableName())
    	->select('id')
    	->where('buyer_phone=:buyer_phone and buyer_phone<>""', array(':buyer_phone'=>$buyerPhone))
    	->andWhere($filterId ? "id<>{$filterId}" : "1")
    	->queryRow();
    }
    
    
    public function checkUniqueByBuyerId($buyerId, $filterId = null){
    	return $this->getDbConnection()->createCommand()
    									->from(self::tableName())
    									->select('id')
    									->where('buyer_id=:buyer_id  and buyer_id<>""', array(':buyer_id'=>$buyerId))
    									->andWhere($filterId ? "id<>{$filterId}" : "1")
    									->queryRow();
    }
    
    public function checkExistsByBuyerId($buyerId, $status = null){
    	return $this->getDbConnection()->createCommand()
    									->from(self::tableName())
    									->select('id')
    									->where('buyer_id=:buyer_id', array(':buyer_id'=>$buyerId))
    									->andWhere($status ? "status='{$status}'" : "1")
    									->queryRow();
    }
    
    public function checkExistsByBuyerPhone($buyerPhone, $status = null){
    	return $this->getDbConnection()->createCommand()
    									->from(self::tableName())
    									->select('id')
								    	->where('buyer_phone=:buyer_phone', array(':buyer_phone'=>$buyerPhone))
								    	->andWhere($status ? "status='{$status}'" : "1")
								    	->queryRow();
    }
    
    // ========================== search ====================================
    
    /**
     * @desc 搜索筛选栏定义
     * @return multitype:multitype:string  multitype:string multitype:NULL Ambigous <string, string, unknown>   multitype:string NULL
     */
    public function filterOptions(){
    	$status = Yii::app()->request->getParam('status');
    	$scheduleStatus = Yii::app()->request->getParam('schedule_status');
    	return array(
    			array(
    					'name'		=>	'buyer_id',
    					'search'	=>	'=',
    					'type'		=>	'text',
    					'alias'		=>	't'
    
    			),
    			array(
    					'name'		=>	'buyer_email',
    					'search'	=>	'=',
    					'type'		=>	'text',
    
    			),
    			array(
    					'name'		=>	'buyer_phone',
    					'search'	=>	'=',
    					'type'		=>	'text',
    					'alias'		=>	't'
    
    			),
    			array(
    					'name'		=>	'paypal_id',
    					'search'	=>	'=',
    					'type'		=>	'text',
    
    			),
    
    			array(
    					'name'		=>	'status',
    					'search'	=>	'=',
    					'type'		=>	'dropDownList',
    					'data'		=>	$this->getStatusOptions(),
    					'value'		=>	$status
    			),
    			
    			array(
    					'name'		=>	'schedule_status',
    					'search'	=>	'=',
    					'type'		=>	'dropDownList',
    					'data'		=>	$this->getScheduleStatusOptions(),
    					'value'		=>	$scheduleStatus
    			),
    
    
    	);
    }
    
    /**
     * (non-PHPdoc)
     * @see CModel::attributeLabels()
     */
    public function attributeLabels(){
    	return array(
    			'buyer_id'			=>	Yii::t('wish_order', 'Buyer ID'),
    			'status'			=>	Yii::t('wish_order', 'Status'),
    			'paypal_id'			=>	Yii::t('wish_order', 'Paypal ID'),
    			'buyer_phone'		=>	Yii::t('wish_order', 'Buyer Phone'),
    			'buyer_email'		=>	Yii::t('wish_order', 'Buyer Email'),
    			'update_id'			=>	Yii::t('wish_order', 'Update User'),
    			'create_id'			=>	Yii::t('wish_order', 'Create User'),
    			'create_time'		=>	Yii::t('wish_order', 'Create Time'),
    			'update_time'		=>	Yii::t('wish_order', 'Update Time'),
    			'schedule_status'	=>	Yii::t('wish_order', 'Schedule Status'),
    	);
    }
    
    
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'=>'id',
    	);
    
    	$cdbCriteria = new CDbCriteria();
    	$cdbCriteria->select = 't.*';
    	$dataProvider = parent::search($this, $sort, '', $cdbCriteria);
    	$data = $this->addtion($dataProvider->data);
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    
    public function addtion($datas){
    	if(empty($datas)) return $datas;
    	foreach ($datas as &$data){
			$data->status_text = $this->getStatusOptions($data['status']);
			$data->schedule_status_text = $this->getScheduleStatusOptions($data['schedule_status']);
			$data->update_username = MHelper::getUsername($data['update_id']);
			$data->create_username = MHelper::getUsername($data['create_id']);
    	}
    	return $datas;
    }
    
    
    public function getStatusOptions($option = null){
    	$options = array(
    			self::STATUS_YES => '启用',
    			self::STATUS_NO	=>	'停用',
    	);
    	if($option !== null) return isset($options[$option]) ? $options[$option] : '';
    	return $options;
    }
    
    public function getScheduleStatusOptions($option = null){
    	$options = array(
    			self::STATUS_YES => '启用',
    			0	=>	'停用',
    	);
    	if($option !== null) return isset($options[$option]) ? $options[$option] : '';
    	return $options;
    }
    // ========================== end search ================================
}

?>