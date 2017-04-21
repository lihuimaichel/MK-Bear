<?php
/**
 * Template Rules Base Component
 * @author	wx
 * @since	2015-07-29 15:55:00
 */

class TemplateRulesBase extends CComponent {
	
	//定义自动处理动作
	const MATCH_DESCRI_TEMPLATE = 1;		//匹配描述模板
	const MATCH_PARAM_TEMPLATE = 2; 		//匹配参数模板
	const MATCH_PRICE_TEMPLATE = 4; 		//匹配价格模板
	
	//定义计算条件
	const CAL_THAN = '>';
	const CAL_LESS = '<';
	const CAL_EQ   = '=';
	const CAL_GEQ  = '>=';
	const CAL_LEQ  = '<=';
	const CAL_NEQ  = '<>';
	const CAL_IN   = 'in';
	const CAL_NIN  = 'not in';
	const CAL_LIKE = 'like';
	const CAL_NLIKE = 'not like';
	
	const MODEL_PRODUCT = 'Product';
	const MODEL_PRODUCT_SALES = 'ProductSales';
	const MODEL_PRODUCT_ROLE_ASSIGN = 'ProductRole';
	const MODEL_PRODUCT_CATEGORY = 'ProductCategorySkuOld';
	const MODEL_VIRTUAL_SITE = 'VirtualSite'; //站点
	const MODEL_VIRTUAL_ACCOUNT = 'VirtualAccount'; //账号
	
	//定义分隔符
	const STR_SEPARATOR = '{##}';
	
	//定义单位标识
	const SYS_CURRENCY = 'sys_currency';
	const SYS_WEIGHT ='sys_weight';
	const SYS_UDEFINE = 'sys_udef';//自定义
	
	const WEIGHT_KG = 'kg';
	const WEIGHT_G = 'g';
	
	//定义字段检查类型
	const VALIDATE_SKU = 'SKU';
	const VALIDATE_COUNTRY = 'country';
	const VALIDATE_PLAT_ACCOUNT = 'platform_account';
	const VALIDATE_USER = 'user';
	
	//定义自动补全字段
	const  AUTO_COMPLETE_COUNTRY = 'country';
	const  AUTO_COMPLETE_PLATFORMACCOUNT = 'platformaccount';
	const  AUTO_COMPLETE_USER = 'user';
	const  AUTO_COMPLETE_SKU_CATEGORY_OLD = 'categoryskuold';
	const  AUTO_COMPLETE_SITE = 'site';
	
	//定义表 [防止字段显示太多，需要哪些字段都在此添加]
	static $modelList = array(
			self::MODEL_PRODUCT					=> array('tab'=>'ueb_product', 'bound'=>1, 'field'=>array('product_cost','product_weight'),'field_extends'=>array('product_cost'=>array(),'product_weight'=>array()) ),
			self::MODEL_PRODUCT_SALES			=> array(
														 'tab'=>'ueb_product_sales',
														 'bound'=>1, 
														 'field'=>array('sale_num','sale_amount'), 
														 'field_extends'=>array(
														 							'sale_num'	 =>array('day_type'=>array(3=>'3天内',7=>'7天内',15=>'15天内',30=>'30天内',60=>'60天内')),
														 							'sale_amount'=>array('day_type'=>array(3=>'3天内',7=>'7天内',15=>'15天内',30=>'30天内',60=>'60天内'))
														  				  ) 
												   ),
			self::MODEL_PRODUCT_ROLE_ASSIGN		=> array('tab'=>'ueb_product_role_assign', 'bound'=>1, 'field'=>array('user_id'),'field_extends'=>array( 'user_id'=>array('role_code'=>array('purchaser'=>'采购员','product_developers'=>'产品开发员','ebay_user'=>'ebay销售')) )   ),
			self::MODEL_PRODUCT_CATEGORY		=> array('tab'=>'ueb_product_category_sku_old', 'bound'=>1, 'field'=>array('classid'),'field_extends'=>array('classid'=>array())),
			self::MODEL_VIRTUAL_SITE			=> array('tab'=>'VirtualSite', 'bound'=>1, 'field'=>array('site'),'field_extends'=>array('site'=>array())),
			self::MODEL_VIRTUAL_ACCOUNT			=> array('tab'=>'VirtualAccount', 'bound'=>1, 'field'=>array('account'),'field_extends'=>array('account'=>array()))
	);
	
	/**
	 * 处理动作列表
	 * @param	string	$type
	 * @return	mixed
	 */
	static function getRuleClassList($type=null) {
		$list = array(
				self::MATCH_DESCRI_TEMPLATE	=> Yii::t('conditions_field', 'Match Describe Template'),
				self::MATCH_PARAM_TEMPLATE	=> Yii::t('conditions_field', 'Match Param Template'),
				self::MATCH_PRICE_TEMPLATE	=> Yii::t('conditions_field', 'Match Price Template'),
				
		);
		($type != null && array_key_exists($type, $list)) && $list = $list[$type];
		return $list;
	}
	
	public static function getRuleClassMap($type=null){
		$list = array(
				self::MATCH_DESCRI_TEMPLATE	=> array( self::MODEL_VIRTUAL_SITE,self::MODEL_VIRTUAL_ACCOUNT ),
				self::MATCH_PARAM_TEMPLATE	=> array( self::MODEL_VIRTUAL_ACCOUNT ),
				self::MATCH_PRICE_TEMPLATE	=> array( self::MODEL_PRODUCT,self::MODEL_PRODUCT_SALES,self::MODEL_PRODUCT_ROLE_ASSIGN,self::MODEL_PRODUCT_CATEGORY,self::MODEL_VIRTUAL_ACCOUNT )
				
		);
		($type != null && array_key_exists($type, $list)) && $list = $list[$type];
		return $list;
	}
	
	/**
	 * 得到模型
	 */
	static function getModelList() {
		$list = array();
		foreach (self::$modelList as $key => $value) {
			$list[$key] = $key;
		}
		return $list;
	}
	
	/**
	 * 得到计算条件
	 */
	static function getCalConditionList() {
		$list = array(
				self::CAL_THAN	=> self::CAL_THAN,
				self::CAL_LESS	=> self::CAL_LESS,
				self::CAL_EQ	=> self::CAL_EQ,
				self::CAL_GEQ	=> self::CAL_GEQ,
				self::CAL_LEQ	=> self::CAL_LEQ,
				self::CAL_NEQ	=> self::CAL_NEQ,
				self::CAL_IN	=> self::CAL_IN,
				self::CAL_NIN	=> self::CAL_NIN,
				self::CAL_LIKE	=> self::CAL_LIKE,
				self::CAL_NLIKE	=> self::CAL_NLIKE,
		);
		return $list;
	}
	
	/**
	 * 得到计算单位标识
	 */
	static function getCalUnitCodeList() {
		$list = array(
				self::SYS_CURRENCY	=> self::SYS_CURRENCY,
				self::SYS_WEIGHT	=> self::SYS_WEIGHT,
				//self::SYS_UDEFINE	=> self::SYS_UDEFINE,
		);
		return $list;
	}
	
	/**
	 * 得到计算单位标识 转化Data
	 */
	static function getCalUnitCodeToData($code) {
		$data = array();
		$code == self::SYS_CURRENCY && $data = UebModel::model('Currency')->getCurrencyList();
		$code == self::SYS_WEIGHT && $data = array('kg'=>'kg','g'=>'g');
		return $data;
	}
	
	/**
	 * 得到有验证要求的字段的值列表Value List
	 */
	static function getVlidateTypeValueList( $validateType ) {
		$data = array();
		$code == self::SYS_CURRENCY && $data = UebModel::model('Currency')->getCurrencyList();
		$code == self::SYS_WEIGHT && $data = array('kg'=>'kg','g'=>'g');
		switch ( $validateType ){
			case self::VALIDATE_COUNTRY:
				$valueList = UebModel::model('Country')->
				break;
				
		}
		return $data;
	}
	
	/**
	 * 处理计算值
	 * @param	string	$calKey
	 * @param	string	$calVal
	 * @param	string	$calUnit	sys_weight#kg
	 * @return	string
	 */
	static function getCalExp($calKey, $calVal) {
		$ret = array('exp'=>'', 'rel'=>'');
		switch ($calKey) {
			case self::CAL_IN :
				$retExp = "in_array('{waitCheckValue}', TemplateRulesBase::getCalValue('{$calVal}'))";
				break;
			case self::CAL_NIN :
				$retExp = "!in_array('{waitCheckValue}', TemplateRulesBase::getCalValue('{$calVal}'))";
				$ret['rel'] = 'ALL';
				break;
			case self::CAL_EQ:
				$retExp = "'{waitCheckValue}' {$calKey}{$calKey} '{$calVal}'";
				break;
			case self::CAL_NEQ:
				$retExp = "'{waitCheckValue}' {$calKey} '{$calVal}'";
				$ret['rel'] = 'ALL';
				break;
			case self::CAL_LIKE:
				$retExp = "strstr('{waitCheckValue}', '{$calVal}')";
				break;
			case self::CAL_NLIKE:
				$retExp = "!strstr('{waitCheckValue}', '{$calVal}')";
				$ret['rel'] = 'ALL';
				break;
			default:
				$retExp = "{waitCheckValue} {$calKey} {$calVal}";
		}
		
		$ret['exp'] = $retExp;
		return $ret;
	}
	
	static function getCalValue($calVal) {
		$ret = explode(self::STR_SEPARATOR, $calVal);
		return $ret;
	}
	
	/**
	 * Unit 比较
	 * @param	array	array('unit_code'=>'','unit_value'=>'','refer_code'=>array())
	 * @return	array	array('ret_code'=>'','ret_value'=>'')
	 */
	static function getCalValueUnit($val, $calUnit) {
		$ret = array('ret_code'=>'', 'ret_value'=>'');
		$val = explode(',', $val);
		$val2 = $val;
		
		if ($calUnit['unit_code'] == self::SYS_WEIGHT) {
			$ret['ret_code'] = self::WEIGHT_G;
			if ($calUnit['unit_value'] == self::WEIGHT_KG) {
				foreach ($val2 as $key => $value) {
					$val2[$key] = $value * 1000;
				}
			}
		}
		
		if ($calUnit['unit_code'] == self::SYS_CURRENCY) {
			$refer_currency_code = $calUnit['refer_code'][self::SYS_CURRENCY];
			$ret['ret_code'] = $refer_currency_code;
			if (!empty($calUnit['unit_value']) && $calUnit['unit_value'] != $refer_currency_code) {
				foreach ($val2 as $key => $value) {
					$currRate = UebModel::model('CurrencyRate')->getRateByCondition($calUnit['unit_value'],$refer_currency_code,CurrencyRate::RATE_TYPE_BASE);
					if( $currRate == '' ){
						$val2[$key] = $value / $currRate;
					}else{
						$val2[$key] = $value * $currRate;
					}
					
				}
			}
		}
		
		$ret['ret_value'] = implode(',', $val2);
		return $ret;
	}
	
	/**
	 * 针对检查值做处理，如账号等
	 */
	static function getCheckValue($checkVal, $field, $platform) {
		$ret = $checkVal;
		if ( in_array( $field, array(self::MODEL_PRODUCT_ROLE_ASSIGN.'.'.'user_id') ) ) {
			$retUser = UebModel::model('User')->getUserNameArrById( $checkVal );
			$ret = $retUser[$checkVal];
		}else if( in_array( $field, array(self::MODEL_PRODUCT_CATEGORY.'.'.'classid') ) ){
			$retCate = UebModel::model('ProductCategoryOld')->getCatNameCnOrEn( $checkVal );
			$ret = $retCate[$checkVal]['en'];
		}else if( in_array( $field, array(self::MODEL_VIRTUAL_ACCOUNT.'.'.'account') ) ){
			$retAccount = MHelper::getPlatformAccount($platform,$checkVal);
			$ret = $retAccount;
		}else if( in_array( $field, array(self::MODEL_VIRTUAL_SITE.'.'.'site') ) ){
			if( $platform == Platform::CODE_LAZADA ){
				$retSite = LazadaSite::getSiteList( $checkVal );
			}
			$ret = $retSite;
		}
		return $ret;
	}
	
	/**
	 * 得到验证类型
	 */
	static function getValidateTypeList() {
		$list = array(
				self::VALIDATE_SKU	=> self::VALIDATE_SKU,
				self::VALIDATE_COUNTRY	=> self::VALIDATE_COUNTRY,
				self::VALIDATE_PLAT_ACCOUNT	=> self::VALIDATE_PLAT_ACCOUNT,
		);
		return $list;
	}
	
	/**
	 * 检查空值情况
	 */
	static function checkVoidValue($calKey) {
		$ret = false;
		if (in_array($calKey, array(self::CAL_NEQ,self::CAL_NIN,self::CAL_NLIKE))) {
			$ret = true;
		}
		return $ret;
	}
	
	static function getNewSort($sort) {
		$sort = intval($sort);
		$sort < 10 && $sort = '00'.$sort;
		($sort >= 10 && $sort < 100) && $sort = '0'.$sort;
		return $sort;
	}
	
}