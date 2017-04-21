<?php

/**
 * @desc Ebay抓单辅助表
 * @author yangsh
 * @since 2016-06-08
 */
class EbayGetorder extends EbayModel {

    /** @var string 异常信息*/
    private $_Exception   = null;
    
    /** @var int 日志编号*/
    private $_LogID       = 0;
    
    /** @var int 账号ID*/
    private $_AccountID   = null;


    /**@var status状态 */
    const STATUS_PENDING    = 0;
    const STATUS_RUNNING    = 1;
    const STATUS_FINISH     = 2;

    /* 最大运行时间 */
    const MAX_RUNNING_TIME  = 3600;//60分钟

	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_getorder';
    }

    /**
     * 设置异常信息
     * @param string $message           
     */
    public function setExceptionMessage($message) {
        $this->_Exception = $message;
        return $this;
    }

    public function getExceptionMessage() {
        return $this->_Exception;
    }      

    /**
     * 设置账号ID
     * @param int $accountID
     */
    public function setAccountID($accountID) {
        $this->_AccountID = $accountID;
        return $this;
    }           

    /**
     * 设置日志编号
     *
     * @param int $logID            
     */
    public function setLogID($logID) {
        $this->_LogID = $logID;
        return $this;
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
     * [getOne description]
     * @param  int $orderId [description]
     * @return [type]          [description]
     */
    public function getOne($orderId) {
        return $this->find('platform_order_id = :order_id ',array( ':order_id' => $orderId ) );
    }

    /**
     * [isExist description]
     * @param  int  $orderId [description]
     * @return boolean          [description]
     */
    public function isExist($orderId) {
        $res = $this->getOne($orderId);
        return empty($res) ? false : true;
    }

    /**
     * 获取待下载的记录
     * @param  array $criterias
     * @return array
     */
    public function getPendingRecordsByCriterias($criterias) {
        $where = 'status=0';//待下载记录
        if (!empty($criterias['accountId'])) {
            $where .= " AND seller_account_id = '{$criterias['accountId']}'";
        }
        if (!empty($criterias['orderIds'])) {
            $where .= " AND platform_order_id IN ('".implode("','",$criterias['orderIds'])."')";
        }
        if (!empty($criterias['startTime'])) {
            $where .= " AND created_at > '{$criterias['startTime']}' ";
        }
        if (!empty($criterias['endTime'])) {
            $where .= " AND created_at < '{$criterias['endTime']}' ";
        }
        $rows = $this->dbConnection->createCommand ()
                ->select ( ' platform_order_id,seller_account_id ' )
                ->from ( self::tableName () )
                ->where ( $where )
                ->queryAll ();
        $res = array();
        if (!empty($rows)) {
            foreach ($rows as $key => $value) {
                $res[$value['seller_account_id']][] = $value['platform_order_id'];
            }
        }
        return $res;
    }

    /**
     * @desc 检测是正在运行的记录是否超时
     * @param int $accountId
     * @return boolean
     */
    public function checkRunning($accountId){
        $maxRunningTime = date("Y-m-d H:i:s",time() - self::MAX_RUNNING_TIME);
        $rows = $this->dbConnection->createCommand ()
                ->select ( ' id,platform_order_id ' )
                ->from ( self::tableName () )
                ->where ( " seller_account_id = :account_id ", array(':account_id'=> $accountId))
                ->andWhere( " status = :status",  array(':status'=>self::STATUS_RUNNING) )
                ->andWhere( " response_time <= :maxRunningTime ", array(':maxRunningTime' => $maxRunningTime ) )
                ->andWhere( " response_time > 0 " )
                ->queryAll ();
        return $rows;
    }

    /**
     * @desc 标记待下载状态
     * @param int $id
     */
    public function setPending($id){
        return $this->updateByPk($id, array(
                            'status'        => self::STATUS_PENDING,
                            'updated_at'    =>date("Y-m-d H:i:s"),
                            'response_time' => '0000-00-00 00:00:00',
                        )
                    );
    }

    /**
     * @desc 标记待下载状态
     * @param int $id
     */
    public function setPendingForInit($platforOrderId) {
        $conditions = is_array($platforOrderId) ? array('in', 'platform_order_id', $platforOrderId)
                                                 : " platform_order_id='{$platforOrderId}'";
        return $this->dbConnection
                    ->createCommand()
                    ->update(self::tableName(), array(
                                'status'        => self::STATUS_PENDING,
                                'updated_at'    => date("Y-m-d H:i:s"),
                            ),
                            $conditions 
                    );
    }    

    /**
     * 设置正在下载
     * @param  mixed $platforOrderId 
     * @return boolean
     */
    public function setRunning($platforOrderId) {
        if ( !is_array($platforOrderId) ) {
            $platforOrderId = array($platforOrderId);
        }
        return $this->dbConnection
                    ->createCommand()
                    ->update(self::tableName(), array(
                            'status'        => self::STATUS_RUNNING,
                            'updated_at'    => date("Y-m-d H:i:s"),
                            'response_time' => date("Y-m-d H:i:s")
                        ),
                        array('in','platform_order_id', $platforOrderId )
                    );
    }

    /**
     * 设置已完成下载
     * @param  mixed $platforOrderId 
     * @return boolean
     */
    public function setFinish($platforOrderId) {
        $conditions = is_array($platforOrderId) ? array('in', 'platform_order_id', $platforOrderId)
                                                 : " platform_order_id='{$platforOrderId}'";
        return $this->dbConnection
                    ->createCommand()
                    ->update(self::tableName(), array(
                                'status'        => self::STATUS_FINISH,
                                'updated_at'    => date("Y-m-d H:i:s"),
                                'response_time' => date("Y-m-d H:i:s")
                            ),
                            $conditions 
                    );
    }    
    
    /**
     * 插入数据
     */
    public function addNewData($data) {
        $isOk = $this->dbConnection->createCommand()->insert(self::tableName(), $data);
        if ($isOk) {
            return $this->dbConnection->getLastInsertID();
        }
        return false;     
    }

    /**
     * 保存抓单记录表数据
     * @param array $order
     * @return boolean
     */ 
    public function saveEbayGetoderInfo($order) {
        try {
            $accountId          = $this->_AccountID;
            $logId              = $this->_LogID;
            $platformOrderId    = trim($order->OrderID);
            $orderStatus        = trim($order->OrderStatus);
            $total              = floatval($order->Total);
            $orderCreateTime    = date('Y-m-d H:i:s',strtotime(trim($order->CreatedTime)));
            $info               = $this->getOneByCondition('id',"platform_order_id='{$platformOrderId}'");
            //判断订单表是否存在，如果未付款或已经付款且订单金额有变化，则设置重新抓取
            if (!empty($info)) {
                $orderInfo  = EbayOrderMain::model()->getOneByCondition('order_status,payment_status,total,ori_created_time',"platform_order_id='{$platformOrderId}'");
                if ( !empty($orderInfo) ) {
                    $days   = ceil((time() - strtotime($orderInfo['ori_created_time']))/86400);
                    if ($days > 45) {
                        $this->setFinish($platformOrderId);
                    } else if ( $orderInfo['payment_status'] == 0 || $orderInfo['total'] != $total || $orderInfo['order_status'] != 'Completed' ) {
                        $this->setPending($info['id']);
                    }
                }
                return true;
            }         
            return $this->addNewData(
                array(
                    'platform_order_id'     => $platformOrderId,
                    'seller_account_id'     => $accountId,
                    'status'                => self::STATUS_PENDING,
                    'order_create_time'     => $orderCreateTime,
                    'created_at'            => date('Y-m-d H:i:s'),
                    'log_id'                => $logId,
                )
            );      
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }    


}