<?php
/**
 * Shopee 物流相关
 * @author yangsh
 * @since 2017-03-30
 */

class PaytmShipment extends UebModel {
	
	//const EVNET_POSTORDER       = 'post_order';		//创建发货
	const EVENT_UPLOAD_TRACK    = 'upload_track';		//上传跟踪号
	//const EVENT_ADVANCE_SHIPPED = 'advance_shipped';		//提前发货

    //快递合伙人
    static $courierPartners = array(
        '62' => array(
            'shipperID'           => 62,
            'shippingDescription' => 'Gati Air',
            'trackingURL'         => 'https://track.paytm.com/v1/track/order?ff_id=#FFID#&shipperId=#ID#&awbNo=#AWBID#&ff_status=#STATUS#',
        )
    );

	/** @var string 异常信息*/
	public $exception = null;
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * 切换数据库连接
	 */
	public function getDbKey() {
		return 'db_oms_order';
	}
	
	/**
	 * 数据库表名
	 */
	public function tableName() {
		return 'ueb_order';
	}

    /**
     * @desc paytm上传跟踪号
     */
    public function uploadTrackingNumber($accountID,$packageId,$orderId,$platformOrderID,$trackingNumber) {
        if(strpos($platformOrderID,'-') > 0) {
            $tmp = explode('-',$platformOrderID);
            $platformOrderID = $tmp[1];
        }
        $errMsg = '';
        $shipperID = self::$courierPartners[62]['shipperID'];
        $shippingDescription = self::$courierPartners[62]['shippingDescription'];
        $trackingURL = self::$courierPartners[62]['trackingURL'];
        $orderItemIds = $this->getOrderItemIds($packageId,$orderId);
        if(!empty($_REQUEST['bug'])) {
            echo '<br>getOrderItemIds:';MHelper::printvar($orderItemIds,false);
        }
        if(empty($orderItemIds)) {
            $errMsg .= ' orderItemIds is empty';
            return array('flag'=>false,'errMsg'=> $errMsg);
        }

        //1. move the item from Pending Shipment (5) to Shipment Created (23)
        $res = self::createShipment($accountID,$platformOrderID,$trackingNumber,$orderItemIds,
        $shipperID,$shippingDescription,$trackingURL);
        if(!empty($_REQUEST['bug'])) {
            echo '<br>createShipment:';MHelper::printvar($res,false);
        }
        
        $fulfillmentIds = array();
        if(!$res['flag'] || empty($res['data']->fulfillment_id)) {
            $errMsg .= $res['errMsg'];
            $res2 = self::fetchFulfillments($accountID,$platformOrderID,$trackingNumber);
            if(!empty($_REQUEST['bug'])) {
                echo '<br>fetchFulfillments:';MHelper::printvar($res2,false);
            }
            if(!$res2['flag'] || empty($res2['data']) ) {
                $errMsg .= $res2['errMsg'];
                $fulfillmentIds = array();
                $orderInfo = self::getOrder($accountID,$platformOrderID);
                if(!empty($_REQUEST['bug'])) {
                    echo '<br>getOrder:';MHelper::printvar($orderInfo,false);
                }                
                if($orderInfo['flag'] && $orderInfo['data']) {
                    foreach ($orderInfo['data'] as $order) {
                        if(isset($order->status) && $order->status == 15) {//shipped
                            return array('flag'=>1,'errMsg'=>'ok');
                        }
                        foreach ($order->items as $orderItem) {
                            if(in_array($orderItem->status,array(23,25,13))) {
                                $fulfillmentIds[] = $orderItem->fulfillment_id;
                            }
                        }
                    }
                }
                if(empty($fulfillmentIds)) {
                    return array('flag'=>false,'errMsg'=> $errMsg);
                }
            } else {
                foreach ($res2['data'] as $fulfilment) {
                    if(isset($fulfilment->status) && $fulfilment->status == 15) {//shipped
                        return array('flag'=>1,'errMsg'=>'ok');
                    }
                    foreach ($fulfilment->items as $item) {
                        $fulfillmentIds[] = trim($item->fulfillment_id);
                    }
                }
            }
            $fulfillmentIds = array_unique($fulfillmentIds);
        } else {
            $fulfillmentIds[] = $res['data']->fulfillment_id;    
        }
        if(!empty($_REQUEST['bug'])) {
            echo '<br>fulfillmentIds:';MHelper::printvar($fulfillmentIds,false);
        }        
        
        //2. move the item from Shipment Created (23) to Ready to Ship (13)
        $res = self::bulkFetchPackingLabel($accountID,$fulfillmentIds);
        if(!empty($_REQUEST['bug'])) {
            echo '<br>bulkFetchPackingLabel:';MHelper::printvar($res,false);
        }
        if(!$res['flag']) {
            $errMsg .= $res['errMsg'];
            return array('flag'=>false,'errMsg'=> $errMsg);
        }

        //3. move the item from Ready to Ship (13) to Manifest Requested (25)
        $res = self::createManifest($accountID,$fulfillmentIds);
        if(!empty($_REQUEST['bug'])) {
            echo '<br>createManifest:';MHelper::printvar($res,false);
        }
        if(!$res['flag']) {
            $errMsg .= $res['errMsg'];
            return array('flag'=>false,'errMsg'=> $errMsg);
        }        

        //4. move the item from Manifest Requested (25) to Manifest Shipped (15)
        $res = self::bulkMarkShipped($accountID,$fulfillmentIds);
        if(!$res['flag']) {
            $errMsg .= $res['errMsg'];
        }   
        if(!empty($_REQUEST['bug'])) {
            echo '<br>bulkMarkShipped:';MHelper::printvar($res,false);
        }
        $flag = $res['flag'];
        if(!$flag && $res['data']->body) {
            $markShippedBody = json_decode($res['data']->body,true);
            if($markShippedBody['error'] == 'No fulfillment_ids given to update') {
                $flag = true;
                $errMsg = 'ok';
            }
        }
        return array('flag'=>$flag,'errMsg'=>$errMsg);
    }

    /**
     * @desc getOrder
     * @param  int $accountId  
     * @param  string $platformOrderID 
     * @return array
     */
    public static function getOrder($accountId,$platformOrderID) {
        $orderIds = explode(',',$platformOrderID);
        $request = new GetOrdersRequest();
        $request->setOrderIDs($orderIds);
        $response = $request->setAccount($accountId)->setRequest()->sendRequest()->getResponse();
        return array('flag'=>$request->getIfSuccess(),'errMsg'=>$request->getErrorMsg(),'data'=>$response);
    }

    /**
     * @desc createShipment
     * @param  int $accountID  
     * @param  string $platformOrderID     
     * @param  string $trackingNumber     
     * @param  array $orderItemIds        
     * @param  int $shipperID          
     * @param  string $shippingDescription 
     * @param  string $trackingURL        
     * @return Array
            (
                [flag] => 1
                [data] => stdClass Object
                    (
                        [res] => success
                        [msg] => fulfilment creation successful
                        [fulfillment_id] => 2256465849
                    )

            )
     */
    public static function createShipment($accountID,$platformOrderID,$trackingNumber,$orderItemIds,
        $shipperID,$shippingDescription,$trackingURL) {
        $request = new CreateShipmentRequest($platformOrderID);
        $request->setTrackingNumber($trackingNumber);
        $request->setOrderItemIds($orderItemIds);
        $request->setShipperId($shipperID);
        $request->setShippingDescription($shippingDescription);
        $request->setTrackingURL($trackingURL);
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        return array('flag'=>$request->getIfSuccess(),'errMsg'=>$request->getErrorMsg(),'data'=>$response);
    }

    /**
     * @desc bulkFetchPackingLabel
     * @param  int $accountID  
     * @param  array $fulfillmentIds 
     * @return boolean
     */
    public static function bulkFetchPackingLabel($accountID,$fulfillmentIds) {
        $request  = new BulkFetchPackingLabelRequest($fulfillmentIds);
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        $filename = Yii::getPathOfAlias('webroot').'/log/paytm/paytmPackingLabel.pdf';
        file_put_contents($filename, $response);
        return array('flag'=>$request->getIfSuccess(),'errMsg'=>$request->getErrorMsg());
    }

    /**
     * @desc createManifest
     * @param  int $accountID  
     * @param  array $fulfillmentIds 
     * @return Array
            (
                [flag] => 1
                [data] => stdClass Object
                    (
                        [manifest_ids] => Array
                            (
                                [0] => 8969914
                            )

                        [map] => stdClass Object
                            (
                                [2256570038] => 8969914
                            )

                    )

            )
     */
    public static function createManifest($accountID,$fulfillmentIds) {
        $request  = new CreateManifestRequest($fulfillmentIds);
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        return array('flag'=>$request->getIfSuccess(),'errMsg'=>$request->getErrorMsg(),'data'=>$response);
    }

    /**
     * @desc bulkMarkShipped
     * @param  int $accountID  
     * @param  array $fulfillmentIds 
     * @return Array
            (
                [flag] => 1
                [data] => 
            )
     */
    public static function bulkMarkShipped($accountID,$fulfillmentIds) {
        $request = new BulkMarkShippedRequest($fulfillmentIds);
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        return array('flag'=>$request->getIfSuccess(),'errMsg'=>$request->getErrorMsg(),'data'=>$response);
    }

    /**
     * @desc fetchFulfillments
     * @param  int $accountID  
     * @param  string $platformOrderID 
     * @param  string $trackingNumber  
     * @param  string $orderItemId     
     * @return array             
     */
    public static function fetchFulfillments($accountID,$platformOrderID,$trackingNumber,$orderItemId=null) {
        $request = new FetchFulfillmentsRequest();
        $request->setOrderId($platformOrderID);
        $request->setTrackingNumber($trackingNumber);
        if($orderItemId) {
            $request->setOrderItemId($orderItemId);
        }
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        return array('flag'=>$request->getIfSuccess(),'errMsg'=>$request->getErrorMsg(),'data'=> $response);
    }

    /**
     * @desc downloadManifest
     * @param  int $accountID  
     * @param  array $manifestId 
     * @return boolean
     */
    public static function downloadManifest($accountID,$manifestId) {
        $request  = new DownloadManifestRequest($manifestId);
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        $filename = Yii::getPathOfAlias('webroot').'/log/paytm/paytmManifest.pdf';
        file_put_contents($filename, $response);
        return array('flag'=>$request->getIfSuccess(),'errMsg'=>$request->getErrorMsg());
    }

    /**
     * 获取paytm平台待上传追踪号信息到平台的包裹
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getPaytmWaitingUploadPackages($pkCreateDate = null,$packageId = null,$limit = null, $accountID = null ) {
        //包裹创建后
        $pkCreateDateEnd = date('Y-m-d H:i', strtotime("-1 day"));
        $dbCommand = $this->dbConnection->createCommand()
                            ->select("t.package_id,t.track_num,t.real_ship_type,t.ship_code,d.order_id")
                            ->from(OrderPackage::tableName(). " t")
                            ->join(OrderPackageDetail::tableName()." d", "t.package_id = d.package_id")
                            ->join("ueb_order o","o.order_id = d.order_id and o.platform_code='".Platform::CODE_PAYTM."'")
                            ->where("t.is_confirm_shiped=0")
                            ->andWhere("t.track_num != '' and t.ship_status!=".OrderPackage::SHIP_STATUS_CANCEL)
                            ->andWhere('o.paytime >= "'.$pkCreateDate.'" and o.paytime < "'.$pkCreateDateEnd.'" ')
                            ->andWhere("t.platform_code = '".Platform::CODE_PAYTM."'")
                            ->andWhere('t.is_repeat = 0')
                            ->group("d.order_id");
        if(!empty($packageId)){
            $dbCommand->andWhere('t.package_id = "'.$packageId.'"');
        }
        if (!empty($limit))
            $dbCommand->limit((int)$limit, 0);
        if($accountID){
            $dbCommand->andWhere('o.account_id='.$accountID);
        }
        //echo $dbCommand->text;exit;
        if(isset($_REQUEST['bug']) && $_REQUEST['bug']){
            echo $dbCommand->text, "<br/>";
        }
        return $dbCommand->queryAll();
    }

    /**
     * @desc 获取item_id
     * @param  string $packageId
     * @param  string $orderId  
     * @return array
     */
    public function getOrderItemIds($packageId,$orderId,$checked=false) {
        $dbCommand = $this->dbConnection->createCommand()
                            ->select("od.item_id")
                            ->from(OrderPackage::tableName(). " t")
                            ->join(OrderPackageDetail::tableName()." d", "t.package_id = d.package_id ")
                            ->join("ueb_order_detail od","od.id = d.order_detail_id and od.platform_code = '".Platform::CODE_PAYTM."'")
                            ->where('t.package_id = "'.$packageId.'"')
                            ->andWhere('od.order_id = "'.$orderId.'"')
                            ->andWhere("od.detail_type=0 and od.item_id!=''")
                            ->andWhere("t.track_num != '' and t.ship_status!=".OrderPackage::SHIP_STATUS_CANCEL)
                            ->andWhere("t.platform_code = '".Platform::CODE_PAYTM."'");
        //echo $dbCommand->text;exit;
        if(!$checked) {
            //$dbCommand->where("t.is_confirm_shiped=0");
        }                            
        if(isset($_REQUEST['bug']) && $_REQUEST['bug']){
            echo $dbCommand->text, "<br/>";
        }
        return $dbCommand->queryColumn();
    }   
	
}