<?php
/**
 * 修改声明发货 API
 * api.sellerModifiedShipment
 * @author	Rex
 * @since	2015-9-16
 */

class SellerModifiedShipmentRequest extends AliexpressApiAbstract{
	
	/**@var string 老的发货物流服务*/
	public $_oldServiceName = NULL;
	
	/**@var string 老的物流追踪号*/
	public $_oldLogisticsNo = NULL;
	
	/**@var string 新的发货物流服务*/
	public $_newServiceName = NULL;
	
	/**@var string 新的物流追踪号*/
	public $_newLogisticsNo = NULL;
	
	/**@var string 备注*/
	public $_description = NULL;
	
	/**@var string 全部发货(all)、部分发货(part)*/
	public $_sendType = 'all';
	
	/**@var string 平台订单号*/
	public $_outRef = NULL;
	
	/**@var string 追踪网址 serviceName=other时需填写*/
	public $_trackingWebsite = NULL;
	
	public function setApiMethod() {
		$this->_apiMethod = 'api.sellerModifiedShipment';
	}
	
	/**
	 * set oldServiceName
	 * @param	string	$oldServiceName
	 */
	public function setOldServiceName($oldServiceName) {
		$this->_oldServiceName = $oldServiceName;
	}
	
	/**
	 * set oldLogisticsNo
	 * @param	string	$oldLogisticsNo
	 */
	public function setOldLogisticsNo($oldLogisticsNo) {
		$this->_oldLogisticsNo = $oldLogisticsNo;
	}
	
	/**
	 * set newServiceName
	 * @param	string	$newServiceName
	 */
	public function setNewServiceName($newServiceName) {
		$this->_newServiceName = $newServiceName;
	}
	
	/**
	 * set newLogisticsNo
	 * @param	string	$newLogisticsNo
	 */
	public function setNewLogisticsNo($newLogisticsNo) {
		$this->_newLogisticsNo = $newLogisticsNo;
	}
	
	/**
	 * set description
	 * @param	string	$description
	 */
	public function setDescription($description) {
		$this->_description = $description;
	}
	
	/**
	 * set sendType
	 * @param	string	$sendType
	 */
	public function setSendType($sendType) {
		$this->_sendType = $sendType;
	}
	
	/**
	 * set order.platform_order_id
	 * @param	string	$outRef
	 */
	public function setOutRef($outRef) {
		$this->_outRef = $outRef;
	}
	
	/**
	 * set trackingWebsite
	 * @param	string	$trackingWebsite
	 */
	public function setTrackingWebsite($trackingWebsite) {
		$this->_trackingWebsite = $trackingWebsite;
	}
	
	/**
	 * 设置请求参数
	 */
	public function setRequest(){
		$request = array(
				'oldServiceName'	=> $this->_oldServiceName,
				'oldLogisticsNo'	=> $this->_oldLogisticsNo,
				'newServiceName'	=> $this->_newServiceName,
				'newLogisticsNo'	=> $this->_newLogisticsNo,
				'description'		=> $this->_description,
				'sendType'			=> $this->_sendType,
				'outRef'			=> $this->_outRef,
				'trackingWebsite'	=> $this->_trackingWebsite
		);
		$this->request = $request;
		return $this;
	}
	
}
