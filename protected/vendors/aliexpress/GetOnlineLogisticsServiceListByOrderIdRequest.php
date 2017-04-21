<?php
/**
 * 根据订单号获取线上发货物流方案
 * api.getOnlineLogisticsServiceListByOrderId
 * @author	tan
 * @since	2015-9-18
 */

class GetOnlineLogisticsServiceListByOrderIdRequest extends AliexpressApiAbstract {
	
	/**@var string 交易订单ID*/
	public $_orderId = NULL;
	
	/**@var string 包裹重量*/
	public $_goodsWeight = NULL;
	
	/**@var string 包裹长*/
	public $_goodsLength = NULL;
	
	/**@var string 包裹宽*/
	public $_goodsWidth = NULL;
	
	/**@var string 包裹高 */
	public $_goodsHeigth = NULL;
	
	public function setApiMethod() {
		$this->_apiMethod = 'api.getOnlineLogisticsServiceListByOrderId';
	}
	
	/**
	 * set orderID
	 * @param	string	$orderId
	 */
	public function setOrderId($orderId) {
		$this->_orderId = $orderId;
	}
	
	/**
	 * set goodsWeight
	 * @param	string	$goodsWeight
	 */
	public function setGoodsWeight($goodsWeight) {
		$this->_goodsWeight = $goodsWeight;
	}
	
	/**
	 * set goodsLength
	 * @param	string	$goodsLenght
	 */
	public function setGoodsLength($goodsLength) {
		$this->_goodsLength = $goodsLength;
	}
	
	/**
	 * set goodsWidth
	 * @param	string	$goodsWidth
	 */
	public function setGoodsWidth($goodsWidth) {
		$this->_goodsWidth = $goodsWidth;
	}
	
	/**
	 * set goodsHeight
	 * @param	string	$goodsHeight
	 */
	public function setGoodsHeight($goodsHeight) {
		$this->_goodsHeigth = $goodsHeight;
	}
	
	public function setRequest() {
		$request = array(
				'orderId'		=> $this->_orderId,
				'goodsWeight'	=> $this->_goodsWeigth,
				'goodsLength'	=> $this->_goodsLength,
				'goodsWidth'	=> $this->_goodsWidth,
				'goodsHeight'	=> $this->_goodsHeigth
		);
		$this->request = $request;
		return $this;
	}
	
}