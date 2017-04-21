<?php
/**
 * @desc Aliexpress物流相关
 * @author Gordon
 * @since 2015-08-03
 */
class AliexpressShipment extends AliexpressModel{
    
	const EVENT_ADVANCE_SHIPPED = 'advance_shipped';		//提前发货
	const EVENT_UPLOAD_TRACK = 'upload_track';				//上传跟踪号
	
    //const EVENT_SELLERSHIPMENT = 'seller_shipment';		//声明发货
    //const EVENT_SELLERMODIFIEDSHIPMENT = 'seller_modified_shipment';	//声明发货修改
    const EVENT_CREATEWAREHOUSEORDER = 'create_warehouse_order';	///创建线上发货物流订单
    const EVENT_GETONLINELOGISTICSINFO = 'get_online_logistics_info';	//获取线上发货物流信息
    
    const SERVICE_CONFIRM_SHIPPED = 1; //提前确认发货
    const SERVICE_UPLOAD_TRACK    = 2; //上传跟踪号
    
    /**@var 付款到上传的小时数*/
    const HOUR_UPLOAD = 48;
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var string 异常信息*/
    public $exception = null;
    
    /** @var string 错误code*/
    public $errorcode = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 切换数据库连接
     * @see AliexpressModel::getDbKey()
     */
    public function getDbKey() {
        return 'db_oms_order';
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order';
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }
    
    /**
     * @desc 设置错误code
     * @param string $errorcode
     */
    public function setErrorcode($errorcode){
    	$this->errorcode = $errorcode;
    }
    
    /**
     * @desc 获取错误code
     * @return string
     */
    public function getErrorcode(){
    	return $this->errorcode;
    }

    /**
     * 获取速卖通线上渠道需上传跟踪号订单
     */
    public function getAliWaitUploadOrderList1($accountId,$platformOrderId) {
        //$endPayTime = date('Y-m-d H:i:s', strtotime('-12 hours'));
        $startTime = date('Y-m-d H:i:s', strtotime('-20 days'));
        $todayEndTime = date('Y-m-d 09:00:00');
        $obj = Order::model()->dbConnection->createCommand()
            ->select('O.order_id,O.platform_code,O.platform_order_id,O.account_id,O.paytime
                ,P.package_id,P.ship_code,P.real_ship_type,P.track_num,P.ship_status')
            ->from('ueb_order O')
            ->leftJoin('ueb_order_detail OD', 'OD.order_id = O.order_id')
            ->leftJoin('ueb_order_package_detail PD', 'PD.order_detail_id = OD.id')
            ->leftJoin('ueb_order_package P', 'P.package_id = PD.package_id')
            ->where("O.account_id = '{$accountId}' and O.platform_code = '".Platform::CODE_ALIEXPRESS."' 
                and O.paytime >= '{$startTime}' and O.paytime <= '{$todayEndTime}' ")
            ->andWhere("P.ship_status != 5 and P.is_confirm_shiped = 0 and P.track_num != '' ")
            ->andWhere(array('in', 'P.ship_code', self::getAliOnlineCarrierList()))
            ->group('O.order_id')
            ->order('O.ori_pay_time')
            ->limit('2000');
        !empty($platformOrderId) && $obj->andWhere("O.platform_order_id = '{$platformOrderId}'");
        //echo $obj->text;//exit;
        return $obj->queryAll();
    }

    /**
     * 获取速卖通非线上渠道需上传跟踪号订单
     */
    public function getAliWaitUploadOrderList2($accountId,$platformOrderId) {
        $waitPayTime = date('Y-m-d H:i:s', strtotime('-36 hours'));
        $startTime = date('Y-m-d H:i:s', strtotime('-20 days'));
        $todayEndTime = date('Y-m-d 08:00:00');
        $obj = Order::model()->dbConnection->createCommand()
            ->select('O.order_id,O.platform_code,O.platform_order_id,O.account_id,O.paytime
                ,P.package_id,P.ship_code,P.real_ship_type,P.track_num,P.track_num2,P.ship_status')
            ->from('ueb_order O')
            ->leftJoin('ueb_order_detail OD', 'OD.order_id = O.order_id')
            ->leftJoin('ueb_order_package_detail PD', 'PD.order_detail_id = OD.id')
            ->leftJoin('ueb_order_package P', 'P.package_id = PD.package_id')
            ->where("O.account_id = '{$accountId}' and O.platform_code = '".Platform::CODE_ALIEXPRESS."' 
                and O.paytime >= '{$startTime}' and O.paytime <= '{$todayEndTime}' ")
            ->andWhere(" 
                (P.ship_status = 1 and P.is_confirm_shiped = 0)
                or (P.ship_status = 0 and P.is_confirm_shiped = 0 and O.complete_status = 2 and O.ori_pay_time <= '{$waitPayTime}' and P.track_num != '')
             ")
            //->andWhere("P.is_confirm_shiped = 0")
            ->andWhere(array('not in', 'P.ship_code', self::getAliOnlineCarrierList()))
            //->andWhere(array('in', 'P.ship_code', self::getAliCanShipCarrierList()))
            ->group('O.order_id')
            ->order('O.ori_pay_time')
            ->limit('1000');
        !empty($platformOrderId) && $obj->andWhere("O.platform_order_id = '{$platformOrderId}'");
       // echo $obj->text;//exit;
        return $obj->queryAll();
    }

    /**
     * 获取速卖通需提前发货订单列表
     * @param string $limit
     */
    public function getAliNeedAdvanceShippedOrderList($accountId,$gmtime,$orderId,$limit = null) {
        if(!$accountId) return null;
        $endPayTime = date('Y-m-d H:i:s', strtotime('-36 hours'));
        $startTime = date('Y-m-d H:i:s', strtotime('-10 days'));
        //$payTimeStart = '2016-04-14 00:00:00';
        //$payTimeEnd = '2016-04-16 00:00:00';
        $obj = Order::model()->dbConnection->createCommand()
            ->select('OA.*,O.paytime,O.account_id')
            ->from("ueb_aliexpress_order_advance_ship_match OA")
            ->join("ueb_order O", 'O.order_id = OA.order_id')
            ->where("OA.confirm_shiped_status = 0 and OA.type = 1 and track_num != '' and OA.ship_code != '' ")
            ->andWhere("O.platform_code = '".Platform::CODE_ALIEXPRESS."' and O.ship_status=".Order::SHIP_STATUS_NOT)
            ->andWhere("O.paytime >= '{$startTime}' and O.ori_pay_time <= '{$endPayTime}' ")
            ->order('O.paytime');
        
        if (!empty($limit)) {
            $obj->limit((int)$limit, 0);
        } else {
            $obj->limit('2000');
        }
        
        if(!empty($orderId)){
            $obj->andWhere('O.order_id like "'.$orderId.'%"');
        }

        //echo $obj->text;//exit;
        
        return $obj->queryAll();
    }
    
    /**
     * @desc 获取需要交运跟踪号的订单
     * @param string $accountID
     * @param int $dayHanding
     * @return array
     */
    public function getOrdersNeedUpload($accountID, $dayHanding=self::HOUR_UPLOAD){
        return Order::model()->dbConnection->createCommand()
                    ->select('o.*')
                    ->from(Order::model()->tableName().' AS o')
                    ->leftJoin(OrderTrack::model()->tableName().' AS l', 'o.order_id = l.order_id')
                    ->where('l.order_id IS NULL')
                    ->andWhere('o.account_id = "'.$accountID.'"')
                    ->andWhere('o.platform_code = "'.Platform::CODE_ALIEXPRESS.'"')
                    //->andWhere('o.order_id="CO150915008619"')
                    ->andWhere('DATEDIFF(now(),o.paytime) > '.($dayHanding/24))
                    ->queryAll();
    }

    /**
     * 获取支持线上发货的订单
     * @param	array	$shipCodes
     * @return	array
     */
    public function getOnlineShipOrderList($shipCodes=array()) {
    	if (empty($shipCodes)) {
    		$shipCodes = array('wy_01','wy_02');
    	}
    	$orderModel = Order::model();
    	$list = $orderModel->dbConnection->createCommand()
    		->select('O.*')
    		->from($orderModel->tableName().' O')
    		->leftJoin(OrderPackageDetail::model()->tableName().' PD', 'PD.order_id = O.order_id')
    		->leftJoin(OrderPackage::model()->tableName().' P', 'P.package_id = PD.package_id')
    		->leftJoin(OrderCreateOnline::model()->tableName().' OC', 'OC.order_id = O.order_id')
    		->where('O.platform_code = "'.Platform::CODE_ALIEXPRESS.'" ')
    		->andWhere('OC.order_id is NULL')
    		//->andWhere(array('in', 'P.ship_code', $shipCodes))
    		->andWhere('O.order_id = "CO15092318572"')
    		->group('O.order_id')
    		->limit('2')
    		->queryAll();
//     	var_dump($list);exit();
    	return $list;
    }
    
    /**
     * 获取未返回国际运单号订单
     * @return	array
     */
    public function getNoRetTrackNumOrderList() {
    	$model = OrderCreateOnline::model();
    	$list = $model->dbConnection->createCommand()
    		->select('O.*')
    		->from($model->tableName().' OC')
    		->leftJoin(Order::model()->tableName().' O', ' O.order_id = OC.order_id')
    		->where('ret_track_num is NULL or ret_track_num = ""')
    		->limit('2')
    		->queryAll();
    	return $list;
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }
    
    /**
     * @desc 声明发货
     * @param array $shippedData
     */
    public function uploadSellerShipment( $shippedData ){
    	
    	try {
    		$request = new SellerShipmentRequest();
    		$request->setServiceName($shippedData['serviceName']);
    		$request->setLogisticsNo($shippedData['logisticsNo']);
    		$request->setOutRef($shippedData['outRef']);
    		$request->setWebsite($shippedData['trackingWebsite']);
    		$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
    		if ( $request->getIfSuccess() ) {
    			return true;
    		} else {
    			$this->setExceptionMessage($request->getErrorMsg());
    			$this->setErrorcode($request->getErrorCode());
    			return false;
    		}
    	} catch (Exception $e) {
    		$this->setExceptionMessage($e->getMessage());
    		return false;
    	}
    	return true;
    }
    
    /**
     * 修改声明发货
     * @param	array	$shippedData
     * @return	bool
     */
    public function modifySellerShipment( $shippedData ) {
    	try {
    		$request = new SellerModifiedShipmentRequest();
    		$request->setOldServiceName($shippedData['oldServiceName']);
    		$request->setOldLogisticsNo($shippedData['oldLogisticsNo']);
    		$request->setNewServiceName($shippedData['newServiceName']);
    		$request->setNewLogisticsNo($shippedData['newLogisticsNo']);
    		$request->setOutRef($shippedData['outRef']);
    		$request->setTrackingWebsite($shippedData['trackingWebsite']);
    		$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
    		if ( $request->getIfSuccess() ) {
    			return true;
    		} else {
    			$this->setExceptionMessage($request->getErrorMsg());
    			$this->setErrorcode($request->getErrorCode());
    			return false;
    		}
    	} catch (Exception $e) {
    		$this->setExceptionMessage($e->getMessage());
    		return false;
    	}
    	return true;
    }
    
    /**
     * @desc 根据物流商获取声明发货信息
     * @param unknown $serverName
     */
    public static function getSellerShipmentInfoByServerName($serverName){
        $webSite = '';
        switch ($serverName){
            case Logistics::CODE_GHXB_YWBJ:
            case Logistics::CODE_CM_YWBJ:
                $webSite = 'http://www.yw56.com.cn/english/DIY.asp?orderid=';
                break;
            case Logistics::CODE_GHXB_SG:
                $webSite = '';
                break;
            case Logistics::CODE_BE:
                $webSite = 'http://www.bpostinternational.com/nl/e-shipper/track_and_trace.html';
                break;
            case Logistics::CODE_CE:
             	$webSite = 'http://tracking.parcelforce.net/Pfw/index.html?HOME_DISPLAY=public';
               	break;
            case Logistics::CODE_CM_HUNGARY:
            case Logistics::CODE_CM_XBYZX_YSD:
            case Logistics::CODE_CM_DGZX_YSD:
            	$webSite = 'http://www.trackingmore.com/oneworldexpress-tracking.html';
            	break;
            case Logistics::CODE_CM_EHUNGARY:
            case Logistics::CODE_GHXB_EHUNGARY:
                $webSite = 'http://www.17track.net/en/result/express-details-100011.shtml';
                break;
            case Logistics::CODE_CM_SF:
            case Logistics::CODE_GHXB_SF:
               	$webSite = '';
               	break;
            case Logistics::CODE_GHXB_SFEU:
            	$webSite = '';
            	break;
            case Logistics::CODE_CM_SFEU:
             	$webSite = 'http://intl.sf-express.com/?a=trackEn';
               	break;
            case Logistics::CODE_CM_ZX_SYB:
            case Logistics::CODE_CM_SY_MY:
            case Logistics::CODE_CM_SYB:
            	$webSite = 'http://www.17track.net/en/result/post-details.shtml?nums';
            	break;
            case Logistics::CODE_CM_CNXB_E:
            case Logistics::CODE_CM_CNXB:
            	$webSite = 'http://4pl.routdata.com/ecom/MailTrack/index_en.jsp';
            	break;
            case Logistics::CODE_CM_JRXB:
            	$webSite = 'www.17track.net/en/track?nums';
            	break;
            case Logistics::CODE_GHXB_JR:
            	$webSite = '';
            	break;
            case Logistics::CODE_EUB_JIETE:
           		$webSite = '';
           		break;
            case Logistics::CODE_GHXB_GYHL_HK:
            	$webSite = '';
            	break;
            case Logistics::CODE_GHXB_GYHL:
            	$webSite = '';
            	break;
            case Logistics::CODE_CM_GYHL_HK:
            	$webSite = '';
            	break;
           	case Logistics::CODE_CM_MET:
           		$webSite = '';
         		break;
         	case Logistics::CODE_EMS:
         		$webSite = 'http://www.17track.net/en';
         		break;
         	case Logistics::CODE_FEDEX_IE_HK:
         		$webSite = 'http://www.fedex.com/cn/';
         		break;
         	case Logistics::CODE_FEDEX_IP_HK:
         		$webSite = 'http://www.fedex.com/cn/';
         		break;
         	case Logistics::CODE_FEDEX_IE:
         		$webSite = 'http://www.fedex.com/cn/';
         		break;
         	case Logistics::CODE_FEDEX_IP:
         		$webSite = 'http://www.fedex.com/cn/';
        		break;
         	case Logistics::CODE_KD_TOLL:
        		$webSite = 'http://www.tollgroup.com/onetoll';
         		break;
         	case Logistics::CODE_DHL:
         		$webSite = 'http://www.dhl.com/';
         		break;
         	case Logistics::CODE_KD_SFEU:
         		$webSite = 'http://intl.sf-express.com/?a=trackEn';
         		break;
         	case Logistics::CODE_FU_GHXB:
         		$webSite = '';
         		break;
         	case Logistics::CODE_CM_SF_E:
         	case Logistics::CODE_GHXB_SF_E:
         		$webSite = '';
         		break;
         	case Logistics::CODE_UKZX_3HPA:
         		$webSite = 'http://www.17track.net/en';
         		break;
         	case Logistics::CODE_GHXB_PUTIAN:
         		$webSite = '';
         		break;
         	case Logistics::CODE_GHXB_PUTIAN_E:
         		$webSite = '';
         		break;
         	case Logistics::CODE_GHXB_YUNTUDD:
         		$webSite = '';
         		break;
         	case Logistics::CODE_GHXB_HK:
         		$webSite = '';
         		break;
         	case Logistics::CODE_CM_PLUS_SGXB:
         		$webSite = 'http://xoms.4px.com/home/?locale=en_US';
         		break;
         	case Logistics::CODE_CM_YO_EST:
         		$webSite = 'http://www.17track.net/en';
         		break;
         	case Logistics::CODE_CM_DGYZ:
         		$webSite = 'http://intmail.11185.cn/zdxt/yjcx/';
         		break;
         	case Logistics::CODE_CM_HK:
         		$webSite = 'http://www.17track.net/en';
         		break;
         	case Logistics::CODE_CM_ON_SFOZ:
         		$webSite = 'http://intl.sf-express.com/?a=trackEn';
         		break;
         	case Logistics::CODE_CM_GB_SFOZ:
         		$webSite = 'http://intl.sf-express.com/?a=trackEn';
         		break;
         	case Logistics::CODE_GHXB_GB_SFOZ:
         		$webSite = 'http://www.17track.net/en/';
         		break;
         	case Logistics::CODE_GHXB_ON_SFOZ:
         		$webSite = 'http://www.17track.net/en/';
      			break;
         	case Logistics::CODE_GHXB_US_SFOZ:
         		$webSite = 'http://www.17track.net/en/';
         		break;
         	case Logistics::CODE_CM_JNA_LTZX:
         		$webSite = 'https://cn.etowertech.com/';
         		break;
         	case Logistics::CODE_CM_AZ_LTZX:
         		$webSite = 'https://cn.etowertech.com/';
         		break;
            case Logistics::CODE_CM_YD_LTZX:
                $webSite = 'https://cn.etowertech.com/';
                break;
            case Logistics::CODE_CM_XXL_LTZX:
                $webSite = 'https://cn.etowertech.com/';
                break;
         	case Logistics::OODE_SWYH_ALI_GUA:
         		$webSite = 'http://www.17track.net/en/';
         		break;
         	case Logistics::CODE_GHXB_EST:
         		$webSite = 'http://www.17track.net/en/';
         		break;
       		case Logistics::CODE_CM_EST:
       			$webSite = 'http://www.ruston.cc/ru/index_ru.html';
         		break;
       		case Logistics::CODE_GHXB_UKECSLTYD:
         		$webSite = 'http://www.17track.net/en/';
         		break;
         	case Logistics::CODE_GHXB_YW_TEQGH:
         		$webSite = 'http://www.17track.net/en/';
         		break;
         	case Logistics::CODE_CM_YW_TEQXB:
         		$webSite = 'http://www.yw56.com.cn';
         		break;
            case Logistics::CODE_CM_PTY_DSFZX:
            case Logistics::CODE_CM_BLS_DSFZX:
            case Logistics::CODE_CM_YDL_DSFZX:
            case Logistics::CODE_CM_XBY_DSFZX:
            case Logistics::CODE_CM_FG_DSFZX:
                $webSite = 'http://www.4px.com/';
                break;       
            default:
            	$webSite = 'http://www.17track.net/zh-cn/result/post.shtml?nums';
            	break;
            
        }
        return array(
                'website'   => $webSite,
        );
    }
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
    	$this->_accountID = $accountID;
    }

    /**
     * @desc 按指定大小$n 截取数组
     * @param unknown $n
     * @return multitype:unknown multitype:
     */
    public function splitByn($ordArr,$n){
        $newArr = array();
        $count = ceil(count($ordArr)/$n);
        for($i=0;$i<=$count-1;$i++){
            if($i == ($count-1)){
                $newArr[] = $ordArr;
            }else{
                $newArr[] = array_splice($ordArr,0,$n);
            }
        }
        return $newArr;
    }

    /**
     * 生成待标记发货数据  [非上传真实单号部分]
     * @author Rex 2016-09.28 封装
     */
    public function createWaitShippedData($accountId,$aliAccountId,$gmtime,$orderId,$limit) {
        $excludeShipCode = array( Logistics::CODE_DHTD_DHL,Logistics::CODE_DHTD_IP,Logistics::CODE_DHTD_IE,Logistics::CODE_FEDEX_IE,Logistics::CODE_KD_TOLL,Logistics::CODE_EMS,Logistics::OODE_CM_ALI_DGYZ );
        $excludeShipCode = MHelper::getNewArray($excludeShipCode);

        //付款时间超过3天的订单
        $needOrderList = $this->getAliNeedAdvanceShippedOrderList($aliAccountId,$gmtime,$orderId,$limit);
        var_dump(count($needOrderList));//exit;

        $model = AliexpressOrderMarkShippedLog::model();
        foreach ($needOrderList as $value) {
            //var_dump($value);//exit;

            $markShippedInfo = $model->getInfoRowByOrderId($value['order_id']);
            if (!$markShippedInfo) {

                $accountInfo = AliexpressAccount::model()->getAccountInfoById($value['account_id']);
                $toCarrierCode  = LogisticsPlatformCarrier::model()->getCarrierByShipCode($value['ship_code'],Platform::CODE_ALIEXPRESS);
                $toType = AliexpressOrderMarkShippedLog::TYPE_FAKE;

                if (empty($value['order_id']) || empty($value['paytime']) || empty($value['track_num']) || empty($toCarrierCode)) {
                    continue;
                }

                $markOrderData = array(
                    'account_id'        => $accountInfo['id'],
                    'platform_order_id' => $value['platform_order_id'],
                    'order_id'          => $value['order_id'],
                    'package_id'        => $value['package_id'],
                    'paytime'           => $value['paytime'],
                    'status'            => AliexpressOrderMarkShippedLog::STATUS_DEFAULT,
                    'type'              => $toType,
                    'track_num'         => $value['track_num'],
                    'carrier_code'      => $toCarrierCode,
                    'ship_code'         => $value['ship_code'],
                    'create_time'       => date('Y-m-d H:i:s'),
                );

                $ret = $model->saveNewData($markOrderData);

                if ($ret) {
                    UebModel::model('OrderAdvanceShipMatch')->updateByPk($value['order_id'],array('confirm_shiped_status'=>1,'confirm_shiped_time'=>date('Y-m-d H:i:s')));
                }
            } else {
                UebModel::model('OrderAdvanceShipMatch')->updateByPk($value['order_id'],array('confirm_shiped_status'=>1,'confirm_shiped_time'=>date('Y-m-d H:i:s')));
            }

        }

        echo '<br/>ok';

/*        $tmpOrderIds = array();
        $tmpOrderInfos = array();
        foreach($orderInfos as $key => $val){
            $tmpOrderIds[] = $val['order_id'];
            $tmpOrderInfos[$val['order_id']] = $val;
        }

        //var_dump($tmpOrderInfos);exit;
        $orderIdArray = $this->splitByn($tmpOrderIds,500);
        $tmpMarkOrdIds = array();
        $needMarkOrderIds = array();
        //var_dump($orderIdArray);
        foreach( $orderIdArray as $key => $val ){
            //查出有包裹未上传跟踪号的订单
            $unUploadTrackOrders = OrderPackage::model()->getAliUnUploadTrackOrders( MHelper::simplode($val),MHelper::simplode($excludeShipCode) );
            //var_dump($unUploadTrackOrders);
            //查出还没有生成包裹的订单
            $unPackageOrders = OrderPackage::model()->getAliUnCreatePackageOrders( MHelper::simplode($val) );
            //var_dump($unPackageOrders);
            $needMarkOrderIds = array_merge($needMarkOrderIds,$unUploadTrackOrders,$unPackageOrders);
            //var_dump($needMarkOrderIds);
            //查询订单是否确认发货过
            $tmpRet = AliexpressOrderMarkShippedLog::model()->getInfoByOrderIds( MHelper::simplode(array_merge($unUploadTrackOrders,$unPackageOrders)),'order_id' );
            foreach( $tmpRet as $v ){
                $tmpMarkOrdIds[] = $v['order_id'];
            }
        
        }
        //var_dump($needMarkOrderIds);exit;
        //记录此次需要提前上传跟踪号的订单
        foreach( $needMarkOrderIds as $key => $val ){
            if( in_array($val,$tmpMarkOrdIds) ){
                unset($orderInfos[$key]);
                continue;
            }
            //var_dump($tmpOrderInfos);exit();
            $markOrderData = array(
                'account_id'        => $accountId,
                'platform_order_id' => $tmpOrderInfos[$val]['platform_order_id'],
                'order_id'          => $val,
                'paytime'           => $tmpOrderInfos[$val]['paytime'],
                'status'            => AliexpressOrderMarkShippedLog::STATUS_DEFAULT,
                'type'              => AliexpressOrderMarkShippedLog::TYPE_DEFAULT,
                'track_num'         => '',
                'carrier_code'      => '',
            );
            $markModel = new AliexpressOrderMarkShippedLog();
            $markModel->saveNewData($markOrderData);
        }*/
    }

    /**
     * 待标记发货数据 匹配跟踪号
     * @author Rex
     */
    public function matchTrackToWaitShippedData($accountId,$aliAccountId,$orderId,$limit) {
/*        $model = UebModel::model('AliexpressOrderMarkShippedLog');
        $notMatchOrderList = $model->getNotMatchWaitingMarkShipOrder($accountId,$orderId,$limit);
        var_dump(count($notMatchOrderList));//exit;
        foreach ($notMatchOrderList as $key => $value) {
            //var_dump($value);
            if (empty($value['id']) || !empty($value['track_num']) || !empty($value['carrier_code'])) {
                continue;
            }

            //开始取号  
            $toTrackNum = '';
            $toCarrierCode = '';
            $toType = '';
            $toShipCode = '';
            $toPackageId = '';
            $flagNext = true;

            $retPreInfo = UebModel::model('OrderPackage')->getPrePackageInfoByOrderId($value['order_id']);
            if ($flagNext && $retPreInfo['track_num']) {
                $toTrackNum = $retPreInfo['track_num'];
                $toCarrierCode  = LogisticsPlatformCarrier::model()->getCarrierByShipCode($retPreInfo['ship_code'],Platform::CODE_ALIEXPRESS );
                $toType = AliexpressOrderMarkShippedLog::TYPE_FAKE;
                $toShipCode = $retPreInfo['ship_code'];
                $toPackageId = $retPreInfo['package_id'];
                $flagNext = false;
            }
            if ($flagNext) {
/*                $retRandTn = $this->getRandTrackNum(3);//取3次随机跟踪号，直到成功
                if ($retRandTn['ret'] == true && !empty($retRandTn['trackVirtual'])) {
                    $toTrackNum = $retRandTn['trackVirtual'];
                    $toCarrierCode = 'CPAM';
                    $toType = AliexpressOrderMarkShippedLog::TYPE_FAKE;
                }*/
                //var_dump($value['order_id']);
/*                $orderInfo = UebModel::model('Order')->getInfoByOrderId($value['order_id']);
                //var_dump($orderInfo);
                $packageInfoOtherList = UebModel::model('OrderPackage')->getNotAliShippedTrackNum($orderInfo);*/
                //var_dump($packageInfoOtherList);exit;
/*                foreach ($packageInfoOtherList as $packageInfoOther) {
                    //var_dump($packageInfoOther['ship_code']);
                    $toTrackNum = $packageInfoOther['track_num'];

                    $markShipInfo = UebModel::model('AliexpressOrderMarkShippedLog')->getInfoByTrackNum($toTrackNum);
                    if ($markShipInfo) {
                        continue;
                    }

                    $toCarrierCode  = LogisticsPlatformCarrier::model()->getCarrierByShipCode($packageInfoOther['ship_code'],Platform::CODE_ALIEXPRESS);
                    $toType = AliexpressOrderMarkShippedLog::TYPE_SHAM_SHIP;
                    $toShipCode = $packageInfoOther['ship_code'];
                    $toPackageId = $packageInfoOther['package_id'];
                    $flagNext = false;
                }*/

/*            }

            $isCanUpload = $this->checkCarrierIsCanUpload($toShipCode);

            //var_dump($flagNext,$toShipCode,$toTrackNum,$toCarrierCode,$toType);exit;

            if ($isCanUpload && !empty($toTrackNum) && !empty($toCarrierCode) && $toType > 0) {
                $markShipInfo = UebModel::model('AliexpressOrderMarkShippedLog')->getInfoByTrackNum($toTrackNum);
                if (!$markShipInfo) {
                    $updateData = array(
                        'package_id'    => $toPackageId,
                        'track_num'     => $toTrackNum,
                        'carrier_code'  => $toCarrierCode,
                        'ship_code'     => $toShipCode,
                        'type'          => $toType,
                    );
                }
                //var_dump($updateData);
                
            }

            //exit('AAA');

            $updateData['match_time'] = date('Y-m-d H:i:s');
            $ret = $model->updateByPk($value['id'],$updateData);*/

            //exit();
/*        }*/
    }

    /**
     * 取随机号 [假单号]
     */
    public function getRandTrackNum($n=0){
        $rs = false;
        while(!$rs){//取3次随机跟踪号，直到成功
            $n--;
            $trackVirtual = AutoCode::getCode('fake_track_num');
            $check = AliexpressOrderMarkShippedLog::model()->getInfoByTrackNum( $trackVirtual,'id' );
            if(!$check['id']){
                $rs = true;
            }
            if($n<=0) break;
        }
        return array('ret'=>$rs,'trackVirtual'=>$trackVirtual);
    }

    /**
     * 检查渠道是否可上传跟踪号
     */
    public function checkCarrierIsCanUpload($shipCode) {
        $shipCode = strtolower($shipCode);
        $notCanList = self::getAliNotCanShipLogistics();
        if (stripos($shipCode, 'ali') !== false) {
            return true;
        }
        if (in_array($shipCode, self::getAliOnlineCarrierList())) {
            return true;
        }
        if (in_array($shipCode, $notCanList)) {
            return false;
        }
        return true;

    }

    /**
     * 速卖通线上渠道配置
     * @author Rex
     * @since  2016-10-09 10:26:00
     */
    static public function getAliOnlineCarrierList() {
        return array(
            'cm_ali_dgyz',      #速卖通东莞邮局小包
            'cm_ali_gzyz',      #速卖通广州邮局小包
            'cm_ali_sy_hkxb',   #速卖通顺友航空经济小包
            'cm_ali_xyjjxb',    #速卖通递四方新邮经济小包
            'cm_ali_xyxb',      #速卖通深圳燕文西邮经济小包
            'cm_ali_yyxb',      #速卖通深圳燕文英邮经济小包
            'cm_ali_sfxb',      #速卖通顺丰国际经济小包
            'ghxb_ali_zpgh',    #速卖通漳浦邮局挂号
        	'cm_ali_zpxb',		#速卖通漳浦邮局小包
        );
    }

    /**
     * 速卖通平台上不认可渠道配置
     * @author  Rex
     * @since   2016-10-11 16:18:00
     */
    static public function getAliNotCanShipLogistics() {
        return array(
           'cm_dhl',               #A2B香港DHL小包
            'cm_deyz',              #香港A2B德邮英国小包
            'cm_zxyz',              #香港A2B泽西邮局小包
            'cm_e_ptxb',            #莆田邮局带电小包
            'cm_ptxb',              #莆田邮局小包
            'cm_qz_ddxb',           #泉州邮局带电小包
            'cm_qzyz',              #泉州邮局小包
            'cm_yd_ltzx',           #上海利通印度专线
            'cm_yd_hlyz',           #深圳运德荷兰邮局小包
            'cm_plus_sgxb',         #深圳递四方新加坡小包
            'cm_bls_dsfzx',         #深圳递四方比利时专线
            'cm_fg_dsfzx',          #深圳递四方法国专线
            'cm_pty_dsfzx',         #深圳递四方葡萄牙专线
            'cm_xby_dsfzx',         #深圳递四方西班牙专线
            'cm_ydl_dsfzx',         #深圳递四方意大利专线
            //'ghxb_ukecsltyd',     #深圳易时达英国经济专线
            'cm_dh_dhl',            #香港DHL小包
            'cm_hkxb_jhd',          #深圳京华达香港小包
            'cm_dgyz',              #东莞邮局小包
           // 'cm_xxl_ltzx',          #上海利通新西兰专线
           // 'cm_jna_ltzx',          #上海利通加拿大专线
          //  'cm_az_ltzx',           #上海利通澳洲专线
        	 'cm_yzy_szyjxb',       #邮之翼深圳邮局小包
        	 'cm_jrxb',				#广州邮局小包
        );
    }

    /**
     * 速卖通可上传跟踪号渠道配置
     * @author Rex
     * @since  2016-10-07 17:17:00
     */
/*    static public function getAliCanShipCarrierList() {
        return array(
            'cm_ali_dgyz',      #速卖通东莞邮局小包

            'cm_gb_sfoz',       #深圳顺丰电商欧洲小包
            'cm_e_sf',          #深圳顺丰立陶宛带电小包
            'cm_sf',            #深圳顺丰立陶宛小包
            'cm_on_sfoz',       #深圳顺丰欧洲十国小包
            'cm_yw_teqxb',      #燕文土耳其小包
            'cm_zx_syb',        #深圳顺友顺邮宝Plus小包
            'cm_yo_est',        #黑龙江俄速通亚欧小包
            'cm_4px_met',       #深圳递四方美E通专线
            'cm_dh_dhl',        #香港DHL小包
            'cm_hkxb_jhd',      #深圳京华达香港小包
            'cm_dgyz',          #东莞邮局小包

            'ghxb_dgyz',        #东莞邮局挂号
            'ghxb_e_ptgh',      #莆田邮局带电挂号
            'ghxb_ptgh',        #莆田邮局挂号
            'ghxb_qz_ddgh',     #泉州邮局带电挂号
            'ghxb_qzyz',        #泉州邮局挂号
            'ghxb_hk',          #深圳京华达香港挂号
            'ghxb_gb_sfoz',     #深圳顺丰电商欧洲挂号
            'ghxb_e_sf',        #深圳顺丰立陶宛带电挂号
            'ghxb_sf',          #深圳顺丰立陶宛挂号
            'ghxb_us_sfoz',     #深圳顺丰美国挂号
            'ghxb_on_sfoz',     #深圳顺丰欧洲十国挂号
            'ghxb_yw_teqgh',    #燕文土耳其挂号
            'ghxb_yo_est',      #黑龙江俄速通亚欧挂号
            'ghxb_sg',          #深圳递四方新加坡挂号
            'ghxb_yd_hlyz',     #深圳运德荷兰邮局挂号

            'kd_dhtd_dhl',      #深圳敦豪通达DHL快递
            'kd_fedexie',       #深圳联邦FED IE快递
            'kd_ems',           #深圳邮局EMS快递
            
            'eub_jiete',        #长沙邮局E邮宝专线
            
        );
    }*/

}