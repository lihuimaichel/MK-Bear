<?php
/**
 * @desc 获取请求结果列表
 * @since 2015-08-24
 * @author Gordon
 */
class LazadaFeed extends LazadaModel{

    const EVENT_NAME = 'get_feed';
    
    const STATUS_QUEUED     = 'Queued';//提交请求
    const STATUS_PROCESSING = 'Processing';//正在处理
    const STATUS_CANCELED   = 'Canceled';//已取消
    const STATUS_FINISHED   = 'Finished';//处理完成
    const STATUS_ERROR      = 'Error';//错误
    
    const FEED_STATUS_WAITING = 0;//等待
    const FEED_STATUS_SUCCESS = 1;//成功
    const FEED_STATUS_FAILURE = 2;//失败
    
    const IS_MARKED = 1;
    
    const ACTION_PRODUCT_CREATE = 'ProductCreate';//产品刊登
    const ACTION_IMAGE          = 'Image';//上传图片
    const ACTION_PRODUCT_UPDATE = 'ProductUpdate';
    
    const MAX_NUM_PER_TASK = 200;
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var integer 站点ID **/
    public $_siteID = null;
    
    /** @var integer 多属性类型 ,0为一口价1为多属性 **/
    public $_isVariation = 0;
    
    /** @var string 异常信息*/
    public $exception = null;
    
    /** @var int 日志编号*/
    public $_logID = 0;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){}

    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_feed_list';
    }

    /**
     * @desc 设置站点ID
     */
    public function setSiteID($siteID){
    	$this->_siteID = $siteID;
    }    
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
        $this->_accountID = $accountID;
    }
    
    /**
     * @desc 设置多属性类型
     */
    public function setIsVariation($isVariation){
    	$this->_isVariation = $isVariation;
    }   
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }
    
    /**
     * @desc 设置日志编号
     * @param int $logID
     */
    public function setLogID($logID){
        $this->_logID = $logID;
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }
    
    /**
     * @desc 获取报告时间段
     */
    public function getTimeArr($accountID){
        $lastLog = LazadaLog::model()->getLastLogByCondition(array(
                'account_id'    => $accountID,
                'event'         => self::EVENT_NAME,
                'status'        => LazadaLog::STATUS_SUCCESS,
        ));
        return array(
                'start_time'    => !empty($lastLog) ? date('Y-m-d H:i:s',strtotime($lastLog['end_time']) - 15*60) : date('Y-m-d H:i:s',time() - 86400*2 - 8*3600),
        );
    }
    
    /**
     * @desc 获取请求报告
     */
    public function getFeeds($timeArr){
        $accountID = $this->_accountID;
        $request = new FeedOffsetListRequest();
        $request->setUpdatedDate(date('Y-m-d\TH:i:s', strtotime($timeArr['start_time'])));
        $request->setStatus(self::STATUS_FINISHED);
        //抓取订单信息
        $page = 1;
        $finishMark = 0;
        while( !$finishMark ){
            $request->setPageNum($page);
            $response = $request->setSiteID($this->_siteID)->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
            if( $request->getIfSuccess() ){
                try {
                    foreach($response->Body->Feed as $feed) {//循环报告信息
                        //获取报错信息 TODO
                        
                        $status = self::FEED_STATUS_WAITING;
                        if($feed->Status==self::STATUS_FINISHED){
                            if( $feed->ProcessedRecords > 0 && intval($feed->FailedRecords <= 0 )){
                                $status = self::FEED_STATUS_SUCCESS;
                            }else{
                                $status = self::FEED_STATUS_FAILURE;
                            }
                        }
                        $check = $this->findByPk($feed->Feed);
                        $data = array(
                            'status'        => $feed->Status,
                            'modify_time'   => $feed->UpdatedDate,
                            'type'          => $feed->Source,
                            'total_record'  => $feed->TotalRecords,
                            'failed_record' => $feed->FailedRecords,
                            'feed_status'   => $status,
                        );
                        if( $check===null ){
                            $data['feed_id']        = $feed->Feed;
                            $data['create_time']    = $feed->CreationDate;
                            $data['account_id']     = $accountID;
                            $data['action']         = $feed->Action;
                            $this->dbConnection->createCommand()->insert(self::tableName(), $data);
                        }else{
                            $this->dbConnection->createCommand()->update(self::tableName(), $data, 'feed_id = "'.$feed->Feed.'"');
                        }
                        
                        //将各action标记状态
                        $this->comleteStatus();
                    }
                    $page++;
                }catch (Exception $e){
                    $this->setExceptionMessage(Yii::t('lazada', 'Save Failed'));
                    return false;
                }
        
                if( count($response->Body->Feed) < $request->_limit ){//抓取数量小于每页数量，说明抓完了
                    $finishMark = true;
                    break;
                }
            }else{//抓取失败
                $this->setExceptionMessage($request->getErrorMsg());
                return false;
            }
        }
        return true;
    }
    
    /**
     * @desc 获取未完成未取消的请求结果
     */
    public function getActiveFeeds(){
        return $this->dbConnection->createCommand()
            ->select('account_id,feed_id,site_id')
            ->from(self::tableName())
            ->where('status IN ("'.self::STATUS_QUEUED.'","'.self::STATUS_PROCESSING.'")' )
            //->andWhere('account_id=25')
            ->limit(self::MAX_NUM_PER_TASK)
            ->queryAll();
    }
    
    /**
     * @desc 获取完成的报告
     */
    public function getFinishedFeeds(){
        $feedIDs = $this->getActiveFeeds();
        if(!empty($feedIDs)){
            foreach($feedIDs as $item){
                $request = new FeedStatusRequest();
                $request->setFeedID($item['feed_id']);
                $request->setSiteID($item['site_id']);
                $accountInfo = LazadaAccount::getAccountInfoById($item['account_id']);
                $apiAccountID = $accountInfo['account_id'];
                $response = $request->setAccount($apiAccountID)->setRequest()->sendRequest()->getResponse();
                if( $request->getIfSuccess() ){
                    $feed = $response->Body->FeedDetail;
                    $status = self::FEED_STATUS_WAITING;
                    if($feed->Status==self::STATUS_FINISHED){
                        if( $feed->ProcessedRecords > 0 && intval($feed->FailedRecords <= 0 )){
                            $status = self::FEED_STATUS_SUCCESS;
                        }else{
                            $status = self::FEED_STATUS_FAILURE;
                        }
                    }
                    $message = '';
                    foreach($feed->FeedErrors->Error as $error){
                        $message .= $error->Message.'<br/>';
                    }
                    //避免错误消息过多，导致更新失败，截取部分销售
                    $message = substr($message, 0, 1024);
                    $check = $this->findByPk($feed->Feed);
                    $data = array(
                        'status'        => $feed->Status,
                        'modify_time'   => $feed->UpdatedDate,
                        'type'          => $feed->Source,
                        'total_record'  => $feed->TotalRecords,
                        'failed_record' => $feed->FailedRecords,
                        'feed_status'   => $status,
                        'message'       => $message,
                    );
                    if( $check===null ){
                        $data['feed_id']        = $feed->Feed;
                        $data['create_time']    = $feed->CreationDate;
                        $data['account_id']     = $item['account_id'];
                        $data['action']         = $feed->Action;
                        $data['site_id'] 		= $item['site_id'];
                        $this->dbConnection->createCommand()->insert(self::tableName(), $data);
                    }else{
                        $this->dbConnection->createCommand()->update(self::tableName(), $data, 'feed_id = "'.$feed->Feed.'"');
                    }
                } else {
                    if( isset($response->Head->ErrorMessage)){
                        $message = $response->Head->ErrorMessage;
                        $this->dbConnection->createCommand()->update(self::tableName(), array('message' => $message), 'feed_id = "'.$item['feed_id'].'"');
                    }
                }
            }
        }
        //将各action标记状态
        $this->comleteStatus();
        //标记错误的feed
        $this->comleteErrorStatus();
    }
    
    /**
     * @desc 查出完结的feed并将任务标记状态
     */
    public function comleteStatus(){
        $list = $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('status = "'.self::STATUS_FINISHED.'"')
                ->andWhere('marked != '.self::IS_MARKED)
                ->andWhere("type = 'api'")
                ->limit(200)
                ->queryAll();
        foreach($list as $item){
            $result_mark = '';
            switch ($item['action']){
                case self::ACTION_PRODUCT_CREATE:
                    if($item['feed_status']==self::FEED_STATUS_SUCCESS){
                        if ($item['is_variation'] == 1){
                            $result_mark = LazadaProductAddVariation::model()->markVariationStatusByFeedID($item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_IMGFAIL,'');
                        } else {
                            $result_mark = LazadaProductAdd::model()->markStatusByFeedID($item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_IMGFAIL);
                        }
                    	//将上传的产品拉下来保存到listing表
                        if($item['is_variation'] == 1){
                            $records = LazadaProductAddVariation::model()->getVariationRecordByFeedID($item['feed_id']);
                        } else {
                            $records = LazadaProductAdd::model()->getRecordByFeedID($item['feed_id']);
                        }
                    	$skus = array();
                    	foreach($records as $row){
                    		$skus[] = $row['product_id'];
                        }
                    	$accountInfo = LazadaAccount::getAccountInfoById($item['account_id']);
                    	$apiAccountID = $accountInfo['account_id'];
                        if (!empty($skus)) {
                            $model   = new LazadaProductDownload();
                            $isOk    = $model->setAccountAutoID($item['account_id'])
                                            ->setAccountID($apiAccountID)
                                             ->setSiteID($item['site_id'])
                                             ->setProductState(GetProductsRequest::PRODUCT_STATUS_ALL)
                                             ->setSellerSkuList( $skus )
                                             ->startDownloadProducts();
                        }

                    } elseif ($item['total_record']==$item['failed_record']){//全部失败
                        if($item['is_variation'] == 1){
                            $result_mark = LazadaProductAddVariation::model()->markVariationStatusByFeedID($item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_FAILURE,$item['message']);
                        } else {
                            $result_mark = LazadaProductAdd::model()->markStatusByFeedID($item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_FAILURE,$item['message']);
                        }
                    } else {//部分失败
                        //根据sku拉取在线广告
                        if($item['is_variation'] == 1){
                            $records = LazadaProductAddVariation::model()->getVariationRecordByFeedID($item['feed_id']);
                        } else {
                            $records = LazadaProductAdd::model()->getRecordByFeedID($item['feed_id']);
                        }
                        $skus = $recordSku = array();
                        foreach($records as $row){
                            $skus[] = $row['product_id'];
                            $recordSku[$row['product_id']] = $row['product_id'];//担心存在键名之后,json格式交互会报错，所以另外记录一个数组
                        }
                        $accountInfo = LazadaAccount::getAccountInfoById($item['account_id']);
                        $apiAccountID = $accountInfo['account_id'];
                        
                        if (!empty($skus)) {
                            $model   = new LazadaProductDownload();
                            $isOk    = $model->setAccountAutoID($item['account_id'])
                                            ->setAccountID($apiAccountID)
                                             ->setSiteID($item['site_id'])
                                             ->setProductState(GetProductsRequest::PRODUCT_STATUS_ALL)
                                             ->setSellerSkuList( $skus )
                                             ->startDownloadProducts();
                        }

                        $listings = LazadaProduct::model()->getOnlineListingBySku($skus,$apiAccountID, $item['site_id']);
                        $successSku = $failureSku = array();
                        foreach($listings as $listing){
                            $successSku[$listing['seller_sku']] = $listing['seller_sku'];
                            unset($recordSku[$listing['seller_sku']]);
                        }
                        $failureSku = $recordSku;
                        //标记成功失败记录
                        if (!empty($successSku)){
                            if($item['is_variation'] == 1){
                                $result_mark = LazadaProductAddVariation::model()->markStatusBySkusAndFeed($successSku, $item['feed_id'], LazadaProductAdd::UPLOAD_STATUS_IMGFAIL);
                            } else{
                                $result_mark = LazadaProductAdd::model()->markStatusBySkus($successSku, $item['account_id'], $item['site_id'], LazadaProductAdd::UPLOAD_STATUS_IMGFAIL);
                            }
                        }
                        if (!empty($failureSku)){
                            if($item['is_variation'] == 1){
                                $result_mark = LazadaProductAddVariation::model()->markStatusBySkusAndFeed($failureSku, $item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_FAILURE, $item['message']);
                            } else {
                                $result_mark = LazadaProductAdd::model()->markStatusBySkus($failureSku, $item['account_id'], $item['site_id'], LazadaProductAdd::UPLOAD_STATUS_FAILURE, $item['message']);
                            }
                        }
                    }
                    break;
                case self::ACTION_IMAGE:
                    if($item['feed_status']==self::FEED_STATUS_SUCCESS){
                        if($item['is_variation'] == 1){
                            $result_mark = LazadaProductAddVariation::model()->markVariationStatusByFeedID($item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_SUCCESS);
                        } else {
                            $result_mark = LazadaProductAdd::model()->markStatusByFeedID($item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_SUCCESS);
                        }
                    }elseif($item['total_record']==$item['failed_record']){//全部失败
                        if($item['is_variation'] == 1){
                            $result_mark = LazadaProductAddVariation::model()->markVariationStatusByFeedID($item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_FAILURE,$item['message']);
                        } else {
                            $result_mark = LazadaProductAdd::model()->markStatusByFeedID($item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_FAILURE,$item['message']);
                        }
                    }else{
                        //根据sku拉取在线广告
                        if($item['is_variation'] == 1){
                            $records = LazadaProductAddVariation::model()->getVariationRecordByFeedID($item['feed_id']);
                        } else {
                            $records = LazadaProductAdd::model()->getRecordByFeedID($item['feed_id']);
                        }
                        $skus = $recordSku = array();
                        foreach($records as $row){
                            $skus[] = $row['product_id'];
                            $recordSku[$row['product_id']] = $row['product_id'];//担心存在键名之后,json格式交互会报错，所以另外记录一个数组
                        }
                        $accountInfo = LazadaAccount::getAccountInfoById($item['account_id']);
                        $apiAccountID = $accountInfo['account_id'];

                        if (!empty($skus)) {
                            $model   = new LazadaProductDownload();
                            $isOk    = $model->setAccountAutoID($item['account_id'])
                                            ->setAccountID($apiAccountID)
                                             ->setSiteID($item['site_id'])
                                             ->setProductState(GetProductsRequest::PRODUCT_STATUS_ALL)
                                             ->setSellerSkuList( $skus )
                                             ->startDownloadProducts();
                        }

                        $listings = LazadaProduct::model()->getOnlineListingBySku($skus,$apiAccountID, $item['site_id']);
                        $successSku = $failureSku = array();
                        foreach($listings as $listing){
                            if( $listing['main_image']!='' ){
                                $successSku[$listing['seller_sku']] = $listing['seller_sku'];
                                unset($recordSku[$listing['seller_sku']]);
                            }
                        }
                        $failureSku = $recordSku;
                        //标记成功失败记录
                        if (!empty($successSku)){
                            if($item['is_variation'] == 1){
                                $result_mark = LazadaProductAddVariation::model()->markStatusBySkusAndFeed($successSku, $item['feed_id'], LazadaProductAdd::UPLOAD_STATUS_SUCCESS);
                            } else {
                                $result_mark = LazadaProductAdd::model()->markStatusBySkus($successSku, $item['account_id'], $item['site_id'], LazadaProductAdd::UPLOAD_STATUS_SUCCESS);
                            }
                        }
                        if (!empty($failureSku)){
                            if($item['is_variation'] == 1){
                                $result_mark = LazadaProductAddVariation::model()->markStatusBySkusAndFeed($failureSku, $item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_FAILURE, $item['message']);
                            } else {
                                $result_mark = LazadaProductAdd::model()->markStatusBySkus($failureSku, $item['account_id'], $item['site_id'], LazadaProductAdd::UPLOAD_STATUS_FAILURE, $item['message']);
                            }                        
                        }
                       
                    }
                    break;
            }

            if( $result_mark ){
                $this->dbConnection->createCommand()->update(self::tableName(), array('marked' => self::IS_MARKED), 'feed_id = "'.$item['feed_id'].'"');
            }
        }
    }
    
    /**
     * @desc 查出Error的feed并将任务标记状态
     */
    public function comleteErrorStatus(){
        $list = $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('status = "'.self::STATUS_ERROR.'"')
                ->andWhere('marked != '.self::IS_MARKED)
                ->andWhere("type = 'api'")
                ->limit(25)
                ->queryAll();
        foreach($list as $item){
            $result_mark = '';
            if($item['message'] == ''){
                $item['message'] = 'Error';
            }
            switch ($item['action']){
                case self::ACTION_PRODUCT_CREATE:
                    if($item['is_variation'] == 1){
                        $result_mark = LazadaProductAddVariation::model()->markVariationStatusByFeedID($item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_FAILURE,$item['message']);
                    } else {
                        $result_mark = LazadaProductAdd::model()->markStatusByFeedID($item['feed_id'],LazadaProductAdd::UPLOAD_STATUS_FAILURE,$item['message']);
                    }
                    break;
            }
            
            if( $result_mark ){
                $this->dbConnection->createCommand()->update(self::tableName(), array('marked' => self::IS_MARKED), 'feed_id = "'.$item['feed_id'].'"');
            }
        }
    }
    
    /**
     * @desc 添加记录
     * @param array $param
     */
    public function addRecord($param){
        return $this->dbConnection->createCommand()->insert(self::tableName(), $param);
    }
}