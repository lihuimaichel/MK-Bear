<?php
class AmazonLog extends AmazonModel {
	/**
	 * @desc 运行状态
	 * @var tinyint
	 */
	const STATUS_DEFAULT       = 0;//未开始
	const STATUS_RUNNING       = 1;//运行中
	const STATUS_SUCCESS       = 2;//运行成功
	const STATUS_FAILURE       = 3;//运行失败
	const STATUS_FINISH        = 4;//手动完成
	
	const MAX_RUNNING_TIME     = 1800;//最大运行时间
	
	/** @var 把库存置为0 */
	const EVENT_ZERO_STOCK     = 'zero_stock';
	
	/** @var 上传产品 */
	const EVENT_UPLOAD_PRODUCT = 'submitfeed';        
	
	//拉取listing
	const EVENT_GET_PRODUCT    = '_GET_MERCHANT_LISTINGS_DATA_';

    //    const EVENT_GETORDER = 'detect_offline_submission';
    //    const EVENT_GETORDER = 'uploadtracknum';
    //    const EVENT_GETORDER = 'requestreport';
    //    const EVENT_GETORDER = 'pull_up_order';
    //    const EVENT_GETORDER = 'restore_request_count';

	/** @var 拉取订单 */
	const EVENT_GETORDER       = 'getorder';
	
	/** @var 补拉订单 */
	const EVENT_CHECK_GETORDER = 'check_getorder';

    /** @var 拉取订单明细 */
    const EVENT_ORDERITEMS     = 'getorderitems';  

	/** @var 同步订单 */
	const EVENT_SYNC_ORDER     = 'sync_order';

    /** @var 清除三个月前日志 */
    const EVENT_CLEAR_LOG = 'clear_log';	

    //2016-02-03 add
    public static $accountPairs = array();
    
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_amazon_log';
	}
	
	/**
	 * @desc 准备日志数据
	 * @param int $accountID
	 * @param string $eventName
	 */
	public function prepareLog($accountID, $eventName, $reportType = ''){
		$this->setAttributes(array(
				'account_id'    => $accountID,
				'event'         => $eventName,
				'start_time'    => date('Y-m-d H:i:s'),
				'response_time' => date('Y-m-d H:i:s'),
				'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
				'status'        => self::STATUS_DEFAULT,
				'message'       => $reportType
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
	public function setSuccess($id, $msg = ''){
		if (empty($msg)){
			return $this->updateByPk($id, array(
					'status'        => self::STATUS_SUCCESS,
					'response_time' => date('Y-m-d H:i:s'),
					'end_time'      => date('Y-m-d H:i:s'),
					'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
			));
		}else{
			return $this->updateByPk($id, array(
					'status'        => self::STATUS_SUCCESS,
					'response_time' => date('Y-m-d H:i:s'),
					'end_time'      => date('Y-m-d H:i:s'),
					'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
					'message'		=>	$msg
			));
		}
	}
	
	/**
	 * @desc 检测是否能够运行
	 * @param int $accountID
	 * @param string $eventName 事件名称
	 * @param int $my_max_time 自定义最大时间(秒)
	 */
	public function checkRunning($accountID = 0, $eventName = '', $my_max_time = 0){
		$runningRecord = $this->find('account_id = :account_id AND status = :status AND event = :event',array(
				':account_id'   => $accountID,
				':status'       => self::STATUS_RUNNING,
				':event'        => trim($eventName),
		));
		
		$max_time = ($my_max_time > 0) ? $my_max_time : self::MAX_RUNNING_TIME;

		if( isset($runningRecord->id) && $runningRecord->id > 0 ){
			$diffSec = time() - strtotime($runningRecord->response_time);
			if( $diffSec <= $max_time ){
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
	 * @desc 存储参数日志状态
	 * @param tinyint $status
	 */
	public function saveEventStatus($eventName, $logID, $status){
		$tableName = self::tableName().'_'.$eventName;
		return $this->dbConnection->createCommand()->update($tableName, array('status' => $status), 'id = '.$logID);
	}	
	/**
	 * @desc 获取事件日志by logID
	 * @param unknown $logID
	 */
	public function getEventLogListByLogID($eventName, $logID){
		$tableName = self::tableName().'_'.$eventName;
		return $this->dbConnection->createCommand()
								->from($tableName)
								->where("log_id=:log_id", array(":log_id"=>$logID))
								->queryRow();
		
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
                    self::$accountPairs = self::model('AmazonAccount')->getIdNamePairs();
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
				self::EVENT_ZERO_STOCK     => '库存置零',
				self::EVENT_GET_PRODUCT    => '拉取listing',
				self::EVENT_UPLOAD_PRODUCT => '上传产品',
				self::EVENT_GETORDER       => '拉取订单',
				self::EVENT_CHECK_GETORDER => '补拉订单',
				self::EVENT_ORDERITEMS 	   => '拉取订单明细',
				self::EVENT_SYNC_ORDER     => '同步订单',	
				self::EVENT_CLEAR_LOG      => '清除日志',			
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
	 * @desc 获取最近一条账号请求记录信息
	 * @param int $accountId 账号
	 * @param string $reportType 事件类型
	 */
	public function getAccountLastRequestInfo($accountId, $reportType) {
		return $this->getDbConnection()->createCommand()
			->select('*')
			->from(self::model()->tableName())
			->order("id desc")
			->where("account_id = :id and event = :type", array(':id' => $accountId, ':type' => $reportType))
			->limit(1)
			->queryRow();
	}


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