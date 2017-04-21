<?php
/**
 * @desc 订单sku与销售关系
 * @author Yangsh
 * @since 2015-08-26
 */
class OrderskuownerController extends UebController {
	
	/**
	 * 访问过滤配置
	 *
	 * @see CController::accessRules()
	 */
	public function accessRules() {
		return array (
				array (
						'allow',
						'users' => array (
								'*' 
						),
						'actions' => array (
								'updateskuowner',//new
								
						) 
				) 
		);
	}

	/**
	 * 更新订单sku与销售关系
	 * @author yangsh
	 * @since 2016-08-10
	 * @link /orders/orderskuowner/updateskuowner 
	 * 		/orders/orderskuowner/updateskuowner/order_id/CO160811000001EB
	 */
	public function actionUpdateskuowner() {
		set_time_limit(0);
		ini_set("display_errors", true);
		error_reporting(E_ALL);

		$orderID = trim(Yii::app ()->request->getParam ( 'order_id', ''));//系统订单号
		$day = trim(Yii::app ()->request->getParam ( 'day', 1));//天数
		$orderskuowner = new OrderSkuOwnerChild();
		$response = $orderskuowner->asyncUpdateData($orderID,$day);
		echo $response['errorCode'] == '0' ? 'Success' : 'Failure: '.$response['errorMsg'];
		Yii::app()->end();
	}

}