<?php
class LazadaProductRemove {
    const EVENT_NAME = 'product_remove';
    
    /** @var string 异常信息*/ 
    protected $_exception = null;
    
    /**
     * @desc删除指定lazada账号指定seller sku的产品
     * @param integer $accountID
     * @param array $skuList
     * @return boolean
     */
    public function removeAccountProducts($siteID, $accountID, $skuList) {
    	try {
    		$request = new ProductRemoveRequest();
    		$request->setSellerSkuList($skuList);
    		$response = $request->setSiteID($siteID)->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
    		if (!$request->getIfSuccess()) {
    			$this->setExceptionMessage($request->getErrorMsg());
    			return false;
    		}
    		return true;
    	} catch (Exception $e) {
    		$this->setExceptionMessage($e->getMessage());
    		return false;
    	}
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message) {
    	$this->_exception = $message;
    }
    
    /**
     * @desc 获取异常信息
     */
    public function getExceptionMessage() {
    	return $this->_exception;
    }
}