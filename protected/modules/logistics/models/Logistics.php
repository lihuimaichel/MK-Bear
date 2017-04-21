<?php
/**
 * @package Ueb.modules.logistics.models
 * @author Gordon
 */
class Logistics extends LogisticsModel { 
    
    //运送方案
    /**@var EUB*/
    const CODE_EUB             = 'eub';//e邮宝
	const CODE_EUB_JIETE       = 'eub_jiete';//长沙e邮宝
	
	/**@var 小包*/
	const CODE_CM              = 'cm';//小包
	const CODE_DHL_XB          = 'cm_dhl';//敦豪全邮通-小包
	const CODE_CM_CN_VALSUN    = 'cm_valsun_cn';//中邮小包(赛维)
	const CODE_CM_YYB          = 'cm_yyb';//燕邮宝小包   燕文俄罗斯小包
	const CODE_CM_YDT          = 'cm_ydt';
	const CODE_CM_EN           = 'cm_en';//英国邮政小包
	const CODE_CM_YWBJ         = 'cm_cnxb_ywbj';//中邮北京小包    燕文北京小包
	const CODE_CM_BYB          = 'cm_cnxb_byb';//贝邮宝小包
	const CODE_CM_SW           = 'cm_swxb';
	const CODE_CM_SF           = 'cm_sf';//顺丰立陶宛小包
	const CODE_CM_EUB_VALSUN   = 'valsun_eub';//E邮宝(赛维)
	const CODE_CM_HK	       = 'cm_hkxb_jhd';	//香港邮包   香港小包
	const CODE_CM_HUNGARY      = 'cm_hungary';//匈牙利小包		俄罗斯小包        	易时达俄罗斯小包
	const CODE_CM_EHUNGARY     = 'cm_e_hungary';//匈牙利小包(欧洲)  //东欧小包  东欧专线
	const CODE_CM_CNXB         = 'cm_cnxb';//深圳小包
	const CODE_CM_GZ 	       = 'cm_gzxb';//广州小包
	const CODE_CM_DHL          = 'cm_dhl';//DHL小包
	const CODE_DHL_XB_DE       = 'cm_de_dhl'; //dhl优先小包
	const CODE_CM_SFEU         = 'cm_sfoz';//顺丰欧洲小包
	const CODE_CM_FU           = 'cm_fuzhou'; //龙岩小包
	const CODE_CM_2LG          = 'cm_2lg'; //皇邮信封类二级平邮
	const CODE_CM_2PK          = 'cm_2PK'; //皇邮包裹类二级平邮
	const CODE_CM_1LG          = 'cm_1lg'; //皇邮信封类一级平邮
	const CODE_CM_1PK          = 'cm_1PK'; //皇邮包裹类一级平邮
	const CODE_CM_SYB          = 'cm_syb';//顺邮宝小包
	const CODE_CM_SY_MY        = 'cm_sy_my';//顺友马来小包
    const CODE_CM_ZX_SYB       = 'cm_zx_syb';   //  深圳顺友顺邮宝小包
	const CODE_CM_CE           = 'cm_ce';//易时达 中英专线小包
	const CODE_CM_JRXB 		   = 'cm_jrxb';//广邮广州小包
	const CODE_CM_WISH		   = 'cm_wish';//wish小包
	const CODE_CM_CNXB_E	   = 'cm_cnxb_e';//深圳纽电小包平邮
	const CODE_CM_MET 		   = 'cm_4px_met';//递四方美E通
	const CODE_CM_SF_E	       = 'cm_e_sf';//顺丰立陶宛带电小包
	const CODE_CM_SGXB	       = 'cm_sgxb';//递四方新加坡小包
	const CODE_CM_GYHL_HK	   = 'cm_gyrd_hk';	//瑞典小包平邮带电
	const CODE_CM_GYHL   	   = 'cm_gyhl';	//荷兰小包平邮
	const CODE_CM_PUTIAN	   = 'CM_PUTIAN'; //鑫宇环莆田小包
	const CODE_CM_PUTIAN_E	   = 'cm_e_putian'; //鑫宇环莆田小包带电
	const CODE_CM_PLUS_SGXB    = 'cm_plus_sgxb';//新加坡挂号加强版
	const CODE_CM_YO_EST 	   = 'cm_yo_est';	//黑龙江俄速通亚欧小包
	const CODE_CM_PTXB		   = 'cm_ptxb';	//莆田邮局小包
	const CODE_CM_PTXB_E	   = 'cm_e_ptxb'; //莆田邮局小包带电
	const CODE_CM_DGYZ		   = 'cm_dgyz'; //东莞邮局小包
	const OODE_CM_ALI_DGYZ 	   = 'cm_ali_dgyz' ;    #速卖通东莞邮局小包
	const CODE_CM_ALI_DGYZ 	   = 'cm_ali_dgyz' ;    #速卖通东莞邮局小包
	const CODE_CM_YW_TEQXB	   = 'cm_yw_teqxb' ;
	
	const CODE_CM_ON_SFOZ 	   = 'cm_on_sfoz';//顺丰欧洲挪威小包
	const CODE_CM_GB_SFOZ 	   = 'cm_gb_sfoz';//顺丰欧洲英国小包
	
	const CODE_CM_JNA_LTZX	   = 'cm_jna_ltzx';//上海利通加拿大专线
	const CODE_CM_AZ_LTZX	   = 'cm_az_ltzx';//上海利通澳洲专线
    const CODE_CM_YD_LTZX      = 'cm_yd_ltzx';  //上海利通印度专线
    const CODE_CM_XXL_LTZX     = 'cm_xxl_ltzx';  //上海利通新西兰专线
	const CODE_CM_ZXYZ	   	   = 'cm_zxyz';//香港A2B泽西邮局小包
	const CODE_CM_DEYZ	       = 'cm_deyz';//香港A2B德邮英国小包
	const OODE_SWYH_ALI_PING   = 'cm_yd_hlyz';//赛维荷兰邮政平邮
	const CODE_CM_EST		   = 'cm_est'; //黑龙江俄速通俄罗斯小包
	const CODE_CM_QZYZ		   = 'cm_qzyz'; //泉州邮局小包
	const CODE_CM_QZ_DDXB	   = 'cm_qz_ddxb'; //泉州邮局带电小包
    const CODE_CM_DH_DHL       = 'cm_dh_dhl';   //香港DH DHL小包
    const CODE_CM_PTY_DSFZX    = 'cm_pty_dsfzx';  //深圳递四方葡萄牙专线
    const CODE_CM_BLS_DSFZX    = 'cm_bls_dsfzx';  //深圳递四方比利时专线
    const CODE_CM_YDL_DSFZX    = 'cm_ydl_dsfzx';  //深圳递四方意大利专线
    const CODE_CM_XBY_DSFZX    = 'cm_xby_dsfzx';  //深圳递四方西班牙专线
    const CODE_CM_FG_DSFZX     = 'cm_fg_dsfzx';  //深圳递四方法国专线
	const CODE_CM_GZ_WISH	   = 'cm_gz_wish';	//广州wish邮小包
	
	/**@var 专线*/
	const CODE_ZE              = 'ghxb_cusl';//中英专线挂号
	const CODE_CM_CUSL         = 'cm_cusl'; //中英专线小包
	const CODE_CM_CESL         = 'cm_cesl'; //中欧专线
	const CODE_GHXB_UKECSLTYD  = 'ghxb_ukecsltyd'; //深圳易时达英国经济专线
	const CODE_CM_XBYZX_YSD	   = 'cm_xbyzx_ysd'; //深圳易时达西班牙专线
	const CODE_CM_DGZX_YSD	   = 'cm_dgzx_ysd'; //深圳易时达德国专线

	
	/**@var 挂号*/
	const CODE_GHXB            = 'ghxb';//挂号
	const CODE_CE              = 'ghxb_cesl';//中欧专线挂号
	const CODE_BE              = 'ghxb_post_be';//比利时邮政
	const CODE_DHL_GH          = 'ghxb_dhl';//敦豪全邮通-挂号	
	const CODE_GHXB_CN         = 'ghxb_cn';//深圳挂号
	const CODE_GHXB_HK         = 'ghxb_hk';//香港挂号
	const CODE_GHXB_SG         = 'ghxb_sg';//新加坡挂号
	const CODE_GHXB_SW         = 'ghxb_sw';//瑞士挂号
	const CODE_GHXB_CN_VALSUN  = 'ghxb_valsun_cn';//中邮挂号(赛维)
	const CODE_GHXB_YYB        = 'ghxb_yyb';//燕邮宝挂号
	const CODE_GHXB_DL         = 'ghxb_dl';//荷兰小包挂号
	const CODE_GHXB_YWBJ       = 'ghxb_cn_ywbj';//中邮北京挂号
	const CODE_GHXB_BYB        = 'ghxb_cn_byb';//贝邮宝挂号
	const CODE_FU_GHXB         = 'ghxb_fuzhou';//福州挂号	 龙岩挂号
	const CODE_GHXB_GZ 	       = 'ghxb_gz';//广州挂号
	const CODE_GHXB_EHUNGARY   = 'ghxb_e_hungary';//匈牙利挂号(欧洲)  东欧挂号
	const CODE_GHXB_HUNGARY    = 'ghxb_hungary';//匈牙利挂号(非欧洲)
	const CODE_GHXB_SFEU       = 'ghxb_sfoz';//顺丰欧洲挂号
	const CODE_GHXB_SF         = 'ghxb_sf';
	const CODE_GHXB_YUNTUDD    = 'ghxb_yuntudd'; //云图带电挂号     云图福州挂号
	const CODE_GHXB_2ND_CLASS  = 'ghxb_2nd_class';//乐宝标准型
	const CODE_GHXB_2LGR       = 'ghxb_2LGR'; //皇邮信封类二级挂号
	const CODE_GHXB_2PKR       = 'ghxb_2PKR'; //皇邮包裹类二级挂号
	const CODE_GHXB_1LGR       = 'ghxb_1LGR'; //皇邮信封类一级挂号
	const CODE_GHXB_1PKR       = 'ghxb_1PKR'; //皇邮包裹类一级挂号
	const CODE_4HY_ECONOMY     = 'ghxb_4hy_economy';//美国经济型四海邮
	const CODE_4HY_STANDARD    = 'ghxb_4hy_standard';//美国标准型四海邮
	const CODE_4HY_EXPRESS     = 'ghxb_4hy_express';//美国仓快速型四海邮
	const CODE_GHXB_SYB        = 'ghxb_syb';//顺邮宝挂号
	const CODE_GHXB_SY_MY      = 'ghxb_sy_my';//顺友马来挂号
	const CODE_GHXB_CE         = 'ghxb_ce';//易时达 中英专线挂号
	const CODE_GHXB_WISH	   = 'ghxb_wish';//wish邮挂号
	const CODE_GHXB_CNXB_E	   = 'ghxb_cn_e';//深圳纽电小包挂号
	const CODE_GHXB_JR		   = 'ghxb_jr';	//广州广邮挂号2
	const CODE_GHXB_SF_E 	   = 'ghxb_e_sf';//顺丰立陶宛带电挂号
	const CODE_UKZX_3HPA	   = 'GHXB_3HPA';//易时达英国专线
	const CODE_GHXB_GYHL_HK	   = 'ghxb_gyrd_hk';	//瑞典小包挂号带电
	const CODE_GHXB_GYHL 	   = 'ghxb_gyhl';	//荷兰小包挂号
	const CODE_GHXB_PUTIAN	   = 'GHXB_PUTIAN'; //鑫宇环莆田挂号
	const CODE_GHXB_PUTIAN_E   = 'ghxb_e_putian'; //鑫宇环莆田邮局带电挂号
	const CODE_GHXB_DGYZ	   = 'ghxb_dgyz'; //东莞邮局挂号
	const CODE_GHXB_GB_SFOZ    = 'ghxb_gb_sfoz';//顺丰欧洲英国挂号
	const CODE_GHXB_ON_SFOZ    = 'ghxb_on_sfoz';//顺丰欧洲挪威挂号
	const CODE_GHXB_US_SFOZ    = 'ghxb_us_sfoz';//顺丰欧洲美国挂号
	const OODE_SWYH_ALI_GUA    = 'ghxb_yd_hlyz';//赛维荷兰邮政挂号
	const CODE_GHXB_EST    	   = 'ghxb_est';//黑龙江俄速通俄罗斯挂号
	const CODE_GHXB_YW_TEQGH   = 'ghxb_yw_teqgh' ;
	const CODE_GHXB_QZ_DDGH	   = 'ghxb_qz_ddgh'; //泉州邮局带电挂号
	const CODE_GHXB_QZYZ	   = 'ghxb_qzyz'; //泉州邮局挂号
	const CODE_GHXB_GZ_WISH	   = 'ghxb_gz_wish';//广州挂号wish邮
	
	
	/**@var 快递*/
	const CODE_EXPRESS 		   = 'kd';//快递
	const CODE_FEDEX_IE        = 'kd_fedexie';//FEDEXIE
	const CODE_FEDEX_IE_HK     = 'kd_fedexie_hongkong';//fedex-ie香港
	const CODE_FEDEX_IP        = 'kd_fedexip';//FEDEXIP
	const CODE_FEDEX_IP_HK     = 'kd_fedexip_hongkong';//fedex-ip香港
	const CODE_KD_TOLL         = 'kd_toll';
	const CODE_KD_SFEU         = 'kd_sfeu'; //顺丰欧洲专递
	const CODE_EMS             = 'kd_ems';//EMS
	const CODE_DHL             = 'kd_dhl';
	const CODE_TNT             = 'kd_tnt';
	const CODE_UPS             = 'kd_ups';
	const CODE_ARAMEX          = 'kd_aramex';
	const CODE_OTHER           = 'kd_other';
	const CODE_DHTD_IP         = 'kd_dhtd_ip';
	const CODE_DHTD_IE         = 'kd_dhtd_ie';
	const CODE_DHTD_UPS        = 'kd_dhtd_ups';
	const CODE_DHTD_DHL        = 'kd_dhtd_dhl';
	
    const CODE_MY_LGS          = 'kd_MY_LGS';
    const CODE_SG_LGS          = 'kd_sg_lgs';
    const CODE_ID_LGS          = 'kd_id_lgs';
    const CODE_TH_LGS          = 'kd_th_lgs';
    const CODE_PH_LGS          = 'kd_ph_lgs';
    const CODE_VN_LGS          = 'kd_vn_lgs';
	
    
    //======= 2016-12-20 =========== //
    
    const CODE_XN_DE_YZ = 'cm_dyxb_zgyz';//德国邮政虚拟小包 cm_dyxb_zgyz
    const CODE_XN_DE_YZ_DD = 'cm_dyxb_dd_zgyz';//德国邮政虚拟带电小包 cm_dyxb_dd_zgyz
    const CODE_XN_YZ_DD = 'cm_xb_dd_zgyz';//中国邮政虚拟带电小包 cm_xb_dd_zgyz
    const CODE_XN_YZ = 'cm_xnxb_zgyz';//中国邮政虚拟小包 cm_xnxb_zgyz
    const CODE_WYT_XGXB = 'cm_xgxb_wyt';//万邑通香港小包  cm_xgxb_wyt
    
    
    // ========= 2017-03-28 ========== //
    const CODE_KD_YT_EU = 'kd_yt_eu'; //中欧独轮车专线
    const CODE_KD_YT_US = 'kd_yt_us'; //中美独轮车专线
    const CODE_GHXB_HNGH_YT = 'ghxb_hngh_yt';	//云途华南挂号
    const CODE_CM_HNXB_YT   = 'cm_hnxb_yt';		//云途华南小包
    // ======== 2017-03-31 ========== //
    const CODE_CM_BYYD = 'cm_byxb_yd';//深圳运德比邮小包
    
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {     
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'ueb_logistics';
    }
    
    /**
     * 物流发货地址
     */
    public static function getSendAddressGm() {
    	return array(
    			'sender'            => 'Dragon.long',
    			'name'              => 'Universal E-Business(SZ)',
    			'address_1'         => '5th Floor B Buliding,DiGuang Digital Science And Technology Park',
    			'address_2'         => 'RD 9th,ChangZhen Community',
    			'county'            => 'GuangMing New District',
    			'city'              => 'Shenzhen',
    			'province'          => 'Guangdong',
    			'post_code'         => '518000',
    			'country'           => 'China',
    			'phone'             => '13151613679',
    	);
    }
    
    /**
     * @desc 根据物流编码获取物流ID
     * @param string $shipCode
     * @return int
     */
    public function getLogisticsIdByShipCode($shipCode){
        $logisticsInfo = $this->dbConnection->createCommand()
                        ->select('*')->from(self::tableName())->where('ship_code = "'.$shipCode.'"')->queryRow();
        return $logisticsInfo['id'];
    }
    
    /**
     * @desc 获取运费
     * @param string $shipCode
     * @param float $weight
     * @param array $param
     */
    public function getShipFee($shipCode, $weight, $param = array()){
        $param['ship_code'] = $shipCode;
    	$shipInfo = $this->getMinShippingInfo($weight, $param);
    	if(empty($shipInfo)) return 0;
    	return $shipInfo['ship_cost'];
    }
    
    /**
     * @desc 获取最佳物流
     * @param string $shipCode
     * @param float $weight
     * @param array $param
     */
    public function getMinShippingInfo($weight, $param = array()){
    	/* $start = time();

    	$result =  $this->getMinShippingCost($weight, $param);
    	$end = time();
    	if (isset($_REQUEST['add_type']) && $_REQUEST['add_type'] == 2) {
    		$params = $param;
    		$params['weight'] = $weight;
    	 	//MHelper::writefilelog('timepass_new3.txt', '获取运费 pass: '. ($end-$start).' ### '. json_encode(array('params'=>$params,'result'=>$result))."\r\n" );
    	}
    	return $result; */


    	
    	// return $this->getMinShippingInfoByCurl($weight, $param);
     	$start = time();
     	$tempParam = $param;
     	unset($tempParam['sku']);
        $params = array('weight' => $weight, 'param' => $tempParam );
        
        $api = Yii::app()->erpApi;
        $result = $api->setServer('oms')->setFunction('Logistics:Logistics:getMinShippingInfo')->setRequest($params)->sendRequest()->getResponse();
        $end = time();
		if (isset($_REQUEST['add_type']) && $_REQUEST['add_type'] == 2) {
			//MHelper::writefilelog('timepass_new2.txt', '获取运费 pass: '. ($end-$start).' ### '. json_encode(array('params'=>$params,'result'=>$result))."\r\n" );
		}
		$shipFee = 0;
		$shipCode = '';
		if(!empty($shipResult)){
			$shipFee = $shipResult['ship_cost']; 
			$shipCode = $shipResult['ship_code'];
		}

		// ============ 2017-02-09 =============
		$recordData = array(
				'platform_code'		=>		isset($param['platform_code']) ? $param['platform_code'] : '',
				'sku'				=>		isset($param['sku']) ? $param['sku'] : '',
				'weight'			=>		$weight,
				'ship_code'			=>		isset($param['ship_code']) ? $param['ship_code'] : '',
				'ship_code2'		=>		$shipCode,
				'attribute_id'		=>		isset($param['attributeid']) ? json_encode($param['attributeid']) : '',
				'country'			=>		isset($param['country']) ? $param['country'] : '',
				'warehouse_id'		=>		isset($param['warehouse']) ? intval($param['warehouse']) : 0,
				'ship_cost'			=>		$shipFee,
				'create_time'		=>		date("Y-m-d H:i:s")
		);
		UebModel::model("ProductImageAdd")->getDbConnection()->createCommand()->insert("market_statistics.ueb_sku_calculate_price_record", $recordData);
        // ============== add end ===============
        if( $api->getIfSuccess() ){
            return $result;
        }else{
            return 0;
        }

    }

    /**
     * @desc 获取最佳物流New
     * @param string $shipCode
     * @param float $weight
     * @param array $param
     */
    public function getMinShippingInfoByCurl($weight, $param = array()) {
    	$start = time();
    	//$url 	= 'http://local.oms.com/logistics/calculateShipCost/getshipfee/autorun/1';
		$url 	= 'http://172.16.1.11/logistics/calculateShipCost/getshipfee/autorun/1/rand/'.time().mt_rand(0,1000);
		$data 	= array('weight'=>$weight,'param'=>json_encode($param));
		$result = Yii::app()->curl->post($url, $data);		
		$result = json_decode($result,true);
		$end = time();
		if (isset($_REQUEST['add_type']) && $_REQUEST['add_type'] == 2) {
			//MHelper::writefilelog('timepass_new3.txt', '获取运费 pass: '. ($end-$start).' ### '. json_encode(array('params'=>$data,'result'=>$result))."\r\n" );
		}
		if ( isset($result['errCode'] ) && $result['errCode'] =='200' ) {
			return $result['data'];
		} else {
			return 0;
		}			 	
    }
    
    /**
     * @desc 根据物流Code获取物流名称
     * @param string $shipCode
     * @return string
     */
    public function getShipNameByShipCode($shipCode){
        $logisticsInfo = $this->find('ship_code = :ship_code',array(':ship_code'=>$shipCode));
        if($logisticsInfo===null){
            return '';
        }
        return $logisticsInfo->ship_name;
    }


    public function getShippingByName($name)
    {
        $logisticsInfo = $this->find('ship_name = :shipCode',array(':shipCode'=>$name));
        return $logisticsInfo;
    }

    
    // =========================================================== 迁移 ==========================================
 
    public $volumeWeightYes = 1;//按体积重计算
    public $volumeWeightNo	= 0; //不按计算体积重
    public $logisticsArr = array();
    public $onUse = 1;
    public $stopUse = 0;
    
    public $quotaDefault = 0;
    public $quotaOn = 1;
    
    public $ship_company_logistics = 1;	//公司物流方式
    public $ship_dropshipping_logistics = 2;//分销商物流方式

    //是否有配额
    const SHIP_QUOTA_YES = 1;
    const SHIP_QUOTA_No = 0;
    
    /**
     * Main case id of the pricing solution
     * @var int
     */
    const MainCase = 1;
    
    /**
     * The group id of current logistics
     * @var int
     */
    public $lastGroupNum = 0; //记录当前选择的运输方式,的分组序号
    /**
     * The remark of current logistics
     * @var string
     */
    private $currentRemark = ''; //记录当前选择的运输方式的备注
    
    private $groupCode = '';//记录物流分区编号
    
    private $beforePricePlan = array();//记录计价方案快照数据
    
    /**
     * get the best logistics by info
     * @param array $param = array(
     'weight'      		//weight data(required)
     'country'    		//the destination country
     'ship_code'			//logistics code
     'discount'			//discount(default value:1)
     'attributeid'		//the attributes ids of package
     'volume'			//volume(default value:0)
     'warehouse'			//the warehouse where the package ship out
     'exclude_ship_code'	//the logistics can not be return
     'include_disable'   //是否包含已经禁用的物流方式(为了解决物流禁用了，但之前的订单还没完结的情况)
     'before_price_plan'	//是否使用先前计价方案[解决回传时算不出运费，从先前快照中计算 7.27 add]
     'platform_code'		//来源平台
     'check_attribute'   //是否检查属性，默认为需要检查
     'is_quota'			//是否开启配额
     )
     */
    public function getMinShippingCost($weight, $param=array()){
    	extract($param);
    	if( !isset($exclude_ship_code) ) $exclude_ship_code = array();
    	if( !isset($warehouse) ) $warehouse = '';
    	if( !isset($discount) ) $discount = 1;
    	if( !isset($volume) ) $volume = 0;
    	if( !isset($country) ) $country = '';
    	if( !isset($ship_code) ) $ship_code = '';
    	if( !isset($attributeid) ) $attributeid = array();
    	if( !isset($ship_class) ) $ship_class = $this->ship_company_logistics;
    	if( !isset($include_disable) ) $include_disable = false;
    	if( !isset($before_price_plan) ) $before_price_plan = false;
    	if( !isset($platform_code) ) $platform_code = '';
    	if( !isset($check_attribute) ) $check_attribute = true;
    	if( !isset($is_quota) ) $is_quota = true;      //默认开启
    	static $paras = array();
    
    	$key = $ship_code.(isset($warehouse) ? $warehouse : '').$country.$platform_code;//Request Key
    	if(!isset($paras[$key])){
    		if(isset($ship_code) && $ship_code){
    			$where = '';
    			//Rex修复Bug 15.10.14
    			$isLike = '=';
    			$markPre = '';
    			if (in_array($ship_code, array(self::CODE_CM,self::CODE_GHXB,self::CODE_EXPRESS,self::CODE_EUB))) {
    				$isLike = 'LIKE';
    				$markPre = '%';
    			}
    			if( isset($warehouse) && $warehouse ){
    				$where = ' AND FIND_IN_SET("'.$warehouse.'",ship_warehouse)';
    			}
    			if($include_disable){
    				$logisticsArr = $this->findAll(
    						'ship_class = :ship_class AND ship_code LIKE :ship_code'.$where,
    						array(':ship_class' => $ship_class,':ship_code' => $ship_code.$markPre)
    				);
    
    			}else{
    				$logisticsArr = $this->findAll(
    						'use_status = :use_status AND ship_class = :ship_class AND ship_code '.$isLike.' :ship_code'.$where,
    						array(':ship_class' => $ship_class,':use_status' => $this->onUse,':ship_code' => $ship_code.$markPre)
    				);
    			}
    
    			if(isset($logisticsArr)){
    				$paras[$key] = array();
    				foreach($logisticsArr as $logistics){
    					$paras[$key][$logistics['id']] = $logistics;
    				}
    			}else{
    				$paras[$key] = array();
    			}
    		}else{
    			$this->getLogisticsArr();
    			$paras[$key] = $this->logisticsArr;
    		}
    	}
    	 
    	$tmpParam = array(
    			'min_cost' 				=> 0,
    			'select_type'			=> '',
    			'select_id'				=> '',
    			'cal_weight'			=> $weight,
    			'max_rank'				=> 0,
    			'temp_last_group_num'	=> 0,
    			'temp_current_remark'	=> '',
    	);
    	$tempWeight = 0;
    
    	$msgQuota = '';
    	foreach ($paras[$key] as $logistics){
    
    		//增加平台限制判断
    		if (!empty($logistics['set_platform'])) {
    			$setPlatform = explode(',', $logistics['set_platform']);
    			if (!in_array($platform_code, $setPlatform)) {
    				continue;	//排除掉
    			}
    		}
    
    		//     		var_dump($logistics);
    		if($ship_class == $this->ship_dropshipping_logistics){
    
    		}else{
    			if( !$logistics['ship_warehouse'] ){
    				continue;
    			}elseif( $warehouse && !in_array($warehouse,explode(",",$logistics['ship_warehouse'])) ){
    				continue;
    			}
    		}
    		if(in_array($logistics['ship_code'],$exclude_ship_code)){//If the logistics not include
    			continue;
    		}
    
    		//增加配额检查
    		$curPkCount = 0;
    		if ($is_quota) {
    			$isShipQuota = isset($logistics['is_ship_quota']) ? $logistics['is_ship_quota'] : 0;
    			if ($isShipQuota == Logistics::SHIP_QUOTA_YES && $logistics['quota_max'] > 0) {
    				$curPkCount = OrderPackage::model()->getPkCountByShipCode($logistics['ship_code']);
    				if ($curPkCount >= $logistics['quota_max']) {
    					$msgQuota .= $logistics['ship_code'].' Give UP!'.'<br/>';
    					continue;
    				}
    			}
    		}
    
    		$paramArr = array(
    				'ship_country'		=> $country,
    				'discount'			=> $discount,
    				'volume'			=> $volume,
    				'attributeid'		=> array_unique($attributeid),
    				'include_disable'   => $include_disable,
    				'check_attribute'   => $check_attribute,
    				'before_price_plan' => $before_price_plan,
    		);
    
    		$tempCost = $this->getShipFeeById($logistics['id'], $weight, $tempWeight, $paramArr);
    		$tempRank = $logistics['rank'];
    		$select = false;
    		if($tempCost != 0){//If culculate correct
    			if($tmpParam['select_id'] == '' || $tmpParam['min_cost'] == -1){//If it's empty for the first time
    				$select = true;
    			}elseif($tempRank >= $tmpParam['max_rank'] && $tempCost != -1){//If the shipping cost less than last time
    				if($tempRank == $tmpParam['max_rank']){//If the rank is same
    					if($tempCost <= $tmpParam['min_cost']){
    						$select = true;
    					}
    				}else{
    					$select = true;
    				}
    			}
    		}
    
    		if($select){
    			$tmpParam['min_cost'] 				= $tempCost;
    			$tmpParam['max_rank'] 				= $tempRank;
    			$tmpParam['cal_weight'] 			= $tempWeight;
    			$tmpParam['select_type'] 			= $logistics['ship_code'];
    			$tmpParam['select_id'] 				= $logistics['id'];
    			$tmpParam['temp_last_group_num'] 	= $this->lastGroupNum;
    			$tmpParam['temp_current_remark'] 	= $this->currentRemark;
    			$tmpParam['temp_ship_area']			= $this->groupCode;
    			$tmpParam['is_ship_quota']			= $logistics['is_ship_quota'];
    			$tmpParam['quota_max']				= $logistics['quota_max'];
    		}else{
    			$this->lastGroupNum 				= $tmpParam['temp_last_group_num'];
    			$this->currentRemark 				= $tmpParam['temp_current_remark'];
    			$this->groupCode					= isset($tmpParam['temp_ship_area']) ? $tmpParam['temp_ship_area'] : '';
    		}
    	}
    	!isset($tmpParam['is_ship_quota']) && $tmpParam['is_ship_quota'] = 0;
    	!isset($tmpParam['quota_max']) && $tmpParam['quota_max'] = 0;
    	return array(
    			'ship_cost' 	=> $tmpParam['min_cost'],
    			'ship_code' 	=> $tmpParam['select_type'],
    			'ship_id' 		=> $tmpParam['select_id'],
    			'cal_weight' 	=> $tmpParam['cal_weight'],
    			'last_group_num' => $this->lastGroupNum,
    			'ship_area'		=> $this->groupCode,
    			'current_remark' => $this->currentRemark,
    			'is_ship_quota'	=> $tmpParam['is_ship_quota'],
    			'quota_max'		=> $tmpParam['quota_max'],
    			'cur_count'		=> ($tmpParam['is_ship_quota'] == self::SHIP_QUOTA_YES) ? OrderPackage::model()->getPkCountByShipCode($tmpParam['select_type']) : 0,
    			'msg_quota'		=> $msgQuota,
    	);
    }
    
    
    
    /**
     * Get ship cost by logistics id
     * @param array $param = array(
     'logistics_id'      		//logistics id(required)
     'weight'    				//weight
     'ship_country'				//the destination of the country
     'discount'					//discount(default value:1)
     'volume'					//whether calculate with volume weight information(default value:0)
     'ship_group'				//the group id of the pricing solution
     'attributeid'				//the attribute id
     'include_disable'          //include_disable
     ‘check_attribute'          //check_attribute
     'before_price_plan'		//before_price_plan
     )
     * @param float $cal_weight
     */
    public function getShipFeeById($logistics_id, $weight, &$calWeight = '', $param = array()){
    	static $paramShipCountry = array();
    	static $paramShipSpecial = array();
    	static $paramShipSheet = array();
    	extract($param);
    	 
    	if( !isset($discount) ){ $discount = 1; }
    	if( !isset($volume) ){ $volume = 0; }
    	if( !isset($weight) ){ $weight = 0; }
    	if( !isset($include_disable) ) $include_disable = false;
    	if( !isset($before_price_plan) ){ $before_price_plan = false; }
    	if( !isset($check_attribute) ) $check_attribute = true;
    	if( !$logistics_id || $weight < 1){
    		return false;
    	}
    
    	if ($include_disable) {
    		$this->getLogisticsArr2($logistics_id);
    	} else {
    		$this->getLogisticsArr();
    	}
    	$logisticsInfo = $this->logisticsArr[$logistics_id];
    	//@todo
    	/** If the volume weight is heavier than real weight,calculate the shipping cost by volume weight*/
    	if($logisticsInfo['is_volume_weight']==1 && floatval($volume) > floatval($logisticsInfo['volume_weight_rate'])*$weight){
    		$weight = floatval($volume)/floatval($logisticsInfo['volume_weight_rate']);
    	}
    
    	$shipCost = 0; /** Shipping Cost */
    	$calWeight = $weight; /** Calculate weight(written on package)  */
    	 
    	//判断是否需人快照中取计价方案数据
    	if ($before_price_plan === true && !empty($this->beforePricePlan)) {
    		$paramShipSheet[$logistics_id] = $this->beforePricePlan;
    	}else {
    		if(!isset($paramShipSheet[$logistics_id])){
    			/** Pricing Solution */
    			$paramShipSheet[$logistics_id] = LogisticsPricingSolution::model()->getPricingSolutionByLogisticsId($logistics_id,array('order' => 'ship_step_price'));
    		}
    	}
    
    	$logisticsInfo['attrbutes'] = array();
    	$logisticsAttribute = LogisticsAttribute::model()->getAttributeByLogisticsId($logistics_id);/** Get the logistics attribute*/
    	if( !empty($logisticsAttribute) ){
    		foreach( $logisticsAttribute as $attribute ){
    			$logisticsInfo['attrbutes'][$attribute['id']] = $attribute;
    		}
    	}
    	$this->lastGroupNum = 0;
    	if(isset($ship_country)){
    		if( !isset($paramShipCountry[$ship_country]) ){
    			//$paramShipCountry[$ship_country] = UebModel::model('country')->getCountryCnameByEname($ship_country);
    			$countryCname = Country::model()->getCountryCnameByEname($ship_country);
    			$paramShipCountry[$ship_country] = empty($countryCname) ? $ship_country : $countryCname;
    		}
    	}
    
    	foreach ($paramShipSheet[$logistics_id] as $key=>$value){
    		$this->lastGroupNum++;
    		$this->currentRemark = $value['ship_remark'];//运送计价方案备注
    		$this->groupCode = $value['ship_area'];//方案分区
    
    		if( isset($ship_country) ){
    
    			if( empty($ship_country) && $value['ship_group'] != self::MainCase ){
    				continue;
    			}
    
    			//俄罗斯处理	tan 11.25 add
    			if ($paramShipCountry[$ship_country] == '俄罗斯') {
    				$value['ship_include_countries'] = str_replace('白俄罗斯', '', $value['ship_include_countries']);
    			}
    			if ($paramShipCountry[$ship_country] == '印度') {
    				$value['ship_include_countries'] = str_replace('印度尼西亚', '', $value['ship_include_countries']);
    			}
    
    			if( $ship_country ){
    				if( $value['ship_exclude_countries'] != '' &&  strpos($value['ship_exclude_countries'], $paramShipCountry[$ship_country]) !== false ){
    					continue;
    				}
    				if( $value['ship_include_countries'] != '' && strpos($value['ship_include_countries'], $paramShipCountry[$ship_country]) === false ){
    					continue;
    				}
    			}
    
    		}else{
    			if( $value['ship_group'] != self::MainCase ){
    				continue;
    			}
    		}
    
    		/** Check the pricing solution if it is the same as the specific value */
    		if( isset($ship_group) ){
    			if( $value['ship_group'] != $ship_group ){
    				continue;
    			}
    		}
    
    		/** Check the attribute */
    		if ($check_attribute) {
    			$attributeid = array_filter($attributeid);
    			if( isset($attributeid) && count($attributeid) > 0 ){
    				if( isset($logisticsInfo['attrbutes'][$value['ship_attribute']]) ){//Check the attribute whether the logistics support
    					if( !empty($logisticsInfo['attrbutes'][$value['ship_attribute']]['exclude_attribute_id']) ){
    						$excludeAttributeids = array_unique( explode(',', $logisticsInfo['attrbutes'][$value['ship_attribute']]['exclude_attribute_id']) );
    						$diffExcludeAttributeids = array_diff($excludeAttributeids, $attributeid);
    						if( count($diffExcludeAttributeids) < count($excludeAttributeids) ){
    							continue;
    						}
    					}
    					if( !empty($logisticsInfo['attrbutes'][$value['ship_attribute']]['include_attribute_id']) ){
    						$includeAttributeids = array_unique( explode(',', $logisticsInfo['attrbutes'][$value['ship_attribute']]['include_attribute_id']) );
    						$diffIncludeAttributeids = array_diff($attributeid, $includeAttributeids);
    						if( count($diffIncludeAttributeids) > 0 ){
    							continue;
    						}
    					}
    				}
    			}else {
    				if (!empty($logisticsInfo['attrbutes'][$value['ship_attribute']]['include_attribute_id']) ) {
    					continue;
    				}
    			}
    		}
    
    		$finalWeight = $calWeight = $this->getRelativeWeight($weight, $value);/** Get the relative weight*/
    		if( $value['ship_max_weight'] > 0 && $calWeight > $value['ship_max_weight'] ){ /** Check the weight if it is more than the max weight of the pricing weight */
    			continue;
    		}
    		/** First weight */
    		$firstWeight = intval( $value['ship_first_weight'] );
    		if( strpos(',',$value['ship_min_weight']) ){/** If has the min weight range */
    
    		}else{
    			if( $value['ship_min_weight'] > 0 && $value['ship_min_weight'] > $finalWeight ){
    				continue;
    			}
    		}
    		$shipCost = 0;
    		if( isset($rule[$logisticsInfo['ship_code']][$key]) ){/** Special rules,such as eub */
    			 
    			$shipCost = $this->getShipFeeByRule($finalWeight,$rule[$logisticsInfo['ship_code']][$key]);
    		}else{
    			 
    			/** First weight price */
    			$firstCost = floatval($value['ship_first_price']);
    			 
    			if( !empty($value['ship_step']) && strpos(',',$value['ship_step']) ){//如果有多个区间规则
    				//例快递的复杂算法
    					
    			}else{//单个区间规则
    				//小于首重   运送价格 = 首重价格
    
    				if($finalWeight < $firstWeight){
    					$shipCost = $firstCost;
    				}else{ //大于首重   运送价格 = 首重价 + 区间价.
    					$shipCost = $firstCost;
    					$stepWeight = $finalWeight - $firstWeight; //将进行区间价计算的重量
    					//lihy add
    					if($value['ship_step'] == 0) $value['ship_step'] = $stepWeight;
    					$shipCost += ceil( $stepWeight / intval($value['ship_step'])) * floatval($value['ship_step_price'] );
    				}
    			}
    		}
    
    		if( isset($discount) && $discount && $value['ship_discount'] > 0 ){
    			 
    			$shipCost = $shipCost * ( $value['ship_discount'] / 10 );
    		}
    
    		if( $value['ship_add_price'] > 0 ){//是否有挂号费
    			$discount && $value['ship_add_price_discount'] > 0 && $value['ship_add_price'] *= $value['ship_add_price_discount'] / 10;//如果有折扣,算出折后挂号费
    			$shipCost += $value['ship_add_price']; 	//加上挂号费后的运费成本.
    		}
    
    		//根据设置的汇率换算成RMB
    		if( trim($value['ship_currency']) != 'CNY' ){
    			$shipCost = $shipCost * floatval( CurrencyRate::model()->getRateByCondition(trim($value['ship_currency']), 'CNY') );
    		}
    
    		//有计价方案分组号为分组号
    		if ($value['ship_group'] > 0) {
    			$this->lastGroupNum = $value['ship_group'];
    		}
    		break;
    	}
    
    	if(empty($paramShipSheet[$logistics_id])){
    		$shipCost = -1;/** Can not calculate the shipping cost */
    	}
    	 
    	return round(floatval($shipCost),2);
    }
    
    /**
     * Get all able logistics
     */
    public function getLogisticsArr(){
    	if(empty($this->logisticsArr)){
    		//$logisticsArr = $this->findAllByAttributes(array('use_status' => $this->onUse));
    		$logisticsArr =$this->getDbConnection()->createCommand()
    		->select('*')
    		->from(self::tableName())
    		->where("use_status = :use_status and ship_class = :ship_class",array(':use_status' => $this->onUse,':ship_class' => $this->ship_company_logistics))
    		->order('ship_type')
    		->queryAll();
    		foreach($logisticsArr as $logistics){
    			$this->logisticsArr[$logistics['id']] = $logistics;
    		}
    	}
    	return $this->logisticsArr;
    }
    
    /**
     * 含停用
     */
    public function getLogisticsArr2($logistcsId){
    	if(!isset($this->logisticsArr[$logistcsId])){
    		//$logisticsArr = $this->findAllByAttributes(array('use_status' => $this->onUse));
    		$logisticsArr =$this->getDbConnection()->createCommand()
    		->select('*')
    		->from(self::tableName())
    		->where("ship_class = :ship_class",array(':ship_class' => $this->ship_company_logistics))
    		->order('ship_type')
    		->queryAll();
    
    
    
    		foreach($logisticsArr as $logistics){
    			$this->logisticsArr[$logistics['id']] = $logistics;
    		}
    	}
    	return $this->logisticsArr;
    }
    
    /**
     * get the relative weight by weight and logistics infomation(reduce diff weight)
     * @param float $weight
     * @param weight $ship_type_sheet
     * @return float
     */
    public function getRelativeWeight($weight,$logisticsPricingSolution){
    	if(floatval($logisticsPricingSolution['ship_weight_diff'] > 0)){
    		$weight_diff = ($weight-$logisticsPricingSolution['ship_first_weight']) % intval($logisticsPricingSolution['ship_step']);
    		if($weight_diff <= floatval($logisticsPricingSolution['ship_weight_diff'])){//diff weight less than allowed
    			$weight -= $weight_diff;
    		}
    	}
    	return $weight;
    }
    
    
    /**
     * Get the cost base on specific rule
     * @param float $weight
     * @param array $rule
     * @return float
     */
    public function getShipFeeByRule($weight,$rule){
    	$shipFee = 0;
    	$shipRule = $rule['rule'];				//计算规则
    	$pricision= $rule['weightPricision'];	//重量精度:1 / 0.1 / 0.01 ?
    
    	foreach ($shipRule as $option) {
    		if($option['start_weight'] <= $weight && ($option['end_weight'] >= $weight || $option['end_weight'] == 0)){
    			$temp_weight = $weight - $option['start_weight'] + $pricision;  //在此规则下要算的重量
    			if($weight==$option['end_weight'] && isset($option['special_rate'])){//如果刚好等于分段的重量.则使用此分段重量的费用来计算
    				$shipFee = $temp_weight * $option['special_rate'];
    			}
    			else{
    				$shipFee = $temp_weight * $option['final_rate'];			//计算此重量段的运费
    			}
    
    			$leave_weight = $weight - $temp_weight;							//此规则剩下的重量
    			if($leave_weight > 0){											//如果还有剩余，则继续按此规则算运费
    				$shipFee += $this->getShipFeeByRule($leave_weight,$rule);
    			}
    			break;
    		}
    	}
    	return $shipFee;
    }
}