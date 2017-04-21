<?php
/**
 * 
 * @author xiej 2015-6-12
 * @package Website
 * @since 专门用来订单下载   ---其他操作暂时未同步过来
 */
abstract class WebsiteOrderController extends WebsiteController{
	
	//
	public $runThreadsCount = '10';
	public $perThreadCount = '10';
	//不要有这个方法
	// 	public function __construct(){

	// 	}
	public function accessRules(){
		return array(
				array(
						'allow',
						'users' => array('*'),
						'actions' => array('getpaypaltransactionrecords','downloadorder','crondownloadorder','downloadorderperday','checkundownlodorderlist','croncreateshipment','createshipment','createcreditmemo','updateinvoice','chart')
				),
		);
	}
	/**

	 * 之前用来补齐paypal record 使用
	 */
	public function actionGetPaypalTransactionRecords(){
		$transactionId  = Yii::app()->getRequest()->getParam('transaction_id',null);
		$orderService = new WebsiteOrderService();
		$orderService->getPaypalTransactionRecords($this->getPlatformcode(),$transactionId);
		echo 'done';
	}
	/**
	 * 根据 订单号单个订单下载
	 */
	public function actionDownloadOrder(){
		$platformOrderId = Yii::app()->getRequest()->getParam('platformOrderId',null);
		if($platformOrderId) {
			$orderService = new WebsiteOrderService();
			$result = $orderService->downloadOrder($this->getPlatformcode(), $platformOrderId);
			if (TRUE === $result){
				echo $platformOrderId.' has been download!';
				return;
			}
		} else {
			 $message = Yii::t('websites', 'Please Input OrderID') ;
		}
		//失败
		$message = isset($message) ? $message : $result;
		echo $this->failureJson(array(
				'message'=> $message
		));
	} 
	/**
	 * 
	 */
	public function actionGCOrderRefunds(){
		$platformCode = $this->getPlatformcode();
		$orderService = new WebsiteOrderService();
	}
	/**
	 * 自动下载程序  - 下载最近三十天的订单，并下载交易信息  按天多线程进行下载  每五天一个url 共15个url
	 */
	public function actionCronDownloadOrder(){
		set_time_limit(0);
		$requestUrls = array();
		//最近一天的
		{
			$from_time=date("Y-m-d", strtotime('-1 days'));
			$to_time= date("Y-m-d", strtotime('+1 days'));
			$url = $this->getServerHost().$this->createUrl('DownloadOrderPerDay',array("from_time"=>$from_time,"to_time"=>$to_time));
			array_push($requestUrls, $url);
		}
		//之后30天的
		$daysAsThread = 10;
		for($j=11; $j<=31; $j += $daysAsThread){
			$kk = $j - $daysAsThread; //后10天
			$from_time=date("Y-m-d", strtotime('-'.$j.' days'));
			$to_time=date("Y-m-d", strtotime('-'.$kk.' days'));
			$url = $this->getServerHost().$this->createUrl('DownloadOrderPerDay',array("from_time"=>$from_time,"to_time"=>$to_time));
			array_push($requestUrls, $url);
		}
		//下载
		$reulst = CommUtils::runMutilThreads($requestUrls);
		CommUtils::OutputMutilThreadsResult($reulst);
		echo Yii::t('Websites', 'Done');//完成
	}
	/**
	 * 按天下载订单 我们只获取 pending processing 的订单
	 */
	public function actionDownloadOrderPerDay(){
		$from_time	= Yii::app()->getRequest()->getParam('from_time',null);
		$to_time	= Yii::app()->getRequest()->getParam('to_time',null);
		$filters = 	array(
				"updated_at"=>array("from"=>$from_time,"to"=>$to_time,"datetime"=>true),"status"=>array("in"=> WebsiteOrderService::$downloadOrderStatus)
		);
		$orderService = new WebsiteOrderService();
		var_dump($orderService->getWebsiteOrderlist($this->getPlatformcode(),$filters));
	}
	/**
	 * 检查未下载的订单
	 */
	public function actionCheckUndownlodOrderList(){
		$from_time	= Yii::app()->getRequest()->getParam('from_time',null);
		$to_time	= Yii::app()->getRequest()->getParam('to_time',null);
		if ($from_time == NULL){
			$from_time=date("Y-m-d", strtotime('-31 days'));
			$to_time= date("Y-m-d", strtotime('-2 days'));
		}
		//proccesing状态的订单
		$filters = 	array(
				"updated_at"=>array("from"=>$from_time,"to"=>$to_time,"datetime"=>true),"status"=>array("in"=> array(WebsiteOrderService::WEBSITE_ORDER_STATUS_PROCESSING))
		);
		$undownloadOrderList = $this->getOrderService()->checkUndownlodOrderList($this->getPlatformcode(),$filters);
		print_r($undownloadOrderList);//打印
	}
	/**
	 * CronCreateShipment - 包括创建 Creditmemo
	 */
	public function actionCronCreateShipment(){
		$from_time	= Yii::app()->getRequest()->getParam('from_time',null);
		$to_time	= Yii::app()->getRequest()->getParam('to_time',null);
		$orderService =  $this->getOrderService();
		//优先处理合并的包裹 type = 4  一个包裹对应多个订单
		if(empty( $_GET['type']) || !empty($_GET['type']) && $_GET['type'] == 4)$result4 = $orderService->sysCombinePackagesOrdersToWebsites($this->getPlatformcode(),$from_time,$to_time);
		//一个订单对应一个或者多个包裹
		if(empty( $_GET['type']) || !empty($_GET['type']) && $_GET['type'] == 1)$result1 = $orderService->uploadOrderListShipment($this->getPlatformcode(),$from_time,$to_time);
		//以下为部分或全额退款订单
		if(empty( $_GET['type']) || !empty($_GET['type']) && $_GET['type'] == 2)$result2 = $orderService->createCreditmemoListToWebsite($this->getPlatformcode(),$from_time,$to_time);
		if(empty( $_GET['type']) || !empty($_GET['type']) && $_GET['type'] == 3)$result3 = $orderService->closedOrderListToWebsite($this->getPlatformcode(),$from_time,$to_time);
		var_dump($result4,$result1,$result2,$result3);
	}
	/**
	 * 同步订单发货状态到网站 
	 */
	public function actionCreateShipment(){
		$checkConfirmShiped = isset($_GET['checkConfirmShiped']) ? FALSE : TRUE ;
		$platformOrderId	= Yii::app()->getRequest()->getParam('order_id',null);
		if(empty($platformOrderId)) die('Please Input $platformOrderId');
		$result = $this->getOrderService()->sysOrderShipmentToWebsite($this->getPlatformcode(), $platformOrderId,$checkConfirmShiped);
		var_dump($result);
	}
	public function actionCreateCreditmemo(){
		$platformOrderId	= Yii::app()->getRequest()->getParam('order_id',null);
		if(empty($platformOrderId)) die('Please Input $platformOrderId');
		$result = $this->getOrderService()->createCreditmemoToWebsite($this->getPlatformcode(), $platformOrderId);
		var_dump($result);
	}
	/**
	 * 更新订单的 invoice
	 */
	public function actionUpdateInvoice(){
		$platformOrderId	= Yii::app()->getRequest()->getParam('order_id',null);
		if(empty($platformOrderId)) die('Please Input $platformOrderId');
		$result = $this->getOrderService()->updateOrderInvoices($this->getPlatformcode(), $platformOrderId);
		var_dump($result);
	}
	/**
	 * 获取 platformcode
	 */
	abstract function getPlatformcode();
	/**
	 * 获取WebsiteOrderService 实例
	 * @return WebsiteOrderService
	 */
	public function getOrderService(){
		return new WebsiteOrderService();
	}
}	