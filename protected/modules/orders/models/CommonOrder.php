<?php
/**
 * 订单表model
 * 
 * @package Market.modules.orders.models
 * @author Yangsh
 * 
 */
class CommonOrder extends Order {

    /** @var string [ExceptionMsg] */
    protected $_ExceptionMsg    = null;
    
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
        return 'ueb_order';
    }      

    /**
     * 设置异常信息
     * @param string $message           
     */
    public function setExceptionMessage($message) {
        $this->_ExceptionMsg = $message;
        return $this;
    }

    public function getExceptionMessage() {
        return $this->_ExceptionMsg;
    }    

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return [type]         [description]
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
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
     * @return [type]         [description]
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }     

    /**
     * @desc   保存订单数据
     * @param  string $platformCode
     * @param  string $orderID
     * @param  array $formatOrderInfo
     * @return boolean
     */
    public function saveOrderInfo($platformCode,$orderID,$formatOrderInfo) {
        try {
            $formatOrderInfo['order_id'] = $orderID;
            $formatOrderInfo['platform_code'] = $platformCode;
            $info = $this->getOneByCondition('order_id',"order_id='{$orderID}'");
            if (!empty($info)) {
                throw new Exception($orderID."订单号重复!");
            } else {
                $this->dbConnection->createCommand()->insert($this->tableName(), $formatOrderInfo);
            }
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }   

    /**
     * @desc 保存订单扩展信息
     * @param  string $platformCode
     * @param  string $orderID
     * @param  array $orderExtendInfo
     * @return boolean
     */
    public function saveOrderExtendInfo($platformCode,$orderID,$orderExtendInfo) {
        try {
            $orderExtendInfo['order_id'] = $orderID;
            $orderExtendInfo['platform_code'] = $platformCode;
            $info = OrderExtend::model()->getOneByCondition('order_id',"order_id='{$orderID}'");
            if (!empty($info)) {
                $this->dbConnection->createCommand()
                     ->update(OrderExtend::tableName(), $orderExtendInfo, "order_id=:id", array('id'=> $orderID));
            } else {
                $orderExtendInfo['k3_cloud_status'] = 0;
                $orderExtendInfo['error_code']      = 0;
                $orderExtendInfo['create_time']     = date('Y-m-d H:i:s');
                $this->dbConnection->createCommand()
                     ->insert(OrderExtend::tableName(), $orderExtendInfo);
            }
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }

    /**
     * @desc   保存订单明细数据
     * @param  string $platformCode      
     * @param  string $orderID           
     * @param  array $formatDetailInfos 
     * @param  array $formatDetailsExts 
     * @return boolean
     */
    public function saveOrderDetailInfo($platformCode,$orderID,$formatDetailInfos,$formatDetailsExts) {
        try {
            $this->dbConnection->createCommand()->delete(OrderDetail::tableName(), "order_id=:id", array('id'=>$orderID) );
            foreach ($formatDetailInfos as $key => $detailInfo) {
                if(!isset($formatDetailsExts[$key])) {
                    throw new Exception("detailExtend data is empty");
                }
                $detailInfo['order_id'] = $orderID;
                $detailInfo['platform_code'] = $platformCode;
                $isOk = $this->dbConnection->createCommand()
                             ->insert(OrderDetail::tableName(), $detailInfo);
                if(!$isOk) {
                    throw new Exception("save detail failure");
                } 
                $formatDetailsExtInfo = $formatDetailsExts[$key];
                unset($formatDetailsExts[$key]);
                $formatDetailsExtInfo['detail_id'] = $this->dbConnection->getLastInsertID();
                $isOk = OrderDetailExtend::model()->addOrderDetailExtend($formatDetailsExtInfo);
                if(!$isOk) {
                    throw new Exception("save detailExtend failure");
                }                 
            }
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }    

    /**
     * @desc   保存订单交易数据
     * @param  string $platformCode                [description]
     * @param  string $orderID                     [description]
     * @param  string $formatOrderTransactionInfos [description]
     * @return boolean
     */
    public function saveOrderTransactionInfo($platformCode,$orderID,$formatOrderTransactionInfos) {
        try {
            $transactionIdArr = array();
            foreach ($formatOrderTransactionInfos as $key => $orderTransactionInfo) {
                $transactionIdArr[] = $orderTransactionInfo['transaction_id'];
            }
            $this->dbConnection->createCommand()->delete(OrderTransaction::tableName(), "transaction_id IN ('". implode("','", $transactionIdArr) ."')" );
            foreach ($formatOrderTransactionInfos as $key => $orderTransactionInfo) {
                $orderTransactionInfo['order_id'] = $orderID;
                $orderTransactionInfo['platform_code'] = $platformCode;
                $this->dbConnection->createCommand()
                         ->insert(OrderTransaction::tableName(), $orderTransactionInfo);
            }
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }

    /**
     * @desc   保存paypal交易数据
     * @param  string $orderID                      [description]
     * @param  array $formatPaypalTransactionInfos [description]
     * @return boolean
     */
    public function savePaypalTransactionInfo($orderID,$formatPaypalTransactionInfos) {
        try {
            $transactionIdArr = array();
            foreach ($formatPaypalTransactionInfos as $key => $paypalTransactionInfo) {
                $transactionIdArr[] = $paypalTransactionInfo['transaction_id'];
            }
            $this->dbConnection->createCommand()->delete(OrderPaypalTransactionRecord::tableName(), "transaction_id IN ('". implode("','", $transactionIdArr) ."')" );

            foreach ($formatPaypalTransactionInfos as $key => $paypalTransactionInfo) {
                $paypalTransactionInfo['order_id'] = $orderID;
                $this->dbConnection->createCommand()
                         ->insert(OrderPaypalTransactionRecord::tableName(), $paypalTransactionInfo);
            }
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }    

    /**
     * @desc   保存订单备注数据
     * @param  string $orderID [description]
     * @param  string $note    [description]
     * @return boolean
     */
    public function saveOrderNoteInfo($orderID,$note){
        try {
            $nowTime = date('Y-m-d H:i:s');
            $this->dbConnection->createCommand()->delete(OrderNote::tableName(), "order_id='{$orderID}'");
            $this->dbConnection->createCommand()->insert(OrderNote::tableName(), array(
                'order_id'          => $orderID,
                'note'              => $note,
                'status'            => 0,
                'create_time'       => $nowTime,
                'modify_time'       => '0000-00-00 00:00:00',
                'modify_user_id'    => 0,
                'create_user_id'    => 0
            ));
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }      

    /**
     * @desc 添加订单明细sku对应转接头
     * @param string $platformCode       
     * @param string $orderID
     * @param array $formatOrderInfo
     * @param array $formatDetailInfos
     * @return boolean|Ambigous <number, boolean>
     */
    public function addOrderAdapter($platformCode,$orderID,$formatOrderInfo,$formatDetailInfos) {
        try {       
            $platformOrderID        = $formatOrderInfo['platform_order_id'];
            $shipCountryName        = $formatOrderInfo['ship_country_name'];
            foreach ($formatDetailInfos as $key => $detail) {
                $transactionId      = $detail['transaction_id'];
                $currency           = $detail['currency'];
                $sku                = $detail['sku'];
                $quantity           = $detail['quantity'];
                //查找sku对应转接头
                $adapterSku         = ProductAdapter::model()->getAdapterSkuByOrderSku($sku,$shipCountryName);
                if (!empty($adapterSku)) {
                    //如果该订单sku本身就是对应转接头，则不发货，一般在设置转接头时不会存在该情况，这里以防万一判断一下
                    if($adapterSku == $sku){
                        return true;
                    }
                    $info = $this->dbConnection->createCommand ()
                                    ->select ( 'id,quantity' )
                                    ->from ( OrderDetail::tableName() )
                                    ->where ( "transaction_id='{$transactionId}' and order_id='{$orderID}' and sku='{$adapterSku}' " )
                                    ->queryRow ();

                    if ($info) {//update
                        $oldQuantity    = $info['quantity'];
                        //$newQuantity    = $oldQuantity + $quantity;
                        $data           = array(
                            'quantity'          => $oldQuantity,
                            'modify_user_id'    => 0,
                            'modify_time'       => date('Y-m-d H:i:s')
                        );
                        $this->dbConnection->createCommand ()
                             ->update(OrderDetail::tableName(), $data, "id=:id", array('id'=> $info['id']) );
                    }else{//insert
                        $data = array(
                                'transaction_id'    => $transactionId,
                                'order_id'          => $orderID,
                                'platform_code'     => $platformCode,
                                'currency'          => $currency,
                                'sku'               => $adapterSku,
                                'quantity'          => $quantity,
                                'detail_type'       => OrderDetail::IS_ADAPTER,
                                'create_user_id'    => 0,
                                'create_time'       => date('Y-m-d H:i:s'),
                                'modify_user_id'    => 0,
                                'modify_time'       => '0000-00-00 00:00:00'
                        );
                        $this->dbConnection->createCommand()->insert(OrderDetail::tableName(), $data);
                    }
                }  
            }
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 设置订单完成状态
     * @param tinyint $status
     * @param string $orderID
     */
    public function setOrderCompleteStatus($status, $orderID){
        return $this->dbConnection->createCommand()->update($this->tableName(), 
                        array('complete_status' => $status), 'order_id = "'.$orderID.'"');
    }    

    /**
     * @desc 设置订单为异常订单
     * @param string $orderID
     * @param string $exceptionType
     * @param string $exceptionReason
     * @return boolean
     */
    public function setExceptionOrder($orderID, $exceptionType, $exceptionReason = ''){
        //把订单标为异常状态
        $res = $this->setOrderCompleteStatus(Order::COMPLETE_STATUS_EXCEPTION,$orderID);
        if(!$res){
            return false;
        }
        //写入异常记录表
        $params = array(
            'exception_reason'  =>  $exceptionReason,
            'status'            =>  OrderExceptionCheck::STATUS_DEFAULT
        );      
        return OrderExceptionCheck::model()->addExceptionRecord($orderID, $exceptionType, $params);
    }

    /**
     * 保存订单sku与销售关系
     * @param string $platformCode       
     * @param string $orderID
     * @param array $formatOrderInfo  ['platform_order_id'=>'131854548713','account_id'=>11]
     * @param array $formatDetailInfos ['sku_online'=>'1234','sku_old'=>'123','site'=>'1','item_id'=>'456']
     * @return boolean               
     */
    public function saveOrderSkuOwnerInfo($platformCode,$orderID,$formatOrderInfo,$formatDetailInfos) {
        try {
            $platformOrderID        = $formatOrderInfo['platform_order_id'];
            $accountID              = $formatOrderInfo['account_id'];
            $nowTime                = date('Y-m-d H:i:s');
            foreach ($formatDetailInfos as $key => $detail) {
                $orderSkuOwner      = OrderSkuOwner::model();
                $onlineSku          = $detail['sku_online'] == '' ? 'unknown' : $detail['sku_online'];//在线sku
                $sku                = $detail['sku_old'] == '' ? 'unknown' : $detail['sku_old'];//系统sku
                $siteID             = isset($detail['site']) ? $detail['site'] : -1; 
                $itemID             = isset($detail['item_id']) ? $detail['item_id'] : '';
                //format data
                $row = array();
                $row['platform_code']   = $platformCode;
                $row['platform_order_id'] = $platformOrderID;
                $row['online_sku']      = $onlineSku;
                $row['account_id']      = $accountID;
                $row['site']            = $siteID;
                $row['sku']             = $sku;
                $row['item_id']         = $itemID;
                $row['order_id']        = $orderID;
                $row['created_at']      = $nowTime;
                $row['freight_price']   = isset($detail['freight_price']) ? $detail['freight_price'] : 0;//运费
                $row['copoun_price']    = isset($detail['copoun_price']) ? $detail['copoun_price'] : 0;//优惠金额
                //check exist
                $response = $orderSkuOwner->isExist($platformCode,$platformOrderID,$onlineSku);
                if ( isset($response['data']) ) {
                    if ($response['data'] == '0') {//不存在
                        $response = $orderSkuOwner->addRow($row);
                        if ($response['errorCode'] != '0') {
                            throw new Exception($response['errorMsg']);
                        }
                    } else {
                        $orderSkuOwner->updateOrderId(array('order_id'=>$orderID,'freight_price'=>$row['freight_price'],'copoun_price'=>$row['copoun_price']),$response['data']);
                    }
                }
            }
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 批量更新COD订单交易状态, COD订单默认导入OMS状态为Pending
     * @param  string $platformCode
     * @param  array $platformOrderIDs 
     * @param  array $updateTimes 
     * @return boolean
     */
    public function updateOmsTransactionStatus($platformCode,$platformOrderIDs,$updateTimes=array()) {
        $nowTime = date('Y-m-d H:i:s');
        $order = new Order();
        $orderTransaction = new OrderTransaction();
        $paypalTransactionRecord = new OrderPaypalTransactionRecord();
        $res = $order->getDbConnection()->createCommand()
                    ->select('order_id,platform_order_id')
                    ->from($order->tableName())
                    ->where("platform_code='{$platformCode}'")
                    ->andWhere(array('in','platform_order_id',$platformOrderIDs))
                    ->andWhere("ship_status=2") //2已发货
                    ->queryAll();                        
        if (empty($res)) {
            return array();
        }

        $orderIds = array(); 
        foreach ($res as $v) {
            $dbTransaction = $order->getDbConnection()->beginTransaction(); 
            try {
                //更新付款时间及状态
                $payTime = isset($updateTimes[$v['platform_order_id']]) && $updateTimes[$v['platform_order_id']] != '' ? $updateTimes[$v['platform_order_id']] : $nowTime;
                $isOk = $orderTransaction->getDbConnection()->createCommand()
                        ->update($orderTransaction->tableName(),array(
                                'payment_status'    => 'Completed',
                                'order_pay_time'    => $payTime,
                                'modify_time'       => $nowTime,
                                'k3_cloud_status'   => 0,
                                'sync_cloud_error'  => 0,
                            ),"order_id ='{$v['order_id']}' and payment_status='Pending' and k3_cloud_status=2 and platform_code='{$platformCode}' ");
                if($isOk) {
                    $isOk = $paypalTransactionRecord->getDbConnection()->createCommand()
                        ->update($paypalTransactionRecord->tableName(),array(
                                    'payment_status'    => 'Completed',
                                    'order_time'        => $payTime,
                                    'modify_time'       => $nowTime,
                                ),"order_id ='{$v['order_id']}' and payment_status='Pending' ");
                }
                if (!$isOk) {
                    throw new Exception("update failure");
                }
                $orderIds[$v['platform_order_id']] = $v['order_id'];
                $dbTransaction->commit();
            } catch (Exception $e) {
                $dbTransaction->rollback();
            }
        }
        
        return array_keys($orderIds);                                          
    }    

}