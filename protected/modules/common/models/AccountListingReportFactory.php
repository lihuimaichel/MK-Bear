<?php
/**
 * @desc 账号listing报告工厂类
 * @author zhangF
 *
 */
class AccountListingReportFactory {
	
	/**
	 * @desc 构造函数，不能被实例化
	 */
	private function __construct() {
		
	}
	
	/**
	 * @desc 工厂方法，生成账号listing报告模型
	 * @param unknown $platformCode
	 * @return AmazonListReport
	 */
	public static function factory($platformCode) {
		switch ($platformCode) {
			case Platform::CODE_AMAZON:
				return new AmazonListingReport($platformCode);
			case Platform::CODE_ALIEXPRESS:
				return new AmazonListingReport($platformCode);
			default:
				throw new Exception(Yii::t('account_listing_report', 'This Platform Have Not List Report'));
		}
	}
}