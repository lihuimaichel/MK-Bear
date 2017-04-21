<?php
/**
 * @desc ebay日志主表
 * @author Gordon
 * @since 2015-06-06
 */
class EbayLog extends EbayModel{
    
    /**
     * @desc 运行状态
     * @var tinyint
     */
    const STATUS_DEFAULT    = 0;//未开始
    const STATUS_RUNNING    = 1;//运行中
    const STATUS_SUCCESS    = 2;//运行成功
    const STATUS_FAILURE    = 3;//运行失败
    const STATUS_FINISH     = 4;//手动完成
    
    const MAX_RUNNING_TIME  = 1800;//最大运行时间
    
    public static $accountPairs = array();

    /** @var 把库存置为0 */
    const EVENT_ZERO_STOCK = 'zero_stock';

    /** @var 拉取订单 */
    const EVENT_GETORDER = 'getorder';

    //拉取listing
    const EVENT_GET_PRODUCT = 'get_product';

    //下载订单id
    const EVENT_GETORDERIDS   = 'get_orderids';

    //补拉订单id
    const EVENT_CHECKORDERIDS  = 'check_orderids';

    //下载订单交易
    const EVENT_DOWNTRANSACTIONS  = 'down_transactions';

    const EVENT_SYNCORDER       = 'sync_order';    

    //上传产品
    const EVENT_UPLOAD_PRODUCT = 'upload_product';

    //上传跟踪号
    const EVENT_UPLOADTRACKNUM = 'uploadtracknum';

    //自动补库存
    const EVENT_REVISE_INVENTORY = 'revise_inventory';

    //拉取paypal交易
    const EVENT_TRANSACTIONSEARCH = 'transaction_search';

    //拉取paypal交易明细
    const EVENT_GETTRANSACTIONDETAILS = 'get_transaction_details';

    //同步paypal交易
    const EVENT_SYNC_TRANSACTION = 'sync_transaction';

    //拉取Item Best Matches转化率数据
    const EVENT_ITEM_BEST_MATCHES = 'get_item_best_matches';    

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_log';
    }
    
    /**
     * @desc 准备日志数据
     * @param int $accountID
     * @param string $eventName
     */
    public function prepareLog($accountID, $eventName){
        $this->setAttributes(array(
                'account_id'    => $accountID,
                'event'         => $eventName,
                'start_time'    => date('Y-m-d H:i:s'),
                'response_time' => date('Y-m-d H:i:s'),
                'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
                'status'        => self::STATUS_DEFAULT,
        ),false);
        $this->setIsNewRecord(true);
        $flag = $this->save();
        if( $flag ){
            return $this->dbConnection->getLastInsertID();
        }
        return false;
    }
    
    /**
     * @desc 标记事件手动改结束
     * @param int $id
     */
    public function setFinish($id){
        return $this->updateByPk($id, array(
            'status'    => self::STATUS_FINISH
        ));
    }
    
    /**
     * @desc 标记事件正在运行
     * @param int $id
     */
    public function setRunning($id){
        return $this->updateByPk($id, array(
            'status'    => self::STATUS_RUNNING
        ));
    }
    
    /**
     * @desc 标记事件失败
     * @param int $id
     */
    public function setFailure($id, $message = ''){
        
        return $this->updateByPk($id, array(
            'status'        => self::STATUS_FAILURE,
            'message'       => $message,
            'response_time' => date('Y-m-d H:i:s'),
            'end_time'      => date('Y-m-d H:i:s'),
            'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
        ));
    }
    
    /**
     * @desc 标记事件成功
     * @param int $id
     */
    public function setSuccess($id, $message = ''){
        return $this->updateByPk($id, array(
            'status'        => self::STATUS_SUCCESS,
        	'message' 		=> $message,
            'response_time' => date('Y-m-d H:i:s'),
            'end_time'      => date('Y-m-d H:i:s'),
            'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
        ));
    }
    
    /**
     * @desc 检测是否能够运行
     * @param int $accountID
     * @param string $eventName
     */
    public function checkRunning($accountID, $eventName){
        $runningRecord = $this->find('account_id = :account_id AND status = :status AND event = :event',array(
                ':account_id'   => $accountID,
                ':status'       => self::STATUS_RUNNING,
                ':event'        => trim($eventName),
        ));
        if( isset($runningRecord->id) && $runningRecord->id > 0 ){
            $diffSec = time() - strtotime($runningRecord->response_time);     
            if( $diffSec <= self::MAX_RUNNING_TIME ){
                return false;
            }else{
                $this->setFinish($runningRecord->id);
            }
        }
        return true;
    }
    
    /**
     * @desc 根据条件获取失败的log记录
     * @param array $params
     */
    public function getLastLogByCondition( $params = array() ){
    	$whereArr = array();
    	foreach( $params as $col=>$param){
    		$whereArr[] = $col.' = "'.$param.'"';
    	}
    	return $this->dbConnection->createCommand()
    	->select('*')
    	->from(self::tableName())
    	->where(implode(' AND ', $whereArr))
    	->order('end_time DESC')
    	->queryRow();
    }
    
    /**
     * @desc 根据条件获取拉取失败的log记录
     * @param array $params
     */
    public function getFailLogsByCondition($time,$msg){
  
        return $this->dbConnection->createCommand()
                    ->select('*')
                    ->from(self::tableName())
                    ->where(' TO_DAYS(start_time) = TO_DAYS("'.$time.'") ')  
                    ->andWhere(' message != "'.$msg.'"')
                    ->andWhere(' status = 3 or status=4 ')
                    ->andWhere(' event =  '."'getorder'")
                    ->group(' account_id ')
                    ->order(' start_time ')
                    ->queryAll();
    }
    
    /**
     * @desc 根据条件获取拉取成功的log记录
     * @param array $params
     */
    public function getSucessLogsByCondition($time){
    
    	return $this->dbConnection->createCommand()
    	->select('*')
    	->from(self::tableName())
    	->where(' TO_DAYS(start_time) = TO_DAYS("'.$time.'") ')
    	->andWhere(' status = 2 ')
    	->andWhere(' event =  '."'getorder'")
    	->group(' account_id ')
    	->queryAll();
    }
    
    /**
     * @desc 获取动作日志
     * @param int $id
     */
    public function getEventLogByLogID($eventName,$id){
        $tableName = self::tableName().'_'.$eventName;
        return $this->dbConnection->createCommand()->select('*')->from($tableName)->where('log_id = '.$id)->queryRow();
    }
    
    /**
     * @desc 存储参数日志
     * @param string $eventName
     * @param array $param
     */
    public function saveEventLog($eventName, $param){
        $tableName = self::tableName().'_'.$eventName;
        $flag = $this->dbConnection->createCommand()->insert($tableName, $param);
        if( $flag ){
            return $this->dbConnection->getLastInsertID();
        }
        return false;
    }
    
    /**
     * @desc 存储参数日志状态
     * @param tinyint $status
     */
    public function saveEventStatus($eventName, $logID, $status){
        $tableName = self::tableName().'_'.$eventName;
        return $this->dbConnection->createCommand()->update($tableName, array('status' => $status), 'id = '.$logID);        
    }

    /**
     * @desc 存储参数日志记录总数
     * @param tinyint $status
     */
    public function saveEventTotal($eventName, $logID, $total = 0){
        $tableName = self::tableName().'_'.$eventName;
        $total = (int)$total;
        if ($total > 0){
            $sql = 'update ' .$tableName. ' set total = total + ' .$total. ' where id = ' .$logID;
            return $this->getDbConnection()->createCommand($sql)->execute(); 
        }
        return false;
    }    
    
    // =========== begin: liuj 2016-03-15 add search ==================

    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'id'                        =>      Yii::t('system', 'No.'),
            'account_id'		=>	'账号',
            'event'                     =>	'运行类型',
            'start_time'		=>	'开始时间',
            'end_time'			=>	'结束时间',
            'response_time'		=>	'响应时间',
            'status'			=>	'运行状态',
            'message'			=>	'运行信息'
        );
    }

    /**
     * get search info
     */
    public function search() {
            $sort = new CSort();
            $sort->attributes = array(
                'defaultOrder'  => 'id',
            );
            $dataProvider = parent::search(get_class($this), $sort);
            $data = $this->addtions($dataProvider->data);
            $dataProvider->setData($data);
            return $dataProvider;
    }

    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
            $event = Yii::app()->request->getParam('event');
            $status = Yii::app()->request->getParam('status');
            $account_id = Yii::app()->request->getParam('account_id');
            $result = array(
                array(
                        'name'=>'account_id',
                        'type'=>'dropDownList',
                        'search'=>'=',
                        'data'=>$this->getAccountList()
                ),

                array(
                                'name'=>'status',
                                'type'=>'dropDownList',
                                'search'=>'=',
                                'data'=>$this->getStatusOptions(),
                                'value'=>$status
                ),

                array(
                                'name'=>'event',
                                'type'=>'dropDownList',
                                'search'=>'=',
                                'data'=>$this->getEventOptions(),
                                'value'=>$event
                ),
                array(
                                'name'          => 'start_time',
                                'type'          => 'text',
                                'search'        => 'RANGE',
                                'htmlOptions'   => array(
                                                'class'    => 'date',
                                                'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
                                ),
                ),
            );
            return $result;
    }

    /**
     * @desc  获取公司账号
     */
    public function getAccountList(){
            if(self::$accountPairs == null)
                    self::$accountPairs = self::model('EbayAccount')->getIdNamePairs();
            return self::$accountPairs;
    }
    
    public function getStatusOptions($status = null){
            $statusOptions = array(
                self::STATUS_DEFAULT    =>  '未开始',
                self::STATUS_RUNNING    =>  '正在运行',
                self::STATUS_SUCCESS    =>  '成功',
                self::STATUS_FAILURE    =>  '失败',
                self::STATUS_FINISH     =>  '手动结束'
            );
            if($status !== null)
                return isset($statusOptions[$status])?$statusOptions[$status]:'';
            return $statusOptions;
    }

    public function getEventOptions($event = null){
            //@todo 后续语言处理
            $eventOptions = array(
                self::EVENT_GET_PRODUCT           => '拉取listing',
                self::EVENT_GETORDERIDS           => '下载订单',
                self::EVENT_CHECKORDERIDS         => '补拉订单',
                self::EVENT_DOWNTRANSACTIONS      => '下载订单交易',
                self::EVENT_SYNCORDER             => '同步订单',
                self::EVENT_UPLOAD_PRODUCT        => '上传产品',
                self::EVENT_UPLOADTRACKNUM        => '上传跟踪号',
                self::EVENT_REVISE_INVENTORY      => '自动补库存',
                self::EVENT_TRANSACTIONSEARCH     => '拉取paypal交易',
                self::EVENT_GETTRANSACTIONDETAILS => '拉取paypal交易明细',
                self::EVENT_SYNC_TRANSACTION      => '同步paypal交易',
                self::EVENT_ITEM_BEST_MATCHES     => '拉取Item转化率数据'
            );
            if($event !== null)
                    return isset($eventOptions[$event])?$eventOptions[$event]:$event;
            return $eventOptions;
    }

    public function addtions($datas){
            if(empty($datas)) return $datas;
            foreach ($datas as &$data){
                //状态
                $data['status'] = $this->getStatusOptions($data['status']);
                //类型
                $data['event'] = $this->getEventOptions($data['event']);
                $account_list = self::$accountPairs;
                if(!isset($account_list[$data['account_id']])){
                    continue;
                }
                //账号名称
                $data['account_id'] = $account_list[$data['account_id']];
            }
            return $datas;
    }
    // =========== end: 2016-03-15 add search ==================
    
    
    /**
     * @desc 存储日志
     * @param string $eventName
     * @param array $param
     */
    public function savePrepareLog($param){
        $flag = $this->dbConnection->createCommand()->insert(self::tableName(), $param);
        if( $flag ){
            return $this->dbConnection->getLastInsertID();
        }
        return false;
    }
}