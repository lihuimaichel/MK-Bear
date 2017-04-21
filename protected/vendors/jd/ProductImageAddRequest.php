<?php
/**
 * @desc 获取订单列表API
 * @author zhangf
 *
 */
class ProductImageAddRequest extends JdApiAbstract {
	
	/** @var int 商品ID **/
	protected $_wareId = null;
	
	/** @var int 图片排序	**/
	protected $_slot = null;

	/** @var Stream 图片二进制流 **/
	protected $_img = null;
	
	protected $_apiMethod = 'jingdong.ept.warecenter.waredetailimg.add';				
	
	protected $_isPost = true;
	
	/**
	 * (non-PHPdoc)
	 * @see JdApiAbstract::setRequest()
	 */
	public function setRequest() {
		$request = array(
			'wareId' => $this->_wareId,
			'img' => $this->_img
		);
		if (!is_null($this->_slot))
			$request['slot'] = $this->_slot;
		$this->_request = $request;
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
	 * @desc 设置Slot
	 * @param unknown $index
	 */
	public function setSlot($index) {
		$this->_slot = $index;
	}
	
	/**
	 * @desc 设置Img
	 * @param unknown $bytes
	 */
	public function setImg($bytes) {
		$this->_img = $bytes;
	}
}