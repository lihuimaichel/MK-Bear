<?php
/**
 * @desc ebay重新上架记录
 * @author hanxy
 *
 */
class EbayproductonlineController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new EbayProductOnlineLog();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$this->render("list", array("model"=>$this->_model));
	}
}