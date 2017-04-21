<?php
/**
 * @desc Wish订单拉取
 * @author Gordon
 * @since 2015-06-22
 */
class WishSpecialOrder extends WishModel{
	/** @var tinyint 未付款*/
	const  PAYMENT_STATUS_NOT = 0;
	
	/** @var tinyint 已付款*/
	const  PAYMENT_STATUS_END = 1;
	
	/** @var tinyint 刚导入*/
	const  COMPLETE_STATUS_DEFAULT = 0;
	
	/** @var tinyint 待处理*/
	const  COMPLETE_STATUS_PENGDING = 1;
	
	/** @var tinyint 备货中*/
	const  COMPLETE_STATUS_PROCESSIBLE = 2;
	
	/** @var tinyint 已完成*/
	const  COMPLETE_STATUS_END = 3;
	
	/** @var tinyint 异常订单*/
	const  COMPLETE_STATUS_EXCEPTION = 4;
	
	/** @var tinyint 锁定订单*/
	const  COMPLETE_STATUS_WAIT_CANCEL = 9;
	
	/** @var tinyint 未出货*/
	const SHIP_STATUS_NOT = 0;
	
	/** @var tinyint 部分出货*/
	const SHIP_STATUS_PART =  1;
	
	/** @var tinyint 已出货*/
	const SHIP_STATUS_YES = 2;
	
	const PENDIND_STATUS_CUSTOMER=2;
	
	/** @var tinyint  退款状态默认  退款状态:0默认,2全部退款**/
	const REFUND_STATUS_DEFAULT = 0; //默认
	const REFUND_STATUS_PART = 1; //部分
	const REFUND_STATUS_ALL = 2; //全部
	
	const PENDING_STATUS_SEND=0;  //可发待处理
	const PENDING_STATUS_OWE=1;   //欠货待处理
	const PENDING_STATUS_CUSTOMER=2;//客服待处理
	
	
	public $account_name;
	public $ship_info;
	public $ship_status_text;
	public $complete_status_text;
	public $reciver_address;
	public $detail;
	public static $wishAccountPairs;
	
	public static $SPECIAL_BID = array('574d2a7a34220519387dcec6');
	public static $SPECIAL_PNM = array('778 8982407', '778 8982407', '832 8780683',
										'778 8616652', '778 8982407', '778 6535584');
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_wish_order';
	}

    /**
     * @desc 根据订单id查询订单信息
     * @param string $orderId,string $field
     */
    public function getInfoByOrderId( $orderId,$field = '*' ){
    	if( empty($orderId) ) return null;
    	$ret = $this->dbConnection->createCommand()
		    	->select($field)
		    	->from(self::tableName().' AS o')
		    	->andWhere('o.order_id="'.$orderId.'"')
		    	->queryRow();
    	return $ret;
    }	
	
	/**
	 * @desc 根据平台订单号获取订单信息
	 * @param string $platformOrderID
	 * @param string $platformCode
	 * @return mixed
	 */
	public function getOrderInfoByPlatformOrderID($platformOrderID, $platformCode=''){
		$where = '';
		if($platformCode){
			$where .= ' AND platform_code = "'.$platformCode.'"';
		}
		return $this->dbConnection->createCommand()
		->select('*')
		->from(self::tableName())
		->where('platform_order_id = "'.$platformOrderID.'"'.$where)
		->queryRow();
	}
	
	/**
	 * @desc 根据订单号更新订单信息
	 * @param string $orderID
	 * @param array $params
	 */
	public function updateColumnByOrderID($orderID, $params){
		return $this->dbConnection->createCommand()->update(self::tableName(), $params, 'order_id = "'.$orderID.'"');
	}
	
	/**
	 * @desc 设置订单完成状态
	 * @param tinyint $status
	 * @param string $orderID
	 */
	public function setOrderCompleteStatus($status, $orderID){
		return $this->dbConnection->createCommand()->update(self::tableName(), array('complete_status' => $status), 'order_id = "'.$orderID.'"');
	}
	
	/**
	 * @desc 保存订单主表记录
	 * @param array $param
	 */
	public function saveOrderRecord($param){
		if( isset($param['order_id']) ){
			return $this->dbConnection->createCommand()->replace(self::tableName(), $param);
		}else{
			return $this->dbConnection->createCommand()->insert(self::tableName(), $param);
		}
	}
	
	
	/**
	 * 获取wish付款超过3天还没有跟踪号的订单
	 * @param integer $limit string $gmtime integer $accountId string $orderId
	 */
	public function getWishWaitingConfirmOrders($accountId, $gmtime, $orderId = '', $limit = null, $offset = 0, $type = null) {
		if(!$accountId) return null;
		$dbCommand = $this->dbConnection->createCommand()
		->select('o.order_id,o.account_id,o.paytime,o.platform_order_id,o.ship_country,o.timestamp,o.ori_pay_time')
		->from(self::tableName() . " o")
		->where("o.payment_status = ".Order::PAYMENT_STATUS_END)
		->andWhere('o.ship_status='.Order::SHIP_STATUS_NOT)
		->andWhere('o.complete_status != '.Order::COMPLETE_STATUS_END)
		->andWhere("o.paytime < '{$gmtime}'")
		->andWhere("o.platform_code = '".Platform::CODE_WISH."'")
		->andWhere('o.account_id="'.$accountId.'"')
		->andWhere('o.paytime >= "2016-06-30 00:00:00"')
		->order("o.paytime asc, o.timestamp asc");
		if($type == 1){
			$bjDate = date("Y-m-d H:i:s");
			$dbCommand->andWhere("TIMESTAMPDIFF(hour,o.ori_pay_time,'".$bjDate."') > (16) ");
		}
		if (!empty($limit))
			$dbCommand->limit((int)$limit, (int)$offset);
		 
		if(!empty($orderId)){
			$dbCommand->andWhere('o.order_id like "'.$orderId.'%"');
		}
		 
		//echo $dbCommand->text;exit;
		return $dbCommand->queryAll();
	}
	
	
	
	// =================== 列表 ============================= //
	
	public function getAccessUser(){
		$id = Yii::app()->user->id;
		if(in_array($id, array(1, 4, 17, 1373, 1017, 1250))){
			return true;
		}
		exit('待处理');
	}
	/**
	 * @desc 获取账号
	 */
	public function getWishAccountPairs(){
		if(!self::$wishAccountPairs)
			self::$wishAccountPairs = self::model('WishAccount')->getIdNamePairs();
		return self::$wishAccountPairs;
	}
	
	/**
	 * @desc 搜索筛选栏定义
	 * @return multitype:multitype:string  multitype:string multitype:NULL Ambigous <string, string, unknown>   multitype:string NULL
	 */
	public function filterOptions(){
		$shipStatus = Yii::app()->request->getParam('ship_status');
		return array(
				array(
						'name'		=>	'order_id',
						'search'	=>	'LIKE',
						'type'		=>	'text',
						'alias'		=>	't'
	
				),
				array(
						'name'		=>	'platform_order_id',
						'search'	=>	'=',
						'type'		=>	'text',
				
				),
				array(
						'name'		=>	'ship_country_name',
						'search'	=>	'LIKE',
						'type'		=>	'text',
						'alias'		=>	't'
				
				),
				array(
						'name'		=>	'buyer_id',
						'search'	=>	'=',
						'type'		=>	'text',
				
				),
				array(
						'name'		=>	'ship_phone',
						'search'	=>	'=',
						'type'		=>	'text',
						'alias'		=>	't'
				
				),
				array(
						'name'		=>	'sku',
						'search'	=>	'=',
						'type'		=>	'text',
						'rel'		=>	true
				
				),
				array(
						'name'		=>	'ori_pay_time',
						'search'	=>	'range',
						'type'		=>	'text',
						'rel'		=>	true,
						'htmlOptions'	=> array(
							'size' => 4,
							'class'=>'date',
							'datefmt'=>'yyyy-MM-dd HH:mm:ss',
							'style'=>'width:80px;'
						),
				
				),
				array(
						'name'		=>	'account_id',
						'search'	=>	'=',
						'type'		=>	'dropDownList',
						'data'		=>	$this->getWishAccountPairs()
				),
				
				array(
						'name'		=>	'ship_status',
						'search'	=>	'=',
						'type'		=>	'dropDownList',
						'data'		=>	$this->getShipStatusOptions(),
						'value'		=>	$shipStatus
				),
	
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels(){
		return array(
				'account_id'	=>	Yii::t('wish_listing', 'Account Name'),
				'account_name'	=>	Yii::t('wish_listing', 'Account Name'),
				'order_id'		=>	Yii::t('wish_order', 'Order ID'),
				'sku'			=>	'SKU',
				'platform_order_id'	=>	Yii::t('wish_order', 'Platform Order ID'),
				'ship_info'			=>	Yii::t('wish_order', 'Shiping Info'),
				'reciver_address'	=>	Yii::t('wish_order', 'Reciver Address'),
				'ship_country'		=>	Yii::t('wish_order', 'Ship Country'),
				'ship_country_name'	=>	Yii::t('wish_order', 'Ship Country'),
				'buyer_id'			=>	Yii::t('wish_order', 'Buyer ID'),
				'timestamp'			=>	Yii::t('wish_order', 'Down Order Time'),
				'ori_pay_time'		=>	Yii::t('wish_order', 'Beijing Pay Time'),
				'paytime'			=>	Yii::t('wish_order', 'UTC Pay Time'),
				'total_price'		=>	Yii::t('wish_order', 'Total Price'),
				'complete_status'	=>	Yii::t('wish_order', 'Complete Status'),
				'ship_status'		=>	Yii::t('wish_order', 'Ship Status'),
				'ship_phone'		=>	Yii::t('wish_order', 'Buyer Phone'),
		);
	}
	
	
	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'=>'order_id',
		);
	
		$dataProvider = parent::search($this, $sort, '', $this->_setDbCriteria());
		$data = $this->addtion($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	
	private function _setDbCriteria(){
		$cdbCriteria = new CDbCriteria();
		$cdbCriteria->select = 't.*';
		if(!empty($_REQUEST['ori_pay_time'][0])){
			$cdbCriteria->addCondition("ori_pay_time>='{$_REQUEST['ori_pay_time'][0]}'");
		}
		if(!empty($_REQUEST['ori_pay_time'][1])){
			$cdbCriteria->addCondition("ori_pay_time<'{$_REQUEST['ori_pay_time'][1]}'");
		}
		
		$accountId = Yii::app()->request->getParam('account_id');
		$buyerId = Yii::app()->request->getParam('buyer_id');
		$platformOrderId = Yii::app()->request->getParam('platform_order_id');
		$sku = Yii::app()->request->getParam('sku');
		if(!empty($sku)){
			//获取子sku
			$detailCondition = "t.sku='{$sku}' ";
			if($accountId){
				$detailCondition .= " and  o.account_id='{$accountId}' ";
			}
			if($buyerId){
				$detailCondition .= " and o.buyer_id='{$buyerId}' ";
			}
			if($platformOrderId){
				$detailCondition .= " and o.platform_order_id='{$platformOrderId}' ";
			}
			$orderIds = WishSpecialOrderDetail::model()->getOrderIdsByCondition($detailCondition);
			if($orderIds){
				$cdbCriteria->addInCondition('order_id', $orderIds);
			}else{
				$cdbCriteria->addCondition("1=0");
			}
		}
		return $cdbCriteria;
	}
	
	
	public function addtion($datas){
		if(empty($datas)) return $datas;
		$this->getWishAccountPairs();
		$sku = Yii::app()->request->getParam('sku');
		foreach ($datas as &$data){
			$data['account_name'] = self::$wishAccountPairs[$data['account_id']];
			$data['ship_info'] = "";
			//
			$shipInfo = WishSpecialOrderTraceNumber::model()->getShipInfoByOrderId($data['order_id']);
			$data['ship_info'] = $shipInfo ? $shipInfo['ship_name'].":".$shipInfo['trace_number'] : '';
			$data['reciver_address'] = $data['ship_country_name'] . " " . $data['ship_stateorprovince'] . " " . $data['ship_city_name'] . "  ". $data['ship_street1']. "  ". $data['ship_street2'];
			$data['complete_status_text'] = $this->getCompleteStatusOptions($data['complete_status']);
			$data['ship_status_text'] = $this->getShipStatusOptions($data['ship_status']);
			$data['total_price'] = $data['total_price'] ."(".$data['currency'].")";
			$data->detail = array();
			//找出详情
			$orderDetailList = WishSpecialOrderDetail::model()->getOrderDetailByOrderIdAndSKU($data['order_id'], $sku, 'sku, quantity');
			/* if($orderDetailList){
				foreach ($orderDetailList as $detail){
					
				}
			} */
			$data->detail = $orderDetailList;
			
			
		}
		return $datas;
	}
	
	
	public function getCompleteStatusOptions($option = null){
		$options = array(
							self::COMPLETE_STATUS_DEFAULT => '刚导入',
							self::COMPLETE_STATUS_PENGDING	=>	'待处理',
							self::COMPLETE_STATUS_EXCEPTION	=>	'异常订单',
							self::COMPLETE_STATUS_PROCESSIBLE	=>	'备货中',
							self::COMPLETE_STATUS_WAIT_CANCEL	=>	'锁定订单',
							self::COMPLETE_STATUS_END	=>	'已完成',
						);
		if($option !== null) return isset($options[$option]) ? $options[$option] : '';
		return $options;
	}
	
	public function getShipStatusOptions($option = null){
		$options = array(
				self::SHIP_STATUS_NOT	=>	'未发货',
				self::SHIP_STATUS_PART	=>	'部分发货',
				self::SHIP_STATUS_YES	=>	'已出货',
		);
		if($option !== null) return isset($options[$option]) ? $options[$option] : '';
		return $options;
	}
	// =================== 列表 ============================= //
}