<?php
/**
 * @desc Shopee日志主表
 * @author Gordon
 * @since 2015-08-07
 */ 
class ShopeeLog extends ShopeeModel{
    
    /**
     * @desc 运行状态
     * @var tinyint
     */
    const STATUS_DEFAULT    = 0;//未开始
    const STATUS_RUNNING    = 1;//运行中
    const STATUS_SUCCESS    = 2;//运行成功
    const STATUS_FAILURE    = 3;//运行失败
    const STATUS_FINISH     = 4;//手动完成
    
    const MAX_RUNNING_TIME  = 3600;//最大运行时间
    
    public static $accountPairs = array();
    /** @var 把库存置为0 */
    const EVENT_ZERO_STOCK           = 'zero_stock';
    //拉取listing
    const EVENT_GET_PRODUCT          = 'getproduct';
    /** @var 拉取取消单 */
    const EVENT_CANCELORDER          = 'cancelorder';
    /** @var 产品更新 */
    const EVENT_PRODUCT_UPDATE       = 'product_update';
    /** @var 产品更新 */
    const EVENT_PRODUCT_PRICE_UPDATE = 'product_price_update';
    /** @var 产品更新 */
    const EVENT_PRODUCT_STOCK_UPDATE = 'product_stock_update';
    /** @var 拉取订单 */
    const EVENT_GETORDER             = 'getorder';
    
    /** @var 补拉订单 */
    const EVENT_CHECK_GETORDER       = 'check_getorder';   
    
    /** @var 更新COD订单交易状态 */
    const EVENT_UPDATE_TRANS_STATUS  = 'update_trans_status';    
    
    /**@var上传跟踪号**/
    const EVENT_UPLOAD_TRACK         =  'upload_track';        
    
    /** @var 获取订单跟踪号 */
    const EVENT_GET_ORDER_TRACK_NUM  = 'get_order_track_num';

    /** @var 更新订单附加费用 */
    const EVENT_UPDATE_ORDERFEESEXT  = 'update_orderfeesext';
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_shopee_log';
    }
    
    /**
     * @desc 准备日志数据
     * @param int $accountID
     * @param string $eventName
     */
    public function prepareLog($accountID, $eventName, $siteID = 0){
        $this->setAttributes(array(
                'account_id'    => $accountID,
                'event'         => $eventName,
        		'site_id'		=>	$siteID,
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
    public function setFailure($id,$message = ''){
        
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
    public function setSuccess($id,$message = ''){
        return $this->updateByPk($id, array(
            'status'        => self::STATUS_SUCCESS,
            'message'       => $message,
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
    public function checkRunning($accountID, $eventName, $siteID = 0){
        $runningRecord = $this->find('account_id = :account_id AND status = :status AND event = :event and site_id=:site_id',array(
                ':account_id'   => $accountID,
                ':status'       => self::STATUS_RUNNING,
                ':event'        => trim($eventName),
        		':site_id'		=>$siteID
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
     * @desc 根据条件获取上一条log记录
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
     * 修改日志信息
     * @param	string	$eventName
     * @param	int		$logID
     * @param	array	$data
     */
    public function updateLogData($eventName, $logID, $data){
    	$tableName = self::tableName().'_'.$eventName;
    	return $this->dbConnection->createCommand()->update($tableName, $data, 'id = '.$logID);
    }
    
    /**
     * @desc 存储参数日志状态
     * @param tinyint $status
     */
    public function saveEventStatus($eventName, $logID, $status){
        $tableName = self::getEventTable($eventName);
        return $this->dbConnection->createCommand()->update($tableName, array('status' => $status), 'id = '.$logID);
    }
    
    /**
     * @desc 返回参数日志表名
     * @param string $eventName
     */
    public static function getEventTable($eventName){
        return self::tableName().'_'.$eventName;
    }
    
    // =========== begin: liuj 2016-03-15 add search ==================

    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'id'                =>  Yii::t('system', 'No.'),
            'account_id'		=>	'账号',
            'event'             =>	'运行类型',
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
                    self::$accountPairs = self::model('ShopeeAccount')->getAvailableIdNamePairs();
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
                self::EVENT_GET_PRODUCT          => '拉取listing',
                self::EVENT_PRODUCT_PRICE_UPDATE => '更新价格',
                self::EVENT_PRODUCT_STOCK_UPDATE => '更新库存',
                //self::EVENT_ZERO_STOCK         => '库存置零',
                //self::EVENT_CANCELORDER        => '拉取取消单',
                self::EVENT_PRODUCT_UPDATE       => '产品更新',
                self::EVENT_GETORDER             => '拉取订单',
                self::EVENT_CHECK_GETORDER       => '补拉订单',
                self::EVENT_UPDATE_TRANS_STATUS  => '更新COD订单交易状态',
                self::EVENT_UPLOAD_TRACK         => '上传跟踪号',
                self::EVENT_UPDATE_ORDERFEESEXT  => '更新订单附加费用',
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