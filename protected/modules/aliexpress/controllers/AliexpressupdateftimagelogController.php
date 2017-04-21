<?php
/**
 * @desc 更新附图
 * @author hanxy
 *
 */
class AliexpressupdateftimagelogController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new AliexpressUpdateFtImageLog();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$this->render("index", array("model"=>$this->_model));
	}
}