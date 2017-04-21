<?php
/**
 * @desc 销售平台model
 * @author Gordon
 * @since 2015-06-08
 */
class Platform extends SystemsModel {
	const CODE_EBAY        	= 'EB';//Ebay
	const CODE_NEWFROG     	= 'NF';//Newfrog
	const CODE_YESFOR      	= 'YF';//Yesfor
	const CODE_ALIEXPRESS  	= 'ALI';//Aliexpress
	const CODE_WISH        	= 'KF';//Wish
	const CODE_AMAZON      	= 'AMAZON';//Amazon
	const CODE_LAZADA      	= 'LAZADA';//LAZADA
	const CODE_ALL		   	= 'ALL'; //所有平台
	const CODE_JD		   	= 'JDGJ'; //JDGJ
	const CODE_JOOM		   	= 'JM';	//JOOM
	const CODE_PM		   	= 'PM';	//PM - priceminister
	const CODE_DUNHUANG    	= 'DH';// --- 未启用
	const CODE_KF 			= 'KF';//Wish  
	const CODE_BELLABUY 	= 'BELLABUY';//Bellabuy  -- --- 未启用
	const CODE_NEWEGG 		= 'NE'; //Newegg --- 未启用
	const CODE_ECOOLBUY 	= 'ECB'; //ECOOLBUY  -- 未启用
	// const CODE_JDGJ 		= 'JDGJ'; //JDGJ
	//const CODE_JM 			= 'JM'; //JOOM    注释2017/03/27
	const CODE_SHOPEE 		= 'SHOPEE'; //SHOPEE
	const CODE_ALIBABA 		= 'ALIBB'; //ALIB  -- 未启用
    const CODE_PAYTM        = 'PAYTM';//PAYTM
    const CODE_CD           = 'CD'; //CDISCOUNT
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    
    }
    
    public function getDbKey(){
        return 'db_oms_system';
    }
    
    public function tableName() {	
    	return 'ueb_platform';
    }
    
    /**
     * @desc 获取所有平台CODE
     * @return array
     */
    public function getPlatformCodes() {
    	return array(
    			self::CODE_EBAY,
    			self::CODE_NEWFROG,
    			self::CODE_YESFOR,
    			self::CODE_ALIEXPRESS,
    			//self::CODE_DUNHUANG,
    			self::CODE_AMAZON,
    			self::CODE_WISH,
    			self::CODE_LAZADA,
    			self::CODE_JD,
    			self::CODE_JOOM,
    			self::CODE_PM
    	);
    }
    
    public function getPlatformList($platformCode=''){
    	static $objPlatform = array();
    	$platformArr = array();
    	if(empty($objPlatform)){
    		$objPlatform = $this->getPlatformParis();
    	}
    	foreach($objPlatform as $val){
    		$platformArr[$val->platform_code] = $val->platform_name;
    	}
    	if(!empty($platformCode)){
    		return isset($platformArr[$platformCode]) ? $platformArr[$platformCode] : '';
    	}
    	return $platformArr;
    	 
    }
    
    public function getPlatformParis(){
    	$arr = $this->findAll();
    	return $arr;
    }
    
    public function getNameById($id) {
    	return $this->getDbConnection()->createCommand()->from(self::tableName())
    	->select("platform_name")
    	->where("id = :id", array(':id' => $id))
    	->queryScalar();
    }
    
    public function getPlatformByCode($platformCode) {
    	return $this->find("platform_code = :code", array(':code' => $platformCode));
    }
    
    /**
     * @desc 获取所有平台CODE和name
     * @return array
     */
    public function getPlatformCodesAndNames() {
    	return array(
    			self::CODE_EBAY         =>'Ebay',
    			self::CODE_NEWFROG      =>'Newfrog',
    			self::CODE_YESFOR       =>'Yesfor',
    			self::CODE_ALIEXPRESS   =>'Aliexpress',
    			self::CODE_AMAZON       =>'Amazon',
    			self::CODE_WISH         =>'Wish',
    			self::CODE_LAZADA       =>'Lazada',
    			self::CODE_JD           =>'Jd',
    			self::CODE_JOOM         =>'Joom',
    			self::CODE_PM         =>'Priceminister',
    	);
    }
    
    // ====================== OMS sku 刊登状态迁移End =====================
    public  function getUseStatusCode(){
    	$platformCode=$this->getPlatformList();
    	unset($platformCode['ECB']);
    	unset($platformCode['YF']);
    	unset($platformCode['NE']);
    	unset($platformCode['BELLABUY']);
    	return $platformCode;
    }
    
    // ====================== OMS sku 刊登状态迁移Start =====================



	public function departmentPlatform()
	{
	    return Department::departmentPlatform();
        /*		return array(
			15 => self::CODE_WISH,
			37 => self::CODE_WISH,
			4 => self::CODE_ALIEXPRESS,
			25 => self::CODE_ALIEXPRESS,
			5 => self::CODE_AMAZON,
			24 => self::CODE_AMAZON,
			20 => self::CODE_LAZADA,
			3 => self::CODE_EBAY,
			23 => self::CODE_EBAY,
			19 => self::CODE_EBAY,
            53 => self::CODE_EBAY,
            54 => self::CODE_EBAY,
            55 => self::CODE_EBAY,
            56 => self::CODE_EBAY,
			38 => self::CODE_JOOM,
		);*/
	}
}