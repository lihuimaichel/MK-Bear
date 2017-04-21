<?php
/**
 * @desc Ebay产品管理
 * @author Gordon
 * @since 2015-07-31
 */
class EbayProduct extends EbayModel{
    
    /** @var 事件名称*/
    const EVENT_NAME = 'get_product';

    const EVENT_NAME_GETALL = 'get_product_all';

    /** @var 更新listing事件*/
    const EVENT_NAME_UPDATE = 'update_product';
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var int 站点ID*/
    public $_siteID = 0;
    
    /** @var string 异常信息*/
    public $_exception = null;
    /**
     * 异常code
     * @var unknown
     */
    public $_exceptionCode = null;
    
    /** @var int 日志编号*/
    public $_LogID = 0;
    
    /** @var object 拉listing返回信息*/
    public $_listing_Response = null;
    /** @var 拉取listing响应时间 */
    private $_getlistingTimestamp = '';
    /**@var 在线状态*/
    const STATUS_ONLINE     = 1;
    const STATUS_OFFLINE    = 0;
    
    public $seller_id = null;
    public $seller_name = null;
    public $detail = null;
    public $account_name = null;
    public $item_status_text = null;
    public $item_id_link = null;
    public $site_name = null;
    public $_SkuArray = null;   //再次listing用的SKU数组
    public $listing_type_text = null;   //listing类型  
    public $_event_logID = 0;  //事件日志ID 
    public $_listing_mode = 0;   //拉listing模式
    public $_listing_time_type = 0;   //拉listing时间类型
    public $variants_id;
    public $profit;
    public $profit_rate;
    public $li_height;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product';
    }

    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
        $this->_accountID = $accountID;
    }
    
    /**
     * @desc 设置日志编号
     * @param int $logID
     */
    public function setLogID($logID) {
    	$this->_LogID = $logID;
    }

    /**
     * @desc 设置拉取方式
     * @param int $mode 默认为0，1为拉全部数据
     */
    public function setListingMode($mode = 0) {
        $this->_listing_mode = $mode;
    } 

    /**
     * @desc 设置拉取时间类型
     * @param int $type 默认为0，刊登时间；1下架时间
     */
    public function setListingTimeType($type = 0) {
        $this->_listing_time_type = $type;
    }         

    
    public function getExceptionCode(){
    	return $this->_exceptionCode;
    }
    
    public function setExceptionCode($exceptionCode){
    	$this->_exceptionCode = $exceptionCode;
    	return $this;
    }
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->_exception = $message;
    }
    
    /**
     * @desc 设置站点ID
     */
    public function setSite($site){
        $this->_siteID = $site;
    }
    
    /**
     * @desc 保存数据
     * @param array $params
     */
    public function saveProductRecord($params){
    	$tableName = self::tableName();
    	$flag = $this->dbConnection->createCommand()->insert($tableName, $params);
    	if($flag) {
    		return $this->dbConnection->getLastInsertID();
    	}
    	return false;
        //return $this->dbConnection->createCommand()->insert(self::tableName(), $params);
    }
    
    /**
     * @desc 获取拉产品listing时间段
     * @since 2015/08/12
     */
    public function getTimeArr($accountID){
    	$lastLog = EbayLog::model()->getLastLogByCondition(array(
    			'account_id'    => $accountID,
    			'event'         => self::EVENT_NAME,
    			'status'        => EbayLog::STATUS_SUCCESS,
    	));
    	return array(
    			//上次有成功,则将上次结束时间往前推15分钟，避免漏单，若不存在已成功的，则从1天前开始拉(需换算成格林威治时间)
//     			'start_time'    => !empty($lastLog) ? date('Y-m-d\TH:i:s\Z',strtotime($lastLog['end_time']) - 15*60) : date('Y-m-d\TH:i:s\Z',time() - 86400*1 - 8*3600),
    			'start_time'    => !empty($lastLog) ? date('Y-m-d\TH:i:s\Z',strtotime($lastLog['end_time']) - 15*60 - 8*3600) : date('Y-m-d\TH:i:s\Z',time() - 86400*7*4 - 8*3600),
    			'end_time'      => date('Y-m-d\TH:i:s\Z',time() - 8*3600),
    	);
    }


    /**
     * @desc 获取拉产品listing时间段(新)
     * @param  int $mode 1为抓取所有数据（48天）
     * @since 2016/06/14
     */
    public function getNewTimeArr($mode = 0, $day = 0){
        if ($mode == 1){
            $startTime = date('Y-m-d\T00:00:00\Z',time()-86400*3);   //开始时间，三天前   
            $endTime = date('Y-m-d\T00:00:00\Z',time()+86400*45); //结束时间，将来45天，通过下架时间计算账号在线listing，GTC和一口价最大有效时间是30天，所以只要30天就可以获取所有在线listing数据
        }else{
            // $endTimeFrom = date('Y-m-d\T00:00:00\Z',time()-86400*1);   //开始时间，一天前   
            // $endTimeTo = date('Y-m-d\T00:00:00\Z',time()+86400*31);  //结束时间，后三十天，超过30天（固定价最长周期时间为30天）  
      		if($day){
      			$startTime = date('Y-m-d\T00:00:00\Z',time()-86400*$day);   //开始时间，指定多少天前
      		}else{
      			$startTime = date('Y-m-d\T00:00:00\Z',time()-86400*3);   //开始时间，三天前
      		}
               
            $endTime = date('Y-m-d\T00:00:00\Z',time()+86400*2);  //结束时间，后两天，共5天
        }
        return array(
            'start_time' => $startTime,
            'end_time'   => $endTime,
        );
    }    
    
    /**
     * @desc 根据sku获取listing
     * @param string $sku
     * @param tinyint $siteID
     * @param boolean $includeVariation
     */
    public function getListingBySku($sku,$siteID,$includeVariation){
        $build = $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName().' AS ep')
                ->where('site_id = '.$siteID)
                ->andWhere('item_status = '.self::STATUS_ONLINE);
        if( $includeVariation ){
            $list = $build->leftJoin(EbayProductVariation::model()->tableName().' AS epv', 'epv.item_id = ep.item_id')
                    ->andWhere('ep.sku = "'.$sku.'" OR epv.sku = "'.$sku.'"')->queryAll();
        }else{
            $list = $build->andWhere('ep.sku = "'.$sku.'"')->queryAll();
        }
        return $list;
    }
    
    /**
     * @desc 依据条件获取指定的listing
     * @param Array $date
     */
    public function getListingByDate($date) {
    	return $this->getListingByCondition(array(
    		'StartTimeFrom' => $date['start_time'], 
    		'StartTimeTo'   => $date['end_time'],
    	));
    }
    
    /**
     * @desc 获取listring
     * @param array $params
     */
    public function getListingByCondition ($params = array()) {
    	$accountID = $this->_accountID;
    	$request = new GetSellerListRequest();
    	$request->setIncludeVariations(true);
    	foreach($params as $col=>$val){
    		switch ($col){
    			case 'StartTimeFrom':
    				$request->setStartTimeFrom($val);
    				break;
    			case 'StartTimeTo':
    				$request->setStartTimeTo($val);
    				break;	
    		}
    	}
    	while($request->_pageNumber <= $request->_totalPage){
    		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
    		echo "<pre>";
    		print_r($response);
    		echo "</pre>";
    		exit;
    		if($request->getIfSuccess()){
    			$request->setTotalPage($response->PaginationResult->TotalNumberOfPages); //设置总页数
    			$request->setPageNumber($request->_pageNumber + 1);
    			try {
    				//遍历listing数据
    				foreach($response->ItemArray->Item as $item){
    					$this->_listing_Response = $item;
    					unset($item);
    					// 1. 保存listing数据信息
    					$listingID = $this->saveListingInfo();
    					if($listingID){
    						// 2. 保存listing多属性数据信息
    						$this->saveListingVariation($listingID);
    						// 3. 保存extend信息
    						$this->saveListingDetails($listingID);
    					}
    				}
    			}catch (Exception $e) {
    				$this->setExceptionMessage(Yii::t('listing', 'Save listing Information Failed：'. $e->getMessage()));
    				return false;
    			}
    		}else {
    			$this->setExceptionMessage($request->getErrorMsg());
    			return false;
    		}
    	}
    	return true;
    }

    /**
     * @param $accountId
     * @author ketu.lai
     */
    public function getFullListingByAccountId($accountId)
    {
        if(!$accountId) {
            return false;
        }
        $this->setAccountID($accountId);

        $logID = EbayLog::model()->prepareLog($accountId,EbayProduct::EVENT_NAME);

        $request = new GetSellerListRequest();
        $totalPage = 1;
        $pageNum = 1;

        while($pageNum <= $totalPage){
            $request->setIncludeVariations(true);
            $request->setIncludeWatchCount(true);
            $response = $request->setAccount($accountId)->setRequest()->sendRequest()->getResponse();

            if($request->getIfSuccess()){
                $this->_getlistingTimestamp = $response->Timestamp;

                $totalPage = $response->PaginationResult->TotalNumberOfPages; //设置总页数
                $request->setPageNum(++$pageNum);
                try {
                    foreach($response->ItemArray->Item as $item){
                        $this->_listing_Response = $item;
                        unset($item);
                        $listingID = $this->saveListingInfo();
                        if($listingID){
                            $this->saveListingVariation($listingID);
                        }
                        EbayLog::model()->setSuccess($logID);
                        return true;
                    }
                }catch (Exception $e) {
                    $this->setExceptionMessage(Yii::t('listing', 'Save listing Information Failed：'. $e->getMessage()));
                }
            }else {
                $this->setExceptionMessage($request->getErrorMsg());
            }
        }
        EbayLog::model()->setFailure($logID);

        return false;
    }

    /**
     * @desc 根据账号ID拉取listing
     * @param unknown $accountID
     * @return boolean
     */
    public function getListingByAccountID($accountID){
    	if($accountID){
    		$logID = EbayLog::model()->prepareLog($accountID,EbayProduct::EVENT_NAME);
    		if($logID) {
    			// 1.检查该帐号是否可拉取listing
    			$checkRunning = EbayLog::model()->checkRunning($accountID, EbayProduct::EVENT_NAME);
    			if(!$checkRunning){
    				EbayLog::model()->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
    				//echo Yii::t('system', 'There Exists An Active Event');
    			}else{
    				// 2.准备拉取日志信息
    				$timeArr = EbayProduct::model()->getTimeArr($accountID);

    				//$timeArr['start_time'] = "2010-10-01T00:00:00Z";
    				// 插入本次log参数日志(用来记录请求的参数)
    				$eventLog = EbayLog::model()->saveEventLog(
    						EbayProduct::EVENT_NAME,
    						array(
    								'log_id'     => $logID,
    								'account_id' => $accountID,
    								'start_time' => $timeArr['start_time'],
    								'end_time'   => $timeArr['end_time'],
    						)
    				);
    				// 设置日志的状态为正在运行
    				EbayLog::model()->setRunning($logID);
    				// 3. 拉取listing
    				$ebayProductModel = new EbayProduct();
    				$ebayProductModel->setAccountID($accountID);  //设置帐号
    				$ebayProductModel->setLogID($logID); //设置日志编号
    				$flag = $ebayProductModel->getListing();
    				// 4.更新日志信息
    				if($flag){
    					EbayLog::model()->setSuccess($logID);
    					EbayLog::model()->saveEventStatus(EbayProduct::EVENT_NAME,$eventLog,EbayLog::STATUS_SUCCESS);
    					return true;
    				}else {
    					EbayLog::model()->setFailure($logID,$ebayProductModel->getExceptionMessage());
    					EbayLog::model()->saveEventStatus(EbayProduct::EVENT_NAME,$eventLog,EbayLog::STATUS_FAILURE);
    				}
    			}
    		}
    	}
    	return false;
    }
    
    /**
     * @desc 拉取产品 
     * @return boolean
     */
    public function getListing() {
    	$startTime = date("Y-m-d H:i:s",time()-28800-60*10);
    	$accountID = $this->_accountID;
    	//$request = new GetMyeBaySellingRequest();
    	$request = new GetSellerListRequest();
    	$totalPage = 1;
    	$pageNum = 1;
    	$endTimeFrom = date('Y-m-d\TH:i:s\Z',time()-3600*72);//开始时间
    	$endTimeTo = date('Y-m-d\T00:00:00\Z',time()+86400*45);//结束时间
    	while($pageNum <= $totalPage){
    		$request->setIncludeVariations(true);
    		$request->setIncludeWatchCount(true);
    		$request->setEndTimeFrom($endTimeFrom);
    		$request->setEndTimeTo($endTimeTo);
    		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
    		/* echo "<pre>";
    		print_r($response);
    		echo "</pre>";
    		exit; */
    		if($request->getIfSuccess()){
    			$this->_getlistingTimestamp = $response->Timestamp;
    			//$response = $response->ActiveList;
    			$totalPage = $response->PaginationResult->TotalNumberOfPages; //设置总页数
    			$request->setPageNum(++$pageNum);
    			
    			try {
    				//遍历listing数据
    				foreach($response->ItemArray->Item as $item){
    					$this->_listing_Response = $item;
    					unset($item);
    					// 1. 保存listing数据信息
    					$listingID = $this->saveListingInfo();
    					if($listingID){
    						// 2. 保存listing多属性数据信息
    						$this->saveListingVariation($listingID);
    					}
    				}
    			}catch (Exception $e) {
    				$this->setExceptionMessage(Yii::t('listing', 'Save listing Information Failed：'. $e->getMessage()));
    				return false;
    			}
    		}else {
    			$this->setExceptionMessage($request->getErrorMsg());
    			return false;
    		}
    	}
    	//下线
    	// $this->getDbConnection()->createCommand()->update($this->tableName(), 
    	// 											array('item_status'=>self::STATUS_OFFLINE), 
    	// 											"account_id='{$this->_accountID}' and timestamp<'$startTime'");
    	return true;
    }


    /**
     * @desc 根据账号ID拉取listing（new） Liz|20160603
     * @param int $accountID
     * @param int $mode 拉取模式1:获取以listing下架计算48天时间段即所有数据；0:获取以listing刊登（或上架）时间计算前后几天内数据
     * @param int $type 拉取listing时间类型：0-刊登时间；1-下架时间；
     * @return boolean
     */
    public function getNewListingByAccountID($accountID,$type,$mode, $day = 0){
        if($accountID){     
            $eventName = $mode == 1 ? EbayProduct::EVENT_NAME_GETALL : EbayProduct::EVENT_NAME;
            $logID = EbayLog::model()->prepareLog($accountID,$eventName);
            if($logID) {
                // 1.检查该帐号是否可拉取listing
                $checkRunning = EbayLog::model()->checkRunning($accountID, $eventName);
                if(!$checkRunning){
                    echo 'There Exists An Active Event<br>';
                    EbayLog::model()->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                }else{
                    // 2.准备拉取日志信息，设置拉取的时间段
                    $eventLog = null;
                    $timeArr = $this->getNewTimeArr($mode, $day);
                    if ($mode != 1) {
                        $eventLog = EbayLog::model()->saveEventLog(
                                $eventName,
                                array(
                                    'log_id'     => $logID,
                                    'account_id' => $accountID,
                                    'start_time' => date('Y-m-d H:i:s',strtotime($timeArr['start_time'])),
                                    'end_time'   => date('Y-m-d H:i:s',strtotime($timeArr['end_time'])),
                                )
                        );
                    }
                    // 设置日志的状态为正在运行
                    EbayLog::model()->setRunning($logID);
                    // 3. 拉取listing
                    $ebayProductModel = new EbayProduct();
                    $ebayProductModel->setAccountID($accountID);    //设置帐号
                    $ebayProductModel->setLogID($logID);            //设置日志编号
                    $ebayProductModel->setEventLogID($eventLog);    //设置事件日志编号
                    $ebayProductModel->setListingMode($mode);       //设置拉取模式
                    $ebayProductModel->setListingTimeType($type);   //设置拉取时间类型                        

                    //获取listing拉单接口数据，并把数据更新或新增入库
                    $flag = $ebayProductModel->getNewListing($timeArr['start_time'],$timeArr['end_time']);

                    // 4.更新日志信息
                    if($flag){
                        EbayLog::model()->setSuccess($logID);
                        if ($mode != 1) {
                            EbayLog::model()->saveEventStatus($eventName,$eventLog,EbayLog::STATUS_SUCCESS);
                        }
                        return true;
                    }else {
                        EbayLog::model()->setFailure($logID,$ebayProductModel->getExceptionMessage());
                        if ($mode != 1) {
                            EbayLog::model()->saveEventStatus($eventName,$eventLog,EbayLog::STATUS_FAILURE);
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * @desc 根据账号ID拉取listing（英国） Liz|20160603
     * @param int $accountID
     * @param int $mode 拉取模式1:获取以listing下架计算48天时间段即所有数据；0:获取以listing刊登（或上架）时间计算前后几天内数据
     * @param int $type 拉取listing时间类型：0-刊登时间；1-下架时间；
     * @return boolean
     */
    public function getUKNewListingByAccountID($accountID,$type,$mode){
        if($accountID){     
            $logID = EbayLog::model()->prepareLog($accountID,EbayProduct::EVENT_NAME);
            if($logID) {
                // 1.检查该帐号是否可拉取listing
                $checkRunning = EbayLog::model()->checkRunning($accountID, EbayProduct::EVENT_NAME);
                if(!$checkRunning){
                    EbayLog::model()->setFailure($logID, Yii::t('system', 'There Exists An Active Event'));
                }else{
                    // 2.准备拉取日志信息，设置拉取的时间段
                    $timeArr = $this->getNewTimeArr($mode);
                    $eventLog = EbayLog::model()->saveEventLog(
                            EbayProduct::EVENT_NAME,
                            array(
                                    'log_id'     => $logID,
                                    'account_id' => $accountID,
                                    'start_time' => $timeArr['start_time'],
                                    'end_time'   => $timeArr['end_time'],
                            )
                    );

                    // 设置日志的状态为正在运行
                    EbayLog::model()->setRunning($logID);
                    // 3. 拉取listing
                    $ebayProductModel = new EbayProduct();
                    $ebayProductModel->setAccountID($accountID);    //设置帐号
                    $ebayProductModel->setLogID($logID);            //设置日志编号
                    $ebayProductModel->setEventLogID($eventLog);    //设置事件日志编号
                    $ebayProductModel->setListingMode($mode);       //设置拉取模式
                    $ebayProductModel->setListingTimeType($type);   //设置拉取时间类型                        

                    $flag = $ebayProductModel->getNewListing($timeArr['start_time'],$timeArr['end_time']);


                    // 4.更新日志信息
                    if($flag){
                        EbayLog::model()->setSuccess($logID);
                        EbayLog::model()->saveEventStatus(EbayProduct::EVENT_NAME,$eventLog,EbayLog::STATUS_SUCCESS);
                        return true;
                    }else {
                        EbayLog::model()->setFailure($logID,$ebayProductModel->getExceptionMessage());
                        EbayLog::model()->saveEventStatus(EbayProduct::EVENT_NAME,$eventLog,EbayLog::STATUS_FAILURE);
                    }
                }
            }
        }
        return false;
    }    

    /**
     * @desc 循环时间 拉取产品
     * @return boolean
     */
    public function getNewListingByDate($startTime,$endTime) {
        $currentTime = $startTime;
        $nextTime = date('Y-m-d\T00:00:00\Z',strtotime($currentTime)+86400*10);  //每次增加十天
        do{
            $this->getNewListing($currentTime,$nextTime);

            $currentTime = $nextTime;
            $nextTime = date('Y-m-d\T00:00:00\Z',strtotime($currentTime)+86400*10);
        }
        while($nextTime <= $endTime);
        return true;
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
     * @return array
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
     * 获取ebay产品信息
     * @param  string $fields 
     * @param  string $where 
     * @param  string $order 
     * @return [type]     
     */
    public function getEbayProductInfoJoinAccount($fields='a.*', $where='1',$order=''){
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName().' as a')
            ->leftJoin(EbayAccount::tableName().' as b', "a.account_id=b.id")
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();        
    }   

    /**
     * @desc 拉取产品 
     * @return boolean
     */
    public function getNewListing($startTime,$endTime) {
        $accountID       = $this->_accountID;
        $listingMode     = $this->_listing_mode;
        $listingTimeType = $this->_listing_time_type;

        $request = new GetSellerListRequest();
        $totalPage = 1;
        $pageNum = 1;
        $path  = 'ebay/GetSellerList/'.date("Ymd").'/'.$accountID;

        while($pageNum <= $totalPage){
            $request->setIncludeVariations(true);
            $request->setIncludeWatchCount(true);

            //如果拉取全部数据或时间类型为1（下架时间）
            if ($listingMode == 1 || $listingTimeType == 1){
                $request->setEndTimeFrom($startTime);
                $request->setEndTimeTo($endTime);
            }else{
                //拉取部分数据，以上架时间计算前后几天的时间段
                $request->setStartTimeFrom($startTime);
                $request->setStartTimeTo($endTime);  
            }

            //如果有SKU数组为参数拉单
            $request->setPageNum($pageNum);
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            //MHelper::writefilelog($path.'/response_'.$pageNum.'.txt', print_r($response,true)."\r\n");

            $pageNum++;
            if($request->getIfSuccess()){
                $this->_getlistingTimestamp = (string)$response->Timestamp;

                $totalPage = $response->PaginationResult->TotalNumberOfPages; //设置总页数
                //每页总数据量入库累加 便于跟踪添加到哪位置，不用接口提供的记录总数：$response->PaginationResult->TotalNumberOfEntries
                $page_listing_nums = 0;
                if (isset($response->ItemArray->Item)) $page_listing_nums = count($response->ItemArray->Item);

                if ($this->_event_logID) {
                    $this->setListingNums($page_listing_nums);
                }
                
                try {
                    //过滤帐号错误
                    if($response->Seller->Email=='Invalid Request'){
                        return false;
                    }
                    $accountInfo = EbayAccount::getAccountInfoById($this->_accountID);
                    if($accountInfo['email'] != $response->Seller->Email && $accountInfo['user_name']!= $response->Seller->UserID){
                        return false;
                    }
                    //遍历listing数据
                    if ($response->ItemArray){
                        foreach($response->ItemArray->Item as $item){
                            $this->_listing_Response = $item;
                            unset($item);
                            
                            //插入运费信息
                            $ebayProductShippingModel = new EbayProductShipping();
                            $ebayProductShippingModel->insertShipping($this->_accountID,$this->_listing_Response->ItemID,$this->_listing_Response->ShippingDetails);

                            // 1. 更新listing数据信息
                            $listingID = $this->updateListingInfo();
                            if($listingID){
                                // 2. 保存listing多属性数据信息
                                $this->saveListingVariation($listingID);

                                // 3. 保存extend信息
                                $this->saveListingDetails($listingID);
                            }
                        }
                    }
                }catch (Exception $e) {
                    $this->setExceptionMessage(Yii::t('listing', 'Save listing Information Failed：'. $e->getMessage()));
                    return false;
                }
            }else {
                $this->setExceptionMessage($request->getErrorMsg());
                return false;
            }
        }
        return true;
    }    

    /***
     * @desc 更新产品listing信息 -- 保存主表信息
     * @param object 
     */
    public function updateListingInfo(){
        $listing = $this->_listing_Response;
        $statuslog = array();

        if (!isset($listing->SKU) || empty($listing->SKU)) return false;    //如果没有SKU，很可能是正在删除的listing，不用入库 Liz|20160615
        //MHelper::writefilelog('ebay/updateListingInfo.txt', date('Y-m-d H:i:s').' @@@ responsedata ### '.print_r($listing,true)."\r\n\r\n");

        $itemID = trim($listing->ItemID);
        $onlineSku = trim($listing->SKU);
        
        //保存description
        if (isset($listing->Description)) {
            $filename  = $this->_accountID .'/'. $listing->ItemID. '.html';
            $description = trim($listing->Description);
            MHelper::saveDescription($filename, $description, Platform::CODE_EBAY );
        }

        $isMulti = 0;
        if(isset($listing->Variations) && !empty($listing->Variations)){
            $isMulti = 1;
        }

        // $statuslog['itemID'] = $itemID;
        // $statuslog['status'] = isset($listing->SellingStatus->ListingStatus)  ? trim($listing->SellingStatus->ListingStatus) : 'empty';

        if (isset($listing->SellingStatus->ListingStatus) ) {
            $itemStatus = self::STATUS_OFFLINE;    //默认下架状态: 0 listing下架状态(Completed、Ended)
            if ( trim($listing->SellingStatus->ListingStatus) == 'Active' ){
                $itemStatus = self::STATUS_ONLINE; //在线状态: 1
                //$statuslog['status_2'] = $itemStatus;
            } 
        }

        $quantity = (int)$listing->Quantity;// quantity = available + QuantitySold
        $soldQty = (int)$listing->SellingStatus->QuantitySold;// the sum of all varations QuantitySold
        $availableQty = $quantity - $soldQty;//available
        if ($availableQty < 0) {
            $availableQty = 0 ;
        }

        $ebaySite = new EbaySite();
        $listing_site_name = isset($listing->Site) ? (string)$listing->Site : '';
        //针对处理[Site] => eBayMotors：eBayMotors站点归为美国站点，因为eBayMotors本来就是美国站点的下的一个子站
        if (!empty($listing_site_name)){
            if($listing_site_name == 'eBayMotors') $listing_site_name = 'US';
        }
        $siteID = !empty($listing_site_name) ? $ebaySite->getSiteIdByName($listing_site_name) : '';
        $itemInfo = $this->getItemRow($this->_accountID, $itemID);
        $variationSpecificName = "";
        if($itemInfo){
        	$variationSpecificName = isset($itemInfo->Variations->Pictures->VariationSpecificName) ? (string)$itemInfo->Variations->Pictures->VariationSpecificName: '';
        }
        $addData = array(
                'item_id'                    => $itemID,
                'site_id'                    => $siteID,
                'account_id'                 => $this->_accountID,
                'sku'                        => isset($listing->SKU) ? encryptSku::getRealSku($onlineSku) : '',
                'sku_online'                 => isset($listing->SKU) ? $onlineSku : '',
                'title'                      => isset($listing->Title) ? trim($listing->Title) : '',
                'view_item_url'              => isset($listing->ListingDetails->ViewItemURL) ? trim($listing->ListingDetails->ViewItemURL) : '',
                'gallery_url'                =>(isset($listing->PictureDetails) && isset($listing->PictureDetails->GalleryURL)) ? trim($listing->PictureDetails->GalleryURL) : '',
                'quantity'                   => $quantity,
                'quantity_sold'              => $soldQty,
                'quantity_available'         => $availableQty,
                'listing_duration'           => isset($listing->ListingDuration) ? trim($listing->ListingDuration) : '',
                'listing_type'               => isset($listing->ListingType) ? trim($listing->ListingType) : '',
                'buyitnow_price'             => isset($listing->BuyItNowPrice) ? floatval($listing->BuyItNowPrice) : 0,
                'buyitnow_price_currency'    => isset($listing->BuyItNowPrice['currencyID']) ? $listing->BuyItNowPrice['currencyID'] : '',
                'current_price'              => isset($listing->SellingStatus->CurrentPrice) ? floatval($listing->SellingStatus->CurrentPrice) : 0,
                'current_price_currency'     => isset($listing->SellingStatus->CurrentPrice['currencyID']) ? trim($listing->SellingStatus->CurrentPrice['currencyID']) : '',
                'shipping_price'             => isset($listing->ShippingDetails->ShippingServiceOptions->ShippingServiceCost) ? floatval($listing->ShippingDetails->ShippingServiceOptions->ShippingServiceCost) : 0,
                'shipping_price_currency'    => isset($listing->ShippingDetails->ShippingServiceOptions->ShippingServiceCost['currencyID']) ? trim($listing->ShippingDetails->ShippingServiceOptions->ShippingServiceCost['currencyID']) : '',
                'total_price'                =>0.00,
                'total_price_currency'       =>'',
                'timestamp'                  => $this->transationUTCFormatTimeToNormal($this->_getlistingTimestamp),
                'start_time'                 => isset($listing->ListingDetails->StartTime) ? date('Y-m-d H:i:s',strtotime(trim($listing->ListingDetails->StartTime))-8*3600) : '0000-00-00 00:00:00',
                'end_time'                   => isset($listing->ListingDetails->EndTime) ? date('Y-m-d H:i:s',strtotime(trim($listing->ListingDetails->EndTime))-8*3600) : '0000-00-00 00:00:00',   //下架时间：如果为0则记录下架时间，如果为1则为空
                'time_left'                  => isset($listing->TimeLeft) ? trim($listing->TimeLeft) : 0,
                'update_sku'                 => 0,
                'paypal_email'               => isset($listing->PayPalEmailAddress) ? trim($listing->PayPalEmailAddress) : '',
                'question_count'             =>0,
                'is_multiple'                =>$isMulti,
                'watch_count'                =>isset($listing->WatchCount) ? intval($listing->WatchCount) : 0,
                'location'                   =>isset($listing->Location) ? trim($listing->Location) : '',
                'handing_time'               =>isset($listing->DispatchTimeMax) ? intval($listing->DispatchTimeMax) : 0,
                'category_id'                =>isset($listing->PrimaryCategory->CategoryID) ? trim($listing->PrimaryCategory->CategoryID) : 0,
                'category_id2'               =>0,
                'category_name'              =>isset($listing->PrimaryCategory->CategoryName) ? trim($listing->PrimaryCategory->CategoryName) : 0,
                'store_category_id'          =>isset($listing->Storefront->StoreCategoryID)? trim($listing->Storefront->StoreCategoryID) : 0,
                'subtitle'                   =>'',
        		'variation_picture_specific' => $variationSpecificName,
        );
        // ==== lihy add 2016-02-15 ====
        $addData['sale_start_time'] =   isset($listing->SellingStatus->PromotionalSaleDetails->StartTime) ? date('Y-m-d H:i:s',strtotime( trim($listing->SellingStatus->PromotionalSaleDetails->StartTime) )-8*3600): '0000-00-00 00:00:00';
        $addData['sale_end_time']   =   isset($listing->SellingStatus->PromotionalSaleDetails->EndTime) ? date('Y-m-d H:i:s',strtotime( trim($listing->SellingStatus->PromotionalSaleDetails->EndTime) )-8*3600) : '0000-00-00 00:00:00';
        $addData['original_price']  =   isset($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice) ? floatval($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice) : 0;
        $addData['original_price_currency'] =   isset($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice['currencyID']) ? strval($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice['currencyID']) : '';
        $addData['is_promote']              =   (isset($listing->SellingStatus->PromotionalSaleDetails->EndTime) && trim($listing->SellingStatus->PromotionalSaleDetails->EndTime) > date("Y-m-d H:i:s", time()-8*3600+30*60) ) ? 1 : 0;
        // ==== lihy add ====

        //新增字段，更新拉listing表机制 Liz|20160530
        $logID = $this->_LogID; //日志ID
        $addData['log_id']         = $logID;
        // $addData['confirm_status'] = 1;
        $addData['bak_time']       = date('Y-m-d H:i:s');

        if (!is_null($itemStatus)) {
            $addData['item_status'] = $itemStatus;
            $statuslog['status_3'] = $itemStatus;
        }

        //更新刊登记录为下架
        if ( self::STATUS_OFFLINE == $addData['item_status'] ) {
            EbayProductAdd::model()->getDbConnection()->createCommand()->update(EbayProductAdd::tableName(),array('item_status'=> self::STATUS_OFFLINE),"item_id='{$itemID}'");
        }
        //MHelper::writefilelog('ebay/getNewListing.txt', date('Y-m-d H:i:s').' @@ result ### '.print_r($statuslog,true)."\r\n\r\n");

        //判断是否存在
        $checkExists = $this->getOneByCondition("id","item_id='{$itemID}'");
        if($checkExists){//update
            $flag = $this->dbConnection->createCommand()->update($this->tableName(),$addData,"item_id='{$itemID}'");
            if($flag){
                return $checkExists['id'];
            }else{
                return false;
            }
        }else{//insert
            $addData['create_time'] = date('Y-m-d H:i:s');
            return $this->saveProductRecord($addData);   
        }
    }    
    
    /**
     * @desc 获取单个item数据
     * @param unknown $accountID
     * @param unknown $itemID
     * @return boolean
     */
    public function getItemRow($accountID, $itemID){
    	$request = new GetItemRequest();
    	$request->setItemID($itemID);
    	$request->setAccount($accountID);
    	$request->setIncludeSpecifics(true);
    	$request->setIncludeVariations(true);
    	$request->setIncludeWatchCount(true);
    	$request->setOutSelector(true);
    	$response = $request->setRequest()->sendRequest()->getResponse();
    	if($request->getIfSuccess()){
    		return $response->Item;
    	}else{
    		return false;
    	}
    }
    /**
     * @DESC 获取单个
     * @todo 
     * @param unknown $itemID
     */
    public function getItemInfo($itemID) {
    	$accountID = $this->_accountID;
        if (empty($accountID)) {
            $ebayProductInfo = $this->getOneByCondition('account_id',"item_id='{$itemID}'");
            if (!empty($ebayProductInfo)) {
                $accountID = $ebayProductInfo['account_id'];
            } else {
                return false;
            }
        }   
        $this->_accountID = $accountID;     
    	$request = new GetItemRequest();
    	$request->setItemID($itemID);
    	$request->setAccount($accountID);
    	$request->setIncludeSpecifics(true);
    	$request->setIncludeVariations(true);
    	$request->setIncludeWatchCount(true);
    	$request->setOutSelector(true);
    	$response = $request->setRequest()->sendRequest()->getResponse();
    	//echo "<pre>";
    	//print_r($response);
    	//exit;
        //MHelper::writefilelog('ebay/getItem/'.$accountID.'/'.date("Ymd").'/getItem.txt',print_r($response,true)."\r\n\r\n");
    	if($request->getIfSuccess()){
    		try{
                $this->_getlistingTimestamp = (string)$response->Timestamp;
    			$this->_listing_Response = $response->Item;
    			$listingID = $this->saveListingInfo();
    			if($listingID){
    				// 2. 保存listing多属性数据信息
    				$this->saveListingVariation($listingID);
    			}
    		}catch (Exception $e){
    			$this->setExceptionMessage($e->getMessage());
    			return false;
    		}
    	}else{
    		$this->setExceptionMessage($request->getErrorMsg());
    		return false;
    	}
    	return true;
    }

    /**
     * @desc 把UTC格式转换为普通格式，时间不变
     * @param unknown $utcTime
     * @return string
     */
    public function transationUTCFormatTimeToNormal($utcTime){
    	$normalTime = date("Y-m-d H:i:s", strtotime($utcTime)-8*3600);
    	return $normalTime;
    }

    /***
     * @desc 保存产品listing信息
     * @param object 
     */
    public function saveListingInfo(){
        $listing      = $this->_listing_Response;
        //帐号不正确过滤
        if($listing->Seller->Email=='Invalid Request'){
            return false;
        }
        $accountInfo = EbayAccount::getAccountInfoById($this->_accountID);
        if($accountInfo['email'] != $listing->Seller->Email && $accountInfo['user_name']!= $listing->Seller->UserID){
            return false;
        }
        //MHelper::writefilelog('ebay/saveListingInfo.txt', date('Y-m-d H:i:s').' @@ responsedata ### '.print_r($listing,true)."\r\n\r\n");    
        $statuslog    = array();
        $itemID       = trim($listing->ItemID);
        $onlineSku    = trim($listing->SKU);
        $quantity     = intval($listing->Quantity);
        $soldQty      = intval($listing->SellingStatus->QuantitySold);
        $availableQty = $quantity - $soldQty;
        if ($availableQty < 0) {
            $availableQty = 0;
        }
        $isMulti = 0;
        if(isset($listing->Variations) && !empty($listing->Variations)){
            $isMulti = 1;
        }
        
        $statuslog['itemID'] = $itemID;
        $statuslog['status'] = isset($listing->SellingStatus->ListingStatus)  ? trim($listing->SellingStatus->ListingStatus) : 'empty';

        if (isset($listing->SellingStatus->ListingStatus) ) {
            $itemStatus = EbayProduct::STATUS_OFFLINE;
            if (trim($listing->SellingStatus->ListingStatus) == 'Active') {
                $itemStatus = EbayProduct::STATUS_ONLINE;
                $statuslog['status_2'] = $itemStatus;
            }
        }

        $ebaySite = new EbaySite();
        $siteID = !empty($listing->Site) ? $ebaySite->getSiteIdByName((string)$listing->Site) : '';
        $addData = array(
                'item_id'                    => $itemID,
                'site_id'                    => $siteID,
                'account_id'                 => $this->_accountID,
                'sku'                        => encryptSku::getRealSku($onlineSku),
                'sku_online'                 => trim($onlineSku),
                'title'                      => trim($listing->Title),
                'view_item_url'              => isset($listing->ListingDetails->ViewItemURL) ? trim($listing->ListingDetails->ViewItemURL) : '',
                'gallery_url'                =>isset($listing->PictureDetails->GalleryURL) ? trim($listing->PictureDetails->GalleryURL) : '',
                'quantity'                   => $quantity,
                'quantity_available'         => $availableQty,
                'quantity_sold'              => $soldQty,   
                'listing_duration'           => isset($listing->ListingDuration) ? trim($listing->ListingDuration) : '',
                'listing_type'               => isset($listing->ListingType) ? trim($listing->ListingType) : '',
                'buyitnow_price'             => isset($listing->BuyItNowPrice) ? floatval($listing->BuyItNowPrice) : 0,
                'buyitnow_price_currency'    => isset($listing->BuyItNowPrice['currencyID']) ? strval($listing->BuyItNowPrice['currencyID']) : '',
                'current_price'              => isset($listing->SellingStatus->CurrentPrice) ? floatval($listing->SellingStatus->CurrentPrice) : 0,
                'current_price_currency'     => isset($listing->SellingStatus->CurrentPrice['currencyID']) ? strval($listing->SellingStatus->CurrentPrice['currencyID']) : '',
                'shipping_price'             => isset($listing->ShippingDetails->ShippingServiceOptions->ShippingServiceCost) ? floatval($listing->ShippingDetails->ShippingServiceOptions->ShippingServiceCost) : 0,
                'shipping_price_currency'    => isset($listing->ShippingDetails->ShippingServiceOptions->ShippingServiceCost['currencyID']) ? strval($listing->ShippingDetails->ShippingServiceOptions->ShippingServiceCost['currencyID']) : '',
                'total_price'                => 0.00,
                'total_price_currency'       => '',
                'timestamp'                  => $this->transationUTCFormatTimeToNormal($this->_getlistingTimestamp),
                'start_time'                 => isset($listing->ListingDetails->StartTime) ? $this->transationUTCFormatTimeToNormal($listing->ListingDetails->StartTime) : '0000-00-00 00:00:00',
                'end_time'                   => isset($listing->ListingDetails->EndTime) ? $this->transationUTCFormatTimeToNormal($listing->ListingDetails->EndTime) : '0000-00-00 00:00:00',
                'time_left'                  => isset($listing->TimeLeft) ? trim($listing->TimeLeft) : '0',
                'update_sku'                 => 0,
                'paypal_email'               => isset($listing->PayPalEmailAddress) ? trim($listing->PayPalEmailAddress) : '',
                'question_count'             => 0,
                'is_multiple'                => $isMulti,
                'watch_count'                => isset($listing->WatchCount) ? intval($listing->WatchCount) : 0,
                'location'                   => trim($listing->Location),
                'handing_time'               => isset($listing->DispatchTimeMax) ? intval($listing->DispatchTimeMax) : 0,
                'category_id'                => isset($listing->PrimaryCategory->CategoryID) ? trim($listing->PrimaryCategory->CategoryID) : 0,
                'category_id2'               =>0,
                'category_name'              =>isset($listing->PrimaryCategory->CategoryName) ? trim($listing->PrimaryCategory->CategoryName) : 0,
                'store_category_id'          => isset($listing->Storefront->StoreCategoryID) ? trim($listing->Storefront->StoreCategoryID) : 0,
                'subtitle'                   => '',
        		'variation_picture_specific' => isset($listing->Variations->Pictures->VariationSpecificName) ? (string)$listing->Variations->Pictures->VariationSpecificName: '',
        );
        // ==== lihy add 2016-02-15 ====
        $addData['sale_start_time'] = isset($listing->SellingStatus->PromotionalSaleDetails->StartTime) ? $this->transationUTCFormatTimeToNormal(trim($listing->SellingStatus->PromotionalSaleDetails->StartTime)) : '0000-00-00 00:00:00';
        $addData['sale_end_time']   = isset($listing->SellingStatus->PromotionalSaleDetails->EndTime) ? $this->transationUTCFormatTimeToNormal(trim($listing->SellingStatus->PromotionalSaleDetails->EndTime)) : '0000-00-00 00:00:00';
        $addData['original_price']  = isset($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice) ? floatval($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice) : 0;
        $addData['original_price_currency'] = isset($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice['currencyID']) ? strval($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice['currencyID']) : '';
        $addData['is_promote']              = isset($listing->SellingStatus->PromotionalSaleDetails->EndTime) && $this->transationUTCFormatTimeToNormal($listing->SellingStatus->PromotionalSaleDetails->EndTime) > date("Y-m-d H:i:s", time()-8*3600+30*60)  ? 1 : 0;
        // ==== lihy add ====
        
        if (!is_null($itemStatus)) {
            $addData['item_status'] = $itemStatus;
            $statuslog['status_3'] = $itemStatus;
        }

        //更新刊登记录为下架
        if ( self::STATUS_OFFLINE == $addData['item_status'] ) {
            EbayProductAdd::model()->getDbConnection()->createCommand()->update(EbayProductAdd::tableName(),array('item_status'=> self::STATUS_OFFLINE),"item_id='{$itemID}'");
        }

        //MHelper::writefilelog('ebay/getItemInfo.txt', date('Y-m-d H:i:s').' @@ result ### '.print_r($statuslog,true)."\r\n\r\n");

        //判断是否存在
        $checkExists = $this->getOneByCondition("id","item_id='{$itemID}'");
        if($checkExists){//update
            $flag = $this->dbConnection->createCommand()->update($this->tableName(),$addData,"item_id='{$itemID}'");
            if($flag){
                return $checkExists['id'];
            }else{
                return false;
            }
        }else{//insert
            $addData['create_time'] = date('Y-m-d H:i:s');
            return $this->saveProductRecord($addData);
        }
    }

    /**
     * @desc 保存产品listing的多属性数据信息
     * @param object
     */
    public function saveListingVariation($listingID) {
        $ebayProductVariantModel = EbayProductVariation::model();
    	$listing = $this->_listing_Response;
        $itemID = trim($listing->ItemID);
        //先删除后添加
        //$ebayProductVariantModel->dbConnection->createCommand()->delete($ebayProductVariantModel->tableName(), "item_id='{$itemID}' ");        
        
        //多属性子sku
    	if(isset($listing->Variations) && !empty($listing->Variations)){
            $mainSku = trim($listing->SKU);
    		foreach ($listing->Variations->Variation as $varition){
                $addVariantsData = array();
    			$varitionSpecifics = array();
    			if(isset($varition->VariationSpecifics)){
    				foreach ($varition->VariationSpecifics->NameValueList as $specifics){
    					$varitionSpecifics[(string)$specifics->Name] = (string)$specifics->Value;
    				}
    			}
                $quantity = intval($varition->Quantity);
                $soldQty = isset($varition->SellingStatus->QuantitySold) ? intval($varition->SellingStatus->QuantitySold)  : 0;
                $availableQty = $quantity - $soldQty ;
                if ($availableQty < 0) {
                    $availableQty = 0;
                }
                $onlineSku = isset($varition->SKU) ? trim($varition->SKU) : '';
    			$varitionSpecifics = json_encode($varitionSpecifics);
    			$addVariantsData = array(
    					'listing_id'	       => $listingID,
    					'account_id'	       => $this->_accountID,
    					'item_id'              => $itemID,
    					'sku'                  => $onlineSku == '' ? '' : encryptSku::getRealSku($onlineSku),
    					'sku_online'           => $onlineSku,
    					'main_sku'             => encryptSku::getRealSku($mainSku),
    					'quantity'             => $quantity,
    					'quantity_sold'	       => $soldQty,
                        'quantity_available'   => $availableQty,
    					'sale_price'           => isset($varition->StartPrice) ? floatval($varition->StartPrice) : 0, 
    					'currency'             => isset($varition->StartPrice['currencyID']) ? strval($varition->StartPrice['currencyID']) : '',
    					'variation_specifics' => $varitionSpecifics
    			);
    			// ==== lihy add 2016-02-15 ====
    			$addVariantsData['sale_start_time']	= 	isset($varition->SellingStatus->PromotionalSaleDetails->StartTime) ? date('Y-m-d H:i:s',strtotime($varition->SellingStatus->PromotionalSaleDetails->StartTime)-8*3600) : '0000-00-00 00:00:00';
    			$addVariantsData['sale_end_time']	=	isset($varition->SellingStatus->PromotionalSaleDetails->EndTime) ? date('Y-m-d H:i:s',strtotime($varition->SellingStatus->PromotionalSaleDetails->EndTime)-8*3600) : '0000-00-00 00:00:00';
    			$addVariantsData['original_price']	=	isset($varition->SellingStatus->PromotionalSaleDetails->OriginalPrice) ? floatval($varition->SellingStatus->PromotionalSaleDetails->OriginalPrice) : 0;
    			$addVariantsData['original_price_currency']	=	isset($varition->SellingStatus->PromotionalSaleDetails->OriginalPrice['currencyID']) ? strval($varition->SellingStatus->PromotionalSaleDetails->OriginalPrice['currencyID']) : '';
    			$addVariantsData['is_promote'] = (isset($varition->SellingStatus->PromotionalSaleDetails->EndTime) && $this->transationUTCFormatTimeToNormal($varition->SellingStatus->PromotionalSaleDetails->EndTime) > date("Y-m-d H:i:s", time()-8*3600+30*60) ) ? 1 : 0;
    			// ==== lihy add ====
    			
    			//判断是否增加
                $checkExists = $ebayProductVariantModel->getOneByCondition('id',"item_id='{$itemID}' and sku_online='{$onlineSku}'");
    			if($checkExists){//add
    				 $ebayProductVariantModel->updateProductVariationByID($checkExists['id'], $addVariantsData);
    			}else{//insert
    				$ebayProductVariantModel->saveProductVariation($addVariantsData);
    			}
    		}
    	} else {//单品sku
            $onlineSku          = trim($listing->SKU);
            $quantity           = (int)$listing->Quantity;// quantity = available + QuantitySold
            $soldQuantity       = (int)$listing->SellingStatus->QuantitySold;// the sum of all varations QuantitySold
            $quantity_available = $quantity - $soldQuantity;//available
            if ($quantity_available < 0) {
                $quantity_available = 0 ;
            }
            $main_sku = isset($listing->SKU) ? encryptSku::getRealSku($onlineSku) : '';
            $addVariantsData = array(
                'listing_id'        => $listingID,
                'account_id'        => $this->_accountID,
                'item_id'           => $listing->ItemID,
                'main_sku'          => $main_sku,
                'sku'               => $main_sku,
                'sku_online'        => isset($listing->SKU) ? $onlineSku : '',
                'quantity'          => $quantity,
                'quantity_sold'     => $soldQuantity,
                'quantity_available'=> $quantity_available,
                'sale_price'        => isset($listing->SellingStatus->CurrentPrice) ? $listing->SellingStatus->CurrentPrice : 0,
                'currency'          => isset($listing->SellingStatus->CurrentPrice['currencyID']) ? $listing->SellingStatus->CurrentPrice['currencyID'] : '',
                'variation_specifics' => json_encode(array())
            );
            $addVariantsData['sale_start_time'] =   isset($listing->SellingStatus->PromotionalSaleDetails->StartTime) ? date('Y-m-d H:i:s',strtotime($listing->SellingStatus->PromotionalSaleDetails->StartTime )-8*3600): '0000-00-00 00:00:00';
            $addVariantsData['sale_end_time']   =   isset($listing->SellingStatus->PromotionalSaleDetails->EndTime) ? date('Y-m-d H:i:s',strtotime($listing->SellingStatus->PromotionalSaleDetails->EndTime )-8*3600) : '0000-00-00 00:00:00';
            $addVariantsData['original_price']  =   isset($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice) ? floatval($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice) : 0;
            $addVariantsData['original_price_currency'] =   isset($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice['currencyID']) ? strval($listing->SellingStatus->PromotionalSaleDetails->OriginalPrice['currencyID']) : '';
            $addVariantsData['is_promote'] = (isset($listing->SellingStatus->PromotionalSaleDetails->EndTime) && $this->transationUTCFormatTimeToNormal($listing->SellingStatus->PromotionalSaleDetails->EndTime) > date("Y-m-d H:i:s", time()-8*3600+30*60) ) ? 1 : 0;
            
            //判断是否增加
            $checkExists = $ebayProductVariantModel->getOneByCondition('id',"item_id='{$itemID}' and sku_online='{$onlineSku}'");
            if($checkExists){//add
                $ebayProductVariantModel->updateProductVariationByID($checkExists['id'], $addVariantsData);
            }else{//insert
                $ebayProductVariantModel->saveProductVariation($addVariantsData);
            }
        }
    	return true;
    }

    /**
     * @DESC 获取并更新单个
     * @todo 
     * @param unknown $itemID
     */
    public function updateItemSimpleInfo($itemID,$accountID=null){
        if (empty($accountID)) {
            $ebayProductInfo = $this->getOneByCondition('account_id',"item_id='{$itemID}'");
            if (!empty($ebayProductInfo)) {
                $accountID = $ebayProductInfo['account_id'];
            } else {
                return false;
            }
        }
        $request = new GetItemRequest();
        $request->setItemID($itemID);
        $request->setAccount($accountID);
        $request->setOutSelectorSimple(true);
        $response = $request->setRequest()->sendRequest()->getResponse();
        //MHelper::writefilelog('ebay/updateItemSimpleInfo/'.$accountID.'/'.date("Ymd").'/getItem.txt',date('Y-m-d H:i:s').'###'.print_r($response,true)."\r\n\r\n");
        if($request->getIfSuccess()){
            try{
                $this->_getlistingTimestamp = (string)$response->Timestamp;
                $this->_listing_Response = $response->Item;
                $listingID = $this->saveListingSimpleInfo();
                if($listingID){
                    // 2. 保存listing多属性数据信息
                    $this->saveListingSimpleVariation($listingID);
                }
            }catch (Exception $e){
                $this->setExceptionMessage($e->getMessage());
                return false;
            }
        }else{
            $this->setExceptionMessage($request->getErrorMsg());
            return false;
        }
        return true;
    }

    /***
     * @desc 保存产品listing信息（只价格）
     * @param object 
     */
    public function saveListingSimpleInfo(){
        $listing = $this->_listing_Response;
        if (!isset($listing->SKU) || trim($listing->SKU) == '') return false;    //如果没有SKU，很可能是正在删除的listing，不用入库 Liz|20160615
        //MHelper::writefilelog('ebay/saveListingSimpleInfo.txt', date('Y-m-d H:i:s').' @@ responsedata ### '.print_r($listing,true)."\r\n\r\n");    
         
        $statuslog    = array();
        $itemID       = trim($listing->ItemID);
        $onlineSku    = trim($listing->SKU);
        $quantity     = intval($listing->Quantity);
        $soldQty      = intval($listing->SellingStatus->QuantitySold);
        $availableQty = $quantity - $soldQty;
        if ($availableQty < 0) {
            $availableQty = 0;
        }

        $statuslog['itemID'] = $itemID;
        $statuslog['status'] = isset($listing->SellingStatus->ListingStatus)  ? trim($listing->SellingStatus->ListingStatus) : 'empty';

        if ( isset($listing->SellingStatus->ListingStatus) ) {
            $item_status = self::STATUS_OFFLINE;    //默认下架状态: 0 listing下架状态(Completed、Ended)
            if ( trim($listing->SellingStatus->ListingStatus) == 'Active'){
                $item_status = self::STATUS_ONLINE; //在线状态: 1
                $statuslog['status_2'] = $item_status;
            }
        }
   
        $addData = array(
                'quantity'                  => $quantity,
                'quantity_sold'             => $soldQty,
                'quantity_available'        => $availableQty,
                'current_price'             => floatval($listing->SellingStatus->CurrentPrice),
                'current_price_currency'    =>isset($listing->SellingStatus->CurrentPrice['currencyID']) ? strval($listing->SellingStatus->CurrentPrice['currencyID']) : '',
                'timestamp'                 => $this->transationUTCFormatTimeToNormal($this->_getlistingTimestamp),
        );
        if (!is_null($item_status)) {
            $addData['item_status'] = $item_status;
            $statuslog['status_3'] = $item_status;
        }

        //更新刊登记录为下架
        if ( self::STATUS_OFFLINE == $addData['item_status'] ) {
            EbayProductAdd::model()->getDbConnection()->createCommand()->update(EbayProductAdd::tableName(),array('item_status'=> self::STATUS_OFFLINE),"item_id='{$itemID}'");
        }
        
        //MHelper::writefilelog('ebay/getSimpleItemInfo.txt', date('Y-m-d H:i:s').' @@ result ### '.print_r($statuslog,true)."\r\n\r\n"); 
           
        //判断当前listing表是否存在此记录
        $checkExists = $this->getOneByCondition('id',"item_id='{$itemID}'");
        if($checkExists){
            $flag = $this->dbConnection->createCommand()->update($this->tableName(),$addData,"item_id='{$itemID}'");
            if($flag){          
                return $checkExists['id'];
            }else{               
                return false;
            }
        }
    }
 
    /**
     * @desc 保存产品listing的多属性数据信息（只价格）
     * @param object
     */
    public function saveListingSimpleVariation($listingID)
    {
        $variationModel = EbayProductVariation::model();
        $listing = $this->_listing_Response;
        $itemID  = trim($listing->ItemID);
        if(isset($listing->Variations) && !empty($listing->Variations) ){
            foreach ($listing->Variations->Variation as $varition){
                $onlineSku = isset($varition->SKU) ? trim($varition->SKU) : 'unknown';
                $quantity  = intval($varition->Quantity);
                $soldQty   = isset($varition->SellingStatus->QuantitySold) ? intval($varition->SellingStatus->QuantitySold)  : 0;
                $availableQty = $quantity - $soldQty ;
                if ($availableQty < 0) {
                    $availableQty = 0;
                }
                $addVariantsData = array(
                    'quantity'              => $quantity,
                    'quantity_sold'         => $soldQty,
                    'quantity_available'    => $availableQty,
                    'sale_price'            => isset($varition->StartPrice) ? (string)$varition->StartPrice : 0, 
                    'currency'              => isset($varition->StartPrice['currencyID']) ? strval($varition->StartPrice['currencyID']) : '',
                );
                //判断是否存在
                $checkExists = $variationModel->getOneByCondition("id","item_id='{$itemID}' AND sku_online='{$onlineSku}'" );
                if($checkExists){
                    $variationModel->getDbConnection()->createCommand()->update($variationModel->tableName(), $addVariantsData, "item_id='{$itemID}' AND sku_online='{$onlineSku}'");
                }
            }
        } else {
            $onlineSku    = trim($listing->SKU);
            $quantity     = intval($listing->Quantity);
            $soldQty      = intval($listing->SellingStatus->QuantitySold);
            $availableQty = $quantity - $soldQty;
            if ($availableQty < 0) {
                $availableQty = 0;
            }
            $addVariantsData = array(
                'quantity'                  => $quantity,
                'quantity_sold'             => $soldQty,
                'quantity_available'        => $availableQty,
                'sale_price'             => floatval($listing->SellingStatus->CurrentPrice),
                'currency'    =>isset($listing->SellingStatus->CurrentPrice['currencyID']) ? strval($listing->SellingStatus->CurrentPrice['currencyID']) : ''
            );
            //判断是否存在
            $checkExists = $variationModel->getOneByCondition("id","item_id='{$itemID}' AND sku_online='{$onlineSku}'" );
            if($checkExists){
                $variationModel->getDbConnection()->createCommand()->update($variationModel->tableName(), $addVariantsData, "item_id='{$itemID}' AND sku_online='{$onlineSku}'");
            }
        }
        return true;
    }

    /**
     * @desc 保存listing的详细信息
     * @param unknown $listingID
     */
    public function saveListingDetails($listingID){
    	$listing = $this->_listing_Response;
    	$addDetailsData = array(
    						'listing_id'	=>	$listingID,
    						'description'	=>	$listing->Description
				    	);
    	$checkExists = EbayProductExtend::model()->getProductExtendInfoByCondition("listing_id=:listing_id", 
    										array(":listing_id"=>$listingID), "id");
    	if($checkExists){
    		return EbayProductExtend::model()->updateProductExtendByID($checkExists['id'], $addDetailsData);
    	}else{
    		return EbayProductExtend::model()->saveProductExtend($addDetailsData);
    	}
    }
    
    /**
     * @desc 保存产品listing的运输信息
     * @param object
     */
    public function saveListingShip()
    {
    	$listing = $this->_listing_Response;
    	return EbayProductShip::model()->saveProductShip(array(
    			'item_id'            		=> $listing->ItemID,
    			'sku_online'         		=> $listing->SKU,
    			'weight_major'       		=> $listing->ShippingDetails->CalculatedShippingRate->WeightMajor,
    			'weight_minor'       		=> $listing->ShippingDetails->CalculatedShippingRate->WeightMinor,
    			'sales_tax_percent'  		=> $listing->ShippingDetails->SalesTax->SalesTaxPercent,
    			'shipping_included_intax'	=> $listing->ShippingDetails->SalesTax->ShippingIncludedInTax,
    			'shipping_type'				=> $listing->ShippingDetails->ShippingType,
    			'third_checkout'            => $listing->ShippingDetails->ThirdPartyCheckout
    	));
    }

    /**
     * @desc 获取ebay listing 信息
     * @param array $condition
     * @param string $order
     * @param string $field
     * @return mixed
     */
    public function getEbayProductInfo($condition, $order = '', $field = '*'){
    	$conditions = " 1 ";
    	if(is_array($condition)){
    		foreach ($condition as $key=>$val){
    			$conditions .= " AND {$key}='{$val}' ";
    		}
    	}else {
    		$conditions = $condition;
    	}
    	$data =	$this->getDbConnection()->createCommand()
    								->from($this->tableName())
    								->where($conditions)
    								->select($field)
    								->order($order)
    								->queryRow();
    	return $data;
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
    	return $this->_exception;
    }
    
    
    // ================= ebay Listing Search =====================
    /**
     * @desc 设置筛选条件
     * @return multitype:
     */
    public function filterOptions(){
    	$itemStatus = Yii::app()->request->getParam("item_status");
    	$siteID = Yii::app()->request->getParam("site_id");
        $listingType = Yii::app()->request->getParam("listing_type");
        $location = Yii::app()->request->getParam("location");
    	$siteList = EbaySite::getSiteList();
    	$siteList[-1] = "unkown";
    	return array(
    			array(
    					'name' => 'sku',
    					'type' => 'text',
    					'search' => 'LIKE',
    					'htmlOption' => array(
							'size' => '22',
    					),
    			),
    			array(
    					'name'		=>	'item_id',
    					'type'		=>	'text',
    					'search'	=>	'=',
    			),
    			array(
    					'name' => 'sku_online',
    					'type' => 'text',
    					'search' => 'LIKE',
    					'htmlOption' => array(
    							'size' => '22',
    					),
    			),
    			array(
    					'name' => 'sub_sku',
    					'type' => 'text',
    					'search' => 'LIKE',
    					'rel'	=>	true,
    					'htmlOption' => array(
    							'size' => '22',
    					),
    			),
                array(
                        'name' => 'department_id',
                        'type' => 'dropDownList',
                        'data' => $this->getDepart(),
                        'search' => '=',
                        'rel'   =>  true,
                ),
    			array(
    					'name' => 'account_id',
    					'type' => 'dropDownList',
                        'value'=> Yii::app()->request->getParam('account_id'),
    					'data' => EbayAccount::model()->getIdNamePairs(),
    					'search' => '=',
    			),
    			array(
    					'name' => 'site_id',
    					'type' => 'dropDownList',
                        'value'=> Yii::app()->request->getParam('site_id'),
    					'data' => $siteList,
    					'value'=> $siteID,
    					'search' => '=',
    			),
                array(
                        'name' => 'item_status',
                        'type' => 'dropDownList',
                        'data' => $this->getEbayItemStatusOption(),
                        'value'=>$itemStatus,
                        'search' => '=',
                ),
                array(
                        'name' => 'sub_sku_online',
                        'type' => 'text',
                        'search' => 'LIKE',
                        'rel'   =>  true,
                        'htmlOption' => array(
                                'size' => '22',
                        ),
                ),
                array(
                        'name' => 'listing_type',
                        'type' => 'dropDownList',
                        'data' => $this->getEbayListingTypeOption(),
                        'value' => $listingType,
                        'search' => '=',
                ),
                array(
                    'name' => 'location',
                    'type' => 'dropDownList',
                    'data' => $this->getLocation(),
                    'value' => $location,
                    'search' => '=',
                ),

        );
    }
    /*
     * 获取ebay部门
     */
    public function getDepart(){
        $departId = Department::model()->getDepartmentByPlatform(Platform::CODE_EBAY);
        $departList = Department::model()->findAll("id in ( " . MHelper::simplode($departId) . " )");
        $departData = array();
        foreach($departList as $value){
            $departData[$value['id']] = $value['department_name'];
        }
        return $departData;
    }

    /**
     * @desc  获取location
     * @return array
     */
    public function getLocation($locationId = null){
        $location = array(
            'ShenZhen'=>'ShenZhen',
            'Derby'=>'Derby',
            'UK'=>'UK',
            'US'=>'US',
            'NEW YORK'=>'NEW YORK',
            'NewYork'=>'NewYork',
            'MIDDLESEX'=>'MIDDLESEX',
            'London'=>'London',
            'Manchester'=>'Manchester',
            'Bremen'=>'Bremen',
            'Bruchsal'=>'Bruchsal',
            'Dortmund'=>'Dortmund',
            'DUNSABLE'=>'DUNSABLE',
            'DUNSTABLE'=>'DUNSTABLE',
            'Germany'=>'Germany',
            'Hamburg'=>'Hamburg',
            'HONG KONG'=>'HONG KONG',
            'HongKong'=>'HongKong',
            'Markgröningen'=>'Markgröningen',
            'NSW'=>'NSW',
            'NJ'=>'NJ',
        );

        if($locationId !== null){
            return $location[$locationId];
        }
        return $location;
    }
    /**
     * @desc  获取item链接
     * @param unknown $itemID
     * @param unknown $siteID
     * @return Ambigous <string, unknown>
     */
	public function getItemlink($itemID, $siteID){
    	$return = $itemID;
    	if($itemID){
    		$url = "http://www.ebay.com/itm/{$itemID}";
    		$return = '<a href="'.$url.'" target="__blank">'.$itemID.'</a>';
    	}
    	return $return;
    }
    /**
     * @desc 获取ebay状态选项
     * @param string $itemStatus
     * @return Ambigous <NULL, Ambigous <string, string, unknown>>|multitype:NULL Ambigous <string, string, unknown>
     */
    public function getEbayItemStatusOption($itemStatus = null){
    	$itemStatusOptions = array(
    								self::STATUS_OFFLINE	=>	Yii::t("ebay", "Status Offline"),
    								self::STATUS_ONLINE		=>	Yii::t("ebay", "Status Online")
    							);
    	if($itemStatus !== null){
    		return $itemStatusOptions[$itemStatus];
    	}
    	return $itemStatusOptions;
    }

    /**
     * @desc 获取ebay的listing刊登类型选项
     * @param string $itemStatus
     * @return Ambigous <NULL, Ambigous <string, string, unknown>>|multitype:NULL Ambigous <string, string, unknown>
     */
    public function getEbayListingTypeOption($itemStatus = null){
        $itemStatusOptions = array(
                                    'FixedPriceItem' =>  Yii::t("ebay", "FixedFrice"),
                                    'Chinese'        =>  Yii::t("ebay", "Auction")
                                );
        if($itemStatus !== null){
            return $itemStatusOptions[$itemStatus];
        }
        return $itemStatusOptions;
    }    
    
    public function getEbayProductVariantList($accountID, $itemID){
    	$conditions = "account_id={$accountID} AND item_id='{$itemID}'";
    	if(isset($_REQUEST['sub_sku_online']) && $_REQUEST['sub_sku_online']){
    		$conditions .= " AND sku_online='{$_REQUEST['sub_sku_online']}'";
    	}
    	if(isset($_REQUEST['sub_sku']) && $_REQUEST['sub_sku']){
    		$conditions .= " AND sku='{$_REQUEST['sub_sku']}'";
    	}
    	return EbayProductVariation::model()->findAll($conditions);
    }
    /**
     * @desc 设置格外的数据处理
     * @param unknown $datas
     * @return unknown
     */
    public function addtions($datas){
    	if($datas){
    		$accountLists = EbayAccount::model()->getIdNamePairs();
    		$sellerUserList = User::model()->getPairs();
    		foreach ($datas as &$data){
    			$data->account_name = isset($accountLists[$data['account_id']])?$accountLists[$data['account_id']]:'-';
    			$data->item_status_text = $this->getEbayItemStatusOption($data['item_status']);
                $data->listing_type_text = ($data['listing_type'] == 'FixedPriceItem') ? Yii::t("ebay", "FixedFrice") : Yii::t("ebay", "Auction");
    			$data->item_id_link = $this->getItemlink($data['item_id'], $data['site_id']);
    			$data->site_name = EbaySite::getSiteName($data['site_id']);
                $data->gallery_url = '<img src="'.$data['gallery_url'].'" width="100" height="100" />';
    			$data->detail = array();
    			
                $variantList = $this->getEbayProductVariantList($data['account_id'], $data['item_id']);
                if($variantList){
                    foreach ($variantList as $variant){
                        $tdHeight = 0;
                        $productSellerRelationInfo = EbayProductSellerRelation::model()->getProductSellerRelationInfoByItemIdandSKU($data['item_id'], $variant['sku'], $variant['sku_online']);
                        $opreator = "";
                        if($variant['quantity_available'] > 0){
                        	$opreator = "<select onchange='offlineVaration(this)' data-id='".$variant['id']."'><option value>选择</option>
                        			<option value=0>下架</option></select>";
                        }

                        //获取sku的信息
                        $skuInfo = Product::model()->getProductBySku($variant['sku']);

                        //获取利润和利润率
                        $profitRate = $profit = $shippingPrices = '<ul class="listul">';
                        $profitFields = 'shipping_price,profit,profit_rate';
                        $profitConditions = 'item_id=:item_id AND sku_online=:sku_online';
                        $profitParam = array(':item_id'=>$variant['item_id'], ':sku_online'=>$variant['sku_online']);
                        $profitInfo = EbayProductProfit::model()->getOneByCondition($profitFields,$profitConditions,$profitParam);
                        if($profitInfo){
                            $shippingPriceArr = explode(',', $profitInfo['shipping_price']);
                            foreach ($shippingPriceArr as $shippingKey => $shippingValue) {
                                $shippingPrices .= '<li>'.$shippingValue.'</li>';
                                $tdHeight += 24;
                            }

                            $profitArr = explode(',', $profitInfo['profit']);
                            foreach ($profitArr as $profitKey => $profitValue) {
                                $profit .= '<li>'.$profitValue.'</li>';
                            }

                            $profitRateArr = explode(',', $profitInfo['profit_rate']);
                            foreach ($profitRateArr as $profitRateKey => $profitRateValue) {
                                $profitRate .= '<li>'.$profitRateValue.'%</li>';
                            }
                        }

                        $shippingPrices .= '</ul>';
                        $profit .= '</ul>';
                        $profitRate .= '</ul>';

                        $data->detail[] = array(
                        		'variants_id'			 =>	 $variant['id'],
                                'sku'                    =>  $variant['sku'],
                                'sku_online'             =>  $variant['sku_online'],
                                'current_price'          =>  CHtml::link($variant['sale_price'],"/ebay/ebayproduct/reviseinventorystatusprice/variationID/".$variant['id'],
                    array("title"=>$variant['sku'],"style"=>"color:blue","target"=>"dialog","width"=>400,"mask"=>true,"height"=>240)),
                                'current_price_currency' =>  $variant['currency'],
                                'quantity_available'     =>  CHtml::link($variant['quantity_available'],"/ebay/ebayproduct/reviseinventorystatus/variationID/".$variant['id'],
                    array("title"=>$variant['sku'],"style"=>"color:blue","target"=>"dialog","width"=>400,"mask"=>true,"height"=>200)),
                                'quantity_sold'          =>  $variant['quantity_sold'],
                                'seller_name'            =>  $productSellerRelationInfo && isset($sellerUserList[$productSellerRelationInfo['seller_id']]) ? $sellerUserList[$productSellerRelationInfo['seller_id']] : '',
                        		'opreator'				 =>	 $opreator,
                                'product_weight'         =>  isset($skuInfo['product_weight'])?$skuInfo['product_weight']:'',
                                'product_cost'           =>  isset($skuInfo['product_cost'])?$skuInfo['product_cost']:'',
                                'shipping_price'         =>  $shippingPrices,
                                'td_height'              =>  'height:'.$tdHeight.'px;',
                                'available_qty'          =>  WarehouseSkuMap::model()->getAvailableBySkuAndWarehouse($variant['sku']),
                                'profit'                 =>  $profit,
                                'profit_rate'            =>  $profitRate,
                        );
                    }
                }
    		}
    	}
    	return $datas;
    }

    /**
     * @desc 提供数据
     * @see UebModel::search()
     */
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'=>'quantity_sold'
    	);
    	$dataProvider = parent::search($this, $sort, '', $this->setSearchDbCriteria());   
    	$datas = $this->addtions($dataProvider->data);
    	$dataProvider->setData($datas);
    	return $dataProvider;
    }
    /**
     * @desc 设置搜索条件
     * @return CDbCriteria
     */
    public function setSearchDbCriteria(){
    	$cdbcriteria = new CDbCriteria();
    	$cdbcriteria->select = '*';
        $accountId = Yii::app()->request->getParam('account_id');
        $siteId = Yii::app()->request->getParam('site_id');
        $userSiteArr = array();
        $userAccountArr = array();
        $userID = Yii::app()->user->id;
        if(isset($userID)){
            $department_id = User::model()->getDepIdById($userID);
            if($department_id != EbayDepartmentAccountSite::DEPARTMENT_OVERSEAS){//海外仓人员暂时不受限制
                $job_param = 'job_id=:job_id AND is_del =:is_del AND seller_user_id =:seller_user_id';
                $job_array = array(':job_id' => ProductsGroupModel::GROUP_LEADER, ':is_del' => 0,':seller_user_id' => $userID);
                $is_job = ProductsGroupModel::model()->find($job_param,$job_array);
                if($is_job){//排除组长

                }else{
                    $userAccountSite = SellerUserToAccountSite::model()->getAccountSiteByCondition(Platform::CODE_EBAY,$userID);
                    if($userAccountSite){
                        foreach ($userAccountSite as $sellerList) {
                            $userSiteArr[] = EbaySite::model()->getSiteIdByName($sellerList['site']);
                            $userAccountArr[] = $sellerList['account_id'];
                        }
                        $userSiteArr = array_unique($userSiteArr);
                        $userAccountArr = array_unique($userAccountArr);
                    }
                }
            }
        }
        if($userAccountArr && !in_array($accountId, $userAccountArr)){
            $accountId = implode(',', $userAccountArr);
        }
        if($accountId){
            $cdbcriteria->addCondition("t.account_id IN (".$accountId.")");
        }
        if($userSiteArr && !in_array($siteId, $userSiteArr)){
            $siteId = implode(',', $userSiteArr);
        }
        if($siteId){
            $cdbcriteria->addCondition("t.site_id IN (".$siteId.")");
        }
    	$conditions = array();
    	if(isset($_REQUEST['sub_sku_online']) && $_REQUEST['sub_sku_online']){
    		$conditions[] = " sku_online='{$_REQUEST['sub_sku_online']}'";
    	}
    	if(isset($_REQUEST['sub_sku']) && $_REQUEST['sub_sku']){
    		$conditions[] = " sku='{$_REQUEST['sub_sku']}'";
    	}
    	if($conditions){
    		$conditions = implode(" AND ", $conditions);
    		if(isset($_REQUEST['account_id']) && $_REQUEST['account_id']){
    			$conditions .= " AND account_id='{$_REQUEST['account_id']}'";
    		}
    		if(isset($_REQUEST['sku']) && $_REQUEST['sku']){
    			$conditions .= " AND main_sku='{$_REQUEST['sku']}'";
    		}
            if(isset($_REQUEST['listing_type']) && $_REQUEST['listing_type']){
                $conditions .= " AND listing_type='{$_REQUEST['listing_type']}'";
            }
            if(isset($_REQUEST['location']) && $_REQUEST['location']){
                $conditions .= " AND location='{$_REQUEST['location']}'";
            }
            $variants = EbayProductVariation::model()->getDbConnection()->createCommand()
    										->select('listing_id')
    										->from(EbayProductVariation::model()->tableName())
    										->where($conditions)
    										->queryColumn();
    		$conditions = "0=1";
    		if($variants){
    			$conditions = "id in(" . MHelper::simplode($variants) . ")";
    		}
    		$cdbcriteria->addCondition($conditions);
    	}

        //按部门搜索
        if(isset($_REQUEST['department_id']) && !$_REQUEST['account_id']){
            $ebayDepartArr = Department::model()->getDepartmentByPlatform(Platform::CODE_EBAY);
            if(in_array($_REQUEST['department_id'], $ebayDepartArr)){
                $where = 'department_id = '.$_REQUEST['department_id'];
                $info = EbayDepartmentAccountSite::model()->getListByCondition($where);
                if($info){
                    $searchWhere = '';
                    foreach ($info as $key => $value) {
                        $accountArr[] = '(account_id = '.$value['account_id'].' AND site_id = '.$value['site_id'].')';
                    }
                    $searchWhere = implode(' OR ', $accountArr);
                    $cdbcriteria->addCondition($searchWhere);
                }
            }
        }

    	return $cdbcriteria;
    }
    /**
     * @desc 设置对应的字段标签名称
     * @see CModel::attributeLabels()
     */
    public function attributeLabels(){
    	return array(
                'id'                     => '',
                'account_name'           => Yii::t('amazon_product', 'Account Id'),
                'account_id'             => Yii::t('amazon_product', 'Account Id'),
                'title'                  =>	Yii::t('ebay', 'Title'),
                'sku'                    => Yii::t('amazon_product', 'Sku'),
                'seller_sku'             => Yii::t('amazon_product', 'Seller Sku'),
                'sku_online'             => Yii::t('ebay', 'Sku Online'),
                'sub_sku_online'         => Yii::t('ebay', 'Sub Sku Online'),
                'sub_sku'                => Yii::t('ebay', 'Sub Sku'),
                'current_price'          =>	Yii::t('ebay', 'Current Price'),
                'current_price_currency' =>	Yii::t('ebay', 'Current Price Currency'),
                'quantity_available'     =>	Yii::t('ebay', 'Quantity Available'),
                'quantity_sold'          =>	Yii::t('ebay', 'Quantity Sold'),
                'listing_duration'       =>	Yii::t('ebay', 'Listing Duration'),
                'opreator'               =>	Yii::t('system', 'Opration'),
                'item_status'            =>	Yii::t('ebay', 'Status'),
                'site_id'                =>	Yii::t('ebay', 'Site Name'),
                'listing_type'           => Yii::t('ebay', 'Listing Type'),
                'item_id'                =>	Yii::t('ebay', 'Item ID'),
                'seller_name'            =>	Yii::t('common', 'Seller Name'),
                'start_time'             => Yii::t('ebay', 'Start Time'),
                'gallery_url'            => Yii::t('ebay', 'Gallery Url'),
                'product_cost'           => Yii::t('product', 'Product Cost'),
                'product_weight'         => Yii::t('product', 'Product Weight'),
                'shipping_price'         => Yii::t('ebay', 'Shipping Price'),
                'available_qty'          => Yii::t('product', 'Available Qty'),
                'profit'                 => Yii::t('product', 'Profit'),
                'profit_rate'            => Yii::t('product', 'Profit Rate'),
                'department_id'          => Yii::t('product', '所在部门'),
                'location'               => Yii::t('product', 'location'),
                'handing_time'           => Yii::t('product', 'dispatchTime'),
    	);
    }
    // ================= ebay Listing Search =====================

    /**
     * @desc 根据SKU获取listing记录
     * @param string $SKU
     */
    public function getListingByOnlySku($SKU, $siteId = null, $listingType = null, $itemStatus = 1){
        if(!$SKU) return false;
        $ret = $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('sku="'.$SKU.'"')
                ->andWhere($itemStatus === null ? "1" : "item_status={$itemStatus}")
                ->andWhere($siteId === null ? "1" : "site_id='{$siteId}'")
                ->andWhere($listingType === null ? "1" : (is_array($listingType) ? array("IN", 'listing_type', $listingType) : "listing_type='{$listingType}'"))
                ->queryAll();
        return $ret;
    }

    public function getOnlineAccountIdArr($sku, $siteId = null, $listingType = null, $itemStatus = 1) {
        return $this->dbConnection->createCommand()
                    ->selectDistinct('account_id')
                    ->from($this->tableName())
                    ->where('sku="'.$sku.'"')
                    ->andWhere($itemStatus === null ? "1" : "item_status={$itemStatus}")
                    ->andWhere($siteId === null ? "1" : "site_id='{$siteId}'")
                    ->andWhere($listingType === null ? "1" : (is_array($listingType) ? array("IN", 'listing_type', $listingType) : "listing_type='{$listingType}'"))
                    ->queryColumn();
    }

    /**
     * @desc 把当前listing表状态confirm_status更新为0
     * @param int $accountID
     * 
     */
    public function setProductConfirmStatusToZero($accountID = 0){
        //每在当天的半夜2点才执行
        $hour = date('H');
        if ($hour == 2){
            if (!empty($accountID) && $accountID > 0){
                return $this->dbConnection->createCommand()->update(self::tableName(), array('confirm_status' => 0), "confirm_status = 1 and account_id = ".$accountID);
            }else{
                return $this->dbConnection->createCommand()->update(self::tableName(), array('confirm_status' => 0), "confirm_status = 1");
            }  
        }
    }    


    /**
     * @desc 删除当前listing表状态为0（无效的listing产品）的记录
     * @param int $accountID
     * 
     */
    public function delProductConfirmStatusZero($accountID = 0){
        if (!empty($accountID) && $accountID > 0){
            return $this->dbConnection->createCommand()->delete(self::tableName(), 'confirm_status = 0 and account_id = '.$accountID);
        }else{
            return $this->dbConnection->createCommand()->delete(self::tableName(), 'confirm_status = 0');
        }
    } 

    /**
     * @desc 获取确认状态为0的记录总数
     * @param int $accountID
     */
    public function getProductStatusZeroCount($accountID){
        return $result = self::model()->count("confirm_status = 0 and account_id = ".$accountID);
    }      


    /**
     * @desc 获取确认状态为0的在线SKU记录
     * @param int $accountID
     * 
     */
    public function setSkuArray($accountID){
        $ret = $this->dbConnection->createCommand()
                ->select('sku_online as sku')
                ->from(self::tableName())
                ->where('confirm_status = 0')
                ->andWhere("account_id = ".$accountID)
                ->queryAll();
                
        $this->_SkuArray = $ret;
    }  


    /**
     * @desc 获取拉产品listing是否执行的判断
     * 通过日志判断上一次，如果拉成功了并超过八小时的才重拉，如果是成功八小时内的不执行
     * 判断如果上一次拉listing不成功的，则重拉
     * 不用此方法做判断，用定时任务的指定时间方式处理
     * @since 2016/06/13
     */
    public function getListingLogToDo($accountID){

        $status = 1;   //执行拉取操作
        $lastLog = EbayLog::model()->getLastLogByCondition(array(
                'account_id'    => $accountID,
                'event'         => self::EVENT_NAME
        ));
        if ($lastLog){
            //如果最近一次拉listing的日志是成功的
            if ($lastLog['status'] == 2){
                $diffSec = time() - strtotime($lastLog['start_time']);   
                //如果不超过8小时，不执行
                if($diffSec < 8) $status = 2;  //默认设置8小时拉一次listing 3600*8
            }
        }
        if ($status == 1){
            return true;
        }else{
            return false;
        }
    }   

    /**
     * 统计listing账号记录总数
     */
    public function setListingNums($nums = 0){
        $nums = (int)$nums;
        if ($nums > 0){
            EbayLog::model()->saveEventTotal(EbayProduct::EVENT_NAME,$this->_event_logID,$nums); 
        }            
    }

    /**
     * @desc 设置事件日志编号
     * @param int $eventLogID
     */
    public function setEventLogID($eventLogID) {
        $this->_event_logID = $eventLogID;
    }       

	
    
    /**
     * @desc ebay产品下线操作
     * @param unknown $sellerSKU
     * @param unknown $itemID
     * @param unknown $accountID
     */
	public function ebayProductOffline($itemID){
		try{
			$rows = $this->find("item_id='{$itemID}'");
			if(empty($rows)) throw new Exception("没有对应item");
			$accountID = $rows['account_id'];
			$sku = $rows['sku'];
			$sellerSKU = $rows['seller_sku'];
			if ($rows['listing_type'] == 'Chinese') {
				$request = new EndItemRequest();
			} else {
				$request = new EndFixedPriceItemRequest();
				$request->setSKU($rows['sku_online']);
			}
			$request->setItemID($rows['item_id']);
			$request->setEndingReason('NotAvailable');
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			
			//写入记录表
			if(!$request->getIfSuccess()){
				//收集错误信息
				throw new Exception($request->getErrorMsg());
			}	
			$this->getDbConnection()
			->createCommand()
			->insert("ueb_ebay_product_offline_log", array('item_id'=>$itemID, 'sku'=>$sku, 'seller_sku'=>$sellerSKU, 'create_time'=>date("Y-m-d H:i:s"), 'create_user_id'=>intval(Yii::app()->user->id)));
			
			return true;
		}catch (Exception $e){
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
	}


    /**
     * @desc 根据ID上架产品
     * @param unknown $ID
     * @throws Exception
     * @return boolean
     */
    public function ebayProductOnlineById($ID){
        try{
            $ebayProductModel = new EbayProduct();
            $ebayProductOnlineLogModel = new EbayProductOnlineLog();
            $rows = $ebayProductModel->find("id='{$ID}'");
            if(empty($rows)) throw new Exception("没有对应item");
            $accountID = $rows['account_id'];
            $siteID = $rows['site_id'];
            $siteLists = UebModel::model('EbaySite')->getSiteList();
            $siteName = isset($siteLists[$siteID]) ?  $siteLists[$siteID] : 'US';

            if ($rows['listing_type'] == 'FixedPriceItem') {
                $request = new RelistFixedPriceItemRequest();
            } else {
                $request = new RelistItemRequest();
            }

            $request->setItemID($rows['item_id']);
            $request->setSite($siteName);
            $request->setSiteID($siteID);
            $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();

            $onlineData = array();
            $onlineData['item_id'] = $rows['item_id'];
            $onlineData['seller_sku'] = $rows['sku_online'];
            $onlineData['sku'] = $rows['sku'];
            $onlineData['account_id'] = $rows['account_id'];
            $onlineData['site_id'] = $rows['site_id'];
            $onlineData['create_user_id'] = intval(Yii::app()->user->id);
            $onlineData['create_time'] = date("Y-m-d H:i:s");
            if(!$request->getIfSuccess()){

                $onlineData['status'] = 2;
                $onlineData['message'] = $request->getErrorMsg();
                $ebayProductOnlineLogModel->getDbConnection()->createCommand()->insert($ebayProductOnlineLogModel->tableName(), $onlineData);
                throw new Exception("itemid:{$rows['item_id']} ".$request->getErrorMsg());
            }else{

                $onlineData['new_item_id'] = $response->ItemID;
                $onlineData['status'] = 1;
                $onlineData['message'] = 'ok';
                $ebayProductOnlineLogModel->getDbConnection()->createCommand()->insert($ebayProductOnlineLogModel->tableName(), $onlineData);
            }
            return true;
        }catch (Exception $e){
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }

	/**
	 * @desc 根据ID下架产品
	 * @param unknown $ID
	 * @throws Exception
	 * @return boolean
	 */
	public function ebayProductOfflineByID($ID){
		try{
			$ebayProductModel = new EbayProduct();
			$rows = $ebayProductModel->find("id='{$ID}'");
			if(empty($rows)) throw new Exception("没有对应item");
			$accountID = $rows['account_id'];
			if ($rows['listing_type'] == 'Chinese') {
				$request = new EndItemRequest();
			} else {
				$request = new EndFixedPriceItemRequest();
				$request->setSKU($rows['sku_online']);
			}
			$request->setItemID($rows['item_id']);
			$request->setEndingReason('NotAvailable');
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			//写入记录表
			if(!$request->getIfSuccess()){
				//收集错误信息
				throw new Exception("itemid:{$rows['item_id']} ".$request->getErrorMsg());
			}
			
			
			//更新本地
			$ebayProductModel->getDbConnection()
							->createCommand()
							->update($ebayProductModel->tableName(),
							array('item_status'=>self::STATUS_OFFLINE), "id=".$ID);
			
			$ebayProductModel->getDbConnection()
			->createCommand()
			->insert("ueb_ebay_product_offline_log", array('item_id'=>$rows['item_id'], 'sku'=>$rows['sku'], 'seller_sku'=>$rows['sku_online'], 'account_id'=>$rows['account_id'], 'site_id'=>$rows['site_id'], 'create_time'=>date("Y-m-d H:i:s"), 'create_user_id'=>intval(Yii::app()->user->id),'type'=>1));
			return true;
		}catch (Exception $e){
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
	}

	/**
	 * @desc 根据子SKU ID下架操作
	 * @param unknown $variationID
	 * @return boolean
	 */
	public function ebayVariationProductOfflineByVaritionID($variationID){
		try{
			$ebayProductVariationModel = new EbayProductVariation;
            $ebayProductModel          = new EbayProduct;
			$variationInfo = $ebayProductVariationModel->findByPk($variationID);
			if(empty($variationInfo)){
				throw new Exception("没有对应子SKU");
			}

            $productInfo = $ebayProductModel->getOneByCondition('site_id','id = '.$variationInfo['listing_id']);
            if(empty($productInfo)){
                throw new Exception("没有对应主SKU");
            }

			$itemID = $variationInfo['item_id'];
			$sku = $variationInfo['sku'];
			$accountID = $variationInfo['account_id'];
			$sellerSKU = $variationInfo['sku_online'];
			$oldQuantity = $variationInfo['quantity_available'];
			$quantity = 0;
			$reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
			$reviseInventoryStatusRequest->setAccount($accountID);
			
			$reviseInventoryStatusRequest->setSku($sellerSKU);
			$reviseInventoryStatusRequest->setItemID($itemID);
			$reviseInventoryStatusRequest->setQuantity($quantity);
			$reviseInventoryStatusRequest->push();
			$response = $reviseInventoryStatusRequest->setRequest()->sendRequest()->getResponse();
			$reviseInventoryStatusRequest->clean();
			//收集错误信息
			$errormsg = "";
			if( isset($response->Errors) ){
				foreach($response->Errors as $error) {
					$errormsg .= $error->LongMessage.".";
				}
			}
			if(isset($response->Fees) && $response->Fees){
				$feedItemIDs = array();
				if(!isset($response->Fees[0])){
					$feedItemIDs[] = $response->Fees->ItemID;
				}else{//返回多个
					foreach ($response->Fees as $feed){
						$feedItemIDs[] = $feed->ItemID;
					}
				}
				if(in_array($itemID, $feedItemIDs)){
					$ebayProductVariantModel = new EbayProductVariation();
					$ebayProductVariantModel->getDbConnection()
											->createCommand()
											->update($ebayProductVariantModel->tableName(), 
														array("quantity_available"=>$quantity), "id=".$variationID);
					
					$ebayProductVariantModel->getDbConnection()
					->createCommand()
					->insert("ueb_ebay_product_offline_log", array('item_id'=>$itemID, 'sku'=>$sku, 'seller_sku'=>$sellerSKU, 'account_id'=>$variationInfo['account_id'], 'site_id'=>$productInfo['site_id'], 'create_time'=>date("Y-m-d H:i:s"), 'create_user_id'=>intval(Yii::app()->user->id),'type'=>2,'old_quantity'=>$oldQuantity));
					
					return true;
				}
			}
			
			throw new Exception($errormsg);
		}catch (Exception $e){
			$this->setExceptionMessage($e->getMessage());
			return  false;
		}
	}
	
    /**
     * @desc 改价
     */
    public function ebayProductRevisePrice($itemID){
        if(empty($itemID)) return false;
        if(!is_array($itemID)){
            $itemID = array($itemID);
        }
        $limit = 20;
    
        $listing = $variantListing = array();
        $ebayProductModel = new EbayProduct();
        $ebayProductVariantModel = new EbayProductVariation();
        $command = $ebayProductModel->getDbConnection()->createCommand()
                                ->from($ebayProductModel->tableName())
                                ->select("id, sku, sku_online, account_id, current_price as sale_price, site_id, item_id, quantity, is_multiple")
                                ->where("is_multiple=0") //单品
                                ->andWhere('item_status='.EbayProduct::STATUS_ONLINE)
                                ->andWhere("listing_type in('FixedPriceItem', 'StoresFixedPrice')")//非拍卖下
                                ->andWhere("gtin_update_status=0");
        if($itemID){
            $command->andWhere(array("in", "item_id", $itemID));
        }
        $listing = $command->queryAll();
        
        $command = $ebayProductVariantModel->getDbConnection()->createCommand()
                                ->from($ebayProductVariantModel->tableName() . " as t")
                                ->leftJoin($ebayProductModel->tableName() . " p", "p.id=t.listing_id")
                                ->select("t.id, t.sku, t.sku_online, t.sale_price, t.account_id, t.quantity, t.item_id, p.site_id, p.is_multiple")
                                ->where('p.item_status='.EbayProduct::STATUS_ONLINE)
                                ->andWhere("p.listing_type in('FixedPriceItem', 'StoresFixedPrice')")//非拍卖下
                                ->andWhere("t.gtin_update_status=0");
        if($itemID){
             $command->andWhere(array("in", "t.item_id", $itemID));
        }
        $variantListing = $command->queryAll();
         
         
        if($listing && $variantListing)
            $listing = array_merge($listing, $variantListing);
        elseif(!$listing && $variantListing)
        $listing = $variantListing;
        unset($variantListing);
        //从本地listing取出有效的listing
        $newListing = array();
        if($listing){
            //$isContinue = true;
            foreach ($listing as $list){
                if($list['site_id'] != 3) continue;
                $newListing[$list['account_id']][] =  $list;
            }
        }else{
            //$isContinue = false;
        }
        foreach ($newListing as $accountID=>$skuList){
            $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
            $reviseInventoryStatusRequest->setAccount($accountID);
            $count = 0;$maxcount = 3;$currentSku = array();
            foreach ($skuList as $list){
                $newPrice = round($list['sale_price']*1.15, 3);
                //MHelper::writefilelog( "ebay/".$accountID . '-revisePrice.txt', date("Y-m-d H:i:s")."-".$list['item_id'].'---old:'.$list['sale_price'].'---new:'.$newPrice."\r\n");

                $reviseInventoryStatusRequest->setSku($list['sku_online']);
                $reviseInventoryStatusRequest->setItemID($list['item_id']);
                $reviseInventoryStatusRequest->setStartPrice($newPrice);
                $reviseInventoryStatusRequest->push();
                $response = $reviseInventoryStatusRequest->setRequest()->sendRequest()->getResponse();
                
                $reviseInventoryStatusRequest->clean();
                //收集错误信息
                $errormsg = $reviseInventoryStatusRequest->getErrorMsg();
                
                //写入记录表
                if(!$reviseInventoryStatusRequest->getIfSuccess()){
                    $err = date("Y-m-d H:i:s")."-".$list['item_id'].'---old:'.$list['sale_price'].'---new:'.$newPrice.'---'. $reviseInventoryStatusRequest->getErrorMsg();
                    //MHelper::writefilelog( "ebay/".$accountID . '-revisePrice_err.txt', $err."\r\n");
                }

                if(isset($response->Fees) && $response->Fees){
                    $feedItemIDs = array();
                    if(!isset($response->Fees[0])){
                        $feedItemIDs[] = $response->Fees->ItemID;
                    }else{//返回多个
                        foreach ($response->Fees as $feed){
                            $feedItemIDs[] = $feed->ItemID;
                        }
                    }
                    if(in_array($list['item_id'], $feedItemIDs)){
                        if(!$list['is_multiple']){
                            $ebayProductModel->getDbConnection()->createCommand()->update($ebayProductModel->tableName(), array("gtin_update_status"=>1, "current_price"=>$newPrice), "id=".$list['id']);
                        }else{
                            $ebayProductVariantModel->getDbConnection()->createCommand()->update($ebayProductVariantModel->tableName(), array("gtin_update_status"=>1, "sale_price"=>$newPrice), "id=".$list['id']);
                        }
                    }
                }

            }//foreach2
        }//foreach1
    }

    /**
     * 获取每个ebay账号额度信息
     * @return array
     */
    public function getAccountLimitConfig($accountID = null) {
        $rtn = array();
        $infos = EbayAccountInfo::model()->getAccountInfoListJoinAccount();
        if (empty($infos)) {
            return array();
        }
        foreach ($infos as $v) {
            $rtn[$v['account_id']] = $v;
        }
        return empty($accountID) ? $rtn 
                : isset($rtn[$accountID]) ? $rtn[$accountID] : array();
    }

    /**
     * @desc 获取产品listing数据
     * @param  int $accountID  销售账号id
     * @param  string $itemID  刊登号
     * @param  string $onlineSku  在线sku
     * @return array
     */
    public function getReviseInventoryStatusData($accountID, $itemID=null, $onlineSku=null) {
        $ebayProductModel = EbayProduct::model();
        $ebayProductVariantModel = EbayProductVariation::model();
        $command = $ebayProductVariantModel->getDbConnection()->createCommand()
                                ->select("t.id,t.listing_id,t.item_id, t.sku_online, t.sale_price, t.quantity,t.quantity_sold,t.quantity_available,p.is_multiple")
                                ->from($ebayProductModel->tableName() . " p")
                                ->leftJoin($ebayProductVariantModel->tableName() . " as t", "p.id=t.listing_id")
                                ->where("t.account_id=".$accountID)
                                ->andWhere("p.listing_type in('FixedPriceItem', 'StoresFixedPrice')")//非拍卖下
                                ->andWhere('p.item_status='.EbayProduct::STATUS_ONLINE);
        if($itemID){
            $command->andWhere("t.item_id = '{$itemID}'");
        }
        if ($onlineSku) {
            $command->andWhere("t.sku_online = '{$onlineSku}'");
        }
        return $command->queryAll();
    }


    /**
     * @desc 修改在线数量和价格
     * @param integer $accountID
     * @param array $data ['item_id'=>'282033283450','sku_online'=>'1KR0QT5UM3MM2XS7','count'=>10,'price'=>12.4]
     * @return [type]       
     */
    public function reviseEbayListing($accountID, $data) {
        $path = "ebay/ReviseInventoryStatus/".date("Ymd").'/'.$accountID;
        $ebayProductModel        = EbayProduct::model();
        $ebayProductVariantModel = EbayProductVariation::model();
        $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
        $reviseInventoryStatusRequest->setAccount($accountID);
        if ( !isset($data['item_id']) || !isset($data['sku_online']) || (!isset($data['count']) && !isset($data['price'])) ) {
            //MHelper::writefilelog( $path . '/request-err.txt', date('Y-m-d H:i:s'). ' #### '. print_r($data,true)."\r\n");//fortest
            return array('errorCode'=>101,'errorMsg'=>'参数错误');
        }
        $listing = $this->getReviseInventoryStatusData($accountID, $data['item_id'], $data['sku_online']);
        if (empty($listing)) {
            //MHelper::writefilelog( $path . '/request-err2.txt', date('Y-m-d H:i:s'). ' #### '. print_r($data,true)."\r\n");//fortest
            return array('errorCode'=>102,'errorMsg'=>'listing['. $data['item_id'].' -- '. $data['sku_online'] .']不满足修改条件');
        }
        $listing = $listing[0];
        $reviseInventoryStatusRequest->setItemID($listing['item_id']);
        $reviseInventoryStatusRequest->setSku($listing['sku_online']);
        if (isset($data['count']) && $data['count']>=0 ) {
            $reviseInventoryStatusRequest->setQuantity($data['count']);
        }
        if (isset($data['price']) && $data['price']>0 ) {
            $reviseInventoryStatusRequest->setStartPrice($data['price']);
        }
        $reviseInventoryStatusRequest->push();//加入修改数据中
        $response = $reviseInventoryStatusRequest->setRequest()->sendRequest()->getResponse();
        //MHelper::writefilelog( $path . '/response.txt', date('Y-m-d H:i:s'). ' #### '. print_r(array('param'=>$data,'response'=>$response),true)."\r\n");//fortest
        $reviseInventoryStatusRequest->clean();//清空inventoryStatus节点值
        //写入记录表
        if(!$reviseInventoryStatusRequest->getIfSuccess()){
            $err = date("Y-m-d H:i:s").'###'. $reviseInventoryStatusRequest->getErrorMsg();
            //MHelper::writefilelog( $path . '/reviseCount_err.txt', date('Y-m-d H:i:s'). ' #### '. $err."\r\n");//fortest
            return array('errorCode'=>104,'errorMsg'=>$reviseInventoryStatusRequest->getErrorMsg());
        } else {

            $callBack = $listing['item_id'] .'_'. $listing['sku_online'];
            return array('errorCode'=>200,'errorMsg'=>'ok','data'=> $callBack);
        }
    }

    /**
     * @desc 检测是否存在sku
     * @param unknown $sku
     * @param unknown $accountID
     * @param number $siteID
     * @return boolean
     */
    public function checkSKUExists($sku, $accountID, $siteID = 0, $isOnline = true){
    	$flag = false;
    	$skuInfo = $this->getDbConnection()->createCommand()->from($this->tableName())
    											->where("sku='{$sku}' AND account_id='{$accountID}' AND site_id='{$siteID}'")
    											->andWhere($isOnline ? "item_status=1" : "1")
    											->select("id")
    											->queryRow();
    	if($skuInfo) $flag = true;
    	return $flag;
    }
    
    /**
     * @desc 获取ebay产品信息
     * @param unknown $sku
     * @param number $siteId
     * @return mixed
     */
    public function getEbayProductInfoBySKU($sku, $siteId = null){
    	$skuInfo = $this->getDbConnection()->createCommand()->from($this->tableName())
				    	->where("sku='{$sku}'")
				    	->andWhere($siteId === null ? "1" : "site_id='{$siteId}'")
				    	->select("*")
				    	->queryRow();
    	return $skuInfo;
    }
    
    
    /**
     *
     * @param unknown $itemID
     * @param unknown $accountID
     * @param number $updateType  0  不更新任何， 1只更新附图，2只更新主图，  4只更新标题, 利用位与操作进行组合操作
     * @throws Exception
     * @return boolean
     */
    public function changOnlineDescriptionByItemID($itemID, $accountID, $updateType = 1){
    	try{
    		if(! ($updateType&0x01) && !($updateType & 0x02) && !($updateType & 0x04)){
    			throw new Exception("更新类型不符合");
    		}
    		if(empty($itemID)){
    			throw new Exception("ItemID为空");
    		}
    		
    		//取出对应的itemID
    		$itemInfo = $this->find("item_id='{$itemID}' and account_id='{$accountID}'");
    		if(empty($itemInfo)){
    			throw new Exception("ItemID无效");
    		}
    		$sku = $itemInfo['sku'];
    		$title = $itemInfo['title'];
    		$siteID = $itemInfo['site_id'];
    		$language = "";
    		$description = "";
    		$included = "";
    		$warehouse = "";
    		$imageList = array();
    		$ztImages = array();
    		$variationImages = array();
    		$variationPictureSpecific = isset($itemInfo['variation_picture_specific']) ? $itemInfo['variation_picture_specific'] : '';
    		if($itemInfo['listing_type'] == 'FixedPriceItem'){
    			$requestModel = new ReviseFixedPriceItemRequest();
    		}else{
    			$requestModel = new ReviseItemRequest();
    		}
    		
    		$ebaySiteModel = new EbaySite();
    		//取出附图sku
    		$ebayProductImageAddModel = EbayProductImageAdd::model();
    		//$variationSKUList = EbayProductAddVariation::model()->getEbayProductAddVariationJoinEbayProductAddByAddID($itemInfo['id']);
    		$variationSKUList = EbayProductVariation::model()->getProductVariantListByCondition("item_id=:item_id and account_id=:account_id", array(':item_id'=>$itemID,':account_id'=>$accountID));
    		
    		$requestModel->setAccount($accountID)->setItemID($itemID);
    		$variationSKUArr = array();
    		if($variationSKUList){
    			foreach ($variationSKUList as $variation){
    				$variationSKUArr[] = $variation['sku'];
    			}
    		}
    
    		//@todo 全部实现修改主副图操作
    		
    		if($updateType & 0x04){//标题
    			if($siteID && !$language){//有siteid,可以通过siteid查到语言
    				$language = $ebaySiteModel->getLanguageBySiteIDs($siteID);
    			}
    			$descriptionTemplateModel = new EbayDescriptionTemplate;
    			$conditions = " account_id='$accountID'";
    			if($language){
    				$conditions .= " AND language_code = '$language'";
    			}
    			$productDesc = Productdesc::model()->getDescriptionInfoBySkuAndLanguageCode($sku, $language);
    			$title = $productDesc['title'];
    			$requestModel->setTitle($title);
    		}
    		if($updateType & 0x02){//主图
    			$response = $ebayProductImageAddModel->getSkuImageUpload($accountID, $sku, array(), false);
    			if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs'])) {
    				$variationSKUArr[] = $sku;
    				$ebayProductImageAddModel->addSkuImageUpload($accountID, $variationSKUArr);
    				throw new Exception("图片获取失败z1", 2);
    			}
    			foreach ($response['result']['imageInfoVOs'] as $v) {
    				if ($v['remotePath'] != '' ) {
                        //跳过小图
                        if(isset($v['imageName']) && $sku.'.jpg'==$v['imageName']){
                            continue;
                        }
    					//$_1.JPG?  更换为原图
    					$v['remotePath'] = str_replace('$_1.JPG?', '$_10.JPG?', $v['remotePath']);
    					$ztImages[] = $v['remotePath'];
    				}
    			}
    			$PictureURL = $ztImages[0];
    			
    			$ztImages = array_slice($ztImages, 0, 12);
    			
    			if($ebaySiteModel->getMoreZtSite($siteID)){//可以放多张主图
    				$pictureDetails = array(
    						'PhotoDisplay'	=>	'PicturePack',
    						'PictureSource'	=>	'EPS',
    						'PictureURL'	=>	$ztImages
    				);
    			}else{
    				$pictureDetails = array(
    						'ExternalPictureURL' => $PictureURL,
    						'PictureURL' => $PictureURL,
    				);
    			}
    			$requestModel->setPictureDetails($pictureDetails);
    			
    			if($variationSKUList){
    				$picvarDetail = array();
    				foreach ($variationSKUList as $variationSKU){
    					//specificValue
    					if(empty($variationSKU['variation_specifics'])){
    						break;
    					}
    					$specificValue = json_decode($variationSKU['variation_specifics'], true);
    					if(empty($specificValue)){
    						break;
    					}
    					$specificValue = array_slice($specificValue, 0, 1);
    					$variationSpecificName = key($specificValue);
    					$variationSpecificValue = $specificValue[$variationSpecificName];
    					if(empty($variationPictureSpecific)){
    						$variationPictureSpecific = $variationSpecificName;
    					}
    					$response = $ebayProductImageAddModel->getSkuImageUpload($accountID, $variationSKU['sku'], array(), false);
    					if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs'])) {
    						$ebayProductImageAddModel->addSkuImageUpload($accountID, $variationSKUArr);
    						throw new Exception("图片获取失败z2", 2);
    					}
    					foreach ($response['result']['imageInfoVOs'] as $v) {
    						if ($v['remotePath'] != '' ) {
    							 
    							//$_1.JPG?  更换为原图
    							$v['remotePath'] = str_replace('$_1.JPG?', '$_10.JPG?', $v['remotePath']);
    							
    							$variationImages[$variationSKU['sku_online']][] = $v['remotePath'];
    						}
    					}
    					
    					if($ebaySiteModel->getMoreFtSite($siteID)){//可以放多张副图
    						$images = array_slice($variationImages[$variationSKU['sku_online']], 0, 12);
    					}else{
    						$images = array_slice($variationImages[$variationSKU['sku_online']], 0, 1);
    					}
    					//子sku
    					$picvarDetail[$variationSpecificValue] = array(
    							'VariationSpecificValue' => '<![CDATA['.$variationSpecificValue.']]>',
    							'PictureURL' 	=> 	$images,
    					);
    				}
    				sort($picvarDetail);
    				if($picvarDetail){
    					$variationPictures = array(
    							'VariationSpecificName'			=>	'<![CDATA['.$variationPictureSpecific.']]>',
    							'VariationSpecificPictureSet'	=>	$picvarDetail
    					);
    					$requestModel->setVariationsPictures($variationPictures);
    				}
    			}
    		}
    		
    		if($updateType & 0x01){//副图
    			//2--获取我们服务器图片地址，用来修改详情图片
    			$response = $ebayProductImageAddModel->getSkuImageUpload($accountID, $sku, array(), true);
    			if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs'])) {
    				throw new Exception("图片获取失败f", 2);
    			}
    			foreach ($response['result']['imageInfoVOs'] as $v) {
    				if ($v['remotePath'] != '' ) {
                        if(isset($v['imageName']) && $sku.'.jpg'==$v['imageName']){
                            continue;
                        }
    					//$_1.JPG?  更换为原图
    					$v['remotePath'] = str_replace('$_1.JPG?', '$_10.JPG?', $v['remotePath']);
    					$imageList[] = $v['remotePath'];
    				}
    			}
    			
    			if($variationSKUList){
    				/* foreach ($variationSKUList as $variationSKU){
    					$response = $ebayProductImageAddModel->getSkuImageUpload($accountID, $variationSKU['sku'], array(), true);
    					if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs'])) {
    						throw new Exception("图片获取失败f2", 2);
    					}
    					$maxCount = 0;
    					foreach ($response['result']['imageInfoVOs'] as $v) {
    						if ($v['remotePath'] != '' ) {
    							//$_1.JPG?  更换为原图
    							$v['remotePath'] = str_replace('$_1.JPG?', '$_10.JPG?', $v['remotePath']);
    							$imageList[] = $v['remotePath'];
    							$maxCount++;
    							if($maxCount>=1) break;
    						}
    					}
    				} */
    			}
    			/* $addInfo = EbayProductAdd::model()->find("item_id='{$itemID}' and account_id='{$accountID}'");
    			if(!empty($addInfo)){
    				//取出描述，如果找到系统中有对应刊登记录，则取刊登的描述
    				$description = EbayProductAdd::model()->getDescriptionOnlyImg($title, $addInfo['detail'], $sku, $accountID, $siteID, array(), $imageList);
    			}else{ */
    				//getDescriptionOnlyImg
    				$description = EbayProductAdd::model()->getDescription($sku, $accountID, $siteID, $language, '', false, $title, $description, $included, $warehouse, $imageList);
    			//}
    			$requestModel->setDescription($description);
    		}
    	
    		$response = $requestModel->setRequest()
						    		->sendRequest()
						    		->getResponse();
    
    		if($requestModel->getIfSuccess()){
    			return true;
    		}else{
    			throw new Exception($requestModel->getErrorMsg());
    		}
    	}catch (Exception $e){
    		//echo $e->getMessage();
    		$this->setExceptionMessage($e->getMessage());
    		$this->setExceptionCode($e->getCode());
    		return false;
    	}
    }

    /**
     * @desc 组装description
     * @return string
     */
    public function saveFormatedDescription($itemID, $accountID) {
        try {
            $itemInfo = $this->find("item_id='{$itemID}' and account_id='{$accountID}'");
            if(empty($itemInfo)){
                throw new Exception("ItemID is not Exist", 1);
            }
            $sku = $itemInfo['sku'];
            $title = $itemInfo['title'];
            $siteID = $itemInfo['site_id'];
            $language = "";
            $description = "";
            $included = "";
            $warehouse = "";
            $imageList = array();
            //取出附图sku
            $ebayProductImageAddModel = EbayProductImageAdd::model();
            //2--获取我们服务器图片地址，用来修改详情图片
            $response = $ebayProductImageAddModel->getSkuImageUpload($accountID, $sku, array(), true);
            //MHelper::writefilelog('img.txt', print_r($response,true)."\r\n");
            if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs'])) {
                //如果是多属性并且主sku后缀带字母
                if ($itemInfo['is_multiple'] == 1 && preg_match("/^\d{4,9}[A-Z]{1}?$/i", $sku) ) {
                    $variationInfo = EbayProductVariation::model()->getOneByCondition('sku',"listing_id={$itemInfo['id']}");
                    if ($variationInfo && strrpos($variationInfo['sku'], '.') > 0) {
                        $tmp = explode('.',$variationInfo['sku']);
                        $response = $ebayProductImageAddModel->getSkuImageUpload($accountID, $tmp[0], array(), true);
                        if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs'])) {
                            throw new Exception($itemID.'  @@ '.$sku.' ## '. $tmp[0] .' @@ '."图片获取失败d", 3);
                        }
                    }
                } else {
                    throw new Exception($itemID.'  @@ '.$sku."图片获取失败f", 2);
                }
            }
            foreach ($response['result']['imageInfoVOs'] as $v) {
                if ($v['remotePath'] != '' ) {
                    //$_1.JPG?  更换为原图
                    $v['remotePath'] = str_replace('$_1.JPG?', '$_10.JPG?', $v['remotePath']);
                    $imageList[] = $v['remotePath'];
                }
            }
            //getDescriptionOnlyImg
            $description = EbayProductAdd::model()->getDescription($sku, $accountID, $siteID, $language, '', false, $title, $description, $included, $warehouse, $imageList);
            $filename  = 'newtpl/'.$accountID.'/' . $itemID. '.html';
            MHelper::saveDescription($filename, $description, Platform::CODE_EBAY );
            return true;
        } catch (Exception $e) {
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 修改listing字段信息
     * @param  array $fields
     * @param  string $itemID 
     * @param  string $accountID
     * @param  string $listingType
     * @return boolean
     */
    public function changeListingFields($fields,$itemID,$accountID=null,$listingType=null) {
        if (empty($accountID)) {
            $info = $this->getOneByCondition("account_id,listing_type","item_id='{$itemID}' and item_status=1");
            if (empty($info)) {
                return array('errorFlag'=>false,'errorMsg'=>'item 符合修改条件');
            }
            $accountID = $info['account_id'];
            $listingType = $info['listing_type'];
        }
        if($listingType == 'FixedPriceItem'){
            $requestModel = new ReviseFixedPriceItemRequest();
        }else{
            $requestModel = new ReviseItemRequest();
        }
        $requestModel->setAccount($accountID)->setItemID($itemID);
        if (isset($fields['dispatchTimeMax'])) {
            $requestModel->setDispatchTimeMax($fields['dispatchTimeMax']);
        }
        if (isset($fields['country'])) {
            $requestModel->setCountry($fields['country']);
        }
        if (isset($fields['location'])) {
            $requestModel->setLocation($fields['location']);
        }        
        $response = $requestModel->setRequest()
                                ->sendRequest()
                                ->getResponse();
                             
        return array('errorFlag'=> $requestModel->getIfSuccess(), 
            'errorMsg'=>$requestModel->getErrorMsg());      
    }
    /**
     * @desc 修改本地物流方式
     * @param  string $itemID
     * @param  array $shipping
     * @param  string $accountID
     * @param  string $listingType
     * @return boolean
     */
    public function changeShipping($itemID,$shipping,$accountID,$listingType) {
        if($listingType == 'FixedPriceItem'){
            $request = new ReviseFixedPriceItemRequest();
        }else{
            $request = new ReviseItemRequest();
        }
        foreach ($shipping as $value){
            $shippingServiceOptions = array(
                'FreeShipping'	 	=> 	$value['FreeShipping'],
                'ShippingService' 	=> 	$value['ShippingService'],
                'ShippingServiceCost' => $value['ShippingServiceCost'],
                'ShippingServiceAdditionalCost' => $value['ShippingServiceAdditionalCost'],
                'ShippingServicePriority' => $value['ShippingServicePriority'],
            );
            $request->setShippingServiceOptions($shippingServiceOptions);
        }
        $request->setAccount($accountID)->setItemID($itemID);

        $response = $request->setRequest()->sendRequest()->getResponse();
        $request->clean();
        return array(
            'errorFlag'=> $request->getIfSuccess(),
            'errorMsg'=>$request->getErrorMsg()
        );
    }

    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {
        return array(
            'quantity_sold',
            'quantity_available',
            'current_price',
            'shipping_price',
            'start_time',
            'handing_time',
            //'profit_rate',
            //'profit',
            //'available_qty',
            //'product_weight',
            //'product_cost',
        );
    }


    /**
     * @desc 根据产品ID，修改标题
     * @param unknown $id         产品ID
     * @param string  $title      产品标题
     * @return boolean
     */
    public function reviseTitleByItemID($id, $title){
        $errormsg = "";

        try{
            $productInfo = $this->getOneByCondition('account_id,item_id','id = '.$id);
            if(!$productInfo){
                throw new Exception("无此sku信息");
            }

            $accountID  = $productInfo['account_id'];
            $itemID     = $productInfo['item_id'];

            if(empty($accountID)){
                throw new Exception("账号ID不能为空");
            }

            if(empty($itemID)){
                throw new Exception("线上产品ID不能为空");
            }

            if(empty($title)){
                throw new Exception("产品标题不能为空");
            }

            $reviseFixedPriceItemRequest = new ReviseFixedPriceItemRequest();
            $reviseFixedPriceItemRequest->setAccount($accountID);
            $reviseFixedPriceItemRequest->setItemID($itemID);
            $reviseFixedPriceItemRequest->setTitle($title);
            $response = $reviseFixedPriceItemRequest->setRequest()->sendRequest()->getResponse();
            $reviseFixedPriceItemRequest->clean();
            //收集错误信息
            
            if( isset($response->Errors) ){
                foreach($response->Errors as $error) {
                    $errormsg .= $error->LongMessage.".";
                }
            }
            if(isset($response->Fees) && $response->Fees){
                $this->getDbConnection()
                    ->createCommand()
                    ->update($this->tableName(), array("title"=>$title), "id=".$id);
            }

            return $errormsg;

        }catch (Exception $e){
            $errormsg = $e->getMessage();
            return  $errormsg;
        }
    }


    /**
     * @desc 页面的跳转链接地址
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/ebay/ebayproduct/list');
    }


    public function addOrUpdateEbayProduct($res) {
        $product = new Product();        
        foreach ($res as $v) {
            try {
                $data                      = array();
                $data['ebay_accountid']    = $v['account_id'];
                $data['siteid']            = $v['site_id'];
                $data['location']          = $v['location']; 
                $data['categoryid']        = $v['category_id'];
                $data['categoryname']      = $v['category_name'];
                $data['store_categoryid']  = $v['store_category_id'];   
                $data['sku']               = $v['sku'];
                $data['sku_online']        = $v['sku'];
                $data['encryptsku']        = $v['sku_online']; 
                $data['title']             = $v['title'];
                $data['viewitemurl']       = $v['view_item_url'];
                $data['galleryurl']        = $v['gallery_url'];     
                $data['quantity']          = $v['quantity'];
                $data['quantityavailable'] = $v['quantity_available'];
                $data['listingduration']   = $v['listing_duration'];   
                $data['listingtype']       = $v['listing_type'];
                $data['buyitnowprice']     = $v['buyitnow_price'];
                $data['buyitnowprice_cur'] = $v['buyitnow_price_currency']; 
                $data['currentprice']      = $v['current_price'];
                $data['currentprice_cur']  = $v['current_price_currency'];
                $data['shippingcost']      = $v['shipping_price'];   
                $data['shippingcost_cur']  = $v['current_price_currency'];
                $data['totalprice']        = $v['total_price'];
                $data['totalprice_cur']    = $v['total_price_currency'];   
                $data['timestamp']         = date('Y-m-d H:i:s');
                $data['starttime']         = $v['start_time'];
                $data['timeleft']          = $v['time_left']; 
                $data['update_sku']        = $v['update_sku'];
                $data['status']            = $v['item_status'];
                $data['paypalemail']       = $v['paypal_email']; 
                $data['quantitysold']      = $v['quantity_sold'];   
                $data['questioncount']     = $v['question_count'];
                $data['multiple']          = $v['is_multiple'];
                $data['watch_count']       = $v['watch_count'];
                $data['handing_time']      = $v['handing_time'];                       
                $row = $product->dbConnection->createCommand()
                        ->select('itemid')
                        ->from('ueb_ebay_product')
                        ->where("itemid='{$v['item_id']}'")
                        ->queryRow();
                if (!empty($row)) {
                    $product->dbConnection->createCommand()->update('ueb_ebay_product',$data,"itemid='{$v['item_id']}'");
                } else {
                    $data['itemid']    = $v['item_id'];
                    $product->dbConnection->createCommand()->insert('ueb_ebay_product',$data);
                } 
            } catch (Exception $e) {
                echo $v['item_id'].'###'. $e->getMessage()."\r\n<br>";
                continue;
            }
        }
        return true;   
    }

}