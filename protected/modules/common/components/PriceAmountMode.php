<?php
/**
 * @desc 按金额调整价格模式
 * @author zhangF
 *
 */
class PriceAmountMode implements IPriceStrategy {
	/** @var float 调整金额 **/
	private $_amount = 0;
	/** @var float 调整金额货币符号 **/
	private $_currency = 'USD';
	
	/**
	 * @param float $amount
	 * @throws Exception
	 */
	public function __construct($amount, $currency) {
		if (!is_numeric($amount))
			throw new Exception(Yii::t('system', 'The Paramter Must Is a Number'));
		if ($amount < 0)
			throw new Exception(Yii::t('system', 'The Paramter Must Exceed 0'));
		$this->_amount = floatval($amount);
		$this->_currency = $currency;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IPriceStrategy::raisePrice()
	 */
	public function raisePrice(PriceManage $priceManage) {
		$originPrice = $priceManage->getOriginPrice();
		//将折扣金额转化成和价格货币相同的等价金额
		$equalAmount = $priceManage->getEqualAmount($this->_amount, $this->_currency, $priceManage->getCurrency());
		if ($equalAmount == false)
			throw new Exception($priceManage->getErrorMessage());
		return floatval($originPrice + $equalAmount);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IPriceStrategy::reducePrice()
	 */
	public function reducePrice(PriceManage $priceManage) {
		$originPrice = $priceManage->getOriginPrice();
		//将折扣金额转化成和价格货币相同的等价金额
		$equalAmount = $priceManage->getEqualAmount($this->_amount, $this->_currency, $priceManage->getCurrency());
		if ($equalAmount == false)
			throw new Exception($priceManage->getErrorMessage());
		return floatval($originPrice - $equalAmount);	
	}
}