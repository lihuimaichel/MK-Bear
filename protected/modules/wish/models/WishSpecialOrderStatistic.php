<?php
/**
 * @desc Wish订单拉取
 * @author Gordon
 * @since 2015-06-22
 */
class WishSpecialOrderStatistic extends WishModel{
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
	
	
	public $price_count;
	public $sku_count;
	public $order_count;
	
	public static $wishAccountPairs;

	
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
	
	
	
	
	// =================== 列表 ============================= //

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
		return array(
				array(
						'name'		=>	'order_id',
						'search'	=>	'LIKE',
						'type'		=>	'text',
						'alias'		=>	't',
	
				),
				array(
						'name'		=>	'platform_order_id',
						'search'	=>	'=',
						'type'		=>	'text',
						'alias'		=>	't',
				),
				array(
						'name'		=>	'ship_country_name',
						'search'	=>	'LIKE',
						'type'		=>	'text',
						'alias'		=>	't',
				
				),
				array(
						'name'		=>	'buyer_id',
						'search'	=>	'=',
						'type'		=>	'text',
						'alias'		=>	't',
				
				),
				array(
						'name'		=>	'ship_phone',
						'search'	=>	'=',
						'type'		=>	'text',
						'alias'		=>	't',
				
				),
				array(
						'name'		=>	'sku',
						'search'	=>	'=',
						'type'		=>	'text',
						'alias'		=>	'd'
				
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
						'alias'		=>	't',
						'data'		=>	$this->getWishAccountPairs()
				),
				
				array(
						'name'		=>	'ship_status',
						'search'	=>	'=',
						'type'		=>	'dropDownList',
						'alias'		=>	't',
						'data'		=>	$this->getShipStatusOptions()
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
				'ship_phone'		=>	Yii::t('wish_order', 'Ship Phone'),
				'order_count'		=>	Yii::t('wish_order', 'Order Count'),
				'sku_count'			=>	Yii::t('wish_order', 'Sku Count'),
				'price_count'		=>	Yii::t('wish_order', 'Price Count'),
		);
	}
	
	
	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'=>'t.order_id',
		);
	
		$dataProvider = parent::search($this, $sort, '', $this->_setDbCriteria());
		//$data = $this->addtion($dataProvider->data);
		//$dataProvider->setData($data);
		return $dataProvider;
	}
	
	
	private function _setDbCriteria(){
		/**
		SELECT buyer_id, COUNT(o.order_id) as order_count, COUNT(d.id) as sku_count, SUM(o.total_price) as price_count
		FROM `ueb_wish_order` o
		JOIN `ueb_wish_order_detail` d on d.order_id=o.order_id
		GROUP BY o.`buyer_id`;
		 */
		$cdbCriteria = new CDbCriteria();
		$cdbCriteria->select = 't.buyer_id, COUNT(DISTINCT t.order_id) as order_count, COUNT(d.id) as sku_count, SUM(d.total_price+d.ship_price) as price_count';
		$cdbCriteria->group = "t.buyer_id";
		$cdbCriteria->join = "join `ueb_wish_order_detail` d on d.order_id=t.order_id";
		if(!empty($_REQUEST['ori_pay_time'][0])){
			$cdbCriteria->addCondition("t.ori_pay_time>='{$_REQUEST['ori_pay_time'][0]}'");
		}
		if(!empty($_REQUEST['ori_pay_time'][1])){
			$cdbCriteria->addCondition("t.ori_pay_time<'{$_REQUEST['ori_pay_time'][1]}'");
		}
		
		$accountId = Yii::app()->request->getParam('account_id');
		$buyerId = Yii::app()->request->getParam('buyer_id');
		$platformOrderId = Yii::app()->request->getParam('platform_order_id');
		$sku = Yii::app()->request->getParam('sku');
		if(!empty($sku)){
			//获取子sku
			$detailCondition = "d.sku='{$sku}' ";
			if($accountId){
				$detailCondition .= " and  t.account_id='{$accountId}' ";
			}
			if($buyerId){
				$detailCondition .= " and t.buyer_id='{$buyerId}' ";
			}
			if($platformOrderId){
				$detailCondition .= " and t.platform_order_id='{$platformOrderId}' ";
			}
			
			$cdbCriteria->addCondition($detailCondition);
			
		}
		return $cdbCriteria;
	}
	
	
	public function addtion($datas){
		if(empty($datas)) return $datas;
		$this->getWishAccountPairs();
		$sku = Yii::app()->request->getParam('sku');
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