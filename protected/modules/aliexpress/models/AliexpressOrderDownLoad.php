<?php
/**
 * ALiexpress OrderDownLoad Model  [拉单专用]
 * @author	Rex
 * @since	2016-05-26
 */
class AliexpressOrderDownLoad extends AliexpressOrderHeader {
    
    const EVENT_NAME          = 'order_download';
    const EVENT_NAME_WAIT     = 'get_waitorders';
    const EVENT_NAME_CHECK    = 'check_downloadorder';
    const EVENT_NAME_SYNC     = 'sync_order';
    
    /** @var int 分组ID*/
    protected $_groupID = 0;

    /** @var object 拉单返回信息*/
    public $orderResponse = null;
    
    /** @var int 账号ID*/
    public $_accountID = null;
    /** @var 速卖通账号信息 */
    public $_accountInfo=null;
    
    /** @var string 异常信息*/
    public $exception = null;
    /**
     * @desc 订单留言
     * @var unknown
     */
    public $_orderMemo = null;
    /** @var int 日志编号*/
    public $_logID = 0;
    public $_orderIDs = null;

    public $_orderStatus = null;
    
    /** @var int 手续费比例*/
    public $_feeRate = 0.08;
    public $orderTotalFee = 0;

    private $_fromCode = null;     #来源
    private $_sysNote = null;
    private $_flagUpdate = false;  #强制更新标识

    protected $_timeArr = array();
    
    /** @var string aliexpress订单状态*/
    const PLACE_ORDER_SUCCESS               = 'PLACE_ORDER_SUCCESS';	#下单成功
    const ORDER_RISK_CONTROL                = 'RISK_CONTROL';			#订单处于风控24小时中
    const ORDER_WAIT_SELLER_SEND_GOODS      = 'WAIT_SELLER_SEND_GOODS';	#等待卖家发货
    const ORDER_SELLER_PART_SEND_GOODS      = 'SELLER_PART_SEND_GOODS';	#部分发货
    const ORDER_WAIT_BUYER_ACCEPT_GOODS     = 'WAIT_BUYER_ACCEPT_GOODS';#等待买家收货
    const ORDER_IN_CANCEL                   = 'IN_CANCEL';				#买家申请取消
    const ORDER_FUND_PROCESSING             = 'FUND_PROCESSING';		#买卖家达成一致，资金处理中
    const ORDER_FINISH                      = 'FINISH';					#已结束的订单
    const ORDER_IN_ISSUE                    = 'IN_ISSUE';				#含纠纷的订单
    const ORDER_IN_FROZEN                   = 'IN_FROZEN';				#冻结中的订单
    const ORDER_WAIT_SELLER_EXAMINE_MONEY   = 'WAIT_SELLER_EXAMINE_MONEY';	#等待卖家确认金额
   
    
    /* 资金状态 */
    const FUND_NOT_PAY						= 'NOT_PAY';		#未付款
    const FUND_PAY_SUCCESS					= 'PAY_SUCCESS';	#付款成功 
    const FUND_WAIT_SELLER_CHECK			= 'WAIT_SELLER_CHECK';	#卖家预付款
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
        $this->_accountID   = $accountID;
        $this->_accountInfo = AliexpressAccount::model()->getMapAccountInfoById($accountID);
        $this->_groupID     = (int)$this->_accountInfo['group_id'];
    }

    public function isOverseasAccount() {
        return $this->_accountInfo ? $this->_accountInfo['is_overseas_warehouse'] : null;
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
    	return $this->exception;
    }

    /**
     * @desc 设置分组ID
     * @param integer $groupID
     */
    public function setGroupID($groupID) {
        $this->_groupID = $groupID;
    }
    
    /**
     * @desc 设置日志编号
     * @param int $logID
     */
    public function setLogID($logID){
        $this->_logID = $logID;
    }
    
    public function setOrderIDs($orderIds){
    	$this->_orderIDs = $orderIds;
    }
    
    public function setOrderStatus($orderStatus){
    	$this->_orderStatus = $orderStatus;
    }

    public function setFromCode($fromCode) {
        $this->_fromCode = $fromCode;
    }

    public function getFromCode() {
        return $this->_fromCode;
    }

    /**
     * @desc 获取订单主表名称
     * @return string
     */
    public function getGroupTableName() {
        $groupID = intval($this->_groupID);
        $tableName = $this->tableName();
        if ($groupID) {
            $tableName = $tableName.'_'.$groupID;
        }
        return $tableName;
    }


    /**
     * @desc 获取订单明细表名称
     * @return string
     */
    public function getGroupDetailTableName() {
        $groupID = intval($this->_groupID);
        $tableName = AliexpressOrderDetails::model()->tableName();
        if ($groupID) {
            $tableName = $tableName.'_'.$groupID;
        }
        return $tableName;
    }    

    /**
     * 可拉的几种状态
     */
    private function canDownOrderStatus() {
        return array(self::PLACE_ORDER_SUCCESS,self::ORDER_RISK_CONTROL,self::ORDER_WAIT_SELLER_SEND_GOODS,self::ORDER_FINISH);
    }

    /**
     * 不能更新的订单状态 
     */
    private function notCanUpdateOrderStatus() {
        return array(self::ORDER_WAIT_SELLER_SEND_GOODS,self::ORDER_FINISH) ;
    }
    
    /**
     * 拉单主入口
     * @author	Rex
     * @since	2016-05-26
     */
    public function startDownLoadMore($timeArr){
        $path      = 'aliexpress/startDownLoadMore/'.date("Ymd").'/'.$this->_accountID.'/'.date("Hi");
        $accountID = $this->_accountID;
        $request   = new FindOrderListRequest();
    	$request->setStartTime(date('m/d/Y H:i:s', strtotime($timeArr['start_time'])));
    	$request->setEndTime(date('m/d/Y H:i:s', strtotime($timeArr['end_time'])));
        $page   = 1;
        $errMsg = '';
    	while ($page <= ceil($request->_totalItem/$request->_pageSize)) {
            try {
                $request->setPage($page);
                $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                // MHelper::writefilelog($path.'/response_'.$page.'.txt', print_r($response,true)."\r\n");
                if (!$request->getIfSuccess()) {
                    throw new Exception($request->getErrorMsg(), 110);
                }
                if ( !isset($response->totalItem) ) {
                    throw new Exception("totalItem is empty", 112);
                }
                if ( $response->totalItem <= 0 ) {
                    break;
                }
                $request->_totalItem = $response->totalItem;
                $page++;
                $i=0;
                foreach ($response->orderList as $order) {
                    if ( strrpos($order->orderId, 'E') > 0 ) {//orderId不要加trim,加了会对转字符串有影响
                        $platformOrderID = number_format($order->orderId,0,'','');
                    } else {
                        $platformOrderID = $order->orderId;
                    }

                    $memo = '';
                    foreach ($order->productList as $key => $product) {
                        if (isset($product->memo) && !empty($product->memo)) {
                             $memo .= $product->memo;
                        }
                    }
                    if ( !in_array($order->orderStatus, $this->canDownOrderStatus()) ) {
                        continue;
                    }
                    $flagSave = false;
                    $headerInfo = $this->getOneByCondition('*',"platform_order_id='{$platformOrderID}'");
                    if ( !empty($headerInfo) ) {
                        if (!in_array($headerInfo['order_status'], $this->notCanUpdateOrderStatus()) && $headerInfo['order_status'] != $order->orderStatus ) {
                            $flagSave = true;
                        }
                    }else {
                        $flagSave = true;
                    }
                    //开始保存订单
                    if ($flagSave) {
                        $orderDetailInfo = $this->findOrderById($platformOrderID);
                        // MHelper::writefilelog($path.'/orderDetailInfo-response_'.($page-1).'.txt', print_r($orderDetailInfo,true)."\r\n");
                        if ( empty($orderDetailInfo) || !isset($orderDetailInfo->id) ) {
                            continue;
                        }
                        $dbTransaction = $this->dbConnection->beginTransaction();//开启事务
                        try {
                            $this->saveOrderHeader($order,$orderDetailInfo,$params=array('memo'=>$memo));
                            $this->saveOrderDetailsP($order, $orderDetailInfo, $platformOrderID);
                            $dbTransaction->commit();
                        } catch (Exception $e) {
                            $dbTransaction->rollback();
                            $errMsg .= $platformOrderID . ' ## ' . $e->getMessage()."<br>";
                            continue;
                        }
                    }                    
                }
            } catch (Exception $e) {
                $errMsg .= 'page: '.$page.' @@@ '. $e->getMessage()."<br><br>";
                break;
            }
    	}
        $this->setExceptionMessage($errMsg);
    	return $errMsg == '' ? true : false;
    }
    
    /**
     * 补拉单主入口
     * @author	Rex
     * @since	2016-06-06
     */
    public function startOffsetDownLoadMore($timeArr) {
        $path = 'aliexpress/startOffsetDownLoadMore/'.date("Ymd").'/'.$this->_accountID.'/'.date("Hi");
    	if (!isset($timeArr['start_time']) || !isset($timeArr['end_time'])) {
    		return false;
    	}
    	$accountID = $this->_accountID;
        $request = new FindOrderListRequest();
    	$request->setStartTime(date('m/d/Y H:i:s', strtotime($timeArr['start_time'])));
    	$request->setEndTime(date('m/d/Y H:i:s', strtotime($timeArr['end_time'])));
        if (!empty($this->_orderStatus)) {
            $request->setOrderStatus($this->_orderStatus);
        } else {
            $request->setOrderStatus(self::ORDER_WAIT_SELLER_SEND_GOODS);
        }
    	$page = 1;
        $errMsg = '';
    	while ($page <= ceil($request->_totalItem/$request->_pageSize)) {
    		try {
                $request->setPage($page);
                $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
                // MHelper::writefilelog($path.'/response_'.$page.'.txt', print_r($response,true)."\r\n");
                if (!$request->getIfSuccess()) {
                    throw new Exception($request->getErrorMsg(), 110);
                }
                if ( !isset($response->totalItem) ) {
                    throw new Exception("totalItem is empty", 111);
                }
                if ($response->totalItem <= 0) {
                    break;
                }
                $request->_totalItem = $response->totalItem;
                $page++;
                $i=0;
                foreach ($response->orderList as $order) {
                    if ( strrpos($order->orderId, 'E') > 0 ) {//orderId不要加trim,加了会对转字符串有影响
                        $platformOrderID = number_format($order->orderId,0,'','');
                    } else {
                        $platformOrderID = $order->orderId;
                    }

                    $memo = '';
                    foreach ($order->productList as $key => $product) {
                        if (isset($product->memo) && !empty($product->memo)) {
                             $memo .= $product->memo.' ';
                        }
                    }
                    if ( !in_array($order->orderStatus, $this->canDownOrderStatus()) ) {
                        continue;
                    }
                    $flagSave = false;
                    $headerInfo = $this->getOneByCondition('*',"platform_order_id='{$platformOrderID}'");
                    if (!empty($headerInfo)) {
                        if (!in_array($headerInfo['order_status'], $this->notCanUpdateOrderStatus()) && $headerInfo['order_status'] != $order->orderStatus ) {
                            $flagSave = true;
                        }
                    }else {
                        $flagSave = true;
                    }
                    //开始保存订单
                    if ($flagSave) {
                       $orderDetailInfo = $this->findOrderById($platformOrderID);
                       // MHelper::writefilelog($path.'/orderDetailInfo-response_'.($page-1).'.txt', print_r($orderDetailInfo,true)."\r\n");
                       if ( empty($orderDetailInfo) || !isset($orderDetailInfo->id) ) {
                           continue;
                       }
                       $dbTransaction = $this->dbConnection->beginTransaction();//开启事务
                       try {
                           $this->saveOrderHeader($order,$orderDetailInfo,$params=array('memo'=>$memo) );
                           $this->saveOrderDetailsP($order, $orderDetailInfo, $platformOrderID);
                           $dbTransaction->commit();
                        } catch (Exception $e) {
                            $dbTransaction->rollback();
                            $errMsg .= $platformOrderID . ' ## ' . $e->getMessage()."<br>";
                            continue;
                        }
                    }                        
                }
            } catch (Exception $e) {
                $errMsg .= 'page: '.$page.' @@@ '. $e->getMessage()."<br>";
                break;
            }
    	}
        $this->setExceptionMessage($errMsg);
        return $errMsg == '' ? true : false;
    }   

    /**
     * 只返回数据
     */
    public function startDownLoadOneTest($platformOrderID) {
        $orderDetailInfo = $this->findOrderById($platformOrderID);
        return $orderDetailInfo;
    }
    
    /**
     * 拉单 [单个订单]
     * @author	Rex
     * @since	2016-05-26
     */
    public function startDownLoadOne($platformOrderID) {
        try {
            $orderDetailInfo = $this->findOrderById($platformOrderID);
            if (!empty($_REQUEST['debug'])) {
                MHelper::printvar($orderDetailInfo,false);
            }
            if (!$orderDetailInfo) {
                return false;
            }

            if (!isset($orderDetailInfo->orderStatus)) {
                return false;
            }

            $gmtCreate = $orderDetailInfo->gmtCreate;
            $gmtCreate = $this->formatDate($gmtCreate);

            $accountID = $this->_accountID;
            $request = new FindOrderListRequest();
            $request->setStartTime(date('m/d/Y H:i:s', strtotime($gmtCreate)-1));
            $request->setEndTime(date('m/d/Y H:i:s', strtotime($gmtCreate)+1));
            if (!empty($this->_orderStatus)) {
                $request->setOrderStatus($this->_orderStatus);
            }
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            
            if (!empty($_REQUEST['debug'])) {
                //echo '<hr>';
                //MHelper::printvar($response,false);
            }

            if (!$request->getIfSuccess()) {
                $this->setExceptionMessage('匹配不到订单');
                return false;
            }

            $orderInfo = null;
            if (!empty($response->orderList)) {
                foreach ($response->orderList as $key => $order) {
                    if (strrpos($order->orderId,'E')>0) {
                        $orderId = number_format($order->orderId,0,'','');
                    } else {
                        $orderId = $order->orderId;
                    }
                    if ($orderId == $platformOrderID) {
                        $orderInfo = $order;
                    }
                }
            }

            if (empty($orderInfo)) {
                return false;
            }

            if (!empty($_REQUEST['debug'])) {
                echo '<hr>';
                MHelper::printvar($orderInfo,false);
            }               

            $memo = '';
            foreach ($orderInfo->productList as $key => $product) {
                if (isset($product->memo)) {
                    $memo .= $product->memo.' ';
                }
            }

            $flagSave = false;
            $headerInfo = $this->getOneByCondition('*',"platform_order_id='{$platformOrderID}'");
            if (!empty($headerInfo)) {
                if ($this->_flagUpdate) {
                    $flagSave = true;
                } else {
                    $orderInfo2 = Order::model()->getOrderInfosByPlatformOrderIs($platformOrderID, Platform::CODE_ALIEXPRESS);
                    if (empty($orderInfo2)) {
                        $flagSave = true;
                    }
                }
            }else {
                $flagSave = true;
            }

            if ($flagSave) {
                $dbTransaction = $this->dbConnection->beginTransaction();//开启事务
                try {
                    $this->saveOrderHeader($orderInfo,$orderDetailInfo,$params=array('memo'=>$memo) );
                    $this->saveOrderDetailsP($orderInfo, $orderDetailInfo, $platformOrderID);
                    $dbTransaction->commit ();
                } catch (Exception $e) {
                    $dbTransaction->rollback ();
                    throw new Exception($e->getMessage());
                }
            }

            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }        

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->getGroupTableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->getGroupTableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }

    /**
     * @desc 更新订单数据
     * @param  array $data           
     * @param  integer $platformOrderID 
     * @return int
     */
    public function updateOrderByPlatformOrderID($data,$platformOrderID) {
        return $this->dbConnection->createCommand()
                ->update($this->getGroupTableName(),$data,"platform_order_id='{$platformOrderID}'");
    }

    /**
     * @desc  插入订单数据
     * @param  array $data
     * @return int
     */
    public function insertOrder($data) {
        return $this->dbConnection->createCommand()
                ->insert($this->getGroupTableName(),$data);
    }

    /**
     * @des 按平台订单号删除订单明细记录
     * @param  integer $platformOrderID 
     * @return int
     */
    public function deleteOrderDetails($platformOrderID) {
        return $this->dbConnection->createCommand()
                ->delete($this->getGroupDetailTableName(),"platform_order_id='{$platformOrderID}'");
    }

    /**
     * @desc  插入订单明细表数据
     * @param  array $data
     * @return int
     */
    public function insertOrderDetail($data) {
        return $this->dbConnection->createCommand()
                ->insert($this->getGroupDetailTableName(),$data);
    }

    /**
     * 得到未同步的订单
     */
    public function getNotLoadOrderList($platformOrderID) {
        $maxPaySuccess = date("Ymd",strtotime('-30 days'));
        $obj = $this->getDbConnection()->createCommand()
            ->select('*')
            ->from($this->getGroupTableName().' force index (fund_status_2) ')
            ->where("fund_status='PAY_SUCCESS' and order_status in('WAIT_SELLER_SEND_GOODS','WAIT_BUYER_ACCEPT_GOODS') and gmt_pay_success>'{$maxPaySuccess}' and is_to_oms!=".AliexpressOrderHeader::TO_OMS_OK);
        if (empty($platformOrderID)) {
            $obj->order('gmt_pay_success asc');
            $obj->limit(500);
        }else {
            if (is_array($platformOrderID)) {
                $obj->andWhere(array('in','platform_order_id',$platformOrderID));
            } else {
                $obj->andWhere('platform_order_id='.$platformOrderID);
            }
        }
        //echo $obj->getText()."<br>";
        return $obj->queryAll();
    }  

    /**
     * 根据平台系统订单号得到订单详情数据
     * @param unknown $platformOrderId
     * @return mixed
     */
    public function getOrderListByOrderId($platformOrderId) {
        return $this->getDbConnection()->createCommand()
                    ->select('*')
                    ->from($this->getGroupDetailTableName())
                    ->where("platform_order_id = '{$platformOrderId}'")
                    ->queryAll();
    }    

    /**
     * 保存 order header
     * @param   array   $orderDetailInfo    
     * @author	Rex
     * @since	2016-05-27
     */
    private function saveOrderHeader($orderInfo, $orderDetailInfo, $params=array()) {  
        try {
            //aliexpress无运费字段返回，直接报异常
            if (!isset($orderDetailInfo->logisticsAmount->amount)) {
                throw new Exception("logisticsAmount field is empty");
            } 

            if ( strrpos($orderDetailInfo->id, 'E') > 0 ) {//id不要加trim,加了会对转字符串有影响
                $platformOrderID = number_format($orderDetailInfo->id,0,'','');
            } else {
                $platformOrderID = $orderDetailInfo->id;
            }
            
            $date = date('Y-m-d H:i:s');
            $memo = isset($params['memo']) ? $params['memo'] : '';
            if ($memo != '') {//mysql编码格式utf-8格式，不支持带四字节的字符串插入
                $memo = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $memo);
            }

            //收件地址过滤特殊字符
            $receiptDetailAddress = isset($orderDetailInfo->receiptAddress->detailAddress) ? trim($orderDetailInfo->receiptAddress->detailAddress) : '';
            if ($receiptDetailAddress != '') {
                $receiptDetailAddress = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $receiptDetailAddress);
            }

            $receiptDetailAddress2 = isset( $orderDetailInfo->receiptAddress->address2) ? $orderDetailInfo->receiptAddress->address2 : '';
            if ($receiptDetailAddress2 != '') {
                $receiptDetailAddress2 = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $receiptDetailAddress2);
            }

            $totalProductAmount = 0;//产品总金额
            foreach ($orderInfo->productList as $product) {
                $totalProductAmount += floatval($product->totalProductAmount->amount);
            }
            $orderHeader = array(
                    'platform_order_id'     => $platformOrderID,
                    'log_id'                => $this->_logID,
                    'account_id'            => $this->_accountInfo['account'],
                    'order_status'          => strtoupper($orderDetailInfo->orderStatus),
                    'fund_status'           => strtoupper($orderDetailInfo->fundStatus),
                    'loan_status'           => strtoupper($orderDetailInfo->loanStatus),
                    'frozen_status'         => strtoupper($orderDetailInfo->frozenStatus),
                    'issue_status'          => strtoupper($orderDetailInfo->issueStatus),
                    'logistics_status'      => strtoupper($orderDetailInfo->logisticsStatus),
                    'gmt_create'            => $orderDetailInfo->gmtCreate,
                    'gmt_modified'          => $orderDetailInfo->gmtModified,
                    'gmt_pay_success'       => isset($orderDetailInfo->gmtPaySuccess) ? $orderDetailInfo->gmtPaySuccess : '',
                    'payment_type'          => isset($orderDetailInfo->paymentType) ? $orderDetailInfo->paymentType : '',
                    'amount'                => $orderDetailInfo->orderAmount->amount,
                    'actual_payment_amount' => $orderInfo->payAmount->amount,
                    'currency_code'         => $orderDetailInfo->orderAmount->currencyCode,
                    'currency_symbol'       => $orderDetailInfo->orderAmount->currency->symbol,
                    'fee_rate'              => $this->_feeRate, //手续费比率
                    'init_order_amount_2'   => $totalProductAmount,//产品总金额
                    'init_order_amount'     => $orderDetailInfo->initOderAmount->amount,
                    'init_order_currency_code' => $orderDetailInfo->initOderAmount->currencyCode,
                    'logistics_amount'      => isset($orderDetailInfo->logisticsAmount->amount) ? floatval($orderDetailInfo->logisticsAmount->amount) : 0,
                    'logistics_currency_code'   => isset($orderDetailInfo->logisticsAmount->currencyCode) ? trim($orderDetailInfo->logisticsAmount->currencyCode) : $orderDetailInfo->orderAmount->currencyCode,
                    'seller_signer_full_name'   => $orderDetailInfo->sellerSignerFullname,
                    'buyer_signer_full_name'=> $orderDetailInfo->buyerSignerFullname,
                    'buyer_login_id'        => $orderDetailInfo->buyerInfo->loginId,
                    'buyer_last_name'       => $orderDetailInfo->buyerInfo->lastName,
                    'buyer_first_name'      => $orderDetailInfo->buyerInfo->firstName,
                    'buyer_country'         => $orderDetailInfo->buyerInfo->country,
                    'buyer_email'           => isset($orderDetailInfo->buyerInfo->Email) ? $orderDetailInfo->buyerInfo->Email : '',
                    'receipt_contact_person'    => $orderDetailInfo->receiptAddress->contactPerson,
                    'receipt_mobile_no'     => isset($orderDetailInfo->receiptAddress->mobileNo) ? $orderDetailInfo->receiptAddress->mobileNo : '',
                    'receipt_phone_country' => isset($orderDetailInfo->receiptAddress->phoneCountry) ? $orderDetailInfo->receiptAddress->phoneCountry : '',
                    'receipt_phone_area'    => isset($orderDetailInfo->receiptAddress->phoneArea) ? $orderDetailInfo->receiptAddress->phoneArea : '',
                    'receipt_phone_number'  => isset($orderDetailInfo->receiptAddress->phoneNumber) ? $orderDetailInfo->receiptAddress->phoneNumber : '',
                    'receipt_zip'           => $orderDetailInfo->receiptAddress->zip,
                    'receipt_detail_address'=> $receiptDetailAddress,
                    'receipt_address_2'     => $receiptDetailAddress2,
                    'receipt_city'          => $orderDetailInfo->receiptAddress->city,
                    'receipt_province'      => $orderDetailInfo->receiptAddress->province,
                    'receipt_country'       => $orderDetailInfo->receiptAddress->country,
                    'memo'                  => $memo,
                    'is_phone'              => (string)$orderDetailInfo->isPhone,
                    'from_code'             => empty($this->_fromCode) ? '' : $this->_fromCode,
                    'create_time'           => $date,
                    'update_time'           => $date,
            );
            $headerInfo = $this->getOneByCondition('*',"platform_order_id='{$platformOrderID}'");
            if ($headerInfo) {
                unset($orderHeader['create_time']);
                return $this->updateOrderByPlatformOrderID($orderHeader, $platformOrderID);
            }          
            return $this->insertOrder($orderHeader);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }    

    /**
     * 保存 order details by productList
     * @param   array   $orderInfo  订单信息
     * @param   array   $orderDetailInfo  订单详细信息
     * @param   int     $platformOrderID 平台订单号
     * @author  Rex
     * @since   2016-06-21
     */
    private function saveOrderDetailsP($orderInfo, $orderDetailInfo, $platformOrderID) {
        try {
            $this->deleteOrderDetails($platformOrderID);
            $date = date('Y-m-d H:i:s');
            $totalCount = 0;         
            foreach ($orderInfo->productList as $key => $product) {
                $totalCount += $product->productCount;
            }

            foreach ($orderInfo->productList as $key => $product) {    
                $key +=1;
                $key2 = ($key < 10) ? '0'.$key : $key;
                $productSnapUrl = isset($product->productSnapUrl) ? trim($product->productSnapUrl) : '';
                $memo = isset($product->memo) ? trim($product->memo) : '';
                if ($memo != '') {//mysql编码格式utf-8格式，不支持带四字节的字符串插入
                    $memo = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $memo);
                }
                $detailData = array(
                        'detail_id'             => $platformOrderID.'.'.$key2,
                        'platform_order_id'     => $platformOrderID,
                        'product_id'            => $product->productId,
                        'sku_code'              => isset($product->skuCode) ? trim($product->skuCode) : '',
                        'product_count'         => $product->productCount,
                        'product_unit_price'    => $product->productUnitPrice->amount,
                        'product_unit_currency_code' => $product->productUnitPrice->currencyCode,
                        'product_unit'          => isset($product->productUnit) ? $product->productUnit : '',
                        'total_product_amount'  => $product->totalProductAmount->amount,
                        'total_product_currency_code'   => $product->totalProductAmount->currencyCode,
                        'logistics_type'        => isset($product->logisticsType) ? $product->logisticsType : '',
                        'logistics_service_name'=> isset($product->logisticsServiceName) ? $product->logisticsServiceName : '',
                        'logistics_amount'      => isset($orderDetailInfo->logisticsAmount->amount) ? $orderDetailInfo->logisticsAmount->amount/$totalCount * $product->productCount : 0,
                        'logistics_currency_code'   => isset($product->logisticsAmount->currencyCode) ? $product->logisticsAmount->currencyCode : $product->totalProductAmount->currencyCode,
                        'freight_commit_day'    => isset($product->freightCommitDay) ? $product->freightCommitDay : 0,
                        'son_order_status'      => isset($product->sonOrderStatus) ? $product->sonOrderStatus : '',
                        'product_name'          => trim($product->productName),
                        'product_img_url'       => $productSnapUrl,
                        'memo'                  => $memo,   
                        'product_attributes'    => isset($product->productAttributes) ? $product->productAttributes : '',
                        'create_time'           => $date,
                ); 
                $this->insertOrderDetail($detailData);
            }
            return true;            
        } catch (Exception $e) {
            MHelper::writefilelog('ali-exception.txt', $platformOrderID.' ### '. $e->getMessage()."\r\n");
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     *  抓取单个订单信息 [API: findOrderById]
     *  @author	Rex
     *  @since	2016-05-26
     */
    private function findOrderById($platformOrderID) {
    	if (empty($platformOrderID)) return false;
    	try {
    		$request = new FindOrderByIdRequest();
    		$request->setOrderID($platformOrderID);
    		$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
    		
    		if (!$request->getIfSuccess()) {
    			throw new Exception($request->getErrorMsg());
    		}
    		return $response;
    	} catch (Exception $e) {
    		$this->setExceptionMessage($e->getMessage());
    		return false;
    	}
    }
    
    /**
     * @desc 获取拉单时间段
     */
    public function getTimeArr(){
        return $this->_timeArr;
    }

    /**
     * 设置拉单时间段(每半小时拉一次)
     * 上次有成功,则将上次结束时间往前推15分钟，避免漏单，
     * @param array $timeArr
     */
    public function setTimeArr($timeArr = array()) {
        $offtime = 15*3600;//相差15个小时
        if ( empty($timeArr) ) {
            $lastLog = AliexpressLog::model()->getLastLogByCondition(array(
                    'account_id'    => $this->_accountID,
                    'event'         => self::EVENT_NAME,
                    'status'        => AliexpressLog::STATUS_SUCCESS,
            ));
            $lastEventLog = array();
            if( !empty($lastLog) ){
                $lastEventLog = AliexpressLog::model()->getEventLogByLogID(self::EVENT_NAME, $lastLog['id']);
            }
            
            $nowTime  = time() - $offtime;
            if ( !empty($lastEventLog) ) {
                $startTime = strtotime ( $lastEventLog ['end_time'] );//速卖通时间,不用减时差
                if ( $nowTime - $startTime > 3*86400 ) {
                    $startTime = $nowTime - 3*86400;
                } else {
                    $startTime = $startTime - 15*60;
                }
            } else {
                $startTime = $nowTime - 3*86400;
            }
            $endTime  = time() - $offtime;
            $timeArr = array(
                'start_time'  => date('Y-m-d H:i:s',$startTime),
                'end_time'    => date('Y-m-d H:i:s',$endTime),
            );
        }
        $this->_timeArr = $timeArr;
        return $this;        
    }    

    /**
     * [formatDate description]
     * @param  [type] $dateOnline [description]
     * @return [type]             [description]
     */
    public function formatDate($dateOnline){
        return parent::formatDate($dateOnline);
    }   

}