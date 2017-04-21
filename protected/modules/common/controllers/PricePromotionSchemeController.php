<?php
class PricePromotionSchemeController extends UebController {
	protected $_model = null;
	
	public function init() {
		$this->_model = new PricePromotionScheme();
	}
	
	/**
	 * @desc 创建
	 */
	public function actionCreate() {
		if (Yii::app()->request->isPostRequest) {
			$this->_model->attributes = $_POST['PricePromotionScheme'];
			$startDate = $_POST['PricePromotionScheme']['start_date'];
			$endDate = $_POST['PricePromotionScheme']['end_date'];
			$discountMode = (int)$_POST['PricePromotionScheme']['discount_mode'];
			$this->_model->setAttribute('discount_mode', $discountMode);
			$discountFactor = floatval($_POST['PricePromotionScheme']['discount_factor']);
			$this->_model->setAttribute('discount_factor', $discountFactor);
			$platformCodes = $_POST['PricePromotionScheme']['platform_code'];
			//如果选择有适用所有平台则只插入一条对应关系到平台促销对应关系表里面
			if (in_array(Platform::CODE_ALL, $platformCodes))
				$platformCodes = array(Platform::CODE_ALL);
			if (!($startTime = strtotime($startDate))) {
				echo $this->failureJson(array(
						'message' => Yii::t('promotion_scheme', 'Invalid Start Date'),
				));
				Yii::app()->end();				
			}
			if (!($endTime = strtotime($endDate))) {
				echo $this->failureJson(array(
						'message' => Yii::t('promotion_scheme', 'Invalid End Date'),
				));
				Yii::app()->end();				
			}
			//检查开始时间是否大于结束时间
			if (strtotime($startDate) > strtotime($endDate)) {
				echo $this->failureJson(array(
						'message' => Yii::t('promotion_scheme', 'Start Date Could Not Greater Than End Date'),
				));
				Yii::app()->end();
			}
			//按百分比降价时，降价数不能大于等于100或者小于0, 按固定金额降价时，降价数不能小于0
			if ($discountMode == PricePromotionScheme::PRICE_PROMOTION_MODE_PERCENT && ($discountFactor >= 100 || $discountFactor < 0)) {
				echo $this->failureJson(array(
						'message' => Yii::t('promotion_scheme', 'Discount Percent Must Between 0 AND 100'),
				));
				Yii::app()->end();
			} else if ($discountMode == PricePromotionScheme::PRICE_PROMOTION_MODE_AMOUNT && $discountFactor < 0) {
				echo $this->failureJson(array(
						'message' => Yii::t('promotion_scheme', 'Discount Amount Could Not Less Than 0'),
				));
				Yii::app()->end();
			}
			$userId = Yii::app()->user->id;
			$this->_model->setAttribute('create_user_id', $userId);
			$this->_model->setAttribute('modify_user_id', $userId);
			if (!$this->_model->save()) {
				echo $this->failureJson(array(
					'message' => Yii::t('system', 'Save Failure'),
				));
			} else {
				$promotionID = $this->_model->getDbConnection()->getLastInsertID();
				$platfromPromotionModel = new PlatformPromotion();
				foreach ($platformCodes as $platformCode) {
					$platfromPromotionModel->setAttribute('platform_code', $platformCode);
					$platfromPromotionModel->setAttribute('promotion_id', $promotionID);
					$platfromPromotionModel->setAttribute('promotion_type', PricePromotionScheme::PROMOTION_TYPE_PRICE);
					$platfromPromotionModel->isNewRecord = true;
					$platfromPromotionModel->save();
				}				
				echo $this->successJson(array(
					'message' => Yii::t('system', 'Save successful'),
					'callbackType' => 'closeCurrent',
					'navTabId' => 'page' . self::getIndexNavTabId(),
				));
			}
			Yii::app()->end();
		}
		$platformList = CHtml::listData(UebModel::model('Platform')->findAll(), 'platform_code', 'platform_name');
		$platformList = array_merge(array(Platform::CODE_ALL => Yii::t('promotion_scheme', 'All Platform')), $platformList);
		$this->_model->start_date = date('Y-m-d H:i:s');
		$this->_model->end_date = date('Y-m-d H:i:s');
		$this->render('create', array(
			'model' => $this->_model,
			'platformList' => $platformList,
		));
	}
	
	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$this->render('list', array(
			'model' => $this->_model,
		));
	}
	
	/**
	 * @desc 获取菜单对应ID
	 * @return integer
	 */
	public static function getIndexNavTabId() {
		return UebModel::model('Menu')->getIdByUrl('/common/pricepromotionscheme/list');
	}	
}