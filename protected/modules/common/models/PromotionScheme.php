<?php
abstract class PromotionScheme extends CommonModel{
	
	const PROMOTION_TYPE_PRICE = 1;				//价格促销
	const PROMOTION_TYPE_GIFT = 2;				//礼品促销
	
	const PROMOTION_PROGRESS_WAITING = 0;		//促销方案待实施
	const PROMOTION_PROGRESS_UNDERWAY = 1;		//促销方案进行中
	const PROMOTION_PROGRESS_END = 2;			//促销方案已经结束
	
	const STATUS_ON = 1;			//启用状态
	const STATUS_OFF = 0;		//关闭状态
	
	/**
	 * @desc 状态列表
	 * @param string $key
	 * @return Ambigous <string, Ambigous <string, string, unknown>>|multitype:string Ambigous <string, string, unknown>
	 */
	public static function getStatusList($key = null) {
		$list = array(
				self::STATUS_ON => Yii::t('system', 'Enable'),
				self::STATUS_OFF => Yii::t('system', 'Disable'),
		);
		if (!is_null($key) && array_key_exists($key, $list))
			return $list[$key];
		return $list;
	}
	
	/**
	 * @desc 方案进度列表
	 * @param string $key
	 * @return Ambigous <string, Ambigous <string, string, unknown>>|multitype:string Ambigous <string, string, unknown>
	 */
	public static function getProgressList($key = null) {
		$list = array(
				self::PROMOTION_PROGRESS_WAITING => Yii::t('promotion_scheme', 'Progress Waiting'),
				self::PROMOTION_PROGRESS_UNDERWAY => Yii::t('promotion_scheme', 'Progress Underway'),
				self::PROMOTION_PROGRESS_END => Yii::t('promotion_scheme', 'Progress End')
		);
		if (!is_null($key) && array_key_exists($key, $list))
			return $list[$key];
		return $list;
	}	
 }
