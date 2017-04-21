<?php
/**
 * @desc 订单包裹Model
 * @author Gordon
 */
class OrderPackage extends OrdersModel {

    const SHIP_STATUS_DEFAULT   = 0;//未出货
    const SHIP_STATUS_END       = 1;//已出货
	const SHIP_STATUS_PENGDING  = 2;//待处理
	const SHIP_STATUS_KF        = 3;//客服处理
	const SHIP_STATUS_REPLENISH = 4;//等待补货
	const SHIP_STATUS_CANCEL    = 5;//取消
	
	const UPLOAD_SHIP_YES       = 1;//上传平台sellingId 是
	const UPLOAD_SHIP_NO        = 0;//上传平台sellingId 否
	
	const UPLOAD_TRACK_NO		= 0;//物流商包裹交运标记 否
	const UPLOAD_TRACK_YES		= 1;//物流商包裹交运标记 是
	
	const UPLOAD_SHIP_ERR		= 3;//自动生成跟踪号异常
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order_package';
    }
    
    /**
     * @desc 根据订单号得到包裹信息
     * @param string $orderID
     */
    public function getPackageInfoByOrderID($orderID){
        return $this->dbConnection->createCommand()
                    ->select('op.*')
                    ->from(self::tableName().' AS op')
                    ->leftJoin(OrderPackageDetail::model()->tableName().' AS d', 'd.package_id = op.package_id')
                    ->where('op.ship_status != '.self::SHIP_STATUS_CANCEL)
                    ->andWhere('d.order_id = "'.$orderID.'"')
                    ->queryAll();
    }
    
    /**
     * @desc 根据包裹号获取包裹信息
     * @param string $packageID
     */
    public function getPackageInfoByPackageID($packageID){
        return $this->dbConnection->createCommand()->select('*')->from(self::tableName())->where('package_id = "'.$packageID.'"')->queryRow();
    }

    /**
     * 根据跟踪号获取包裹信息
     * @param   string $trackNum
     */
    public function getPackageInfoByTrackNum($trackNum) {
        return $this->dbConnection->createCommand()->select('*')->from(self::tableName())->where('track_num = "'.$trackNum.'" and ship_status != 5')->queryRow();
    }
    
    /**
     * 获取ebay非eub包裹，待上传跟踪号的
     * @param string $limit string $consignDate string $packageId
     */
    public function getEbayWaitingUploadPackages($consignDate = null,$packageId = null,$limit = null) {
    	$dbCommand = $this->dbConnection->createCommand()
    		->select('t.package_id,t.ship_code,t.real_ship_type,t.track_num,t.ship_country_name,d.order_detail_id,d.order_id')
    		->from(self::tableName() . " t")
    		->join(OrderPackageDetail::model()->tableName().' as d','t.package_id=d.package_id')
    		->where('t.is_confirm_shiped = 0')
    		->andWhere('t.real_ship_type not like "eub%"')
    		->andWhere('t.track_num != ""')
    		->andWhere('t.consign_date >= "'.$consignDate.'" and t.consign_date <= "2016-09-03 00:00:00"')
    		->andWhere('t.platform_code = :platform_code', array(':platform_code' => Platform::CODE_EBAY))
    		->andWhere('t.real_ship_type like "ghxb_%" or t.real_ship_type like "usps_%" ')
    		->andWhere('t.create_time >= "2015-11-20 00:00:00"')
    		->order('t.consign_date asc');
    	
    	if( $packageId ){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	//echo $dbCommand->text;
    	return $dbCommand->queryAll();
    }
    
    /**
     * 获取ebay非eub包裹非平邮小包，待上传跟踪号的 
     * @param string $limit string $consignDate string $packageId
     */
    public function getEbayWaitingUploadPackages1( $pkCreateDate = null,$packageId = null,$limit = null,$offset = null ) {
    	//获取近15天有跟踪号，未上传跟踪号的包裹。
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("t.package_id,t.track_num,t.track_num2,t.real_ship_type,t.ship_code,d.order_detail_id,d.order_id")
			    	->from(self::tableName() . " t")
			    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
			    	->where("t.is_confirm_shiped in(0)")
			    	->andWhere("t.track_num != '' and t.ship_status=1")
			    	->andWhere("t.ship_code in('cm_az_ltzx','cm_jna_ltzx','cm_bls_dsfzx','cm_dgzx_ysd','cm_fg_dsfzx','cm_pty_dsfzx','cm_xby_dsfzx','cm_xbyzx_ysd','cm_xxl_ltzx','cm_ydl_dsfzx','cm_gati_su_mgzx','cm_dsf_ygzx','cm_azbc_ltzx','cm_xgxb_wyt','cm_yw_teqxb','cm_4px_met','cm_hnxb_yt','cm_ztxn_uspsmddb','cm_ztxn_uspsxb','cm_ztxn_fedexmddb','cm_ztxn_fedexmdxb') or t.ship_code not like 'cm_%'")
			    	->andWhere('t.real_ship_type not like "eub%"')
			    	->andWhere('t.is_repeat = 0')
			    	->andWhere('t.create_time >= "'.$pkCreateDate.'"')
			    	->andWhere("t.platform_code = '".Platform::CODE_EBAY."'")
			    	->andWhere('t.modify_time >= "2016-08-20 00:00:00"')
			    	->order("t.package_id asc");
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	/* if( $packageId ){
    		$dbCommand->andWhere('t.package_id in('.MHelper::simplode($packageId).')');
    	} */
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, $offset);
    	if(isset($_REQUEST['bug'])){
    		echo $dbCommand->text;
    	}
    	return $dbCommand->queryAll();
    	
    }
    
    /**
     * 获取Ebay平台待上传追踪号信息到平台的包裹 [欠货包裹上传预匹配的渠道]
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getEbayWaitingUploadSpecial( $pkCreateDate = null,$packageId = null,$limit = null,$offset = null ) {
    	//获取近15天有预匹配跟踪号，但未上传跟踪号的包裹。
    	$dbCommand = $this->dbConnection->createCommand()
		    	->select('t.package_id,(case when m.track_num="" then t.track_num else m.track_num end) as track_num,(case when m.track_num="" then t.ship_code else m.ship_code end) as ship_code,(case when m.track_num="" then t.ship_code else m.ship_code end) as real_ship_type,d.order_detail_id,d.order_id')
		    	->from(self::tableName() . " t")
		    	->join("ueb_order_package_qh_pre_track m", "t.package_id = m.package_id")
		    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
		    	->where("t.is_confirm_shiped in(0)")
		    	->andWhere("t.ship_status != ".self::SHIP_STATUS_END)
		    	->andWhere("m.confirm_shiped_status=0")
		    	->andWhere('m.track_num !=""')
		    	->andWhere('t.create_time >= "'.$pkCreateDate.'" and t.is_repeat = 0 and t.ship_code like "cm_%"')
		    	->andWhere("t.platform_code = '".Platform::CODE_EBAY."'")
		    	->andWhere('t.modify_time >= "2016-08-01 00:00:00"')
		    	->order("t.modify_time asc")
		    	->group('d.order_id');
    
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	/* if( count($packageId)>0 && $packageId ){
    	 $dbCommand->andWhere('t.package_id in('.MHelper::simplode($packageId).')');
    	} */
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, $offset);
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }
    
    /**
     * 获取Ebay平台待上传追踪号信息到平台的包裹的个数
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getEbayWaitingUploadPackagesCount( $pkCreateDate = null,$packageId = null ) {
    	//获取近15天有跟踪号，未上传跟踪号的包裹。
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("count(t.package_id) AS total")
			    	->from(self::tableName() . " t")
			    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
			    	->where("t.is_confirm_shiped in(0)")
			    	->andWhere("t.track_num != '' and t.is_repeat = 0 and t.ship_status=1")
			    	->andWhere("t.ship_code in('cm_az_ltzx','cm_jna_ltzx','cm_bls_dsfzx','cm_dgzx_ysd','cm_fg_dsfzx','cm_pty_dsfzx','cm_xby_dsfzx','cm_xbyzx_ysd','cm_xxl_ltzx','cm_ydl_dsfzx','cm_gb_sfoz') or t.ship_code not like 'cm_%'")
			    	->andWhere('t.real_ship_type not like "eub%"')
			    	->andWhere('t.create_time >= "'.$pkCreateDate.'"')
			    	->andWhere("t.platform_code = '".Platform::CODE_EBAY."'")
			    	->andWhere('t.modify_time >= "2016-08-01 00:00:00"');
    
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	/* if( $packageId ){
    	 $dbCommand->andWhere('t.package_id in('.MHelper::simplode($packageId).')');
    	} */
    
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryRow();
    }
    
    /**
     * 获取Wish平台待上传追踪号信息到平台的包裹
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getWishWaitingUploadPackages( $pkCreateDate = null,$packageId = null,$limit = null ) {
    	//获取近15天有跟踪号，未上传跟踪号的包裹。
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("t.package_id,t.track_num,t.real_ship_type,t.ship_code, d.order_id")
			    	->from(self::tableName() . " t")
			    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
			    	//->join("ueb_order o","o.order_id = d.order_id")
			    	->where("t.is_confirm_shiped in(0)")
			    	->andWhere("(t.track_num != '' and (t.ship_status = 1 or t.ship_code not like 'cm_%') ) or (t.ship_status=".self::SHIP_STATUS_END." and t.track_num = '')")
			    	//->andWhere("t.ship_status=".self::SHIP_STATUS_END)
			    	->andWhere('t.create_time >= "'.$pkCreateDate.'" and t.is_repeat = 0')
			    	->andWhere("t.platform_code = '".Platform::CODE_WISH."'")
			    	->andWhere('t.modify_time >= "2015-12-31 00:00:00"')
			    	//->andWhere("o.account_id=17")
			    	->order("t.modify_time asc")
		    		->group('d.order_id');
    	
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	/* if( count($packageId)>0 && $packageId ){
    		$dbCommand->andWhere('t.package_id in('.MHelper::simplode($packageId).')');
    	} */
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }
    
    /**
     * 获取Wish平台待上传追踪号信息到平台的包裹 [欠货包裹上传预匹配的渠道]
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getWishWaitingUploadSpecial( $pkCreateDate = null,$packageId = null,$limit = null ) {
    	//获取近15天有预匹配跟踪号，但未上传跟踪号的包裹。
    	$dbCommand = $this->dbConnection->createCommand()
		    	->select('t.package_id,(case when m.track_num="" then t.track_num else m.track_num end) as track_num,(case when m.track_num="" then t.ship_code else m.ship_code end) as ship_code,(case when m.track_num="" then t.ship_code else m.ship_code end) as real_ship_type,d.order_id')
    			//->select("t.package_id,t.track_num as pk_track_num,t.ship_code as pk_ship_code,t.real_ship_type as pk_real_ship_type,m.track_num,m.ship_code as real_ship_type,m.ship_code, d.order_id")
		    	->from(self::tableName() . " t")
		    	->join("ueb_order_package_qh_pre_track m", "t.package_id = m.package_id")
		    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
		    	->where("t.is_confirm_shiped in(0)")
		    	->andWhere("t.ship_status != ".self::SHIP_STATUS_END)
		    	->andWhere("m.confirm_shiped_status=0")
		    	->andWhere('t.create_time >= "'.$pkCreateDate.'" and t.is_repeat = 0')
		    	->andWhere("t.platform_code = '".Platform::CODE_WISH."'")
		    	->andWhere('t.modify_time >= "2015-12-31 00:00:00"')
		    	->order("t.modify_time asc")
		    	->group('d.order_id');
    	 
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	/* if( count($packageId)>0 && $packageId ){
    	 $dbCommand->andWhere('t.package_id in('.MHelper::simplode($packageId).')');
    	} */
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }
    
    // ========================= S: WISH 获取未上传订单包裹 ====================== //
  
	public function getWishQhPreTrackPackage($packageId){
		if(!is_array($packageId))
			$packageId = array($packageId);
		$qhPretrackPackageList = $this->getDbConnection()->createCommand()
								->from("ueb_order_package_qh_pre_track")
								->where(array('IN', 'package_id', $packageId))
								->andWhere("track_num!=''")
								->queryAll();
		return $qhPretrackPackageList;
	}
	
    /**
     * @DESC 获取备货中的未上传平台的包裹订单号
     * @param string $pkCreateDate
     * @param string $packageId
     * @param string $limit
     * @param number $type
     * @return Ambigous <multitype:, mixed>
     */
    public function getWishUnshippingOrderPackage($pkCreateDate = null, $orderIDs = array(), $limit = null, $type = 1) {
    		//type 1 、2
    		//1、取出所有处于备货中订单并且物流方式除了（DHL小包：香港A2B德邮英国小包、香港A2B泽西邮局小包、A2B香港DHL小包 HongKong小包：深圳京华达香港小包）之外的订单包裹追踪号，直接上传
    		//2、取出含备货物流方式 DHL小包：香港A2B德邮英国小包、香港A2B泽西邮局小包、A2B香港DHL小包 ，HongKong小包：深圳京华达香港小包;
    		$filterShipCodeCondition = "";
    		$filterShipCode = array(
    				Logistics::CODE_CM_ZXYZ,
    				Logistics::CODE_CM_DEYZ,
    				Logistics::CODE_CM_DHL,
    				Logistics::CODE_CM_HK
    		);
    		if($type == 1){
    			$filterShipCodeCondition = " AND t.ship_code not in(". MHelper::simplode($filterShipCode) .") ";
    		}elseif($type == 2){
    			$filterShipCodeCondition = " AND t.ship_code in(". MHelper::simplode($filterShipCode) .") ";
    		}
    		$dbCommand = $this->dbConnection->createCommand()
				    		->select("t.package_id,t.track_num,t.real_ship_type,t.ship_code, d.order_id")
				    		->from(self::tableName() . " t")
				    		->join("ueb_order_package_detail d", "t.package_id = d.package_id")
				    		->where("t.is_confirm_shiped in(0)")
				    		->andWhere($filterShipCodeCondition)
				    		->andWhere('t.create_time >= "'.$pkCreateDate.'" and t.is_repeat = 0')
				    		->andWhere("t.platform_code = '".Platform::CODE_WISH."'")
				    		->andWhere('t.modify_time >= "2015-12-31 00:00:00"')
				    		->order("t.modify_time asc")
				    		->group('d.order_id');
    		 
    		if(!empty($orderIDs)){
    			$dbCommand->andWhere(array('IN', 'd.order_id', $orderIDs));
    		}
    		if (!empty($limit))
    			$dbCommand->limit((int)$limit, 0);
    		return $dbCommand->queryAll();
    }

    /**
     * 获取是否有预匹配渠道
     * @param  string   $packageId
     * @param  array
     */
    public function getPrePackageInfoByPackageId($packageId) {
        if (empty($packageId)) {
            return false;
        }
        $obj = $this->dbConnection->createCommand()
                ->select('*')
                ->from('ueb_order_package_qh_pre_track')
                ->where("package_id = '{$packageId}' and ship_code = 'cm_zx_syb' and track_num != '' ");
        return $obj->queryRow();
    }

    /**
     * 获取是否有预匹配渠道
     * @param  string   $orderId
     * @param  array
     */
    public function getPrePackageInfoByOrderId($orderId) {
        if (empty($orderId)) {
            return false;
        }
        $obj = $this->dbConnection->createCommand()
                ->select('PQ.*')
                ->from('ueb_order_detail OD')
                ->leftJoin('ueb_order_package_detail PD', 'PD.order_detail_id = OD.id')
                ->leftJoin('ueb_order_package P', 'P.package_id = PD.package_id')
                ->leftJoin('ueb_order_package_qh_pre_track PQ', 'PQ.package_id = P.package_id')
                ->where("OD.order_id = '{$orderId}' and PQ.ship_code = 'cm_zx_syb' and PQ.track_num != '' ");
        return $obj->queryRow();
    }
    
    /**
     * 获取Ali平台待上传追踪号信息到平台的包裹
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getAliWaitingUploadPackages( $pkCreateDate = null,$packageId = null,$limit = null,$offset = null ) {
    	//获取近15天有跟踪号，未上传跟踪号的包裹。
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("t.package_id,t.track_num,t.track_num2,t.real_ship_type,t.ship_code, d.order_id")
			    	->from(self::tableName() . " t")
			    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
			    	//->join("ueb_order o","o.order_id = d.order_id")
                    ->where("t.platform_code = '".Platform::CODE_ALIEXPRESS."'")
                    ->andWhere('t.ship_date >= "'.$pkCreateDate.'"')
			    	->andWhere("t.is_confirm_shiped = 0")
			    	->andWhere("t.ship_status=1 and track_num not like 'PK%' ")
			    	->andWhere('t.is_repeat = 0')
			    	//->andWhere('t.modify_time >= "2016-04-15 00:00:00"')
			    	//->andWhere("o.account_id in('1976698990','1976769325','1976679767','1976699633','1976799293','1976519210','1976879136')")
			    	->order("t.ship_date asc")
			    	->group('d.order_id');

    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	/* if( $packageId ){
    		$dbCommand->andWhere('t.package_id in('.MHelper::simplode($packageId).')');
    	} */
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, $offset);
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }
    
    /**
     * 获取Wish平台待上传追踪号信息到平台的包裹 [欠货包裹上传预匹配的渠道]
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getAliWaitingUploadSpecial( $pkCreateDate = null,$packageId = null,$limit = null,$offset = null ) {
    	//获取近15天有预匹配跟踪号，但未上传跟踪号的包裹。
    	$dbCommand = $this->dbConnection->createCommand()
    				->select('t.package_id,(case when m.track_num="" then t.track_num else m.track_num end) as track_num,(case when m.track_num="" then t.track_num2 else m.track_num end) as track_num2,(case when m.track_num="" then t.ship_code else m.ship_code end) as ship_code,(case when m.track_num="" then t.ship_code else m.ship_code end) as real_ship_type,d.order_id')
			    	//->select("t.package_id,m.track_num,m.ship_code as real_ship_type,m.ship_code, d.order_id")
			    	->from(self::tableName() . " t")
			    	->join("ueb_order_package_qh_pre_track m", "t.package_id = m.package_id")
			    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
			    	->where("t.is_confirm_shiped in(0)")
			    	->andWhere("t.ship_status != ".self::SHIP_STATUS_END)
			    	->andWhere("m.confirm_shiped_status=0")
			    	->andWhere('t.create_time >= "'.$pkCreateDate.'" and t.is_repeat = 0 and t.ship_code != "cm_ali_dgyz"')
			    	->andWhere("t.platform_code = '".Platform::CODE_ALIEXPRESS."'")
			    	->andWhere('t.modify_time >= "2015-12-31 00:00:00"')
			    	->order("t.modify_time asc")
			    	->group('d.order_id');
    
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	/* if( count($packageId)>0 && $packageId ){
    	 $dbCommand->andWhere('t.package_id in('.MHelper::simplode($packageId).')');
    	} */
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, $offset);
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }
    
    /**
     * 获取Ali平台待上传追踪号信息到平台的包裹的个数
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getAliWaitingUploadPackagesCount( $pkCreateDate = null,$packageId = null ) {
    	//获取近15天有跟踪号，未上传跟踪号的包裹。
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("count(t.package_id) AS total")
			    	->from(self::tableName() . " t")
			    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
			    	->where("t.is_confirm_shiped in(0)")
			    	->andWhere("t.track_num != '' and t.is_repeat = 0")
			    	->andWhere('t.create_time >= "'.$pkCreateDate.'"')
			    	->andWhere("t.platform_code = '".Platform::CODE_ALIEXPRESS."'")
			    	->andWhere('t.modify_time >= "2016-04-15 00:00:00"');
    
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	/* if( $packageId ){
    	 $dbCommand->andWhere('t.package_id in('.MHelper::simplode($packageId).')');
    	} */
    
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryRow();
    }
    
    /**
     * 获取Ali平台待上传追踪号信息到平台的包裹的个数
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getAliWaitingUploadSpecialCount( $pkCreateDate = null,$packageId = null ) {
    	//获取近15天有跟踪号，未上传跟踪号的包裹。
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("count(t.package_id) AS total")
			    	->from(self::tableName() . " t")
			    	->join("ueb_order_package_qh_pre_track m", "t.package_id = m.package_id")
			    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
			    	->where("t.is_confirm_shiped in(0)")
			    	->andWhere("t.ship_status != ".self::SHIP_STATUS_END)
			    	->andWhere("m.confirm_shiped_status=0")
			    	->andWhere('t.create_time >= "'.$pkCreateDate.'" and t.is_repeat = 0 and t.ship_code != "cm_ali_dgyz"')
			    	->andWhere("t.platform_code = '".Platform::CODE_ALIEXPRESS."'")
			    	->andWhere('t.modify_time >= "2015-12-31 00:00:00"');
    
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	/* if( $packageId ){
    	 $dbCommand->andWhere('t.package_id in('.MHelper::simplode($packageId).')');
    	} */
    
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryRow();
    }
    
    /**
     * Wish根据订单号查有包裹未上传跟踪号的订单信息.
     * @param string $orderIds
     */
    public function getWishUnUploadTrackOrders( $orderIds = '' ) {
    	if(empty($orderIds)) return null;
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("d.order_id")
			    	->from(self::tableName() . " t")
			    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
			    	->where("t.is_confirm_shiped in(0)")
			    	->andWhere("d.order_id in(".$orderIds.")")
			    	->group('d.order_id');

    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryColumn();
    }
    
    /**
     * Ali根据订单号查有包裹未上传跟踪号的订单信息.
     * @param string $orderIds
     * @param string $excludeShipCode
     */
    public function getAliUnUploadTrackOrders( $orderIds = '',$excludeShipCode = '' ) {
    	if(empty($orderIds)) return null;
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("d.order_id")
			    	->from(self::tableName() . " t")
			    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
			    	->where("t.is_confirm_shiped in(0)")
			    	->andWhere("d.order_id in(".$orderIds.")")
			    	->group('d.order_id');
    	if( $excludeShipCode ){
    		$dbCommand->andWhere('t.ship_code not in('.$excludeShipCode.')');
    	}
    
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryColumn();
    }
    
    /**
     * Wish根据订单号查没有生成包裹的订单信息.
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getWishUnCreatePackageOrders( $orderIds = '' ) {
    	if(empty($orderIds)) return null;
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("o.order_id")
			    	->from(Order::model()->tableName() . " o")
			    	->leftJoin(OrderPackageDetail::model()->tableName().' pd', "o.order_id = pd.order_id")
			    	->where("o.order_id in(".$orderIds.")")
			    	->andWhere("pd.order_id is null")
			    	->group('o.order_id');
    
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryColumn();
    }
    
    /**
     * Ali根据订单号查没有生成包裹的订单信息.
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getAliUnCreatePackageOrders( $orderIds = '' ) {
    	if(empty($orderIds)) return null;
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("o.order_id")
			    	->from(Order::model()->tableName() . " o")
			    	->leftJoin(OrderPackageDetail::model()->tableName().' pd', "o.order_id = pd.order_id")
			    	->where("o.order_id in(".$orderIds.")")
			    	->andWhere("pd.order_id is null")
			    	->group('o.order_id');
    
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryColumn();
    }
    
    /**
     * 获取amazon待上传追踪号信息到平台的包裹 （获取近10天出货的、已出货、有跟踪号、包括已经确认发货过的。）
     * @param string $platformCode
     * @param string $limit
     */
    public function getAmazonWaitingUploadPackages($packageId,$limit = null, $accountID = null, $excludeAccount = array()) {
    	$dbCommand = $this->dbConnection->createCommand()
	    	->select("t.package_id,t.track_num,t.real_ship_type,t.ship_code, t1.order_id, t1.sku, t1.quantity,t1.order_detail_id")
	    	->from(self::tableName() . " t")
	    	->join("ueb_order_package_detail t1", "t.package_id = t1.package_id")
	    	->where("t.is_confirm_shiped in(0)")
	    	->andWhere("t.track_num != '' or (t.ship_status=".self::SHIP_STATUS_END." and t.track_num = '')")
	    	->andWhere("(t.consign_date >= '" . date('Y-m-d', strtotime('-3 days'))."') or (t.modify_time >= '" . date('Y-m-d', strtotime('-3 days'))."' and t.ship_code like 'eub%' and t.ship_status != ".self::SHIP_STATUS_CANCEL.")")
	    	->andWhere("t.platform_code = :platform_code", array(':platform_code' => Platform::CODE_AMAZON))
	    	->andWhere('t.create_time >= "2016-08-28 00:00:00" and t.is_repeat = 0')
	    	->order("t.consign_date asc");
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}else{
    		$packageIdWhere = "";
    		$prepackage = "PK".date("ym");
    		$prepackage2 = "PK".date("ym", strtotime("-1 months"));
    		$dbCommand->andWhere("t.package_id like '{$prepackage}%' or t.package_id like '{$prepackage2}%'");
    	}
    	if(!empty($accountID) || !empty($excludeAccount)){
    		$dbCommand->join("ueb_order t2", "t2.order_id = t1.order_id");
    		$dbCommand->andWhere("t2.platform_code = :platform_code", array(':platform_code' => Platform::CODE_AMAZON));
    	}
    	if(!empty($accountID)){
    		$dbCommand->andWhere("t2.account_id='{$accountID}'");
    		// 
    	}
    	//2017-02-03 add lihy
    	if(!empty($excludeAccount)){
    		$dbCommand->andWhere(array('NOT IN', 't2.account_id', $excludeAccount));
    		//
    	}
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }
	
    /**
     * @desc 设置包裹追踪号已经上传到平台
     * @param int $packageID
     * @param datetime $date
     * @return boolean
     */
    public function setPackageUploadedToPlatform($packageID, $date) {
    	$orderPackageModel = $this->findByPk($packageID);
    	if (!empty($orderPackageModel)) {
    		$orderPackageModel->setAttribute('upload_track_no', 1);
    		$orderPackageModel->setAttribute('upload_track_no_time', $date);
    		return $orderPackageModel->save();
    	}
    	return false;  	
    }
    
    /**
     * @desc 更新跟踪号
     * @param string $packageID
     * @param string $trackNum
     */
    public function updateTrackNum($packageID,$trackNum){
    	$this->dbConnection->createCommand()->update(self::tableName(), array('track_num' => $trackNum),'package_id = "'.$packageID.'"');
    }

    /**
     * @desc 更新跟发货状态
     */
    public function updateShipStatus($packageID,$shipStatus){
    	$this->dbConnection->createCommand()->update(self::tableName(), array('upload_ship' => $shipStatus,'upload_time'=>date('Y-m-d H:i:s',time())),'package_id = "'.$packageID.'"');
    }
    
    /**
     * 查询ebay订单是否有eub的包裹信息
     * @param string $orderId,string $field
     */
    public function getEbayEubPackages( $orderId,$field = '*' ) {
    	$dbCommand = $this->dbConnection->createCommand()
		    	->select($field)
		    	->from(self::tableName() . " t")
		    	->join(OrderPackageDetail::model()->tableName().' as d', 't.package_id = d.package_id')
		    	->where('d.order_id="'.$orderId.'"')
		    	->andWhere('(t.real_ship_type like "eub%" or (t.ship_code like "eub%" and t.real_ship_type=""))')
		    	->andWhere("t.platform_code = :platform_code", array(':platform_code' => Platform::CODE_EBAY))
    			->andWhere('t.ship_status != 5');
    	return $dbCommand->queryAll();
    }
    
    /**
     * 查询ebay订单是否存在另外一个eub包裹 ,并且非gift的eub包裹
     * @param string $orderId,string $field
     */
    public function getEbayEubPackagesByOrderId( $orderId,$packageId ) {
    	$dbCommand = $this->dbConnection->createCommand()
		    	->select('t.package_id')
		    	->from(self::tableName() . " t")
		    	->join(OrderPackageDetail::model()->tableName().' as d', 't.package_id = d.package_id')
		    	->where('d.order_id="'.$orderId.'"')
		    	->andWhere('(t.real_ship_type like "eub%" or (t.ship_code like "eub%" and t.real_ship_type=""))')
		    	->andWhere('t.package_id != "'.$packageId.'"')
		    	->andWhere("t.platform_code = :platform_code", array(':platform_code' => Platform::CODE_EBAY));
    	return $dbCommand->queryRow();
    }
    
    /**
     * 获取ebay非eub包裹，待上传跟踪号的
     * @param string $limit string $consignDate string $packageId
     */
    public function getJdWaitingUploadPackages($consignDate = null,$packageId = null,$limit = null) {
    	$dbCommand = $this->dbConnection->createCommand()
    	->select('t.package_id,t.ship_code,t.real_ship_type,t.track_num,t.ship_country_name')
    	->from(self::tableName() . " t")
    	->join(OrderPackageDetail::model()->tableName().' as d','t.package_id=d.package_id')
    	->join(Order::model()->tableName().' as o','o.order_id=d.order_id')
    	->where('t.is_confirm_shiped = 0')
    	//->andWhere('o.account_id=30')
    	//->andWhere('t.real_ship_type not like "eub%"')
    	->andWhere('t.track_num != ""')
    	//->andWhere('t.consign_date >= "'.$consignDate.'"')
    	->andWhere('t.platform_code = :platform_code', array(':platform_code' => Platform::CODE_JD))
    	//->andWhere('t.real_ship_type like "ghxb_%"')
    	//->andWhere('t.create_time >= "2015-11-01 00:00:00"')
    	->order('t.consign_date asc');
    	if( $packageId ){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	 
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	return $dbCommand->queryAll();
    }

     /**
     * 获取Aliexpress待上传追踪号信息到平台的包裹
     * @param string $platformCode
     * @param string $limit
     */
    public function getAliexpressWaitingUploadPackages($consignDate = null,$packageId = null,$limit = null) {
    	//获取近10天出货的、已出货、有跟踪号、包括已经确认发货过的。
    	$dbCommand = $this->dbConnection->createCommand()
	    	->select("t.package_id,t.track_num,t.real_ship_type,t.ship_code, t1.order_id, t1.sku, t1.quantity,t1.order_detail_id")
	    	->from(self::tableName() . " t")
	    	->join("ueb_order_package_detail t1", "t.package_id = t1.package_id")
	    	->where("t.is_confirm_shiped in(0)")
	    	->andWhere("t.track_num != '' and t.is_repeat = 0 and t.is_to_mid = 1")
	    	->andWhere('t.to_mid_time >= "'.$consignDate.'"')
	    	->andWhere("t.platform_code = :platform_code", array(':platform_code' => Platform::CODE_ALIEXPRESS))
	    	->andWhere('t.modify_time >= "2015-12-22 00:00:00"')
	    	->order("t.to_mid_time asc")
    		->group('t1.order_id');
    	
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryAll();
    }

    /**
     * 获取非速卖通平台目的国相同的已出货订单跟踪号
     */
    public function getNotAliShippedTrackNum($orderInfo) {
        $payTime = $orderInfo['paytime'];
        $endPayTime = date('Y-m-d H:i:s', strtotime($payTime) - 3600*24);
        $startPayTime = date('Y-m-d H:i:s', strtotime($payTime) - 3600*72);
        $obj = $this->dbConnection->createCommand()
            ->select('p.*')
            ->from(self::tableName().' p')
            ->leftJoin('ueb_order_package_detail pd', 'pd.package_id = p.package_id')
            ->leftJoin('ueb_order_detail od', 'od.id = pd.order_detail_id')
            ->leftJoin('ueb_order o', 'o.order_id = od.order_id')
            ->where("o.complete_status = 3 and p.ship_status = 1 and o.paytime >= '{$startPayTime}' and o.paytime <= '{$endPayTime}' ")
            ->andWhere("o.platform_code = 'EB' and o.ship_country = '{$orderInfo['ship_country']}' ");
        //echo $obj->text;
        return $obj->queryAll();
    }

    /**
     * 获取要上传追踪号的订单（临时）
     */
	public function getJdWaitingUploadPackagesTemp($limit = 100) {
		$command = $this->getDbConnection()->createCommand()
			->from("ueb_order_track_num_prepare_match")
			->where("is_confirm_shiped = 0")
			->andWhere("confirm_shiped_time is null")
			->andWhere("platform_code = :platform_code", array('platform_code' => Platform::CODE_JD))
			->andWhere("order_id <> ''")
			->andWhere("track_num <> ''")
			->limit($limit);
		return $command->queryAll();
	} 
	
	/**
	 * @desc 更新ueb_order_track_num_prepare_match表
	 * @param unknown $data
	 * @param unknown $condition
	 * @return boolean
	 */
	public function updatePrepareMatchData($data, $condition) {
		if (empty($condition)) return false;
		return $this->getDbConnection()->createCommand()->update("ueb_order_track_num_prepare_match", $data, $condition);
	}
	
	/**
	 * @desc 根据orderid查询eub礼品包裹
	 * @param string $orderId
	 */
	public function getAmazonEubGiftPackage( $orderId ){
		if( empty($orderId) ) return '';
		$ret = $this->dbConnection->createCommand()
			->select('o.package_id,o.track_num,o.ship_code,o.is_confirm_shiped')
			->from(self::tableName().' AS o')
			->join(OrderPackageDetail::model()->tableName().' AS d', 'd.package_id = o.package_id')
			->andWhere('d.order_id in("'.$orderId.'")')
			->andWhere('o.ship_code like "eub%"')
			->andWhere('o.ship_status != '.self::SHIP_STATUS_CANCEL)
			->group('o.package_id')
			->queryRow();
		return $ret;
	}
        
    /**
     * @author liuj
     * @description 取消包裹 [调用oms的api]
     * @param $params 包裹id数组
     * @return array $return
     * @since	2016-02-25
     */
    public function cancelPackage( $params ){
        $api = Yii::app()->erpApi;
        $return = array();
        $message= '';
        $params = array($params);
        try{
            $result = $api->setServer('oms')->setFunction('Orders:OrderPackage:batchCancelPackage')->setRequest($params)->sendRequest()->getResponse();
            if( $api->getIfSuccess() ){
                if( $result['status'] == true ){
                    $status = true;
                } else {
                    $status = false;
                    $message = $result['message'];
                }
                
            } else {
                $message .= $api->getErrorMsg();
                $status = false;
            }
            $return = array( 'status' => $status, 'message'=> $message );
        } catch (Exception $e ) {
            $return = array( 'status' => false, 'message' => $e->getMessage() );
        }
        return $return;
    }
    
    /**
     * 获取joom平台待上传追踪号信息到平台的包裹
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getJoomWaitingUploadPackages( $pkCreateDate = null,$packageId = null,$limit = null, $accountID = null ) {
    	//获取近15天有跟踪号，未上传跟踪号的包裹。
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("t.package_id,t.track_num,t.real_ship_type,t.ship_code, d.order_id")
			    	->from(self::tableName() . " t")
			    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
			    	//->join("ueb_order o","o.order_id = d.order_id")
			    	->where("t.is_confirm_shiped in(0)")
			    	->andWhere("t.track_num != '' and t.ship_status!=".self::SHIP_STATUS_CANCEL)
			    	->andWhere('t.create_time >= "'.$pkCreateDate.'" and t.is_repeat = 0')
			    	->andWhere("t.platform_code = '".Platform::CODE_JOOM."'")
			    	->andWhere('t.modify_time >= "2015-12-31 00:00:00"')
			    	//->andWhere("o.account_id=17")
			    	->order("t.modify_time asc")
		    		->group('d.order_id');
    	
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	/* if( count($packageId)>0 && $packageId ){
    		$dbCommand->andWhere('t.package_id in('.MHelper::simplode($packageId).')');
    	} */
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	if($accountID){
    		$dbCommand->join("ueb_order o","o.order_id = d.order_id");
    		$dbCommand->andWhere('o.account_id='.$accountID);
    	}
    	//echo $dbCommand->text;exit;
    	if(isset($_REQUEST['bug']) && $_REQUEST['bug']){
    		echo $dbCommand->text, "<br/>";
    	}
    	return $dbCommand->queryAll();
    }
    
    /**
     * joom根据订单号查有包裹未上传跟踪号的订单信息.
     * @param string $orderIds
     */
    public function getJoomUnUploadTrackOrders( $orderIds = '' ) {
    	if(empty($orderIds)) return null;
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("d.order_id")
			    	->from(self::tableName() . " t")
			    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
			    	->where("t.is_confirm_shiped in(0)")
			    	->andWhere("d.order_id in(".$orderIds.")")
			    	->group('d.order_id');

    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryColumn();
    }
    
    /**
     * joom根据订单号查没有生成包裹的订单信息.
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getJoomUnCreatePackageOrders( $orderIds = '' ) {
    	if(empty($orderIds)) return null;
    	$dbCommand = $this->dbConnection->createCommand()
			    	->select("o.order_id")
			    	->from(Order::model()->tableName() . " o")
			    	->leftJoin(OrderPackageDetail::model()->tableName().' pd', "o.order_id = pd.order_id")
			    	->where("o.order_id in(".$orderIds.")")
			    	->andWhere("pd.order_id is null")
			    	->group('o.order_id');
    
    	//echo $dbCommand->text;exit;
    	return $dbCommand->queryColumn();
    }
    
    /**
     * @desc 按照指定的渠道过滤包裹
     * @author wx
     */
    public function getKdPackageList( $packageIdList = array() ){
    	
    	if( empty($packageIdList) ) return null;
    	
    	$kdArray = array(strtolower(Logistics::CODE_FEDEX_IE),strtolower(Logistics::CODE_DHTD_IP),strtolower(Logistics::CODE_DHTD_IE),strtolower(Logistics::CODE_DHTD_UPS),strtolower(Logistics::CODE_DHTD_DHL),strtolower(Logistics::CODE_EMS));
    	
    	$dbCommand = $this->getDbConnection()->createCommand()
    				->select("t.package_id")
			    	->from(self::tableName() . " t")
			    	->where("t.package_id in(".MHelper::simplode($packageIdList).")")
			    	->andWhere("t.ship_code in(".MHelper::simplode($kdArray).")")
    				->andWhere("t.track_num = '' or t.track_num like 'PK%'");
    	
    	return $dbCommand->queryColumn();
    	
    }
    
    /**
     * 统计指定渠道当天已生成包裹
     * @param	string	$shipCode
     * @return	int
     */
    public function getPkCountByShipCode($shipCode) {
    	$date = date('ymd');
    	$row = $this->getDbConnection()->createCommand()
    	->select('count(0) as count')
    	->from(self::tableName())
    	->where("package_id like 'PK{$date}%' and ship_code='{$shipCode}' and ship_status !=".OrderPackage::SHIP_STATUS_CANCEL)
    	->queryRow();
    	return isset($row['count']) ? $row['count'] : 0;
    }
    
    
    
    // ====================== START:特殊调用方法，不要随便调用 ======================//
    /**
     * 
     * @param string $minShipDate
     */
    public function getSpecialPackageList($orderPayTime, $shipCountry, $limit = 0, $minShipDate = "", $filterDgyzNumbers = array(), $filterJrxbNumbers = array()){
    	$tempMinShipDate = $orderPayTime;
    	if(empty($minShipDate)){
    		$minShipDate = $tempMinShipDate;
    	}else{
    		if($minShipDate < $tempMinShipDate){
    			$minShipDate = $tempMinShipDate;
    		}
    	}
    	$dbCommand = $this->getDbConnection()->createCommand()
				    	->select("ship_code, platform_code, track_num, ship_date")
				    	->from(self::tableName())
				    	->where("ship_code in('cm_dgyz', 'cm_jrxb') and ship_status=1 and platform_code != 'KF'")
				    	->andWhere("ship_country='{$shipCountry}'")
				    	->andWhere("ship_date > '{$minShipDate}'")
				    	->order("ship_date asc,ship_code asc");
    	if($limit){
    		$dbCommand->limit($limit);
    	}
    	$condition1 = $condition2 = "";
    	$condition = "";
    	if($filterDgyzNumbers){
    		$condition1 = " ( ship_code='cm_dgyz' and track_num not in(". MHelper::simplode($filterDgyzNumbers)  .") ) ";
    		$condition = $condition1;
    	}
    	if($filterJrxbNumbers){
    		$condition2 = " ( ship_code='cm_jrxb' and track_num not in(". MHelper::simplode($filterJrxbNumbers)  .") ) ";
    		$condition = $condition2;
    	}
    	
    	if($condition1 && $condition2){
    		$condition = "( {$condition1} or {$condition2} )";
    	}
    	if($condition){
    		$dbCommand->andWhere($condition);
    	}
    	if(isset($_REQUEST['bug'])){
    		echo "<br/>";
    		echo $condition;
    		echo "<br/>";
    	}
    	return $dbCommand->queryAll();
    }
    
    /**
     * @DESC  根据shipCode 获取对应的追踪号
     * @param unknown $orderPayTime
     * @param unknown $shipCountry
     * @param number $limit
     * @param string $minShipDate
     * @param unknown $shipCodeArr
     * @param unknown $filterTraceNumber
     * @return Ambigous <multitype:, mixed>
     */
    public function getSpecialPackageList2($orderPayTime, $shipCountry, $limit = 0, $minShipDate = "", $shipCodeArr, $filterTraceNumber = array(), $maxShipDate = "", $excludePlatformCode = Platform::CODE_WISH){
    	$tempMinShipDate = $orderPayTime;
    	if(empty($minShipDate)){
    		$minShipDate = $tempMinShipDate;
    	}else{
    		if($minShipDate < $tempMinShipDate){
    			$minShipDate = $tempMinShipDate;
    		}
    	}
		if(empty($excludePlatformCode)) return array();
		if(empty($maxShipDate)){
    		$maxShipDate = date("Y-m-d H:i:s", strtotime($minShipDate)+(5*24-15)*3600);//5天内
		}
    	$dbCommand = $this->getDbConnection()->createCommand()
					    	->select("ship_code, platform_code, track_num, ship_date")
					    	->from(self::tableName())
					    	->where("ship_code in(". MHelper::simplode($shipCodeArr).") and ship_status=1 and platform_code not in ('".$excludePlatformCode."')")
					    	->andWhere("ship_country='{$shipCountry}'")
					    	->andWhere("ship_date > '{$minShipDate}'")
					    	->andWhere("ship_date < '{$maxShipDate}'")
					    	->order("ship_date desc,ship_code asc");
    	if($limit){
    		$dbCommand->limit($limit);
    	}
    	
    	$condition = "";
    	
    	$conditionArr = array();
    	if($filterTraceNumber){
    		foreach ($filterTraceNumber as $shipCode => $filterNumber){
    			if(empty($filterNumber)) continue;
    			$conditionArr[] = " ( ship_code='{$shipCode}' and track_num not in(". MHelper::simplode($filterNumber)  .") ) ";
    		}
    		if($conditionArr){
    			$condition = "(" . implode(" OR ", $conditionArr) . ")";
    		}
    	}
    	
    	if($condition){
    		$dbCommand->andWhere($condition);
    	}
    	if(isset($_REQUEST['bug'])){
    		echo "<br/>";
    		echo $condition;
    		echo "<br/>";
    	}
    	return $dbCommand->queryAll();
    }
    
    
    
    public function getSpecialPackageListWithGH($orderPayTime, $shipCountry, $limit = 0, $minShipDate = "", $shipCodeArr = array(), $filterTraceNumber = array(), $maxShipDate = "", $excludePlatformCode = Platform::CODE_WISH){
    	$tempMinShipDate = $orderPayTime;
    	if(empty($minShipDate)){
    		$minShipDate = $tempMinShipDate;
    	}else{
    		if($minShipDate < $tempMinShipDate){
    			$minShipDate = $tempMinShipDate;
    		}
    	}
    	if(empty($excludePlatformCode)) return array();
    	if(empty($maxShipDate)){
    		$maxShipDate = date("Y-m-d H:i:s", strtotime($minShipDate)+(5*24-15)*3600);//5天内
    	}
    	$dbCommand = $this->getDbConnection()->createCommand()
    	->select("ship_code, platform_code, track_num, ship_date")
    	->from(self::tableName())
    	->where("ship_status=1 and platform_code not in ('".$excludePlatformCode."')")
    	->andWhere("ship_country='{$shipCountry}'")
    	->andWhere("ship_date > '{$minShipDate}'")
    	->andWhere("ship_date < '{$maxShipDate}'")
    	->order("ship_date desc,ship_code asc");
    	if($limit){
    		$dbCommand->limit($limit);
    	}
    	if($shipCodeArr){
    		$dbCommand->andWhere("ship_code in(".MHelper::simplode($shipCodeArr).")");
    	}else{
    		$dbCommand->andWhere("ship_code like 'ghxb_%'");
    	}
    	$condition = "";
    	 
    	$conditionArr = array();
    	if($filterTraceNumber){
    		foreach ($filterTraceNumber as $shipCode => $filterNumber){
    			if(empty($filterNumber)) continue;
    			$conditionArr[] = " ( ship_code='{$shipCode}' and track_num not in(". MHelper::simplode($filterNumber)  .") ) ";
    		}
    		if($conditionArr){
    			$condition = "(" . implode(" OR ", $conditionArr) . ")";
    		}
    	}
    	 
    	if($condition){
    		$dbCommand->andWhere($condition);
    	}
    	if(isset($_REQUEST['bug'])){
    		echo "<br/>";
    		echo $condition;
    		echo "<br/>";
    	}
    	return $dbCommand->queryAll();
    }
    
    /**
     * 根据订单ID通过接口获取顺友跟踪号
     * @param unknown $orderID
     */
    public function getShunYouTrackNum($orderID, $isSpecial = false) {
    	$packageList = $this->getShunYouNewTrackNum( $orderID, $isSpecial);
    	
    	if(empty($packageList)) return false;
    	/* $transData	= array(//测试
    			'apiDevUserToken'		=> 'AB3B718F71DF0A06BFFBADAD23169F0C',
    			'apiLogUsertoken'		=> '564A4AD96361332B646B8FA25540084A807B6F8A8F1E43679DDBFC319A5C8907',
    			'data'					=> $packageList,
    	); */
    	$transData	= array(//正式
    			'apiDevUserToken'		=> 'BC00C5EDF23EAEFCBC9BC64A13C951D5',
    			'apiLogUsertoken'		=> '7E64A5C1BC00C5EDF23EAEFC3C0E4D39B65FFE5A2EFF4A83A886268852E62A6B',
    			'data'					=> $packageList,
    	);
    	//print_r($transData);
    	$data = json_encode($transData);
    	//获取配置数据
    	require_once Yii::app()->basePath.'/extensions/xlogis/synew/action.php';
    	$syNewPostapiqh = new SyServiceAction();
    	 
    	$ret = $syNewPostapiqh->getTrackNum($data);
    	if(isset($_REQUEST['bug']) && $_REQUEST['bug']){
    		echo "========get sy api result=====<br/>";
    		var_dump($ret);
    	}
    	//2017-01-03
    	$trackNum	= $ret['uploadmsg']['trackNum'];
    	if( $ret['uploadflag'] && $trackNum){ //上传成功,更新状态
    		return $trackNum;
    	}else{
    		return false;
    	}
    }
    
    public function getShunYouNewTrackNum($orderID, $isSpecial = false){
    	if($isSpecial){
    		$orderInfo = WishSpecialOrder::model()->findByPk($orderID);
    	}else{
    		$orderInfo = Order::model()->findByPk($orderID);
    	}
    	
    	if(empty($orderInfo)){
    		return false;
    	}
    	$street1 = str_replace(array('&','#','"',"'",'\\','null','NULL'),' ',$orderInfo['ship_street1']);
    	$street2 = str_replace(array('&','#','"',"'",'\\','null','NULL'),' ',$orderInfo['ship_street2']);
    	$categoryArr = ProductCategoryOld::model()->getCatNameCnOrEn();
    	$productList = array();
    	if($isSpecial){
    		$orderDetails = WishSpecialOrderDetail::model()->findAll("order_id='{$orderID}'");
    	}else{
    		$orderDetails = OrderDetail::model()->findAll("order_id='{$orderID}'");
    	}
    	
    	foreach( $orderDetails as $detail ){
    	
    		$currCategoryId = ProductCategorySkuOld::model()->getClassIdBySku($detail['sku']);
    		if(empty($currCategoryId)) return false;
    		$parent_category_cnname = $categoryArr[$currCategoryId]['cn'];
    		$parent_category_enname = $categoryArr[$currCategoryId]['en'];
    		if( $parent_category_enname == 'Accessories' ){
    			$parent_category_enname = 'Apple Accessories';
    		}
    		if( $parent_category_enname == 'Gift' ){
    			$parent_category_enname = $detail['title'];
    		}
    		$prodInfo = UebModel::model('Product')->getProductInfoBySku($detail['sku']);
    		$onlineCateId = $prodInfo['online_category_id'];
    		$cateNameInfo = UebModel::model('ProductCategoryOnline')->getProductOnlineCategoryPairByClassId($onlineCateId);
    		
    		$parent_category_enname = isset($cateNameInfo[$onlineCateId]) ? $cateNameInfo[$onlineCateId] : $categoryArr[$currCategoryId]['en'];
    	
    		$parent_category_enname = empty($parent_category_enname)?$detail['title']:$parent_category_enname;
    		if( strlen($parent_category_enname) > 100 ){
    			$parent_category_enname = mb_substr($parent_category_enname,0,100,'utf-8');
    		}
    		$parent_category_enname = str_replace(array('&','#','"',"'",'\\',"/"),' ',$parent_category_enname);
    		$parent_category_cnname = str_replace(array('&','#','"',"'",'\\',"/"),' ',$parent_category_cnname);
    		//$ename	= str_replace($ubiSensitiveWords,'',strtolower($parent_category_enname));
    		 
    		$productList[] = array(
    				"productSku"		=> $detail['sku'],
    				"declareEnName"	    => $parent_category_enname,
    				"declareCnName"		=> $parent_category_cnname,
    				"quantity"			=> $detail['quantity']?$detail['quantity']:1,
    				"declarePrice"  	=> round($detail['sale_price'],4),
    				"hsCode"			=> '',
    		);
    	}
    	if( $orderInfo['ship_country'] == 'SRB' ) $orderInfo['ship_country'] = 'RS';
    	if( $orderInfo['ship_country'] == 'UK' ) $orderInfo['ship_country'] = 'GB';
    	$totalWeight = 0.1;
    	//发件人信息
    	$senderUser = Logistics::getSendAddressGm();
    	$packageList = array();
    	$packageList['packageList'][]	= array(
    			'customerOrderNo'		=> $orderID,
    			'customerReferenceNo'	=> '',
    			'trackingNumber'		=> '',
    			'shippingMethodCode'	=> 'SYBAM',//-- //通过此接口 获取 findShippingMethods //'SYBAM',SYBPL
    			'packageSalesAmount'	=> 0,//$totalCost,
    			'packageLength'			=> 0,
    			'packageWidth'			=> 0,
    			'packageHeight'			=> 0,
    			'predictionWeight'		=> $totalWeight,
    			'recipientName'			=> $orderInfo['ship_name'],
    			'recipientCountryCode'	=> $orderInfo['ship_country'],
    			'recipientPostCode'		=> $orderInfo['ship_zip'],
    			'recipientState'		=> $orderInfo['ship_stateorprovince'],
    			'recipientCity'			=> $orderInfo['ship_city_name'],
    			'recipientStreet'		=> $street1.$street2,
    			'recipientPhone'		=> $orderInfo['ship_phone'],
    			'recipientMobile'		=> $orderInfo['ship_phone'],
    			'recipientEmail'		=> '',
    			'senderName'			=> $senderUser['sender'],
    			'senderPhone'			=> $senderUser['phone'],
    			'senderPostCode'		=> $senderUser['post_code'],
    			'senderFullAddress'		=> "5th Floor B Buliding,DiGuang Digital Science And Technology Park,RD 9th,ChangZhen Community,GuangMing New District,ShenZhen,GuangDong,China",
    			'senderAddress'			=> '',
    			'senderCountryCode'		=> 'CN',
    			'senderState'			=> $senderUser['province'],
    			'senderCity'			=> $senderUser['city'],
    			'senderDistrict'		=> $senderUser['county'],
    			'senderEmail'			=> '',
    			'insuranceType'			=> 0,
    			'packageAttributes'		=> "000",
    			'productList'			=> $productList,
    	);
    	return $packageList;
    }
    
    /**
     * @desc 保存顺邮宝追踪记录
     * @param unknown $traceNumber
     * @param unknown $orderID
     * @param string $isSpecial
     * @return boolean
     */
    public function saveSybTrackNumberRecordWithOrderID($traceNumber, $orderID, $isSpecial = false, $shipDate = null){
    	if($isSpecial){
    		$orderInfo = WishSpecialOrder::model()->findByPk($orderID);
    	}else{
    		$orderInfo = Order::model()->findByPk($orderID);
    	}
    	
    	if(empty($orderInfo)){
    		return false;
    	}
    	//验证是否重复
    	$existsTrack = WishSybTrackList::model()->find("package_id='{$orderID}'");
    	if($existsTrack){
    		return true;
    	}
    	$street1 = str_replace(array('&','#','"',"'",'\\','null','NULL'),' ',$orderInfo['ship_street1']);
    	$street2 = str_replace(array('&','#','"',"'",'\\','null','NULL'),' ',$orderInfo['ship_street2']);
    	$categoryArr = ProductCategoryOld::model()->getCatNameCnOrEn();
    	$productList = array();
    	if($isSpecial){
    		$orderDetails = WishSpecialOrderDetail::model()->findAll("order_id='{$orderID}'");
    	}else{
    		$orderDetails = OrderDetail::model()->findAll("order_id='{$orderID}'");
    	}
    	$parent_category_enname = $parent_category_cnname = '';
    	foreach( $orderDetails as $detail ){
    		$currCategoryId = ProductCategorySkuOld::model()->getClassIdBySku($detail['sku']);
    		if(empty($currCategoryId)) continue;
    		$parent_category_cnname = $categoryArr[$currCategoryId]['cn'];
    		$parent_category_enname = $categoryArr[$currCategoryId]['en'];
    		if( $parent_category_enname == 'Accessories' ){
    			$parent_category_enname = 'Apple Accessories';
    		}
    		if( $parent_category_enname == 'Gift' ){
    			$parent_category_enname = $detail['title'];
    		}
    		$prodInfo = UebModel::model('Product')->getProductInfoBySku($detail['sku']);
    		$onlineCateId = $prodInfo['online_category_id'];
    		$cateNameInfo = UebModel::model('ProductCategoryOnline')->getProductOnlineCategoryPairByClassId($onlineCateId);
    	
    		$parent_category_enname = isset($cateNameInfo[$onlineCateId]) ? $cateNameInfo[$onlineCateId] : $categoryArr[$currCategoryId]['en'];
    		 
    		$parent_category_enname = empty($parent_category_enname)?$detail['title']:$parent_category_enname;
    		if( strlen($parent_category_enname) > 100 ){
    			$parent_category_enname = mb_substr($parent_category_enname,0,100,'utf-8');
    		}
    		$parent_category_enname = str_replace(array('&','#','"',"'",'\\',"/"),' ',$parent_category_enname);
    		$parent_category_cnname = str_replace(array('&','#','"',"'",'\\',"/"),' ',$parent_category_cnname);
    		break;
    	}
    	if (trim($orderInfo['ship_country_name']) == 'Serbia') $orderInfo['ship_country'] = 'RS';
    	if (trim($orderInfo['ship_country_name']) == 'Montenegro') $orderInfo['ship_country'] = 'ME';
    	if (trim($orderInfo['ship_country_name']) == 'Albania') $orderInfo['ship_country'] = 'AL';
    	if (trim($orderInfo['ship_country']) == 'UK') $orderInfo['ship_country'] = 'GB';
    	
    	$shipCountry = Country::model()->getCountryInfoByAbbr($orderInfo['ship_country']);

    	$data = array(
    					'trace_number'			=>	$traceNumber,
    					'ship_country_code'		=> 	$orderInfo['ship_country'],
    					'ship_country_cn'		=>	empty($shipCountry['cn_name']) ? $orderInfo['ship_country_name']:$shipCountry['cn_name'],
    					'recive_name'			=>	$orderInfo['ship_name'],
    					'recive_address'		=>	$orderInfo['ship_stateorprovince']." ".$orderInfo['ship_city_name']." ".$street1.$street2,
    					'ship_date'				=>	$shipDate?$shipDate:date("Y-m-d H:i:s"),
    					'good_name_en'			=>	$parent_category_enname,
    					'good_name_cn'			=>	$parent_category_cnname,
    					'quantity'				=>	1,
    					'weight'				=>	0.1,
    					'price'					=>	$orderInfo['total_price'],
    					'package_id'			=>	$orderID,
    					'is_special'			=>	$isSpecial ? 1 : 0,
    					'create_time'			=>	date("Y-m-d H:i:s"),
    			);	
    	return WishSybTrackList::model()->saveOrderTraceNumberRecord($data);
    }
   
    // ====================== END:特殊调用方法，不要随便调用 ======================//
    



    /**
     * 获取shopee平台待上传追踪号信息到平台的包裹
     * @param string $pkCreateDate
     * @param string $packageId
     * @param integer $limit
     */
    public function getShopeeWaitingUploadPackages( $pkCreateDate = null,$packageId = null,$limit = null, $accountID = null ) {
    	//获取近15天有跟踪号，未上传跟踪号的包裹。
    	$dbCommand = $this->dbConnection->createCommand()
    	->select("t.package_id,t.track_num,t.real_ship_type,t.ship_code, d.order_id")
    	->from(self::tableName() . " t")
    	->join("ueb_order_package_detail d", "t.package_id = d.package_id")
    	//->join("ueb_order o","o.order_id = d.order_id")
    	->where("t.is_confirm_shiped=0")
    	->andWhere("t.track_num != '' and t.ship_status!=".self::SHIP_STATUS_CANCEL)
    	->andWhere('t.create_time >= "'.$pkCreateDate.'" and t.is_repeat = 0')
    	->andWhere("t.platform_code = '".Platform::CODE_SHOPEE."'")
    	->andWhere('t.modify_time >= "2015-12-31 00:00:00"')
    	//->andWhere("o.account_id=17")
    	->order("t.modify_time asc")
    	->group('d.order_id');
    
    	if(!empty($packageId)){
    		$dbCommand->andWhere('t.package_id like "'.$packageId.'%"');
    	}
    	/* if( count($packageId)>0 && $packageId ){
    	 $dbCommand->andWhere('t.package_id in('.MHelper::simplode($packageId).')');
    	} */
    	if (!empty($limit))
    		$dbCommand->limit((int)$limit, 0);
    	if($accountID){
    		$dbCommand->join("ueb_order o","o.order_id = d.order_id");
    		$dbCommand->andWhere('o.account_id='.$accountID);
    	}
    	//echo $dbCommand->text;exit;
    	if(isset($_REQUEST['bug']) && $_REQUEST['bug']){
    		echo $dbCommand->text, "<br/>";
    	}
    	return $dbCommand->queryAll();
    }
    
    
    // ===================== YUNTU =============================== //
	 /**
	  * @desc 获取云图取号
	  * @param unknown $orderID
	  * @param string $isSpecial
	  * @return Ambigous <string, unknown>
	  */
    public function getYunTuNum($orderID, $isSpecial = false){
    	$ret = $this->uploadYunTu($orderID, $isSpecial);
    	if(empty($ret)) return false;
    	$trackNumber = explode("-%%", $ret);
    	if($trackNumber[0] == 'error') return false;
    	return $trackNumber[1];
    }
    
    /**
     * @desc 上次云图系统
     * @param unknown $orderID
     * @param unknown $isSpecial
     * @return boolean|string|unknown
     */
    public function uploadYunTu($orderID, $isSpecial){
    	if($isSpecial){
    		$orderInfo = WishSpecialOrder::model()->findByPk($orderID);
    	}else{
    		$orderInfo = Order::model()->findByPk($orderID);
    	}
    	 
    	if(empty($orderInfo)){
    		return false;
    	}

    	if($isSpecial){
    		$orderDetails = WishSpecialOrderDetail::model()->findAll("order_id='{$orderID}'");
    	}else{
    		$orderDetails = OrderDetail::model()->findAll("order_id='{$orderID}'");
    	}

    	//$rate = CurrencyRate::getRateByCondition('CNY','USD');
    	$totalCost = 0;
    	$totalWeight = 0;
    	require_once Yii::app()->basePath.'/extensions/xlogis/yuntu/action.php';
    	$yuntuapi = new YtServiceAction();
    	$detailData = array();
    	foreach($orderDetails as $detail ){
    		$skuInfo = UebModel::model('Product')->getProductInfoBySku($detail['sku']);
    		$cost = floatval($skuInfo['product_cost']) * intval($detail['quantity']);
    		//$cost = round($rate*$cost,2);//美元价值
    		$cost = round($cost/6, 2); //汇率表里没有，直接除6
    		$totalCost += $cost;//总价值
    
    		$weight = round(floatval($skuInfo['product_weight']) * intval($detail['quantity']) / 1000, 3);
    		$totalWeight += $weight;//总重量
    		$eName = $skuInfo['title']['english'];
    		$cName = $skuInfo['title']['Chinese'];
    		if( mb_strlen($eName, "utf-8") > 60 ){
    			$eName = mb_substr($eName, 0, 50, "utf-8");
    		}
    		if( mb_strlen($cName, "utf-8") > 60 ){
    			$cName = mb_substr($cName, 0, 50, "utf-8");
    		}
    		
    		//防错
    		if ($cost == 0) {
    			$cost = 0.1;
    		}
    
    		$qty = intval($detail['quantity']);
    		/**
    		 * 独轮车专线 申报
    		 * 中欧双清专线 150欧元，中美独轮车专线200美金
    		*/
    		if ($orderInfo['ship_code'] == Logistics::CODE_KD_YT_US) {
    			($cost * $qty > 150) && $cost = 150 / $qty;
    		}elseif ($orderInfo['ship_code'] == Logistics::CODE_KD_YT_EU) {
    			($cost * $qty > 150) && $cost = 150 / $qty;
    		}else { 
    			$cost = round(5/count($orderDetails), 2);//其他渠道一直是按5美金报，不变
    		}
    		if (in_array($orderInfo['ship_code'], array(Logistics::CODE_KD_YT_US,Logistics::CODE_KD_YT_EU))) {
    			foreach (array('独轮车','扭扭车') as $val) {
    				if (stripos($cName, $val) !== false) {
    					$cName = $val;
    				}
    			}
    		}
    
    		$detailData['ApplicationInfos'][] = array(
    				'ApplicationName' 	=> $eName,
    				'Qty' 				=> intval($detail['quantity']),
    				'UnitWeight'		=> round($skuInfo['product_weight']/1000, 3),
    				'UnitPrice'			=> $cost,
    				//'UnitPrice'			=> 5,
    				'PickingName'		=> $cName //$skuInfo['enname'],
    		);
    	}
    	
 
    	//$methodCode = 'CNPOSTP_FZ';

    	$methodCode = 'CNDWA';//华南快速小包平邮	运输代码	CNDWA
    	
    
    	if($orderInfo['ship_country']=='UK'){//英国统一为GB
    		$orderInfo['ship_country'] = 'GB';
    	}elseif($orderInfo['ship_country'] == 'ALB'){
    		$orderInfo['ship_country'] = 'AL';
    	}elseif($orderInfo['ship_country'] == 'SRB'){
    		$orderInfo['ship_country'] = 'RS';
    	}elseif($orderInfo['ship_country'] == 'AFG'){
    		$orderInfo['ship_country'] = 'AF';
    	}
    
    	if( !empty($orderInfo['ship_street2']) ){
    		$d_address = str_replace(array('&','#','"',"'",'\\'),' ',$orderInfo['ship_street1']).', '.str_replace(array('&','#','"',"'",'\\'),' ',$orderInfo['ship_street2']);
    	}else{
    		$d_address = str_replace(array('&','#','"',"'",'\\'),' ',$orderInfo['ship_street1']);
    	}
    	if( empty($orderInfo['ship_stateorprovince']) && empty($orderInfo['ship_city_name']) ){
    		return 'error-%%'.'省份且城市为空';
    	}
    	$shipfrominfo = SysConfig::getPairByType('UebShipFromAdd');
    	$time = time();
    	$data = array(
    			'OrderNumber' 					=> $orderID,
    			'ShippingMethodCode' 			=> $methodCode,
    			'ApplicationType'				=> '4',
    			'Weight'						=> $totalWeight,
    			'ShippingInfo'					=> array(
    					'CountryCode'			=> $orderInfo['ship_country'],
    					'ShippingFirstName'		=> $orderInfo['ship_name'],
    					'ShippingLastName'		=> '',
    					'ShippingAddress'		=> $d_address,
    					'ShippingCity'			=> $orderInfo['ship_city_name'],
    					'ShippingState'			=> $orderInfo['ship_stateorprovince'],
    					'ShippingZip'			=> ($orderInfo['ship_country'] == 'IE' && $orderInfo['ship_zip'] == '') ? '000' : $orderInfo['ship_zip'], //Ireland无邮编特殊处理
    					'ShippingPhone'			=> $orderInfo['ship_phone'],
    
    			),
    			'PackageNumber'                 => 1,
    			'SenderInfo'					=> array(
    					'CountryCode'			=> 'CN',
    					'SenderCompany'			=> 'Universal E-Bussiness',
    					'SenderAddress'			=> $shipfrominfo['street'],
    					'SenderCity'			=> 'Shenzhen',
    					'SenderState'			=> 'Guangdong',
    					'SenderZip'				=> '518000',
    					'SenderPhone'			=> $shipfrominfo['mobile'],
    			),
    			'ApplicationInfos'				=> $detailData['ApplicationInfos'],
    	);
    	//			var_dump($data);exit();
    	$result = $yuntuapi->createPackage($data);
    	/* echo "<pre>";
    	print_r($result);
    	echo "</pre>"; */
    	if($result){
    		$resultArr = explode('-%%', $result);
    		if($resultArr && count($resultArr)){
    			if($resultArr[0] == 'success'){
    				//if(in_array($orderInfo['ship_code'], array(Logistics::CODE_CM_HNXB_YT, Logistics::CODE_GHXB_HNGH_YT))){
    					if($orderInfo['platform_code'] == Platform::CODE_KF){	//如果是wish平台 做特殊处理 因为wish要求上传的跟踪号为WayBillNumber(云途系统单号，不是跟踪号)
    						if(!empty($resultArr[2])){
    							return 'success-%%'.$resultArr[2].'-%%'.$resultArr[1];
    						}else{
    							return 'error-%%云途单号获取失败!';
    						}
    					}else{
    						/* if($orderInfo['ship_code'] == Logistics::CODE_CM_HNXB_YT){
    							if(!empty($resultArr[2])){
    								return 'success-%%'.$resultArr[2].'-%%'.$resultArr[1];
    							}else{
    								return 'error-%%云途单号获取失败!';
    							}
    						}else{ */
    							return 'success-%%'.$resultArr[1].'-%%'.$resultArr[1];
    						//}
    					}
    				/* }else{
    					return 'success-%%'.$resultArr[1];
    				} */
    			}else{
    				return $result;
    			}
    		}
    	}else{
    		$result = 'error-%%网络超时,请求错误!';
    	}
    	return $result;
    }
    
    
    /**
     * @desc 保存云图追踪记录
     * @param unknown $traceNumber
     * @param unknown $orderID
     * @param string $isSpecial
     * @return boolean
     */
    public function saveYuntuTrackNumberRecordWithOrderID($traceNumber, $orderID, $isSpecial = false, $shipDate = null){
    	if($isSpecial){
    		$orderInfo = WishSpecialOrder::model()->findByPk($orderID);
    	}else{
    		$orderInfo = Order::model()->findByPk($orderID);
    	}
    	 
    	if(empty($orderInfo)){
    		return false;
    	}
    	//验证是否重复
    	$existsTrack = WishYuntuTrackList::model()->find("package_id='{$orderID}'");
    	if($existsTrack){
    		return true;
    	}
    	$street1 = str_replace(array('&','#','"',"'",'\\','null','NULL'),' ',$orderInfo['ship_street1']);
    	$street2 = str_replace(array('&','#','"',"'",'\\','null','NULL'),' ',$orderInfo['ship_street2']);
    	$categoryArr = ProductCategoryOld::model()->getCatNameCnOrEn();
    	$productList = array();
    	if($isSpecial){
    		$orderDetails = WishSpecialOrderDetail::model()->findAll("order_id='{$orderID}'");
    	}else{
    		$orderDetails = OrderDetail::model()->findAll("order_id='{$orderID}'");
    	}
    	$eName = $cName = '';
    	foreach( $orderDetails as $detail ){
    		$skuInfo = UebModel::model('Product')->getProductInfoBySku($detail['sku']);
    		$eName = $skuInfo['title']['english'];
    		$cName = $skuInfo['title']['Chinese'];
    		if( mb_strlen($eName, "utf-8") > 60 ){
    			$eName = mb_substr($eName, 0, 50, "utf-8");
    		}
    		if( mb_strlen($cName, "utf-8") > 60 ){
    			$cName = mb_substr($cName, 0, 50, "utf-8");
    		}
    		break;
    	}

    	if($orderInfo['ship_country']=='UK'){//英国统一为GB
    		$orderInfo['ship_country'] = 'GB';
    	}elseif($orderInfo['ship_country'] == 'ALB'){
    		$orderInfo['ship_country'] = 'AL';
    	}elseif($orderInfo['ship_country'] == 'SRB'){
    		$orderInfo['ship_country'] = 'RS';
    	}elseif($orderInfo['ship_country'] == 'AFG'){
    		$orderInfo['ship_country'] = 'AF';
    	}
    	$shipCountry = Country::model()->getCountryInfoByAbbr($orderInfo['ship_country']);
    
    	$data = array(
    			'trace_number'			=>	$traceNumber,
    			'ship_country_code'		=> 	$orderInfo['ship_country'],
    			'ship_country_cn'		=>	empty($shipCountry['cn_name']) ? $orderInfo['ship_country_name']:$shipCountry['cn_name'],
    			'recive_name'			=>	$orderInfo['ship_name'],
    			'recive_address'		=>	$orderInfo['ship_stateorprovince']." ".$orderInfo['ship_city_name']." ".$street1.$street2,
    			'ship_date'				=>	$shipDate?$shipDate:date("Y-m-d H:i:s"),
    			'good_name_en'			=>	$eName,
    			'good_name_cn'			=>	$cName,
    			'quantity'				=>	1,
    			'weight'				=>	0.1,
    			'price'					=>	$orderInfo['total_price'],
    			'package_id'			=>	$orderID,
    			'is_special'			=>	$isSpecial ? 1 : 0,
    			'create_time'			=>	date("Y-m-d H:i:s"),
    	);
    	return WishYuntuTrackList::model()->saveOrderTraceNumberRecord($data);
    }
    // ===================== YUNTU =============================== //
}