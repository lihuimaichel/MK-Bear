<?php
/**
 * @desc Paytm产品列表拉取等等
 * @author AjunLongLive!
 * @since 2017-03-02
 */
class PaytmProductMain extends PaytmModel {
	
    //用于子sku的显示
    public $pay_money_type = 'INR';
    
    //用于子sku的显示
    public $detail = null;
    
    //用于批量修改之会员账户id获取
    public $batchAccount = null;
    
    //用于批量修改之子sku状态
    public $setting = null;
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var string 异常信息*/
    public $exception = null;
    
    /** @var int 日志编号*/
    public $_logID = 0;
    
    /** @var int 判断按照limit分次循环采集是否已经开始，默认最开始只采集limit限制的数量，这个是从最开始的产品id开始采集*/
    /** @var int 后面的则要从最开的采集返回的最后大的产品id往后再采集limit个产品，默认直到返回为0了，停止采集这个商户的产品*/
    /** @var int 主要解决一次采集过多，访问时间过长的问题，从国外来的网速很慢*/
    public $_hasStartDownloadListing = false;
    
    /* @var string 默认币种 */
    const DEFAULT_CURRENCY             = 'INR';

    const GET_ORDERNO_ERRNO            = 1000;//获取订单号异常编号
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_paytm_product';
    }
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
        $this->_accountID = $accountID;
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
     * @desc 更新单个或者多个产品的数据
     * 
     */
    //public function updateProducts($productID,$statusType){
    public function updateProducts($productArray){
        //是否开启模拟调试
        //$_REQUEST['debug'] = 1;
        $nowTime = date('Y-m-d H:i:s');
        $return = array('status'=>'success','msg'=>'');
        $statusArray = array('offline' => 'inactive','online' => 'active');
        $statusUpdateArray = array('offline' => '-1','online' => '1');
        if (isset($productArray) && !empty($productArray) && isset($productArray[0]['product_id'])){
            //按平台订单号+账号判断是否存在
            $accountSql = $this->getDbConnection()->createCommand()
                                                    ->from($this->tableName())
                                                    ->select("account_id,sku")
                                                    ->where("product_id=:product_id_arr",array(":product_id_arr" => $productArray[0]['product_id']))
                                                    ->queryRow();
            if($accountSql){
                if (!empty($_REQUEST['debug'])){
                    print_r($accountSql);
                }                
                $accountID = $accountSql['account_id'];
                $request = new UpdatePayTmProductsRequest();
                $request->setAccount($accountID);
                foreach ($productArray as $product){
                    if (isset($product['statusType']) && !empty($product['statusType']))    $request->setStatus($statusArray[$product['statusType']]);
                    if (isset($product['product_id']) && !empty($product['product_id']))    $request->setProductId($product['product_id']);
                    if (isset($product['price']) && !empty($product['price']))              $request->setProductPrice($product['price']);
                    if (isset($product['qty']) && $product['qty'] >= 0)                     $request->setProductQty($product['qty']);
                    //$request->setProductSku($accountSql['sku']);
                    $request->addEachProductParamsToRequestArray();
                }
                
                $response = $request->setRequest()->sendRequest()->getResponse();
                if (!empty($_REQUEST['debug'])){
                    print_r($response);
                }
                if($request->getIfSuccess()){
                    if (!empty($response)) {
                        $responseJson = json_decode(json_encode($response),1);
                        if (!empty($_REQUEST['debug'])){
                            print_r($response);
                        }
                        foreach ($productArray as $product){
                            $productData = array();
                            $productData['update_time'] = $nowTime;
                            if (isset($product['price']) && !empty($product['price']))              $productData['price']     = $product['price'];
                            if (isset($product['qty']) && $product['qty'] >= 0)                     $productData['inventory'] = json_encode(array('qty' => $product['qty']));
                            if (isset($product['statusType']) && !empty($product['statusType']))    $productData['status']    = $statusUpdateArray[$product['statusType']];
                            
                            if (isset($productData['price']) || isset($productData['inventory']) || isset($productData['status'])){
                                $this->getDbConnection()->createCommand()
                                     ->update(
                                        PaytmProductChild::model()->tableName(),
                                        $productData,
                                        "product_id=:product_id_arr",
                                        array(':product_id_arr' => $product['product_id'])
                                );
                            }                                                        
                            
                            if (isset($productData['price']) || isset($productData['inventory']) || isset($productData['status'])){
                                $isOk = $this->getDbConnection()->createCommand()
                                                                ->update(
                                                                    $this->tableName(), 
                                                                    $productData, 
                                                                    "product_id=:product_id_arr", 
                                                                    array(':product_id_arr' => $product['product_id'])
                                                                  );
                                if (!$isOk){
                                    $return['status'] = 'failure';
                                    $return['msg'] = '数据更新失败，原因更新主数据库失败！失败产品id：' . $product['product_id'];
                                    return $return;
                                } else {
                                    $logArray = array();
                                    $logArray['do_action'] = '';
                                    $logArray['product_id'] = $product['product_id'];
                                    $logArray['modify_user'] = Yii::app()->user->id ? Yii::app()->user->id : User::admin();
                                    $logArray['modify_time'] = $nowTime;
                                    isset($productData['status'])       && $logArray['do_action'] = '修改产品状态  ' . $product['statusType'];
                                    isset($productData['price'])        && $logArray['do_action'] = '修改产品价格  ' . $product['price'];
                                    isset($productData['inventory'])    && $logArray['do_action'] = '修改产品库存  ' . $product['qty'];
                                    $logArray['do_action'] .= ' Api传入：' . json_encode($request->getRequest());
                                    $logArray['do_action'] .= ' Api返回：' . json_encode($response);
                                    
                                    $isOkLog = $this->getDbConnection()->createCommand()
                                                                       ->insert(
                                                                            PaytmProductLog::model()->tableName(),
                                                                            $logArray
                                                                         );
                                    if (!$isOkLog){
                                        $return['status'] = 'failure';
                                        $return['msg'] = '数据更新成功，但是产品更新日志插入失败！';
                                        return $return;
                                    }
                                }
                                
                            } else {
                                $return['status'] = 'failure';
                                $return['msg'] = '数据更新失败，原因更新主数据库失败！无输入数据';
                                return $return;
                            }

                        }                        
                        
                    } else {
                        $return['status'] = 'failure';
                        $return['msg'] = '数据返回异常' . "<br />" . json_encode($request->getRequest()) ."<br />" . json_encode($response);
                    }
                } else {  //抓取失败                    
                    $return['status'] = 'failure';    
                    $return['msg'] = $request->getErrorMsg() . "<br />" . json_encode($response) . "<br />" . json_encode($request->getRequest());
                }
            } else {
                $return['status'] = 'failure';    
                $return['msg'] = '不存在这个产品id: ' . $productArray[0]['product_id'];
            } 
        } else {
            $return['status'] = 'failure';
            $return['msg'] = '必须传入数据，且传入的产品ID不能未空！';
        }

        return $return;
    }
    
    
    /**
     * @desc 根据条件获取订单
     * @param int $startTime
     * @param int $endTime
     * @param array $params
     */
    public function getProducts(){   
        $returnStatus = false;
        $request = new GetPayTmProductsRequest();
        $limit = trim(Yii::app()->request->getParam('limit',''));
        $from = trim(Yii::app()->request->getParam('from',''));
        $skus = trim(Yii::app()->request->getParam('skus',''));
        $setLimit =  !empty($limit) ? $limit : 66;
        $request->setProductLimit($setLimit);
        !empty($skus) && $request->setProductSkus($skus);
        //设置每次采集的采集量
        PaytmproductController::settingSession('market', array("paytm_listing_account_{$from}{$this->_accountID}_limit" => $setLimit));
        $request->setAccount($this->_accountID);
        $canLoop = true;
        $nums = 0;
        $whileNums = 1;
        $loop = false;
        while($canLoop){
            PaytmproductController::settingSession('market', array("paytm_listing_account_{$from}{$this->_accountID}_while" => $whileNums));
            //要延时6秒，调试中有出现过504的错误
            sleep(6);
            if ($loop){
                //print_r('_loop: start');
                if ($this->_hasStartDownloadListing){
                    //print_r('$request->getMerchantID:'.$request->getMerchantID());
                    $productInfo = $this->getDbConnection()->createCommand()
                                                            ->from($this->tableName())
                                                            ->select("product_id")
                                                            ->where("merchant_id=:merchantID",array(":merchantID" => $request->getMerchantID()))
                                                            ->order('update_time desc,product_id desc')
                                                            ->queryRow();
            
                    if(!empty($_REQUEST['debug'])){
                        print_r($productInfo);
                    }
                    if($productInfo){
                        $request->setProductAfterId($productInfo['product_id']);
                    }
                }
            }
            $sendTime = time();
            $response = $request->setRequest()->sendRequest()->getResponse();
            //$path = 'paytm/getProducts/'.date("Ymd").'/'.$this->_accountID.'/'.date("His");  // for test
            //MHelper::writefilelog($path.'/response_' . $this->_accountID . '.log', print_r($response,true) . "\r\n");  // for test
            $responseTime = time();
            $betweenTime = $responseTime - $sendTime;
            PaytmproductController::settingSession('market', array("paytm_listing_account_{$from}{$this->_accountID}_whileTime" => $betweenTime));
            if($request->getIfSuccess()){
                $errorMsg = '';
                if (!empty($response) && count($response) > 0) {
                    $response = json_decode(json_encode($response),1);
                    foreach($response as $product) {//循环订单信息
                        try {
                            if(!empty($_REQUEST['debug'])){
                                print_r($product);
                            }
                            if (isset($product['id'])) {
                                $this->savePaytmProductInfo($product);
                            } else {
                                $errorMsg .= 'JSON数据格式返回错误:' . $response . "<br />";
                            }
                        } catch (Exception $e){
                            $errorMsg .= $e->getMessage()."<br/>";
                        }
                        PaytmproductController::settingSession('market', array("paytm_listing_account_{$from}{$this->_accountID}_error" => $errorMsg));
                    }
                    $nums += $setLimit;
                    PaytmproductController::settingSession('market', array("paytm_listing_account_{$from}{$this->_accountID}_nums" => $nums));
                }
                
                $this->setExceptionMessage($errorMsg);
                if ($this->_hasStartDownloadListing == false){
                    $this->_hasStartDownloadListing = true;
                }
                //如果返回还有数据，则再次循环采集
                if(count($response) == 0){
                    $this->_hasStartDownloadListing = false;
                    $returnStatus = true;
                    $canLoop = false;
                    break;
                    //$this->getProducts(true);
                } else {
                    $loop = true;
                }
            
            } else {  //抓取失败
                PaytmproductController::settingSession('market', array("paytm_listing_account_{$from}{$this->_accountID}_error" => '拉取失败: ' . $request->getErrorMsg()));
                $this->setExceptionMessage($request->getErrorMsg());
                $returnStatus = false;
                $canLoop = false;
                break;
            } 
            $whileNums++;
            //调试只拉取一次,仅开发调试用
            //$canLoop = false;
        }        
        return $returnStatus;
    }    

    /**
     * @desc 保存采集的产品数据
     * @param object $product
     * @return boolean
     */
    public function savePaytmProductInfo($product){
        $nowTime = date('Y-m-d H:i:s');
        $from = trim(Yii::app()->request->getParam('from',''));
    	$productData = array(
            'account_id'                        => isset($this->_accountID) ? $this->_accountID : null,
            'product_id'                        => isset($product['id']) ? $product['id'] : null,
            'attributes'                        => isset($product['attributes']) ? json_encode($product['attributes']) : null,          
            'merchant_id'                       => isset($product['merchant_id']) ? $product['merchant_id'] : null,
            'visibility'                        => isset($product['visibility']) ? $product['visibility'] : null,
            'is_in_stock'                       => isset($product['is_in_stock']) ? $product['is_in_stock'] : null,
            'merchant_name'                     => isset($product['merchant_name']) ? $product['merchant_name'].'' : null,
            'parent_id'                         => isset($product['parent_id']) ? $product['parent_id'] : null,
            'url_key'                           => isset($product['url_key']) ? $product['url_key'].'' : null,
            'sku'                               => isset($product['sku']) ? $product['sku'] : null,
            'vertical_id'                       => isset($product['vertical_id']) ? $product['vertical_id'] : null,
            'product_type'                      => isset($product['product_type']) ? $product['product_type'] : null,
            'name'                              => isset($product['name']) ? $product['name'].'' : null,
            'price'                             => isset($product['price']) ? $product['price'] : null,
            'mrp'                               => isset($product['mrp']) ? $product['mrp'] : null,
            'currency_id'                       => isset($product['currency_id']) ? $product['currency_id'] : null,
            'status'                            => isset($product['status']) ? ($product['status'] == 0 ? -1 : $product['status']) : null,
            'paytm_sku'                         => isset($product['paytm_sku']) ? $product['paytm_sku'].'' : null,
            'info'                              => isset($product['info']) ? json_encode($product['info']) : null,
            'category_id'                       => isset($product['category_id']) ? $product['category_id'] : null,
            'custom_int_5'                      => isset($product['custom_int_5']) ? $product['custom_int_5'] : null,
            'custom_text_2'                     => isset($product['custom_text_2']) ? $product['custom_text_2'].'' : null,
            'child_site_ids'                    => isset($product['child_site_ids']) ? json_encode($product['child_site_ids']) : null,
            'short_description'                 => isset($product['short_description']) ? json_encode($product['short_description']) : null,
            'pay_type_supported'                => isset($product['pay_type_supported']) ? json_encode($product['pay_type_supported']) : null,
            'pay_type_supported_meta'           => isset($product['pay_type_supported_meta']) ? json_encode($product['pay_type_supported_meta']) : null,
            'type'                              => isset($product['type']) ? $product['type'].'' : null,
            'product_title_template'            => isset($product['product_title_template']) ? $product['product_title_template'].'' : null,
            'attributes_dim'                    => isset($product['attributes_dim']) ? json_encode($product['attributes_dim']) : null,
            'filter_attributes'                 => isset($product['filter_attributes']) ? $product['filter_attributes'].'' : null,
            'seller_variant'                    => isset($product['seller_variant']) ? $product['seller_variant'] : null,
            'vertical_attributes'               => isset($product['vertical_attributes']) ? $product['vertical_attributes'].'' : null,
            'allowed_fields'                    => isset($product['allowed_fields']) ? $product['allowed_fields'].'' : null,
            'vertical_label'                    => isset($product['vertical_label']) ? $product['vertical_label'].'' : null,
            'attribute_config'                  => isset($product['attribute_config']) ? $product['attribute_config'].'' : null,
            'variable_price'                    => isset($product['variable_price']) ? $product['variable_price'] : null,
            'attributes_dim_values'             => isset($product['attributes_dim_values']) ? json_encode($product['attributes_dim_values']) : null,
            'return_policy_text'                => isset($product['return_policy_text']) ? $product['return_policy_text'] : null,
            'tax_data'                          => isset($product['tax_data']) ? json_encode($product['tax_data']) : null,
            'warehouses'                        => isset($product['warehouses']) ? json_encode($product['warehouses']) : null,
            'inventory'                         => isset($product['inventory']) ? json_encode($product['inventory']) : null,
            'config_extension'                  => isset($product['config_extension']) ? $product['config_extension'] : null,
            //'last_modify_user'                  => '0',
            //'last_do_action'                    => '自动拉取程序更新或者插入数据',
            //'last_modify_time'                  => $nowTime.'',
            'update_time'                       => $nowTime.'',


    	);
    	//按平台订单号+账号判断是否存在
    	$pkId = $this->getDbConnection()->createCommand()
                        ->from($this->tableName())
    					->select("id")
    					->where("product_id=:product_id_arr", 
                            array(":product_id_arr"=>$product['id']))
    					->queryScalar();
        if($pkId){            
            $isOk = $this->getDbConnection()->createCommand()
                        ->update($this->tableName(), $productData, "id=:id", array(':id'=>$pkId));
            if(!empty($_REQUEST['debug'])){
                print_r($isOk);
            }
        } else {
            $productData['creat_time'] = $nowTime.'';
            $isOk = $this->getDbConnection()->createCommand()
                        ->insert($this->tableName(), $productData);
            if($isOk) {
                $pkId = $this->getDbConnection()->getLastInsertID();
            }                        
        }
        if(!$isOk) {
            //throw new Exception("PayTm产品拉取数据错误");
            PaytmproductController::settingSession('market', array("paytm_listing_account_{$from}{$this->_accountID}_error_sql_main" => '主表数据插入或者更新失败,产品ID：' . $product['id']));
        } else {
            //更新仓库分表数据
            if (!empty($product['warehouses'])){
                $warehousesArray = $product['warehouses'];
                foreach ($warehousesArray as $warehouse){
                    $warehousesSqlArray = array();
                    $warehousesSqlArray = $warehouse;
                    $warehousesSqlArray['product_id'] = isset($product['id']) ? $product['id'] : null;
                    $warehousesSqlArray['sku']        = isset($product['sku']) ? $product['sku'] : null;
                    $warehousesSqlArray['paytm_sku']  = isset($product['paytm_sku']) ? $product['paytm_sku'].'' : null;
                    //$this->print_r($warehousesSqlArray);
                    //break;
                    $return = PaytmProductWarehouses::model()->updateProductWarehouses($warehousesSqlArray);
                    if ($return['status'] == 'failure'){
                        //echo $return['msg'] . $product['product_id'];
                        PaytmproductController::settingSession('market', array("paytm_listing_account_{$from}{$this->_accountID}_error_sql_warehouses" => "仓库数据插入或者更新失败,产品ID：{$product['id']},{$return['msg']}"));
                    }
                }
            }
            //更新子SKU分表数据
            if (!empty($product['parent_id']) && !empty($product['id'])){
                $childArray = array();
                $childArray['product_id']   = isset($product['id']) ? $product['id'] : null;
                $childArray['parent_id']    = isset($product['parent_id']) ? $product['parent_id'] : null;
                $childArray['sku']          = isset($product['sku']) ? $product['sku'] : null;
                $childArray['name']         = isset($product['name']) ? $product['name'].'' : null;
                $childArray['price']        = isset($product['price']) ? $product['price'] : null;
                $childArray['mrp']          = isset($product['mrp']) ? $product['mrp'] : null;
                $childArray['paytm_sku']    = isset($product['paytm_sku']) ? $product['paytm_sku'].'' : null;
                $childArray['warehouses']   = isset($product['warehouses']) ? json_encode($product['warehouses']) : null;
                $childArray['inventory']    = isset($product['inventory']) ? json_encode($product['inventory']) : null;
                $childArray['status']       = isset($product['status']) ? ($product['status'] == 0 ? -1 : $product['status']) : null;
                $return = PaytmProductChild::model()->updateChildSku($childArray);
                if ($return['status'] == 'failure'){
                    //echo $return['msg'] . $product['product_id'];
                    PaytmproductController::settingSession('market', array("paytm_listing_account_{$from}{$this->_accountID}_error_sql_variation" => "子sku数据插入或者更新失败,产品ID：{$product['id']},{$return['msg']}"));
                }
            }
            
        }
        return $pkId;
    }

    /**
     * @desc 获取拉单开始时间
     */
    public function getTimeSince($accountID){
        $eventName = PaytmLog::EVENT_GETPRODUCT;
        $lastLog = PaytmLog::model()->getLastLogByCondition(array(
            'account_id'    => $accountID,
            'event'         => $eventName,
            'status'        => PaytmLog::STATUS_SUCCESS,
        ));
        $lastEventLog = array();
        if( !empty($lastLog) ){
            $lastEventLog = PaytmLog::model()->getEventLogByLogID($eventName, $lastLog['id']);
        }
        return (!empty($lastEventLog) && $lastEventLog['complete_time'] != "0000-00-00 00:00:00")
        ? date('Y-m-d\TH:i:s.000\Z',strtotime($lastEventLog['complete_time']) -15*60 - 3600*8 )
        : date('Y-m-d\TH:i:s.000\Z',time() - 86400 - 3600*8);
    }
    
    /**
     * @desc UTC时间格式转换
     * @param unknown $UTCTime
     * @return mixed
     */
    public function transferUTCTimeFormat($UTCTime){
        $UTCTime = strtoupper($UTCTime);
        $newUTCTime = str_replace("T", " ", $UTCTime);
        $newUTCTime = str_replace("Z", "", $UTCTime);
        return $newUTCTime;
    }
    
    /**
     * @desc 转换为北京时间
     * @param unknown $UTCTime
     * @return string
     */
    public function transferToLocal($UTCTime){
        return date("Y-m-d H:i:s", strtotime($UTCTime)+8*3600);
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }
    
    /**
     * addPaytmLog
     * @param int $accountID
     * @param int $status
     * @param string $message
     * @param string $eventName
     */
    public function addPaytmLog($accountID,$status,$message,$eventName=PaytmLog::EVENT_SYNC_ORDER) {
        $logModel = new PaytmLog();
        return $logModel->getDbConnection()->createCommand()->insert(
            $logModel->tableName(), array(
                'account_id'    => $accountID,
                'event'         => $eventName,
                'start_time'    => date('Y-m-d H:i:s'),
                'status'        => $status ,
                'message'       => $message,
                'response_time' => date('Y-m-d H:i:s'),
                'end_time'      => date('Y-m-d H:i:s'),
                'create_user_id'=> intval(Yii::app()->user->id),
            )
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
        $dataProvider = parent::search(get_class($this), $sort,'',$this->_setDbCriteria());
        $data = $this->addtions($dataProvider->data);
        $dataProvider->setData($data);
        return $dataProvider;
    }

    /**
     * 解析json，并获取特定的值，同时输出
     * 
     */
    public function displayJsonValue($jsonString,$jsonKey) {
        $array = json_decode($jsonString,1);
        echo $array[$jsonKey];
    }

    /**
     * 解析json，并获取特定的值，同时输出
     *
     */
    public function displaySettingHtml($productID,$otherArray,$type) {
        //modify stock
        $settingHtml = '';
        $type == 'return' && $between = "&nbsp;";
        $type == 'echo' && $between = "<br />";
        if (isset($otherArray['status'])){
            if ($otherArray['status'] == 1) $settingHtml .= "<input type='button' value='下架' onClick='modifyChildStatus({$productID},\"offline\")' style=\"cursor:pointer;\" />{$between}{$between}";
            if ($otherArray['status'] == -1) $settingHtml .= "<input type='button' value='上架' onClick='modifyChildStatus({$productID},\"online\")' style=\"cursor:pointer;\" />{$between}";
        }
        if ($type == 'return'){
            return $settingHtml;
        } else if ($type == 'echo'){
            echo $settingHtml;
        }        
    }    
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        //$event = Yii::app()->request->getParam('event');
        //$status = Yii::app()->request->getParam('status');
        //$account_id = Yii::app()->request->getParam('account_id');
        $result = array(
            array(
                'name'=>'account_id',
                'type'=>'dropDownList',
                'search'=>'=',
                'data'=>PaytmLog::model()->getAccountList()
            ),
            array(
                'name'=>'status',
                'type'=>'dropDownList',
                'search'=>'=',
                'data'=>array('-1' => ' 下架 ','1' => ' 上架 ')
            ),
            array(
                'name' => 'product_id',
                'type' => 'text',
                'search' => '=',
                //'alias'	=>	'v',
                'htmlOption' => array(
                    
                ),
            ),            
            array(
                'name' => 'sku',
                'type' => 'text',
                'search' => 'LIKE',
                //'alias'	=>	'v',
                'htmlOption' => array(
                    'size' => '22',
                ),
            ),   
            array(
                'name' => 'paytm_sku',
                'type' => 'text',
                'search' => 'LIKE',
                //'alias'	=>	'v',
                'htmlOption' => array(
                    'size' => '22',
                ),
            ),                        
        );
        return $result;
    }
    
    public function addtions($datas){
        if(empty($datas)) return $datas;
        $account_list = PaytmLog::model()->getAccountList();
        foreach ($datas as $key => &$data){
            //print_r($data);
            
            if (isset($data['account_id'])){
                $childAccountID = $data['account_id'];
                $datas[$key]->batchAccount = $data['account_id'];
                if(!isset($account_list[$data['account_id']])){
                    continue;
                }
                //账号名称
                $data['account_id'] = $account_list[$data['account_id']];                
            }
            //处理子sku的相关数据
            //print_r($data);
            if (isset($data['product_id']) && !empty($data['product_id'])){
                $datas[$key]->detail = array();
                $parentThisArray = array();
                $parentThisArray['product_id'] = $data['product_id'];
                $parentThisArray['inventory']  = $data['inventory'];
                $parentThisArray['price']      = $data['price'];
                $parentThisArray['status']     = $data['status'];
                $childSkuArr = PaytmProductChild::model()->getSkusDetailFromParams($data['product_id'],$parentThisArray);
                if ($childSkuArr['status'] == 'success'){
                    if (count($childSkuArr['data']) > 0){
                        foreach ($childSkuArr['data'] as $keyChild => $valChild){
                            //modify stock
                            $eachModifyStockText   = "<input type='text' style='width:28px;' id='stock_value_{$valChild['child_product_id']}' />&nbsp;";
                            $eachModifyStockButton = "<input type='button' value='保存' onClick='modifyChildStock({$valChild['child_product_id']})' />";
                            $childSkuArr['data'][$keyChild]['child_modify_stock'] = $eachModifyStockText . $eachModifyStockButton;
                            //modify price
                            $eachModifyPriceText   = "<input type='text' style='width:28px;' id='price_value_{$valChild['child_product_id']}' />&nbsp;";
                            $eachModifyPriceButton = "<input type='button' value='保存' onClick='modifyChildPrice({$valChild['child_product_id']})' />";
                            $childSkuArr['data'][$keyChild]['child_modify_price'] = $eachModifyPriceText . $eachModifyPriceButton;
                            //stock
                            $arrayTemp = json_decode($valChild['child_inventory'],1);
                            $childSkuArr['data'][$keyChild]['child_inventory'] = $arrayTemp['qty'];
                            $childSkuArr['data'][$keyChild]['child_product_id'] = $data['product_id'].','.$valChild['child_product_id'].','.$childAccountID;
                            //status
                            $childSkuArr['data'][$keyChild]['status'] = $valChild['child_status'] == 1 ? "<span style=\"color:green\" >上架</span>" : "<span style=\"color:red\" >下架</span>";
                            //setting
                            $childSkuArr['data'][$keyChild]['setting'] = $this->displaySettingHtml($valChild['child_product_id'],array("status" => $valChild['child_status']),'return');
                        }
                        $datas[$key]->detail = $childSkuArr['data'];
                        
                    }                    
                }
                //print_r($datas[$key]->detail);
            }
            //print_r($data);
        }
        return $datas;
    }
    
    /**
     * @desc 通过用户和父产品ID获取子sku的相关价格、库存、产品id等等数据
     * @return string
     */
    public function getSkusDetailFromParams($parentProductID,$parentArray = null){
        $return = array('status'=>'success');
        //按照父id去查询相关的子sku情况
        $SkusDetailFind = $this->getDbConnection()->createCommand()
                                        ->from($this->tableName())
                                        ->select("
                                            id as child_id,
                                            product_id as child_product_id,
                                            price as child_price,
                                            sku as child_sku,
                                            inventory as child_inventory,
                                            paytm_sku as child_paytm_sku,
                                            creat_time as child_creat_time,
                                            update_time as child_update_time
                                            ")
                                        ->where("parent_id=:parent_id_arr", array(":parent_id_arr" => $parentProductID))
                                        ->queryAll();
        if($SkusDetailFind){
            $return['data'] = $SkusDetailFind;
        } else {
            if (!is_null($parentArray) && isset($parentArray['product_id'])){
                $return['data'][0]['child_id'] = '';
                $return['data'][0]['child_product_id'] = $parentArray['product_id'];
                $return['data'][0]['child_price'] = $parentArray['price'];
                $return['data'][0]['child_sku'] = '';
                $return['data'][0]['child_inventory'] = $parentArray['inventory'];
                $return['data'][0]['child_paytm_sku'] = '';
                $return['data'][0]['child_creat_time'] = '';
                $return['data'][0]['child_update_time'] = '';
            } else {
                $return['status'] = 'failure';
            }                       
        }     
        return $return;
    }
    
    /**
     * @desc 设置数据结构
     * @return CDbCriteria
     */
    protected function _setDbCriteria() {
        $criteria = new CDbCriteria();
        $criteria->addCondition("parent_id IS NULL");
        //$criteria->addCondition("product_type = 1");
        return $criteria;
    }    
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'id'                                      =>    '序号',
            'account_id'                              =>    '账号名称',
            'product_id'                              =>    '产品ID',
            'account_name'		                      =>	'账号名称',
            'sku'                                     =>	'系统sku',
            'paytm_sku'		                          =>	'在线sku',
            'child_sku'                               =>	'系统子sku',
            'child_paytm_sku'		                  =>	'在线子sku',
            'child_id'		                          =>	'',
            'child_product_id'		                  =>	'',
            'child_price'		                      =>	'价格',
            'child_creat_time'		                  =>	'子sku创建时间',
            'child_update_time'		                  =>	'子sku更新时间',
            'child_modify_stock'		              =>	'修改库存',
            'child_modify_price'		              =>	'修改价格',
            'child_inventory'		                  =>	'库存',
            'child_status'		                      =>	'产品状态',
            'status'			                      =>	'产品状态',
            'setting'			                      =>	'设置',
            'name'		                              =>	'标题',
            'pay_money_type'			              =>	'货币',
            'creat_time'			                  =>	'创建时间',
            'update_time'			                  =>	'更新时间',
            'system_child_skus'			              =>	'系统子sku',
            'online_child_skus'			              =>	'在线子sku',
            'child_skus_price'			              =>	'子sku价格',
            'stock'			                          =>	'库存',
            'child_skus_creat_time'			          =>	'子sku创建时间',
            'child_skus_update_time'			      =>	'子sku更新时间',
        );
    }

}