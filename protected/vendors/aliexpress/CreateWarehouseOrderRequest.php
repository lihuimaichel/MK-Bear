<?php
/**
 * 创建线上发货物流订单
 * api.createWarehouseOrder
 * @author	Rex
 * @since	2015-9-19
 */

class CreateWarehouseOrderRequest extends AliexpressApiAbstract {
	
	/**@var string 交易订单号*/
	public $_tradeOrderId = NULL;
	
	/**@var string 交易订单来源*/
	public $_tradeOrderFrom = 'ESCROW';
	
	/**@var string 物流服务 */
	public $_warehouseCarrierService = NULL;
	
	/**@var string 国内快递ID*/
	public $_domesticLogisticsCompanyId = NULL;
	
	/**@var string 国内快递公司名称*/
	public $_domesticLogisticsCompany = NULL;
	
	/**@var string 国内快递运单号 长度1-32*/
	public $_domesticTrackingNo = NULL;
	
	/**@var text 申报产品信息*/
	public $_declareProductDTOs = NULL;
	
	/**@var text 地址信息*/
	public $_addressDTOs = NULL;
	
	/**@var text 备注*/
	public $_remark = NULL;
	
	public function setApiMethod() {
		$this->_apiMethod = 'api.createWarehouseOrder';
	}
	
	/**
	 * set tradeOrderId
	 * @param	string	$tradeOrderId
	 */
	public function setTradeOrderId($tradeOrderId) {
		$this->_tradeOrderId = $tradeOrderId;
	}
	
	/**
	 * set tradeOrderFrom
	 * @param	string	$tradeOrderFrom
	 */
	public function setTradeOrderFrom($tradeOrderFrom) {
		$this->_tradeOrderFrom = $tradeOrderFrom;
	}
	
	/**
	 * set warehouseCarrierService
	 * @param	string	$warehouseCarrierService
	 */
	public function setWarehouseCarrierService($warehouseCarriderService) {
		$this->_warehouseCarrierService = $warehouseCarriderService;
	}
	
	/**
	 * set domesticLogisticsCompanyId
	 * @param	string	$companyId
	 */
	public function setDomesticLogisticsCompanyId($companyId) {
		$this->_domesticLogisticsCompanyId = $companyId;
	}
	
	/**
	 * set domesticLogisticsCompany
	 * @param	string	$company
	 */
	public function setDomesticsLogisticsCompay($company) {
		$this->_domesticLogisticsCompany = $company;
	}
	
	/**
	 * set domesticTrackingNo
	 * @param	string	$domesticTrackingNo
	 */
	public function setDomesticTrackingNo($domesticTrackingNo) {
		$this->_domesticTrackingNo = $domesticTrackingNo;
	}
	
	/**
	 * set declareProductDTOs
	 * @param	array	$declareProductDTOs
	 */
	public function setDeclareProductDTOs($data) {
		$demo = array('productId'=>'','categoryCnDesc'=>'','categoryEnDesc'=>'','productNum'=>'','productDeclareAmount'=>'','productWeight'=>'','isContainsBattery'=>'',
			'scItemId'=>'');
		foreach ($data as $key => $val) {
			$data[$key] = array_intersect_key($val, $demo);
		}
		$this->_declareProductDTOs = json_encode($data);
	}
	
	/**
	 * set addressDTOs
	 * @param	array	$addressDTOs
	 */
	public function setAddressDTOs($addressDTOs) {
		$this->_addressDTOs = json_encode($addressDTOs);
	}
	
	/**
	 * set remark
	 * @param	text	$remark
	 */
	public function setRemark($remark) {
		$this->_remark = $remark;
	}
	
	public function setRequest() {
		$request = array(
				'tradeOrderId'					=> $this->_tradeOrderId,
				'tradeOrderFrom'				=> $this->_tradeOrderFrom,
				'warehouseCarrierService'		=> $this->_warehouseCarrierService,
				'domesticLogisticsCompanyId'	=> $this->_domesticLogisticsCompanyId,
				//'domesticLogisticsCompany'		=> $this->_domesticLogisticsCompany,
				'domesticTrackingNo'			=> $this->_domesticTrackingNo,
				'declareProductDTOs'			=> $this->_declareProductDTOs,
				'addressDTOs'					=> $this->_addressDTOs,
				'remark'						=> $this->_remark
		);
		$this->request = $request;
		return $this;
	}
	
}