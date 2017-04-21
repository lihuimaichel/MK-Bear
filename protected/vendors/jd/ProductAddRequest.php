<?php
/**
 * @desc 添加商品API
 * @author zhangf
 *
 */
class ProductAddRequest extends JdApiAbstract {
	
	protected $_apiMethod = 'jingdong.ept.warecenter.ware.add';
	
	/** @var int 分类ID **/
	protected $_categoryId = null;

	/** @var int 商品状态 **/
	protected $_wareStatus = null;
	
	/** @var string 商品标题 **/
	protected $_title = null;
	
	/** @var string 商品SKU **/
	protected $_rfId = null;
	
	/** @var string 商品货号 **/
	protected $_itemNum = null;
	
	/** @var string sku的hscode **/
	protected $_hsCode = null;
	
	/** @var int 运费模板ID **/
	protected $_transportId = null;
	
	/** @var string 商品属性 **/
	protected $_attributes = null;
	
	/** @var float 商品价格 **/
	protected $_SupplyPrice = null;
	
	/** @var int 商品库存 **/
	protected $_amountCount = null;
	
	/** @var int 锁定库存数 **/
	protected $_lockCount = null;
	
	/** @var datetime 锁定开始时间 **/
	protected $_lockStartTime = null;
	
	/** @var datetime 锁定结束时间 **/
	protected $_lockEndTime = null;
	
	/** @var stream file 主图二进制流 **/
	protected $_imgByte = null;
	
	/** @var int 推荐产品模板ID **/
	protected $_recommendTpid = null;
	
	/** @var int 自定义属性模板ID **/
	protected $_customTpid = null;
	
	/** @var int 品牌ID **/
	protected $_brandId = null;
	
	/** @var int 发货期 **/
	protected $_deliveryDays = null;
	
	/** @var string 产品关键字 **/
	protected $_keywords = null;
	
	/** @var string 产品描述 **/
	protected $_description = null;
	
	/** @var string 产品包装信息 **/
	protected $_packInfo = null;
	
	/** @var float 产品净重 **/
	protected $_netWeight = null;	
	
	/** @var float 产品毛重 **/
	protected $_weight = null;
	
	/** @var float 产品包装长 **/
	protected $_packLong = null;
	
	/** @var float 产品包装高 **/
	protected $_packWide = null;
	
	/** @var float 产品包装高 **/
	protected $_packHeight = null;
	
	protected $_isPost = true;
	
	/**
	 * (non-PHPdoc)
	 * @see JdApiAbstract::setRequest()
	 */
	public function setRequest() {
		$request = array(
			'categoryId' => $this->_categoryId,
			'wareStatus' => $this->_wareStatus,
			'title' => $this->_title,
			'transportId' => $this->_transportId,
			'SupplyPrice' => $this->_SupplyPrice,
			'amountCount' => $this->_amountCount,
			'deliveryDays' => $this->_deliveryDays,
			'keywords' => $this->_keywords,
			'packInfo' => $this->_packInfo,
			'netWeight' => $this->_netWeight,
			'weight' => $this->_weight,
			'packLong' => $this->_packLong,
			'packWide' => $this->_packWide,
			'packHeight' => $this->_packHeight,
			'imgByte' => $this->_imgByte,
			'description' => $this->_description,
		);
		if (!is_null($this->_rfId))
			$request['rfId'] = $this->_rfId;
		if (!is_null($this->_itemNum))
			$request['itemNum'] = $this->_itemNum;
		if (!is_null($this->_hsCode))
			$request['hsCode'] = $this->_hsCode;
		if (!is_null($this->_attributes))
			$request['attributes'] = $this->_attributes;
		if (!is_null($this->_lockCount))
			$request['lockCount'] = $this->_lockCount;
		if (!is_null($this->_lockStartTime))
			$request['lockStartTime'] = $this->_lockStartTime;
		if (!is_null($this->_lockEndTime))
			$request['lockEndTime'] = $this->_lockEndTime;
		if (!is_null($this->_recommendTpid))
			$request['recommendTpid'] = $this->_recommendTpid;
		if (!is_null($this->_customTpid))
			$request['customTpid'] = $this->_customTpid;
		if (!is_null($this->_brandId))
			$request['brandId'] = $this->_brandId;				
		$this->_request = $request;
		return $this;
	}
	
	/**
	 * @desc 设置category id
	 * @param unknown $cateagoryID
	 */
	public function setCategoryId($cateagoryID) {
		$this->_categoryId = $cateagoryID;
	}
	
	/**
	 * @desc 设置 wareStatus
	 * @param unknown $status
	 */
	public function setWareStatus($status) {
		$this->_wareStatus = $status;
	}
	
	/**
	 * @desc 设置Title
	 * @param unknown $title
	 */
	public function setTitle($title) {
		$this->_title = $title;
	}
	
	/**
	 * @desc 设置SKU
	 * @param unknown $sku
	 */
	public function setRfId($sku) {
		$this->_rfId = $sku;
	}
	
	/**
	 * @desc 设置ItemNum
	 * @param unknown $itemNum
	 */
	public function setItemNum($itemNum) {
		$this->_itemNum = $itemNum;
	}
	
	/**
	 * @desc 设置HsCode
	 * @param unknown $code
	 */
	public function setHsCode($code) {
		$this->_hsCode = $code;
	}
	
	/**
	 * @desc 设置TransportId
	 * @param unknown $id
	 */
	public function setTransportId($id) {
		$this->_transportId = $id;
	}
	
	/**
	 * @desc 设置Attributes
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
		$this->_SupplyPrice = $price;
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
	 * @desc 设置ImageByte
	 * @param unknown $byte
	 */
	public function setImageByte($byte) {
		$this->_imgByte = $byte;
	}
	
	/**
	 * @desc 设置RecommendTpid
	 * @param unknown $id
	 */
	public function setRecommendTpid($id) {
		$this->_recommendTpid = $id;
	}
	
	/**
	 * @desc 设置CustomTpid
	 * @param unknown $id
	 */
	public function setCustomTpid($id) {
		$this->_customTpid = $id;
	}
	
	/**
	 * @desc 设置BrandId
	 * @param unknown $id
	 */
	public function setBrandId($id) {
		$this->_brandId = $id;
	}

	/**
	 * @desc 设置DeliveryDays
	 * @param unknown $day
	 */
	public function setDeliveryDays($day) {
		$this->_deliveryDays = $day;
	}
	
	/**
	 * @desc 设置Keywords
	 * @param unknown $keywords
	 */
	public function setKeywords($keywords) {
		$this->_keywords = $keywords;
	}
	
	/**
	 * @desc 设置Description
	 * @param unknown $description
	 */
	public function setDescription($description) {
		$this->_description = $description;
	}
	
	/**
	 * @desc 设置PackInfo
	 * @param unknown $packageInfo
	 */
	public function setPackInfo($packageInfo) {
		$this->_packInfo = $packageInfo;
	}
	
	/**
	 * @desc 设置NetWeight
	 * @param unknown $weight
	 */
	public function setNetWeight($weight) {
		$this->_netWeight = $weight;
	}
	
	/**
	 * @desc 设置Weight
	 * @param unknown $weight
	 */
	public function setWeight($weight) {
		$this->_weight = $weight;
	}

	/**
	 * @desc 设置PackLong
	 * @param unknown $packageLength
	 */
	public function setPackLong($length) {
		$this->_packLong = $length;
	}
	
	/**
	 * @desc 设置PackWide
	 * @param unknown $width
	 */
	public function setPackWide($width) {
		$this->_packWide = $width;
	} 
	
	/**
	 * @desc 设置PackHeight
	 * @param unknown $height
	 */
	public function setPackHeight($height) {
		$this->_packHeight = $height;
	}
	
	public function getIfSuccess() {
		if (!parent::getIfSuccess()) {
			return false;
		}
		if (isset($this->_response->jingdong_ept_warecenter_ware_add_responce->addnewware_result->success) && $this->_response->jingdong_ept_warecenter_ware_add_responce->addnewware_result->success == false) {
			$this->_errorMessage = $this->_response->jingdong_ept_warecenter_ware_add_responce->addnewware_result->message;
			return false;
		}
		return true;
	}
}