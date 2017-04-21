<?php
class ProductriskrecordController extends UebController {
	
	/**
	 * @var Salepricescheme Instance
	 */
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new ProductRiskRecord();
		parent::init();
	}
	
	/**
	 * @desc 列表
	 */
	public function actionList() {
		$this->render('list', array(
				'model' => $this->_model,
		));
	}
	
}