<?php
/**
 * @平台账号工厂类，根据不同的平台生成账号对象
 * @author zhangF
 *
 */
class AccountFactory extends UebModel {
	
    const FUNCTION_PRODUCT_ADD = 1;//账号刊登功能
    
	/**
	 * @desc 工厂方法
	 * @param unknown $platformCode
	 * @throws Exception
	 * @return AliexpressAccount|AmazonAccount|EbayAccount|WebsiteAccount|WishAccount|NULL
	 */
	public static function factory($platformCode) {
		switch ($platformCode) {
			case Platform::CODE_ALIEXPRESS:
				return new AliexpressAccount;
			case Platform::CODE_AMAZON:
				return new AmazonAccount;
			case Platform::CODE_EBAY:
				return new EbayAccount;
			case Platform::CODE_NEWFROG:
				return new WebsiteAccount;
			case Platform::CODE_WISH:
				return new WishAccount();
			case Platform::CODE_YESFOR:
				return NULL;
			default:
				throw new Exception('Invalid Platform Code');
		}
	}
}