<?php
/**
 * @desc 更新订单备注接口
 * @author zhangf
 *
 */
class UpdateOrderNoteRequest extends JdApiAbstract {
	/** @var string 订单号 **/
	protected $_orderId = null;
	/** @var string 追踪号 **/
	protected $_note = null;

	/** @var 接口方法名 **/
	protected $_apiMethod = 'jingdong.ept.order.updateordernote';
	
	/**
	 * @desc 设置订单号
	 * @param unknown $orderID
	 */
	public function setOrderId($orderID) {
		$this->_orderId = $orderID;
	}
	
	/**
	 * @desc 设置订单备注
	 * @param unknown $note
	 */
	public function setNote($note) {
		$this->_note = $note;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PlatformApiInterface::setRequest()
	 */
	public function setRequest() {
		$request = array(
			'orderId' => $this->_orderId,
			'note' => $this->_note,
		);
		$this->_request = $request;
		return $this;
	}
}