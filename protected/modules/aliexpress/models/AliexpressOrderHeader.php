<?php
/**
 * ALiexpress OrderHeader Model
 * @author	Rex
 * @since	2016-05-26
 */
class AliexpressOrderHeader extends AliexpressModel{

    //同步到oms标记
    const TO_OMS_OK   = 1;    #已同步至OMS
    const TO_OMS_NO   = 0;    #未同步至OMS
    const TO_OMS_FAIL = -1; #同步至OMS 失败

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_order_header';
    }
    
    /**
     * 保存订单主信息
     * @author	Rex
     * @since	2016-05-26
     */
    public function addNewData($data) {
    	$model = new self();
    	foreach ($data as $key => $value) {
    		$model->setAttribute($key, $value);
    	}
    	return $model->save();
    }    

    /**
     * @desc 转换时间数据格式 转换北京时间
     * @param date $dateOnline
     */
    public function formatDateLocal($dateOnline){
        $localTime = substr($dateOnline, 0, 14);
        $timezone = substr($dateOnline, -5, 3);
        $zonetimediff = (8 - (int)$timezone) * 3600;
        return date('Y-m-d H:i:s', strtotime($localTime) + $zonetimediff);
    }
    /**
     * @desc 转换时间
     * @param unknown $dateOnline
     * @return string
     */
    public function formatDate($dateOnline){
        $localTime = substr($dateOnline, 0, 14);
        return date('Y-m-d H:i:s', strtotime($localTime));
    }     

    /**
     * [getAliOrderList description]
     * @param  [type] $list [description]
     * @return [type]       [description]
     */
    public function getAliOrderList($list){
        if(floatval($list['amount']) ==0 || floatval($list['amount']) ==null || floatval($list['amount']) ==''){
            return false;
        }
        if(!trim( $list['buyer_login_id'])){
            return false;
        }

        //判断是否为0
        $actualAmount = $list['actual_payment_amount'];
        if($list['actual_payment_amount'] == 0){
            $actualAmount = $list['amount'];
        }

        //计算运费
        if($list['logistics_amount'] > 0){
            $shipCost = $actualAmount - ($list['init_order_amount'] - $list['logistics_amount']);
            if($shipCost <= 0){
                $shipCost = $list['logistics_amount'];
            }
        }else{
            $shipCost = $list['logistics_amount'];
        }

        $data = array(
            'order_id'              => $list['order_id'],
            'platform_code'         => trim($list['platform_code']),
            'platform_order_id'     => $list['platform_order_id'],
            'account_id'            => trim($list['account_id']),
            'log_id'                => $list['log_id'] == 0 ? 999 : $list['log_id'],
            'order_status'          => $list['order_status'],//后面需讨论
            'buyer_id'              => trim( $list['buyer_login_id']),
            'timestamp'             => date('Y-m-d H:i:s'),
            'created_time'          => $this->formatDate($list['gmt_create']),
            'last_update_time'      => $this->formatDate($list['gmt_modified']),
            'paytime'               => $this->formatDate($list['gmt_pay_success']),
            'currency'              => $list['currency_code'],
            'payment_status'        => Order::PAYMENT_STATUS_END,   //后续如需同步未付款订单，此处需修改
            'email'                 => '',//暂无
            'ship_cost'             => floatval($shipCost),//运费
            'subtotal_price'        => 0,//产品总金额,由订单明细表计算
            'total_price'           => floatval($actualAmount), //订单总额,包含成交费
            'final_value_fee'       => 0,//由订单明细表计算
            'insurance_amount'      => 0,//运费险,无
            'ship_country'          => trim($list['receipt_country']),
            'ship_country_name'     => '',  //下面处理
            'ship_phone'            => isset($list['receipt_mobile_no']) && !empty($list['receipt_mobile_no']) ? trim($list['receipt_mobile_no']) : trim($list['receipt_phone_country']).'-'.trim($list['receipt_phone_area']).'-'.trim($list['receipt_phone_number']),
            'ship_name'             => trim($list['receipt_contact_person']),
            'ship_street1'          => trim($list['receipt_detail_address']),
            'ship_street2'          => trim($list['receipt_address_2']),
            'ship_zip'              => isset($list['receipt_zip']) ? trim($list['receipt_zip']) : '',
            'ship_city_name'        => isset($list['receipt_city']) ? trim($list['receipt_city']) : '',
            'ship_stateorprovince'  => isset($list['receipt_province']) ? trim($list['receipt_province']) : '',
            'ship_code'             => '',//渠道,
            'complete_status'       => order::COMPLETE_STATUS_DEFAULT,  //在添加订单备注里添加这个状态
            'ori_create_time'       => $this->formatDateLocal($list['gmt_create']),
            'ori_update_time'       => $this->formatDateLocal($list['gmt_modified']),
            'ori_pay_time'          => $this->formatDateLocal($list['gmt_pay_success']),
        );
        
        if($data['ship_country'] == 'AL'){
            $data['ship_country'] = 'ALB';
            $data['ship_country_name'] = "Albania";
        }elseif($data['ship_country'] == 'MK'){
            $data['ship_country_name'] = "Macedonia";
        }elseif($data['ship_country'] == 'MNE') {
            $data['ship_country'] = 'ME';
            $data['ship_country_name'] = 'Montenegro';
        }elseif($data['ship_country'] == 'SRB') {
            $data['ship_country_name'] = 'Serbia';
        }else{
            $data['ship_country_name'] = Country::model ()->getEnNameByAbbr ( $data['ship_country'] );
        }

        //组装订单sku与销售关系数据
        $partFormatOrderInfo = array(
            'platform_order_id'     => $list['platform_order_id'],
            'account_id'            => trim($list['account_id']),
        );
   
        return array($data, $partFormatOrderInfo);
    }  

    /**
     * [getFormatOrderTransaction description]
     * @param  [type] $orderId [description]
     * @param  [type] $order   [description]
     * @return [type]          [description]
     */
    public function getFormatOrderTransaction($orderId,$order) {
        if ($order['amount'] <=0 || $order['account_id'] == ''
             || $order['currency_code'] == '' ) {
            return false;
        }

        //判断是否为0
        $actualAmount = $order['actual_payment_amount'];
        if($order['actual_payment_amount'] == 0){
            $actualAmount = $order['amount'];
        }

        $rtn = array(
            'transaction_id'            => $orderId,
            'parent_transaction_id'     => '',
            'order_id'                  => $orderId,
            'status'                    => 0,
            'order_pay_time'            => $this->formatDate($order['gmt_pay_success']),
            'last_update_time'          => $this->formatDate($order['gmt_modified']),
            'fee_amt'                   => 0,
            'amt'                       => $actualAmount,
            'account_id'                => $order['account_id'],
            'platform_code'             => $order['platform_code'],
            'currency'                  => $order['currency_code'],
            'payment_status'            => 'Completed',
            'receive_type'              => OrderTransaction::RECEIVE_TYPE_YES,
            'first'                     => 1,
            'is_first_transaction'      => 1,
            'is_entry'                  => 0,
            'modify_time'               => $this->formatDate($order['gmt_modified'])
        );  
        return array($rtn);
    }

    public function getFormatPaypalTransactionRecord($orderId,$order) {
        if ($order['amount'] <= 0 || $order['buyer_signer_full_name'] == ''
             || $order['currency_code'] == '' ) {
            return false;
        }

        $actualAmount = $order['actual_payment_amount'];
        if($order['actual_payment_amount'] == 0){
            $actualAmount = $order['amount'];
        }

        $rtn = array(
                'transaction_id'            => $orderId,
                'order_id'                  => $orderId,
                'receive_type'              => OrderTransaction::RECEIVE_TYPE_YES,
                'receiver_business'         => '',
                'receiver_email'            => 'unknown@vakind.com',
                'receiver_id'               => '',
                'payer_id'                  => '',
                'payer_name'                => $order['buyer_signer_full_name'],
                'payer_email'               => '',
                'payer_status'              => '',
                'parent_transaction_id'     => '',
                'transaction_type'          => '',
                'payment_type'              => '',
                'order_time'                => $this->formatDate($order['gmt_pay_success']),
                'amt'                       => $actualAmount,
                'fee_amt'                   => 0,
                'tax_amt'                   => 0,
                'currency'                  => $order['currency_code'],
                'payment_status'            => 'Completed',
                'note'                      => '',
                'modify_time'               => ''
        );
        return array($rtn);
    }

    /**
     * @desc 同步订单到oms系统
     * @param  array  $accountIdArr
     * @param  string  $platformOrderId
     * @param  boolean $showResult
     * @return int
     */
    public function syncOrderToOms($accountIdArr,$platformOrderId=null,$showResult=false) {
        $orderCount = 0;//同步订单数量
        $platformCode = Platform::CODE_ALIEXPRESS;
        $getOrderNoFailureFlag = array();//获取订单号失败超过10次则退出程序
        foreach ($accountIdArr as $accountID) {
            $flags  = array();
            $aliexpressOrderDownLoad = new AliexpressOrderDownLoad();
            $aliexpressOrderDownLoad->setAccountID($accountID);
            $isOverseasAccount = $aliexpressOrderDownLoad->isOverseasAccount();

            //按分组去取
            $orderList  = $aliexpressOrderDownLoad->getNotLoadOrderList($platformOrderId);
            if(empty($orderList)){
                echo $accountID.' 没有需要同步订单!<br>';
                break;
            }

            //同步订单
            $orderCount += count($orderList);
            foreach($orderList as $order) {
                $orderObj               = new Order();
                $flag                   = array();
                $platformOrderID        = $order['platform_order_id'];//平台订单号
                $memoInfo               = trim($order['memo']);
                $noteFlag               = !empty($memoInfo) ? true : false;            
                $orderId                = AutoCode::getCodeNew('order'); // 获取订单号   
                if (empty($orderId)) {
                    $flag               = array('errCode'=>'getCodeNew','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单号获取失败');
                    $getOrderNoFailureFlag[] = $platformOrderID;
                } else {
                    $orderId            = $orderId. "ALI"; // 获取订单号
                }     

                if (!empty($getOrderNoFailureFlag) && count($getOrderNoFailureFlag)>10) {
                    $errLogMessage = '获取订单号失败:'.implode(',',$getOrderNoFailureFlag);
                    echo $errLogMessage."<br>";
                    $this->addAliexpressLog(9000,AliexpressLog::STATUS_FAILURE,$errLogMessage);
                    return $orderCount;
                }                          
                
                $order['order_id']      = $orderId;
                $order['platform_code'] = $platformCode;

                //当buyer_signer_full_name为空，用receipt_contact_person代替
                if (trim($order['buyer_signer_full_name']) == '') {
                    $order['buyer_signer_full_name'] = $order['receipt_contact_person'];
                }                    

                //1. 根据平台订单号获取订单信息
                $orderInfo = Order::model()->getOrderInfoByPlatformOrderID($platformOrderID, $platformCode);

                //2. 订单已存在且完成状态不等于刚导入,跳出
                if (empty($flag)) {
                    if(!empty($orderInfo)){
                        echo $platformOrderID.'--'.'标记已存在！<br>';
                        $aliexpressOrderDownLoad->updateOrderByPlatformOrderID(array('is_to_oms' => AliexpressOrderHeader::TO_OMS_OK,'to_oms_time' => date('Y-m-d H:i:s'),'sys_note'=>'订单存在直接标记'), $platformOrderID);
                        continue;
                    }
                }

                //获取系统订单号
                if (!empty($orderInfo)) {
                    $orderId = $orderInfo['order_id'];
                }
                if ( $orderId == '' ) {
                    $flag = array('errCode'=>'order_id','errMessage'=>$platformOrderID.'取订单号失败');
                }

                //3. 对订单数据进行判断和组装
                if (empty($flag)) {
                    list($orderRetult, $partFormatOrderInfo) = UebModel::model('AliexpressOrderHeader')->getAliOrderList($order);
                    if ( empty($orderRetult) ) {
                        $flag = array('errCode'=>'formatOrderInfo','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单主表数据不完整，待补拉');
                    }
                }

                if (empty($flag) && $noteFlag ){
                    $orderRetult['complete_status'] = Order::COMPLETE_STATUS_PENGDING;
                }            

                //4. 获取订单详情
                if (empty($flag)) {
                    $orderDetailList = $aliexpressOrderDownLoad->getOrderListByOrderId($platformOrderID);
                    if ( empty($orderDetailList) ){
                        $flag = array('errCode'=>'AliOrderDetail','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单明细为空');
                    }                  
                }

                //验证数据并返回验证后的数据,对订单详情的数据进行判断
                if( empty($flag) ){
                    $orderDetails = UebModel::model('AliexpressOrderDetails')->getOrderDetailList($order,$orderDetailList,$orderId,$platformCode,$order['fee_rate'],$noteFlag,$isOverseasAccount);
                    if (empty($orderDetails)) {
                        $flag = array('errCode'=>'formatDetailInfos','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单明细数据不完整，待补拉');
                    }
                    if(!isset($orderDetails[$orderId]['orderExtendInfo'])) {
                        $flag = array('errCode'=>'orderExtendInfo','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单扩展表数据不完整，待补拉');
                    }               
                }   

                //验证订单交易   
                if( empty($flag) ){
                    $formatOrderTransactionInfos = UebModel::model('AliexpressOrderHeader')->getFormatOrderTransaction($orderId,$order);
                    if (empty($formatOrderTransactionInfos)) {
                        $flag = array('errCode'=>'formatOrderTransaction','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单交易数据不完整，待补拉');
                    }
                    $formatPaypalTransactionInfos = UebModel::model('AliexpressOrderHeader')->getFormatPaypalTransactionRecord($orderId,$order);
                     if (empty($formatPaypalTransactionInfos)) {
                        $flag = array('errCode'=>'formatPaypalTransactionRecord','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'订单付款数据不完整，待补拉');
                    }
                }  

                //开启事务
                $dbTransaction = $orderObj->dbConnection->beginTransaction();
                if (empty($flag)) {
                    try {
                        $commonOrder = new CommonOrder();

                        $orderDetailDatas = $orderDetails[$orderId]['data'];//订单明细表数据
                        $orderDetailExtDatas = $orderDetails[$orderId]['detailExtData'];//订单明细扩展表数据
                        $partFormatDetails = $orderDetails[$orderId]['part_data'];//订单sku与销售员关系数据
                        $orderExtendInfo = $orderDetails[$orderId]['orderExtendInfo'];//订单主表扩展数据
                        $orderDataInfo = $orderDetails[$orderId]['order_data'];//订单主表部分数据
                        unset($orderDetails[$orderId]['data']);
                        unset($orderDetails[$orderId]['detailExtData']);
                        unset($orderDetails[$orderId]['part_data']);   
                        unset($orderDetails[$orderId]['orderExtendInfo']);
                        unset($orderDetails[$orderId]['order_data']); 

                        //保存订单主表信息
                        $orderRetult['subtotal_price'] = $orderDataInfo['subtotal_price'];//产品总金额
                        $orderRetult['final_value_fee'] = $orderDataInfo['final_value_fee'];//订单成交费
                        //判断是否多仓,如果是则锁定待取消
                        $isMultiWarehouse = isset($orderDataInfo['isMultiWarehouse']) ? $orderDataInfo['isMultiWarehouse'] : false;
                        if($isMultiWarehouse) {
                            $orderRetult['complete_status'] = Order::COMPLETE_STATUS_WAIT_CANCEL;
                            $orderRetult['is_lock'] = 1;//锁定
                        }
                        $isOk = $commonOrder->saveOrderInfo($platformCode,$orderId,$orderRetult);
                        if (!$isOk) {
                            throw new Exception("saveOrderInfo Error " . $commonOrder->getExceptionMessage());
                        }                            

                        //保存订单备注信息
                        if ($noteFlag) {
                            $noteFlag2 = $commonOrder->saveOrderNoteInfo($orderId,$memoInfo);
                            if (!$noteFlag2) {
                                throw new Exception("save OrderNote Error ");
                            }
                        }    

                        //保存订单扩展信息
                        $isOk = $commonOrder->saveOrderExtendInfo($platformCode,$orderId,$orderExtendInfo);
                        if (!$isOk) {
                            throw new Exception("saveOrderExtendInfo Error " . $commonOrder->getExceptionMessage());
                        }

                        //保存订单详情信息
                        $isOk = $commonOrder->saveOrderDetailInfo($platformCode,$orderId,$orderDetailDatas,$orderDetailExtDatas);
                        if (!$isOk) {
                            throw new Exception("saveOrderDetailInfo Error " . $commonOrder->getExceptionMessage());
                        }

                        //匹配插头
                        $isOk = $commonOrder->addOrderAdapter($platformCode,$orderId,$orderRetult,$orderDetailDatas);
                        if (!$isOk) {
                            throw new Exception("addOrderAdapter Error " . $commonOrder->getExceptionMessage());
                        }

                        //保存订单sku异常信息
                        $orderSkuExceptionMsg = $orderDataInfo['orderSkuExceptionMsg'];
                        if ($orderSkuExceptionMsg != '') {
                            $commonOrder->setExceptionOrder($orderId, OrderExceptionCheck::EXCEPTION_SKU_UNKNOWN, $orderSkuExceptionMsg);
                        }

                        $warehouseExceptionMsg = isset($orderDataInfo['warehouseExceptionMsg']) ? $orderDataInfo['warehouseExceptionMsg'] : '';  
                        if ($warehouseExceptionMsg != '') {
                            $commonOrder->setExceptionOrder($orderId, OrderExceptionCheck::EXCEPTION_ORDER_RULE, $warehouseExceptionMsg);
                        }                                                        

                        //保存订单交易信息  
                        $isOk = $commonOrder->saveOrderTransactionInfo($platformCode,$orderId,$formatOrderTransactionInfos);
                        if (!$isOk) {
                            throw new Exception("saveOrderTransactionInfo Error " . $commonOrder->getExceptionMessage());
                        }

                        //保存付款信息
                        $isOk = $commonOrder->savePaypalTransactionInfo($orderId,$formatPaypalTransactionInfos);
                        if (!$isOk) {
                            throw new Exception("savePaypalTransactionInfo Error " . $commonOrder->getExceptionMessage());
                        }

                        //保存订单sku与销售关系
                        $skuOwnerFlag = $commonOrder->saveOrderSkuOwnerInfo($platformCode,$orderId,$partFormatOrderInfo,$partFormatDetails);
                        if (!$skuOwnerFlag) {
                            throw new Exception("save OrderPaypalTransactionRecord Error ");
                        }
                        $flag = array('errCode'=>'success','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").' ### 同步成功！');
                    } catch (Exception $e) {
                        $flag = array('errCode'=>'failure','errMessage'=>$platformOrderID.'###'.date("Y-m-d H:i:s").'@@@'.$e->getMessage() );
                    }
                }

                //add log
                if ($flag['errCode'] != 'success') {
                    $errLogMessage = mb_substr($flag['errMessage'], 0, ( ($len = mb_strlen($flag['errMessage']))>500 ? 500: $len) );
                    $this->addAliexpressLog(9000,AliexpressLog::STATUS_FAILURE,$errLogMessage);
                }

                //根据执行是否出错，确认提交还是回滚
                if ($flag['errCode'] == 'success') {
                    $aliexpressOrderDownLoad->updateOrderByPlatformOrderID(array('is_to_oms' => AliexpressOrderHeader::TO_OMS_OK,'to_oms_time' => date('Y-m-d H:i:s')), $platformOrderID);
                    $dbTransaction->commit();
                }else {
                    $dbTransaction->rollback();
                    $aliexpressOrderDownLoad->updateOrderByPlatformOrderID(array('is_to_oms' => AliexpressOrderHeader::TO_OMS_FAIL,'to_oms_time' => date('Y-m-d H:i:s')), $platformOrderID);
                }
                //记录错误信息
                if ( $showResult ) {
                    $flags[$platformOrderID] = $flag;
                }
            }
            // show result
            if ( $showResult ) {
                MHelper::printvar($flags,false);
            }              
        }
        return $orderCount;
    }

    /**
     * [addAliexpressLog description]
     * @param [type] $accountID [description]
     * @param [type] $status    [description]
     * @param [type] $message   [description]
     * @param [type] $eventName [description]
     */
    public function addAliexpressLog($accountID,$status,$message,$eventName=AliexpressLog::EVENT_SYNCORDER) {
        $logModel = new AliexpressLog;
        return $logModel->getDbConnection()->createCommand()->insert(
            $logModel->tableName(), array(
                'account_id'    => $accountID,
                'event'         => $eventName,
                'start_time'    => date('Y-m-d H:i:s'),                         
                'status'        => $status,
                'message'       => $message,
                'response_time' => date('Y-m-d H:i:s'),
                'end_time'      => date('Y-m-d H:i:s'),
                'create_user_id'=> intval(Yii::app()->user->id),
            )
        );         
    }
}