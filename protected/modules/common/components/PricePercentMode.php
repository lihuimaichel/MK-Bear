<?php
/**
 * @desc 按百分比调整价格模式
 * @author zhangF
 *
 */
class PricePercentMode implements IPriceStrategy {
	/** @var float 调整百分比 **/
	private $_percent = 0;
	
	/**
	 * @param float $percent 百分比
	 * @throws Exception
	 */
	public function __construct($percent = 0) {
		if (!is_numeric($percent))
			throw new Exception(Yii::t('system', 'The Paramter Must Is a Number'));
		if ($percent < 0 || $percent > 100)
			throw new Exception(Yii::t('system', 'The Paramter Must Between 0 And 100'));
		//将百分比转化为小数
		$this->_percent = floatval($percent / 100);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IPriceStrategy::raisePrice()
	 */
	public function raisePrice(PriceManage $priceManage) {
		$originPrice = $priceManage->getOriginPrice();
		$newPrice = $originPrice * (1 + $this->_percent);
		return floatval($newPrice);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IPriceStrategy::reducePrice()
	 */
	public function reducePrice(PriceManage $priceManage) {
		$originPrice = $priceManage->getOriginPrice();
		$newPrice = $originPrice * (1 - $this->_percent);
		return floatval($newPrice);		
	}
}