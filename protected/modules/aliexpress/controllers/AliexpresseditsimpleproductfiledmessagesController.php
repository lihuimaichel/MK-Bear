<?php
/**
 * @desc 等待买家付款发送留言
 * @author hanxy
 *
 */
class AliexpresseditsimpleproductfiledmessagesController extends UebController {
	
	/** @var object 模型实例 **/
	protected $_model = NULL;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new AliexpressEditSimpleProductFiledMessages();
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$aliAccountModel = new AliexpressAccount();
		$accountList     = $aliAccountModel->getIdNamePairs();
		$this->render("index", array("model"=>$this->_model, 'accountList'=>$accountList));
	}
}