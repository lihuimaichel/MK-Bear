<?php
/**
 * @desc 价格策略接口
 * @author zhangF
 *
 */
interface IPriceStrategy {
	
	/**
	 * @desc 提高价格
	 * @param PriceManage $priceManage
	 */
	public function raisePrice(PriceManage $priceManage);
	
	/**
	 * @desc 降低价格
	 * @param PriceManage $priceManage
	 */
	public function reducePrice(PriceManage $priceManage);
}