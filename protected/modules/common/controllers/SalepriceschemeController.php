<?php
/**
 * @desc 卖价方案控制器
 * @author zhangF
 *
 */
class SalepriceschemeController extends UebController {
	
	/**
	 * @var Salepricescheme Instance
	 */
	protected $_model = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new SalePriceScheme();
		parent::init();
	}
	
	/**
	 * @desc 列表
	 */
	public function actionList() {
		//error_reporting(7);
		$platformList = CHtml::listData(UebModel::model('Platform'), 'platform_code', 'platform_name');
		$model_name = Yii::app()->request->getParam('model_name');
		$this->render('list', array(
				'model' => $this->_model,
				'platformList' => $platformList,
				'modelName'=>$model_name
		));
	}
	
	/**
	 * @desc 创建卖价方案
	 */
	public function actionCreate() {
		if (Yii::app()->request->isPostRequest) {
			//判断是否重复提交方案
			$postArr = $_POST['SalePriceScheme'];
			$where   = 'platform_code = :platform_code AND profit_calculate_type = :profit_calculate_type';
			$params  = array(':platform_code'=>$postArr['platform_code'], ':profit_calculate_type'=>$postArr['profit_calculate_type']);
			$isExist = $this->_model->getSalePriceSchemeByWhere($where, $params);
			if($isExist){
				echo $this->failureJson(array(
					'message' => '已经存在，不能重复添加'
				));
				Yii::app()->end();
			}
			$this->_model->attributes = $_POST['SalePriceScheme'];
			$userId = Yii::app()->user->id;
			$this->_model->setAttribute('create_user_id', $userId);
			$this->_model->setAttribute('modify_user_id', $userId);
			if ($this->_model->save(true)) {
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Save successful'),
					'callbackType' => 'closeCurrent',
					'navTabId' => 'page' . SalePriceScheme::getIndexNavTabId(),
 				));
			} else {
				echo $this->failureJson(array(
					'message' => Yii::t('system', 'Save Failure'),
				));
			}
			Yii::app()->end();
		}
		$this->render('create', array(
			'model' => $this->_model,
		));
	}
	
	/**
	 * @desc 更新卖价方案
	 */
	public function actionUpdate() {
		$ids = Yii::app()->request->getParam('ids');
		$standardProfitRates = Yii::app()->request->getParam('standard_profit_rate');
		$lowestProfitRates = Yii::app()->request->getParam('lowest_profit_rate');
		$floatingProfitRates = Yii::app()->request->getParam('floating_profit_rate');
		// $profitCalculateTypes = Yii::app()->request->getParam('profit_calculate_type');
		$platformCodes  = Yii::app()->request->getParam('platform_code');
		if ($ids != '') {
			$ids = explode(',', $ids);
			$ids = array_filter($ids);
			foreach ($ids as $id) {
				$model = SalePriceScheme::model()->findByPk($id);
				if (array_key_exists($id, $standardProfitRates))
					$model->setAttribute('standard_profit_rate', floatval($standardProfitRates[$id]));
				if (array_key_exists($id, $lowestProfitRates))
					$model->setAttribute('lowest_profit_rate', floatval($lowestProfitRates[$id]));
				if (array_key_exists($id, $floatingProfitRates))
					$model->setAttribute('floating_profit_rate', floatval($floatingProfitRates[$id]));
				// if (array_key_exists($id, $profitCalculateTypes))
				// 	$model->setAttribute('profit_calculate_type', (int)$profitCalculateTypes[$id]);
				if (array_key_exists($id, $platformCodes))
					$model->setAttribute('platform_code', $platformCodes[$id]);
				$model->save();
			}
			echo $this->successJson(array(
				'message' => Yii::t('system', 'Save successful'),
				'navTabId' => 'page' . SalePriceScheme::getIndexNavTabId(),
			));
			Yii::app()->end();
		}
		echo $this->failureJson(array(
				'message' => Yii::t('system', 'Save failure'),
		));
		Yii::app()->end();
	}
	
	/**
	 * @desc 删除卖价方案
	 */
	public function actionDelete() {
		$ids = Yii::app()->request->getParam('ids');
		if ($ids) {
			if ($this->_model->deleteAll("id in ($ids)")) {
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Delete successful'),
					'navTabId' => 'page' . SalePriceScheme::getIndexNavTabId()
				));
				Yii::app()->end();
			}
		}
		echo $this->failureJson(array(
			'message' => Yii::t('system', 'Delete Failure')
		));
		Yii::app()->end();
	}
	
	/**
	 * selelct template get template_name
	 */
	public function actionGetcode() {
		$id = Yii::app()->request->getParam('id');
		if ( empty($id) ) die('');
		$id = explode(",", $id);
		$paramTplInfo = UebModel::model('SalePriceScheme')->getParamTplById($id);
		if($paramTplInfo){
			echo json_encode($paramTplInfo);
		}else{
			echo '';
		}
		die();
	}
}