<?php
/**
 * @desc Aliexpress listing
 * @author Gordon
 * @since 2015-06-25
 */
class AliexpresslistingController extends UebController{
    
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('getlist')
			),
		);
    }
    
    public function actionGetlisting() {
    	ini_set('display_errors', true);
    	error_reporting(E_ALL);
    	set_time_limit(8*3600);
    	ini_set('memory_limit', '8096M');
    	$page = 1;
    	$accountID = Yii::app()->request->getParam('account_id');
    	if ($accountID) {
	    	$request = new FindProductInfoListQueryRequest();
	    	while( $page <= ceil($request->_totalItem/$request->_pageSize) ){
	    		$request->setPage($page);
	    		$request->setProductStatusType(FindProductInfoListQueryRequest::PRODUCT_STATUS_OFFLINE);
	    		$request->setPageSize($request->_pageSize);
	    		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
	    		if (!empty($response)) {
	    			$productCount = 0;
	    			if (isset($response->productCount))
	    				$productCount = $response->productCount;
	    			$request->_totalItem = $productCount;
	    			foreach ($response->aeopAEProductDisplayDTOList as $products) {
	    				$productId = $products->productId;
	    				$productInfoRequest = new FindAeProductByIdRequest();
	    				$productInfoRequest->setProductId($productId);
	    				$productInfoResponse = $productInfoRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
	    				if (!empty($productInfoResponse)) {
	    					$aliexpressListingModel = new AliexpressListing();
	    					$aliexpressListingModel->setAccountID($accountID);
	    					$aliexpressListingModel->saveAliexpressListing($productInfoResponse);
	    				}
	    			}
	    		}
	    		$page++;
	    	}
    	} else {
    		$accountList = AliexpressAccount::getAbleAccountList();
    		//循环每个账号发送一个拉listing的请求
    		foreach ($accountList as $accountInfo) {
    			$path = $this->createUrl('getlisting', array('account_id' => $accountInfo['id']));
    			$header = "GET " . $path . $accountInfo['id'] . " HTTP\1.0\r\n";
    			$header .= "Host: " . $_SERVER['HTTP_HOST'] . "\r\n";
    			$header .= "Pragma: no-cache" . "\r\n";
    			$header .= "Connection: Close\r\n\r\n";
    			$fp = fsockopen($_SERVER['HTTP_HOST'], 80, $errno, $error, 30);
    			if ($fp) {
    				fwrite($fp, $header, strlen($header));
    			}
    			/*  				while (!feof($fp)) {
    			 echo fgets($fp, 1024);
    			}
    			exit; */
    			//MHelper::runThreadSOCKET($urls);
    		}    		
    	}
    }
}