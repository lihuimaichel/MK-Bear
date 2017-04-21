<?php 
/**
 * @desc paypal交易 Model
 * @author Gordon
 */
class WishSpecialOrderImportTraceNumber extends WishModel{
    
	const STATUS_YES     = 1; //使用
	const STATUS_NO      = 0; //未使用
	const STATUS_EXPIRE	 = 2; //过期
	
	public $status_text;
	public $ship_name;
    
    public static function model($className = __CLASS__) {
    	return parent::model($className);
    
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return 'ueb_wish_special_order_import_trace_number';
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
    
    public function deleteById($ID){
    	if(!is_array($ID)){
    		$ID = array($ID);
    	}
    	return $this->dbConnection->createCommand()->delete(self::tableName(), array('IN', 'id', $ID));
    }

    public function checkExistsByShipCodeAndTraceNumber($shipCode, $traceNumber){
    	return $this->getDbConnection()->createCommand()
    									->from(self::tableName())
    									->select('id')
    									->where('ship_code=:ship_code and trace_number=:trace_number', array(':ship_code'=>$shipCode, ':trace_number'=>$traceNumber))
    									->queryRow();
    }
    
    /**
     * @desc 获取有效追踪号
     * @param array $shipCode
     * @param unknown $shipCountry
     * @param unknown $beforShipDate
     * @param number $limit
     * @return Ambigous <multitype:, mixed>
     */
    public function getValideTraceNumberList($shipCode, $shipCountry, $beforShipDate, $limit = 1){
    	if(! is_array($shipCode)){
    		$shipCode = array($shipCode);
    	}
    	if(isset($_REQUEST['bug'])){
			var_dump(array(':ship_country'=>$shipCountry, ':status'=>self::STATUS_NO));
			var_dump($shipCode);
			var_dump("ship_date>='{$beforShipDate}'");
    	}
    	return $this->getDbConnection()->createCommand()
	    	->from(self::tableName())
	    	->select('id, trace_number as track_num, ship_code, ship_date')
	    	->where('ship_country=:ship_country and status=:status', array(':ship_country'=>$shipCountry, ':status'=>self::STATUS_NO))
	    	->andWhere(array('IN', 'ship_code', $shipCode))
	    	->andWhere("ship_date>='{$beforShipDate}'")
	    	->limit($limit)
    		->queryAll();
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
    					'name'		=>	'trace_number',
    					'search'	=>	'=',
    					'type'		=>	'text',
    					'alias'		=>	't'
    
    			),
    			array(
    					'name'		=>	'ship_country',
    					'search'	=>	'=',
    					'type'		=>	'text',
    
    			),
    			array(
    					'name'		=>	'ship_country_name',
    					'search'	=>	'=',
    					'type'		=>	'text',
    					'alias'		=>	't'
    
    			),
    			array(
    					'name'		=>	'ship_date',
    					'search'	=>	'RANGE',
    					'type'		=>	'text',
    					'htmlOptions'	=> array(
							'size' 		=> 	4,
							'class'		=>	'date',
							'datefmt'	=>	'yyyy-MM-dd HH:mm:ss',
							'style'		=>	'width:80px;'
						),
    
    			),
    			array(
    					'name'		=>	'ship_code',
    					'search'	=>	'=',
    					'type'		=>	'dropDownList',
    					'data'		=>	$this->getShipCodeOptions(),
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
    			'trace_number'			=>	Yii::t('wish_order', 'Order Trace Number'),
    			'status'				=>	Yii::t('wish_order', 'Status'),
    			'ship_country'			=>	Yii::t('wish_order', 'Country Code'),
    			'ship_code'				=>	Yii::t('wish_order', 'Ship Code'),
    			'ship_date'				=>	Yii::t('wish_order', 'Ship Date'),
    			'ship_country_name'		=>	Yii::t('wish_order', 'Ship Country'),
    			'ship_name'				=>	Yii::t('wish_order', 'Ship Code'),	
    			
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
			$data->ship_name = $this->getShipCodeOptions($data['ship_code']);
			
    	}
    	return $datas;
    }
    
    
    public function getStatusOptions($option = null){
    	$options = array(
    			self::STATUS_YES => '使用',
    			self::STATUS_NO	 =>	'未使用',
    			self::STATUS_EXPIRE	=>'过期'
    			
    	);
    	if($option !== null) return isset($options[$option]) ? $options[$option] : '';
    	return $options;
    }
    
    
    public function getShipCodeOptions($shipCode = null){
    	//获取物流
    	$shipCodes = WishSpecialOrderShipCode::model()->getShipCodesPairs();
    	if($shipCode){
    		return isset($shipCodes[$shipCode]) ? $shipCodes[$shipCode] : ''; 
    	}
    	return $shipCodes;
    }
    
    // ========================== end search ================================
}

?>