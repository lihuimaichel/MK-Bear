<?php
/**
 * @desc 产品价格管理类
 * @author zhangF
 *
 */
class PriceManage {

	protected $_originPrice = 0.00;				//原价
	protected $_newPrice = null;				//处理后的价格
	protected $_currency = 'USD';				//货币符号

	const DEFAULT_RATE = 2;			//50%反算原价
	const DEFAULT_END_DATE = '2025-08-31';	//默认促销截止日期

	
	private $_priceStrategy = null;				//价格计算模式类
	private $_errorMessage = '';				//错误信息
	
	/**
	 * @desc 提高价格
	 * @return float
	 */
	public function raisePrice() {
		$newPrice = $this->_priceStrategy->raisePrice($this);
		$this->_newPrice = floatval($newPrice);
		return $this->_newPrice;
	}
	
	/**
	 * @desc 降低价格
	 * @return float
	 */
	public function reducePrice() {
		$newPrice = $this->_priceStrategy->reducePrice($this);
		$this->_newPrice = floatval($newPrice);
		return $this->_newPrice;
	}
	
	/**
	 * @desc 设置价格计算模式类
	 * @param IPriceStrategy $priceStrategy
	 */
	public function setPriceStrategy(IPriceStrategy $priceStrategy) {
		$this->_priceStrategy = $priceStrategy;
	}
	
	/**
	 * @desc 设置原价
	 * @param float $price
	 */
	public function setOriginPrice($price) {
		$this->_originPrice = $price;
	}
	
	/**
	 * @desc 设置货币
	 * @param string $currency
	 */
	public function setCurrency($currency) {
		$this->_currency = $currency;
	}
	
	/**
	 * @desc 获取调价后的价格
	 */
	public function getNewPrice() {
		return $this->_newPrice;
	}
	
	/**
	 * @desc 获取原价
	 * @return float
	 */
	public function getOriginPrice() {
		return $this->_originPrice;
	}
	
	/**
	 * @desc 获取货币
	 * @return string
	 */
	public function getCurrency() {
		return $this->_currency;
	}
	
	/**
	 * @desc 设置错误信息
	 * @param unknown $message
	 */
	public function setErrorMessage($message) {
		$this->_errorMessage = $message;
	}
	
	/**
	 * @desc 获取错误信息
	 * @return string
	 */
	public function getErrorMessage() {
		return $this->_errorMessage;
	}
	
	/**
	 * @desc 计算产品促销后的价格
	 * @param PromotionScheme $pricePromotion
	 * @throws Exception
	 * @return boolean
	 */
	public function calcaluteDiscountPrice(PromotionScheme $pricePromotion) {
		try {
			$discountMode = $pricePromotion->discount_mode;
			$discountFactor = $pricePromotion->discount_factor;
			$currency = $pricePromotion->currency_code;
			switch ($discountMode) {
				case PricePromotionScheme::PRICE_PROMOTION_MODE_PERCENT:
					$priceStrategy = new PricePercentMode($pricePromotion->discount_factor); break;
				case PricePromotionScheme::PRICE_PROMOTION_MODE_AMOUNT:
					$priceStrategy = new PriceAmountMode($discountFactor, $currency); break;
				default: 
					throw new Exception(Yii::t('promotion_scheme', 'Invalid Price Mode'));			
			}
			$this->setPriceStrategy($priceStrategy);
			return $this->reducePrice();
		} catch (Exception $e) {
			$this->setErrorMessage($e->getMessage());
			return false;
		}
	}
	
	/**
	 * @desc 获取货币间的等价金额
	 * @param float $amount
	 * @param string $fromCurreny
	 * @param string $toCurreny
	 * @return boolean|number
	 */
	public function getEqualAmount($amount, $fromCurreny, $toCurreny) {
		$exchangeRate = CurrencyRate::model()->getRateByCondition($fromCurreny, $toCurreny);
		if (!$exchangeRate) {
			$this->setErrorMessage(Yii::t('promotion_scheme', 'Can not Find Currency Exchange Relation'));
			return false;
		}
		return $amount * $exchangeRate;
	}

}