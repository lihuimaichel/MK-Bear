<?php
/**
 * @desc 添加商品SKU API
 * @author zhangf
 *
 */
class SkuAddRequest extends JdApiAbstract {
	
	/** @var int 商品ID **/
	protected $_wareId = null;
	
	/** @var string 多属性SKU **/
	protected $_rfId = null;
	
	/** @var string sku销售属性 **/
	protected $_attributes = null;
		
	/** @var float sku卖价 **/
	protected $_supplyPrice = null;
	
	/** @var int sku库存数 **/
	protected $_amountCount = null;
	
	/** @var int sku锁定库存数 **/
	protected $_lockCount = null;
	
	/** @var datetime sku锁定库存开始时间 **/
	protected $_lockStartTime = null;
	
	/** @var datetime sku锁定库存结束时间 **/
	protected $_lockEndTime = null;
	
	/** @var string sku hscode **/
	protected $_hsCode = null;
	
	protected $_apiMethod = 'jingdong.ept.warecenter.outapi.waresku.add';
	
	//protected $_isPost = true;
	
	/**
	 * (non-PHPdoc)
	 * @see JdApiAbstract::setRequest()
	 */
	public function setRequest() {
		$request = array(
			'wareId' => $this->_wareId,
			'attributes' => $this->_attributes,
			'supplyPrice' => $this->_supplyPrice,
			'amountCount' => $this->_amountCount,
		);
		if (!is_null($this->_rfId))
			$request['rfId'] = $this->_rfId;
		if (!is_null($this->_lockCount))
			$request['lockCount'] = $this->_lockCount;
		if (!is_null($this->_lockStartTime))
			$request['lockStartTime'] = $this->_lockStartTime;
		if (!is_null($this->_hsCode))
			$request['hsCode'] = $this->_hsCode;							
		$this->_request = $request;
		//print_r($request);exit;
		return $this;
	}
	
	/**
	 * @desc 设置Ware ID
	 * @param unknown $wareID
	 */
	public function setWareId($wareID) {
		$this->_wareId = $wareID;
	}
	
	/**
	 * @desc 设置rfId
	 * @param unknown $sku
	 */
	public function SetRfId($sku) {
		$this->_rfId = $sku;
	}

	/**
	 * @desc 设置attributes
	 * @param unknown $attributes
	 */
	public function setAttributes($attributes) {
		$this->_attributes = $attributes;
	}
		
	/**
	 * @desc 设置SupplyPrice
	 * @param unknown $price
	 */
	public function setSupplyPrice($price) {
		$this->_supplyPrice = $price;
	}

	/**
	 * @desc 设置AmountCount
	 * @param unknown $num
	 */
	public function setAmountCount($num) {
		$this->_amountCount = $num;
	}

	/**
	 * @desc 设置LockCount
	 * @param unknown $num
	 */
	public function setLockCount($num) {
		$this->_lockCount = $num;
	}

	/**
	 * @desc 设置LockStartTime
	 * @param unknown $time
	 */
	public function setLockStartTime($time) {
		$this->_lockStartTime = $time;
	}
	
	/**
	 * @desc 设置LockEndTime
	 * @param unknown $time
	 */
	public function setLockEndTime($time) {
		$this->_lockEndTime = $time;
	}
	
	/**
	 * @desc 设置HsCode
	 * @param unknown $code
	 */
	public function setHsCode($code) {
		$this->_hsCode = $code;
	}
}