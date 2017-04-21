<?php
/**
 * @desc Lazada物流
 * @author ltf
 * @since 2015.11.09
 */
class LazadabatchshipmentController extends UebController{
    
    /**
     * @desc 菲律宾站点批量上传跟踪号到销售平台
     */
    public function actionUpload(){
    	
    	$site     = Yii::app()->request->getParam('site');//自传站点，保证账号一致性
    	
        $packages = LazadaShipment::model()->getBatchPackageReadyToShip($site);//根据站点获取需要上传跟踪号的包裹
//         var_dump(count($packages));exit;
//         var_dump($packages);exit;
        
        foreach($packages as $info){
        	
        	$packageID = $info['package_id'];
        	
        	$orders = Order::model()->getOrderByPackageID($packageID);//根据包裹号查订单信息
        	$packageInfo = OrderPackage::model()->getPackageInfoByPackageID($packageID);//根据包裹号获取包裹信息
        	
        	$orderArr = array();
        	foreach($orders as $order){
        		$orderArr[] = $order['item_id'];//销售ID
        		$accountID  = $order['account_id'];
        	}
			
        	$accountInfo = LazadaAccount::model()->getAccountByOldAccountID($accountID);//根据老系统账号ID获取市场业务系统账号信息
        	$request = new SetStatusToReadyToShipRequest();
        	$request->setOrderItemIds(array_unique($orderArr));
        	$request->setShippingProvider('');
        	$request->setSiteID($site);
        	
        	$response = $request->setAccount($accountInfo['account_id'])->setRequest()->sendRequest()->getResponse();
        	var_dump($response);exit;
        	//把返回的PurchaseOrderId存到数据库中
        	
        	
        	
        	
        	
            
        }
        
        echo '<br/>OK';
    }
} 