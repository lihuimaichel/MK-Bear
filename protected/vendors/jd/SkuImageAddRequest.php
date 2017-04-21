<?php
/**
 * @desc 获取订单列表API
 * @author zhangf
 *
 */
class SkuImageAddRequest extends JdApiAbstract {
	
	/** @var int 商品ID **/
	protected $_wareId = null;
	
	/** @var int 属性值ID	**/
	protected $_attrValueId = null;

	/** @var Stream 图片二进制流 **/
	protected $_image = null;

	/** @var int 图片排序 **/
	protected $_indexId = null;
	
	protected $_apiMethod = 'jingdong.ept.warecenter.waredetailimg.add';				
	
	/**
	 * (non-PHPdoc)
	 * @see JdApiAbstract::setRequest()
	 */
	public function setRequest() {
		$request = array(
			'wareId' => $this->_wareId,
			'image' => $this->_image,
			'attrValueId' => $this->_attrValueId
		);
		if (!is_null($this->_indexId))
			$request['indexId'] = $this->_indexId;
		return $this;
	}
	
	/**
	 * @desc 设置WareID
	 * @param unknown $id
	 */
	public function setWareId($id) {
		$this->_wareId = $id;
	}
	
	/**
	 * @desc 设置AttrValueId
	 * @param unknown $id
	 */
	public function setAttrValueId($id) {
		$this->_attrValueId = $id;
	}
	
	/**
	 * @desc 设置Image
	 * @param unknown $bytes
	 */
	public function setImage($bytes) {
		$this->_image = $bytes;
	}
	
	/**
	 * @desc 设置IndexId
	 * @param unknown $bytes
	 */
	public function setIndexId($index) {
		$this->_indexId = $index;
	}	
}