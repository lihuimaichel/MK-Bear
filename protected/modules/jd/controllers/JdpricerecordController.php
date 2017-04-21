<?php
class JdpricerecordController extends UebController {
	protected $_model = null;
	public function init() {
		$this->_model = new JdProductPriceRecord();
	}

	public function actionList(){
		$this->render('list', array('model'=>$this->_model));
	}

}