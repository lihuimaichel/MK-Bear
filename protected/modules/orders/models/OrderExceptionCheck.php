<?php
class OrderExceptionCheck extends OrdersModel {

    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
	const EXCEPTION_ORDER_RULE = 1;//订单规则产生的异常
	const EXCEPTION_PROFIT_LOSS = 2;//订单利润亏损产生的异常
	const EXCEPTION_SKU_UNKNOWN = 3;//订单产品未知产生的异常
	const EXCEPTION_TRANSACTION_UNKNOWN = 4;//交易记录未知(无)
	const EXCEPTION_LOGISTICS = 5; //物流规则异常
	/**
	 * Status
	 * @var Tinyint
	 */
	
	const STATUS_DEFAULT = 1;//未处理
	const STATUS_FINISHED = 2;//已处理
	const STATUS_MANUAL_FINISHED = 3;//手动标记已处理(手动标记那些未做相关处理也可以正常的订单，如第一天利润亏损，第二天运费变低，不亏损了)
	
	//Addition Data
	public $option = '';
	public $order_id='';
	public $order_status = '';
	public $platform_code;
	public $account_id;
	public $loseProfitsku;
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'ueb_order_exception_check';
    }
    
    /**
     * 获取订单异常类型
     * @return array
     */
    public function getExceptionType(){
    	return array(
    			self::EXCEPTION_ORDER_RULE => Yii::t('order', 'Order Rule Exception'),
    			self::EXCEPTION_PROFIT_LOSS => Yii::t('order', 'Profit Loss Exception'),
    			self::EXCEPTION_SKU_UNKNOWN => Yii::t('order', 'Order Sku Unknown Exception'),
    			self::EXCEPTION_LOGISTICS   => '物流规则异常',
    	);
    }
    
    /**
     * 获取处理状态
     * @return array
     */
    public function getStatusArr(){
    	return array(
    			self::STATUS_DEFAULT => Yii::t('order', 'Exception Status Default'), 
    			self::STATUS_FINISHED => Yii::t('order', 'Exception Status Finished'),
    			self::STATUS_MANUAL_FINISHED => Yii::t('order', 'Exception Status Manual Finished'),
    	);
    }
    
    /**
     * 增加订单异常记录
     * @param string $orderId
     * @param array $params
     */
    public function addExceptionRecord($orderId, $exceptionType, $params = array()){
    	$model = new self();
    	$params['order_id'] = $orderId;
    	$params['exception_type'] = $exceptionType;
    	foreach($params as $column=>$value){
    		$model->setAttribute($column, $value);
    	}
    	$checkException = $this->checkExceptionRecordExsit($orderId,$exceptionType);
    	if( $checkException === true || $checkException === false ){//返回true为已处理，再新增一条记录;返回false没记录，也增加一条记录
    		$model->setAttribute('create_user_id', Yii::app()->user->id);
    		$model->setAttribute('create_time', date('Y-m-d H:i:s'));
    		$model->setAttribute('modify_user_id', Yii::app()->user->id);
    		$model->setAttribute('modify_time', date('Y-m-d H:i:s'));
    	}else{//返回ID update记录
    		$model->setAttribute('id', $checkException);
    		$model->setAttribute('modify_user_id', Yii::app()->user->id);
    		$model->setAttribute('modify_time', date('Y-m-d H:i:s'));
    		$model->setIsNewRecord(false);
    	}
    	return $model->save();//保存记录
    }
    
    /**
     * 检查是否存在订单的检测记录
     * @param unknown $orderId
     * @return boolean (true为有记录，但已处理;false为没记录;返回ID为未处理)
     */
    public function checkExceptionRecordExsit($orderId,$exceptionType){
    	$exceptionRecord = $this->findByAttributes(array(
    			'order_id'	=> $orderId,
    			'exception_type' => $exceptionType
    	));
    	if( $exceptionRecord !== null){//存在记录
    		if( $exceptionRecord->status == self::STATUS_DEFAULT ){
    			return $exceptionRecord->id;
    		}else{
    			return true;
    		}
    	}else{
    		return false;
    	}
    }
    
    /**
     * 根据订单和异常类型获取信息
     * @param string $orderId
     * @param tinyInt $type
     * @return array
     */
    public function getOrderExceptionRecord($orderId, $type){
    	return $this->getDbConnection()->createCommand()
    				->select('*')
    				->from(self::tableName())
    				->where('order_id = "'.$orderId.'"')
    				->andWhere('exception_type = '.$type)
    				->queryRow();
    }
    
    /**
     * 根据订单和异常类型获取信息
     * @param string $orderId
     * @param tinyInt $type tinyInt $status
     * @return array
     */
    public function getOrderExceptionByStatus($orderId,$type, $status){
    	 $data = $this->getDbConnection()->createCommand()
    	->select('id')
    	->from(self::tableName())
    	->where('order_id = "'.$orderId.'"')
    	->andWhere('exception_type = '.$type)
    	->andWhere('status = '.$status)
    	->queryColumn();
    	 return $data;
    }
    /**
     * 
     */
    public function setProfitLossLessThanFive($orderId){
    	$updateArr=array();
    	$updateArr['status']=self::STATUS_FINISHED;
    	$updateArr['check_user_id']=Yii::app()->user->id;
    	$updateArr['check_time']=date('Y-m-d H:i:s');

    	$flag=UebModel::model('OrderExceptionCheck')->updateAll($updateArr,"order_id='$orderId'");
    	return $flag;
    }
   
    /**
     * filter search options
     * @return type
     */
    
    public function filterOptions(){
    	if($_REQUEST[platform_code]){
    		$arr = UebModel::model('Order')->getPlatformAccount(trim($_REQUEST[platform_code]));
    	}else{
    		$arr = array();
    	}
    	$complete_status = Yii::app()->request->getParam('complete_status');
    	$result = array(
    			array(
    					'name'          => 'order_id',
    					'type'          => 'text',
    					'search'        => 'LIKE',
    					'value'			=> UebModel::model('AutoCode')->getcodePrefixbyCodeType('order').date('ymd'),
    					'alias'			=> 't',
    			),
    			array(
    					'name'          => 'exception_type',
    					'type'          => 'dropDownList',
    					'search'        => '=',
    					'data'          => self::getExceptionType(),
    					'alias'			=> 't',
    			),
	    		array(
	    			'name'          => 'platform_code',
	    			'type'          => 'dropDownList',
	    			'search'        => '=',
	    			'data'          => UebModel::model('platform')->getPlatformList(),
	    			'htmlOptions'   => array('onchange' => 'getAccount(this)'),
	    			'alias'			=> 'a',
	    		),
	    		
	    		array(
	    			'name'          => 'account_id',
	    			'type'          => 'dropDownList',
	    			'search'        => '=',
	    			'data'          => $arr,
	    			'htmlOptions'   => array(),
	    			'alias'			=> 'a',
	    		),
    			array(
	    			'name'          => 'complete_status',
	    			'type'          => 'dropDownList',
	    			'search'        => '=',
	    			'value'         => $complete_status,
	    			'data'          => UebModel::model('Order')->getOrderCompleteStatusList(),
	    			'htmlOptions'   => array(),
	    			'alias'			=> 'a',
    			),
    			array(
    					'name'          => 'status',
    					'type'          => 'dropDownList',
    					'search'        => '=',
    					'data'          => self::getStatusArr(),
    					'alias'			=> 't',
    			),
    	);
    	return $result;
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
    	$labels = array(
    			'id'                            => Yii::t('system', 'No.'),
    			'order_id'                      => Yii::t('order', 'Order Id'),
    			'loseProfitsku'					=> Yii::t('products','Lose Profit Sku'),
    			'loseProfit'					=> Yii::t('order','Lose Profit'),
     			'exception_type'                => Yii::t('order', 'Exception Type'),
    			'status'               			=> Yii::t('order', 'Exception Status'),
    			'complete_status'				=> Yii::t('order', 'Complete Status'),
    			'create_time'                   => Yii::t('system', 'Create Time'),
    			'check_time'                    => Yii::t('order', 'Check Time'),
    			'check_user_id'					=> Yii::t('order', 'Check User'), 
    			'exception_reason'              => Yii::t('order', 'Exception Reason'),
    			'option'						=> Yii::t('system', 'Operation'),
    			'order_status'					=> Yii::t('order', 'Order Status'),
    			'platform_code'					=> Yii::t('order', 'Platform Code'),
	        	'account_id'					=> Yii::t('order', 'Account Id'),
    	);
    	return $labels;
    }
    
    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {
    	return array(
    			'create_time','check_time'
    	);
    }
    
    public function search() {
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'  => 'id',
    	);
    	$dataProvider = parent::search(get_class($this), $sort,array(),$this->_setCDbCriteria());

    	$data = $this->addition($dataProvider->data);
    	
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
	private function _setCDbCriteria(){
    	$criteria = new CDbCriteria();
    	$criteria->select = 't.*,a.platform_code,a.account_id';
    	$criteria->join   = 'left join '.UebModel::model('Order')->tableName().' a on t.order_id = a.order_id';   	
    	return $criteria;	
    }
    /**
     * addition information
     * @param type $dataProvider
     */
    public function addition($data) {
    	
    	$typeList = $this->getExceptionType();//获取异常类型
    	$statusList = $this->getStatusArr();//获取状态
    	$orderIdArr = array();
    	foreach ($data as $key => $val) {
    		$orderIdArr[] = $val->order_id;
    	}
    	$orders = UebModel::model('Order')->getOrderInfoByOrderIds( array_unique($orderIdArr) );//获取订单信息
//     	$orderDetail = UebModel::model('OrderDetail')->orderDetailListByorderIds($order_ids);
    	$orderArr = array();
    	foreach($orders as $order){
    		$orderArr[$order['order_id']] = $order;
    	}
    	foreach ($data as $key => $val) {
    		$color = '';
    		$option = '';
    		if( $val->status == self::STATUS_DEFAULT ){
    			$color = 'red';
    		}elseif( $val->status == self::STATUS_FINISHED ){
    			$color = 'green';
    		}else{
    			$color = 'blue';
    		}
    		$orderDetail = UebModel::model('OrderDetail')->orderDetailListByorderIds($val->order_id);
    		$sku = '';
    		foreach ($orderDetail as $item){
    			$sku .= CHtml::link($item['sku'],'/products/product/view/do/view/sku/'.$item['sku'],
														array(
															'height' => '600', 
															'width' => '800', 
															'target' => 'dialog', 
															'style'=> 'color:blue', 
															'title' => '查看')
														).'<br/>';
    		}
    		$data[$key]->loseProfitsku = $sku;
    		$data[$key]->option = CHtml::dropDownList('exceptionOption['.$data[$key]->id.']','', $this->getOptionByTypeAndStatus($val->exception_type, $val->status), array(
    				'empty'=>Yii::t('system', 'Please Select'),'onchange' =>'checkOrderException(this)' ,
    		));//设置异常处理操作
    		$data[$key]->option .= CHtml::link('', "/orders/order/update/ids/".$val->order_id, array(
    				'class' => 'modify_order',
    				'rel'	=> 'orderOption',
    				'target'=> 'dialog',
    				'width'	=> '800',
    				'height'=> '600',
    				'style'	=> 'display:none',
    		));//修改订单
    		$data[$key]->option .= CHtml::link('', "/orders/orderexceptioncheck/release/id/".$val->id, array(
    				'class' 	=> 'release_order',
    				'rel'		=> 'orderOption',
    				'target'	=> 'ajax',
    				'style'		=> 'display:none',
    				'postType' 	=> 'string',
    				'callback' 	=> 'navTabAjaxDone',
    				'title'		=> Yii::t('order', 'Really want to release these orders'),
    		));//放行订单
    		
    		$data[$key]->option .= CHtml::link('', "/orders/orderexceptioncheck/cancelrelease/id/".$val->id, array(
    				'class' 	=> 'cancel_release',
    				'rel'		=> 'orderOption',
    				'target'	=> 'ajax',
    				'style'		=> 'display:none',
    				'postType' 	=> 'string',
    				'callback' 	=> 'navTabAjaxDone',
    				'title'		=> Yii::t('order', 'Really want to release these orders'),
    		));//取消放行订单
    		$data[$key]->exception_type = isset($typeList[$val->exception_type]) ? $typeList[$val->exception_type] : 'Unknown';
    		$data[$key]->status = isset($statusList[$val->status]) ? '<div style="color:'.$color.'">'.$statusList[$val->status].'</div>' : 'Unknown';
    		$data[$key]->order_status = isset($orderArr[$val->order_id]) ? 
    									UebModel::model('Order')->getOrderCompleteStatus($orderArr[$val->order_id]['complete_status']).'<br/>'.
    									UebModel::model('Order')->getOrderShipStatus($orderArr[$val->order_id]['ship_status']) : 
    									'No Order Data';
    	}
    	return $data;
    }
    
    
    /**
     * @desc  返回订单包裹管理界面URL
     * 
     */
    public static function getIndexNavTabId() {
    	return Menu::model()->getIdByUrl('/orders/orderexceptioncheck/list');
    }
    
    
    /**
     * 获取操作动作
     * @param tinyint $type
     * @param tinyint $status
     * @return array
     */
    public function getOptionByTypeAndStatus($type, $status){
    	$optionArr = array();
    	if( $status != self::STATUS_DEFAULT ){//已处理
    		$optionArr['cancel_release'] = Yii::t('order', 'Cancel Release The Order');
    	}else{//未处理
    		$optionArr['modify_order'] = Yii::t('order', 'Edit the order');
    		if( $type == self::EXCEPTION_PROFIT_LOSS ){
    			$optionArr['release_order'] = Yii::t('order', 'Release the order');
    		}
    	}
    	return $optionArr;
    }
    /** 
     * 获取异常订单
     * @param $orderId
     * @return boolean
     */
    public function getOrderIdsByOrderId($orderId){
    	return  $this->getDbConnection()->createCommand()
    	->select('order_id')
    	->from(self::tableName())
    	->where("order_id ='{$orderId}'")
    	->queryRow();
    }
    /**
     * 根据ID获取异常的订单
     * @param array $ids
     */
    public function getOrderIdsByIds($ids){
    	$orderArr = array();
    	$ids = is_array($ids) ? $ids : array($ids);
    	$list = $this->getDbConnection()->createCommand()
    				->select('order_id')
    				->from(self::tableName())
    				->where('id IN ('.implode(',', $ids).')')
    				->queryAll();
    	foreach($list as $item){
    		$orderArr[] = $item['order_id'];
    	}
    	return array_unique($orderArr);
    }
    
    /**
     * 根据订单Id 和规则类型 查看是否存在 异常订单信息
     */
    public function getExceptionInfoByOrderId($orderId,$exceptionType){
    	return $this->getDbConnection()->createCommand()
    	->select('*')
    	->from(self::tableName())
    	->where('order_id="'.$orderId.'" and exception_type = "'.$exceptionType.'"')
    	->queryRow();    	 
    }
    /**
     * 更改异常记录的状态
     * @param array $ids
     * @param tinyint $status
     * @return boolean
     */
    public function setExceptionStatus($ids, $status){
    	$ids = is_array($ids) ? $ids : array($ids);
    	$updateArr['status'] = $status;
    	if( $status != self::STATUS_DEFAULT ){//添加处理人和处理时间
    		$updateArr['check_user_id'] = Yii::app()->user->id;	
    		$updateArr['check_time'] = date('Y-m-d H:i:s');
    	}
    	$flag = $this->updateAll($updateArr,'id IN ('.implode(',',$ids).')');
    	return $flag;
    }
    
    public function saveOrderException($orderExceptionObj){
    	$ableObj = $this->findByAttributes(
    		array('order_id'=>$orderExceptionObj->order_id)
    	);
    	
    	if($ableObj!=null){
    		foreach($orderExceptionObj as $_key => $_value){
				$ableObj->$_key = $_value;
			}
		}else{
			$ableObj = new self();
			foreach($orderExceptionObj as $key => $value){
				$ableObj->setAttribute($key,$value);
			}
		}
		$result = $ableObj->save();	
		return $result;		
    }
    
    public function orderLoseProfitNote($info,$shipCost=''){
    	if(isset($info['ship_cost'])){
    		$shipCost = $info['ship_cost'];
    	}
		$tips = '';
		$tips .= Yii::t('orderexceptioncheck','orderAmount').$info['amount'].'<br>';
		$tips .= Yii::t('orderexceptioncheck','expectWeight').$info['weight'].'<br>';
		$tips .= Yii::t('orderexceptioncheck','finalValueFee').$info['final_value_fee'].'<br>';
    //	$tips .= Yii::t('orderexceptioncheck','commission').$info['final_value_fee'].'<br>';
    	$tips .= Yii::t('orderexceptioncheck','productCost').$info['cost'].','.Yii::t('orderexceptioncheck','loseProfit').'<br>';
	//	$tips .= Yii::t('orderexceptioncheck','selectedLogisticName').$info['cost'].'<br>';
    	$tips .= Yii::t('orderexceptioncheck','logisticCost').$shipCost;
    	
    	return $tips;
    	    	
    }
    
    /**
     * 获取利润亏损 并且没有处理的订单
     */
    public function getProfitLossOrders(){
    	$data=$this->getDbConnection()->createCommand()
    	->select('*')
    	->from(self::tableName())
    	->where('exception_type="'.self::EXCEPTION_PROFIT_LOSS.'" and status = "'.self::STATUS_DEFAULT.'"')
    	->limit(2000)
    	->order('create_time desc')
    	->queryAll();
		if(!empty($data)){
			return $data;
		}
    }
}