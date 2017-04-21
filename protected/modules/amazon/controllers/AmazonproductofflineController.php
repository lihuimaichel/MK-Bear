<?php
/**
 * @desc amazon下架记录
 * @author hanxy
 *
 */
class AmazonproductofflineController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new AmazonOffline();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$this->render("list", array("model"=>$this->_model));
	}
}