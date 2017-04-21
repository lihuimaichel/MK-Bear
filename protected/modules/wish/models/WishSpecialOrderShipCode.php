<?php 
/**
 * @desc paypal交易 Model
 * @author Gordon
 */
class WishSpecialOrderShipCode extends WishModel{
    
	const STATUS_YES     = 1; //开启
	const STATUS_NO      = 2; //关闭
	
	public $status_text;
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
        return 'ueb_wish_special_ship_code';
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
    

    
    public function checkExistsByShipCode($shipCode, $filterIds = array(), $status = null){
    	return $this->getDbConnection()->createCommand()
    									->from(self::tableName())
    									->select('id')
    									->where('ship_code=:ship_code', array(':ship_code'=>$shipCode))
    									->andWhere($status ? "status='{$status}'" : "1")
    									->andWhere($filterIds ? array('not in', 'id', $filterIds) : '1')
    									->queryRow();
    }
    
    public function checkExistsByShipName($shipName, $filterIds = array(), $status = null){
    	return $this->getDbConnection()->createCommand()
    	->from(self::tableName())
    	->select('id')
    	->where('ship_name=:ship_name', array(':ship_name'=>$shipName))
    	->andWhere($status ? "status='{$status}'" : "1")
    	->andWhere($filterIds ? array('not in', 'id', $filterIds) : '1')
    	->queryRow();
    }
   
    
    public function getAvailableShipCodes(){
    	return $this->getDbConnection()->createCommand()
    	->from(self::tableName())
    	->select('ship_code')
    	->where("status=".self::STATUS_YES)
    	->queryColumn();
    }
    
    public function getShipCodesPairs(){
    	$shipCodes = $this->getDbConnection()->createCommand()
    	->from(self::tableName())
    	->select('ship_code, ship_name')
    	->queryAll();
    	$newShipCodes = array();
    	if($shipCodes){
    		foreach ($shipCodes as $shipCode){
    			$newShipCodes[$shipCode['ship_code']] = $shipCode['ship_name'];
    		}
    	}
    	return $newShipCodes;
    }
    // ========================== search ====================================
    
    /**
     * @desc 搜索筛选栏定义
     * @return multitype:multitype:string  multitype:string multitype:NULL Ambigous <string, string, unknown>   multitype:string NULL
     */
    public function filterOptions(){
    	$status = Yii::app()->request->getParam('status');
    	return array(
    			array(
    					'name'		=>	'ship_name',
    					'search'	=>	'=',
    					'type'		=>	'text',
    					'alias'		=>	't'
    
    			),
    			array(
    					'name'		=>	'ship_code',
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
    
    
    	);
    }
    
    /**
     * (non-PHPdoc)
     * @see CModel::attributeLabels()
     */
    public function attributeLabels(){
    	return array(
    			'status'			=>	Yii::t('wish_order', 'Status'),
    			'update_id'			=>	Yii::t('wish_order', 'Account Name'),
    			'create_id'			=>	Yii::t('wish_order', 'Account Name'),
    			'create_time'		=>	Yii::t('wish_order', 'Create Time'),
    			'update_time'		=>	Yii::t('wish_order', 'Update Time'),
    			'ship_name'			=>	Yii::t('wish_order', 'Ship Name'),
    			'ship_code'			=>	Yii::t('wish_order', 'Ship Code'),
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
    
    // ========================== end search ================================
}

?>