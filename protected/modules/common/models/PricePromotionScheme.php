<?php
/**
 * @desc 价格促销方案
 * @author zhangF
 *
 */
class PricePromotionScheme extends PromotionScheme {
	protected $_promotionType = 1;				//价格方案
	public $platform_name = null;
	public $platform_code = null;
	
	const PRICE_PROMOTION_MODE_PERCENT = 1;				//按百分比降价
	const PRICE_PROMOTION_MODE_AMOUNT = 2;				//直接减少对应金额
	
	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_price_promotion_scheme';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CModel::rules()
	 */
	public function rules() {
		return array(
			array('name, start_date, end_date, discount_mode, discount_factor, status, platform_code, currency_code', 'required'),
			array('start_date, end_date', 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'),
			array('discount_factor', 'numerical'), 
		);
	}
	
	public function relations() {
		return array(
			'platforms' => array(
					self::HAS_MANY, 'PlatformPromotion',
					array('promotion_id' => 'id'),
					'select' => 'platform_code',
					'condition' => 'promotion_type = ' . self::PROMOTION_TYPE_PRICE,
			),
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see UebModel::search()
	 */
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 'Create_time',
		);
		$providerData = parent::search(get_class($this), $sort, '', $this->_setCDbCriteria());
		$datas = $this->addition($providerData->data);
		$providerData->setData($datas);
		return $providerData;
	}
	
	/**
	 * @desc 设置查询条件
	 * @return CDbCriteria
	 */
	protected function _setCDbCriteria() {
		$criteria = new CDbCriteria();
		return $criteria;
	}
	
	/**
	 * @desc 处理查询数据
	 * @param mixed $datas
	 * @return mixed
	 */
	public function addition($datas) {
		$platformList = CHtml::listData(UebModel::model('Platform')->findAll(), "platform_code", "platform_name");
		foreach ($datas as $key => $data) {
			$platformName = '';
			foreach ($data->platforms as $platform) {
				if ($platform->platform_code == Platform::CODE_ALL) {
					$platformName = Yii::t('promotion_scheme', 'All Platform');
					break;
				}
				if (array_key_exists($platform->platform_code, $platformList))
					$platformName .= $platformList[$platform->platform_code] . ', ';
			}
			$datas[$key]->platform_name = trim($platformName, ', ');
			if ($data->discount_mode == self::PRICE_PROMOTION_MODE_PERCENT)
				$datas[$key]->discount_factor = $data->discount_factor . '%';
			else if ($data->discount_mode == self::PRICE_PROMOTION_MODE_AMOUNT) {
				if (!empty($data->currency_code) && ($currencyModel = Currency::model()->getByCode($data->currency_code)))
					$datas[$key]->discount_factor = $datas[$key]->discount_factor . ' ' . $currencyModel->symbol;
			}
			$datas[$key]->discount_mode = self::getDiscountModeList($data->discount_mode);
			$datas[$key]->status = self::getStatusList($data->status);
			$datas[$key]->progress = self::getProgressList($data->progress);
		}
		return $datas;
	}
	
	/**
	 * @desc 设置搜索条件
	 */
	public function filterOptions() {
		return array();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels() {
		return array(
			'name' => Yii::t('promotion_scheme', 'Name'),
			'platform_code' => Yii::t('promotion_scheme', 'Platform'),
			'discount_factor' => Yii::t('promotion_scheme', 'Discount Factor'),
			'status' => Yii::t('system', 'Status'),
			'discount_mode' => Yii::t('promotion_scheme', 'Discount Mode'),
			'start_date' => Yii::t('promotion_scheme', 'Start Date'),
			'end_date' => Yii::t('promotion_scheme', 'End Date'),
			'note' => Yii::t('promotion_scheme', 'Note'),
			'currency_code' => Yii::t('system', 'Currency'),
			'create_user_id' => Yii::t('system', 'Create User'),
			'create_time' => Yii::t('system', 'Create Time'),
			'progress'	=> Yii::t('promotion_scheme', 'Progress')
		);
	}
	
	/**
	 * @desc 打折方式列表
	 * @param string $key
	 * @return Ambigous <string, Ambigous <string, string, unknown>>|multitype:string Ambigous <string, string, unknown>
	 */
	public static function getDiscountModeList($key = null) {
		$list = array(
			self::PRICE_PROMOTION_MODE_PERCENT => Yii::t('promotion_scheme', 'Price Promotion Percent'),
			self::PRICE_PROMOTION_MODE_AMOUNT => Yii::t('promotion_scheme', 'Price Promotion Amount'),
		);
		if (!is_null($key) && array_key_exists($key, $list))
			return $list[$key];
		return $list;
	}
	
	/**
	 * @desc　获取价格促销方案
	 * @param string $platformCode
	 * @param string $active
	 * @return Ambigous <multitype:, mixed>
	 */
	public function getPricePromotionScheme($platformCode = null, $active = true) {
		$dbCommand = $this->getDbConnection()->createCommand()
			->from(self::tableName() . " t")
			->select("*")
			->where("promotion_type = " . PricePromotionScheme::PROMOTION_TYPE_PRICE);
		if (!is_null($platformCode)) {
			$dbCommand->join("ueb_platform_promotion t1", "t1.promotion_id = t.id");
			$dbCommand->where .= " and (t1.platform_code = '" . Platform::CODE_ALL . "' OR t1.platform_code = '" . $platformCode . "')";
		}
		if ($active) {
			$dbCommand->where .= " and status = 1";
		}
		return $dbCommand->queryAll();
	}
}