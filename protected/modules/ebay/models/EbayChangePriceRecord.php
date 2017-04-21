<?php

/**
 * @desc Ebay调价记录
 * @author qzz
 * @since 2017-03-29
 */
class EbayChangePriceRecord extends EbayModel
{

    public static $accountPairs = array();
    public $errorMessage = null;
    public $seller_name = null;
    public $condition_type = null;
    public $visiupload = null;

    const TYPE_COST_ADD = 1;//成本增加
    const TYPE_COST_CUT = 2;//成本减少
    const TYPE_WEIGHT_ADD = 3;//毛重增加
    const TYPE_WEIGHT_CUT = 4;//毛重减少

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName()
    {
        return 'ueb_ebay_change_price_record';
    }

    /**
     * @desc 设置报错信息
     * @param string $message
     */
    public function setErrorMessage($message){
        $this->errorMessage = $message;
    }

    /**
     * @desc 获取报错信息
     */
    public function getErrorMessage(){
        return $this->errorMessage;
    }
    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where
     * @param  string $order
     * @return array
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * @desc 添加数据
     * @param unknown $data
     * @return string|boolean
     */
    public function saveData($data){
        if (empty($data)) return false;
        $res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
        if($res)
            return $this->getDbConnection()->getLastInsertID();
        return false;
    }

    /**
     * @desc 更新
     * @param unknown $data
     * @param unknown $id
     * @return Ambigous <number, boolean>
     */
    public function updateDataByID($data, $id)
    {
        if (!is_array($id)) $id = array($id);
        return $this->getDbConnection()
            ->createCommand()
            ->update($this->tableName(), $data, "id in(" . implode(",", $id) . ")");
    }

    /**
     * @desc  检测当天是否已经运行了
     * @param unknown $sellerSku
     * @param unknown $accountID
     * @param number $siteID
     * @return boolean
     */
    public function checkHadRunToday($sellerSku, $accountID, $siteID = 0, $productID = NULL)
    {
        $todayStart = date("Y-m-d 00:00:00");
        $todayEnd = date("Y-m-d 23:59:59");
        $command = $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->select('id,status')
            ->where("sku_online=:sku_online AND account_id=:account_id AND site_id=:site_id",array(':sku_online' => $sellerSku, ':account_id' => $accountID, ':site_id' => $siteID))
            ->andWhere("create_time>=:begin AND create_time<=:end", array(':begin' => $todayStart, ':end' => $todayEnd));
        if ($productID != NULL) {
            $command->andWhere("product_id=:product_id", array(":product_id" => $productID));
        }
        $res = $command->queryRow();
        if ($res)
            return true;
        else
            return false;
    }

    /**
     *
     * @desc 判断是否达到调价条件
     * @param type int 1成本 2毛重
     * @param newField int 新值
     * @param lastField int 上一次值
     * @return int 条件类型
     */
    public function changePriceCondition($type,$newField,$lastField)
    {
        /*
         *  	加权成本变高
            1）原来最后（上一次）加权平均成本(0, 20]RMB成本涨幅比例高于20%
            2）(20, 50]成本涨幅比例超过15%
            3）(50, 100]成本涨幅比例超过10%
            4）(100以上 成本涨幅超过8%
            	产品毛重增加
            产品毛重增加大于等于30g
            	加权成本下降
            1）原来最后（上一次）加权平均成本(0, 20]RMB成本跌幅比例大于20%
            2）(20, 50]成本跌幅比例超过15%
            3）(50, 100]成本跌幅比例超过10%
            4）100以上成本跌幅超过8%
            	产品毛重减少
            产品毛重减少的重量大于等于60g
         */
        $conditionType = 0;
        if($type==1){//成本类型

            $poorCostRate = abs($newField-$lastField)/$lastField * 100;
            if($newField>=$lastField){
                if($lastField>0 && $lastField<=20 && $poorCostRate>20){
                    $conditionType = self::TYPE_COST_ADD;
                }elseif($lastField>20 && $lastField<=50 && $poorCostRate>15){
                    $conditionType = self::TYPE_COST_ADD;
                }elseif($lastField>50 && $lastField<=100 && $poorCostRate>10){
                    $conditionType = self::TYPE_COST_ADD;
                }elseif($lastField>100 && $poorCostRate>8){
                    $conditionType = self::TYPE_COST_ADD;
                }
            }else{
                if($lastField>0 && $lastField<=20 && $poorCostRate>20){
                    $conditionType = self::TYPE_COST_CUT;
                }elseif($lastField>20 && $lastField<=50 && $poorCostRate>15){
                    $conditionType = self::TYPE_COST_CUT;
                }elseif($lastField>50 && $lastField<=100 && $poorCostRate>10){
                    $conditionType = self::TYPE_COST_CUT;
                }elseif($lastField>100 && $poorCostRate>8){
                    $conditionType = self::TYPE_COST_CUT;
                }
            }

        }else if($type==2){//毛重类型
            $weight = abs($newField-$lastField);
            if($newField>=$lastField){
                if($weight>=30){
                    $conditionType = self::TYPE_WEIGHT_ADD;
                }
            }else{
                if($weight>=60){
                    $conditionType = self::TYPE_WEIGHT_CUT;
                }
            }
        }
        return $conditionType;
    }

    /**
     *
     * @desc 计算价格，判断是否达到改价条件
     * @param array skuInfo sku信息
     * @return array
     */
    public function calculatePrice($skuInfo){

        $sku = $skuInfo['sku'];
        $siteID = $skuInfo['site_id'];
        $accountID = $skuInfo['account_id'];
        $oldPrice = $skuInfo['old_price'];
        $currency = $skuInfo['currency'];
        $conditionType = $skuInfo['condition_type'];
        //$categoryID = $skuInfo['category_id'];
        $itemID = $skuInfo['item_id'];

        //获取运费
        $shipCost = EbayProductShipping::model()->getMiniShippingPriceByItemID($itemID);
        //分类名称
        $categoryNames = explode(":", $skuInfo['category_name']);
        $categoryName = array_pop($categoryNames);
        $ebaySalePriceModel = new EbayProductSalePriceConfig();
        //获取利润率
        $oldProfitInfo = $ebaySalePriceModel->getProfitInfo($oldPrice, $sku, $currency, $siteID, $accountID, $categoryName, $shipCost);
        $data = array();
        if(!$oldProfitInfo || !$oldProfitInfo['profit_rate']){
            //重新计算失败
        }

        if($oldProfitInfo){
            $oldProfitRate = $oldProfitInfo['profit_rate']*0.01;
            if($oldProfitRate<0.2 && in_array($conditionType,array(1,3))){//类型1，3
                $newProfitRate = 0.13;
                $newPrice = $ebaySalePriceModel->getSalePriceNew($sku, $currency, $siteID, $accountID, $categoryName,$newProfitRate);
                if($newPrice){
                    $data = array(
                        'old_price'=>$oldPrice,
                        'old_profit_rate'=>$oldProfitRate,
                        'new_price'=>$newPrice['salePrice'],
                        'new_profit_rate'=>$newProfitRate,
                    );
                }
            }

            if($oldProfitRate>0.25 && in_array($conditionType,array(2,4))){//类型2，4
                $newProfitRate = 0.17;
                $newPrice = $ebaySalePriceModel->getSalePriceNew($sku, $currency, $siteID, $accountID, $categoryName,$newProfitRate);
                if($newPrice){
                    $data = array(
                        'old_price'=>$oldPrice,
                        'old_profit_rate'=>$oldProfitRate,
                        'new_price'=>$newPrice['salePrice'],
                        'new_profit_rate'=>$newProfitRate,
                    );
                }
            }
        }
        return $data;
    }

    /**
     * @desc 修改价格
     * @param array skuInfo sku信息
     * @return array
     */
    public function changePrice($skuInfo){

        $reviseInventoryStatusRequest = new ReviseInventoryStatusRequest();
        $reviseInventoryStatusRequest->setAccount($skuInfo['account_id']);
        $reviseInventoryStatusRequest->setSku($skuInfo['sku_online']);
        $reviseInventoryStatusRequest->setItemID($skuInfo['item_id']);
        $reviseInventoryStatusRequest->setStartPrice($skuInfo['new_price']);
        $reviseInventoryStatusRequest->push();

        $response = null;
        $response = $reviseInventoryStatusRequest->setRequest()->sendRequest()->getResponse();
        $errorMsg = $reviseInventoryStatusRequest->getErrorMsg();
        $reviseInventoryStatusRequest->clean();

        if(isset($response->Fees) && $response->Fees){
            $feedItemIDs = array();
            if(!isset($response->Fees[0])){
                $feedItemIDs[] = $response->Fees->ItemID;
            }else{//返回多个
                foreach ($response->Fees as $feed){
                    $feedItemIDs[] = $feed->ItemID;
                }
            }
            if(in_array($skuInfo['item_id'], $feedItemIDs)){
                $updateStatus = 1;
            }else{
                $updateStatus = 2;
                $this->setErrorMessage($errorMsg);
            }
        }else{
            $updateStatus = 2;
            $this->setErrorMessage($errorMsg);
        }
        /*//测试
        $updateStatus = rand(1,2);
        if($updateStatus==2){
            $errorMsg = 'test fail';
            $this->setErrorMessage($errorMsg);
        }*/
        return $updateStatus;
    }

    /**
     * @desc 修改价格并更新日志
     * @param array skuInfo sku信息
     * @return array
     */
    public function uploadPrice($id){

        try{
            $field = 'sku,account_id,sku_online,item_id,new_price,create_time,run_count';
            $where = 'id ='.$id;
            $skuInfo = $this->getOneByCondition($field,$where);
            //大于三天禁止操作
            if(time()-strtotime($skuInfo['create_time'])>=3*86400){
                throw new Exception($skuInfo['sku'].": 超过三天不能再次上传了");
            }

            //调用调价接口
            $result = $this->changePrice($skuInfo);
            $errorMsg = $this->getErrorMessage();

            //更新日志
            $logData = array(
                'status'			=>	$result,
                'message'			=>	is_null($errorMsg) ? 'success' : $errorMsg,
                'update_user_id'	=>  (int)Yii::app()->user->id,
                'last_response_time'=>	date("Y-m-d H:i:s"),
                'run_count'			=>	isset($skuInfo['run_count']) ? ++$skuInfo['run_count'] : 1
            );
            $this->updateDataByID($logData,$id);
            if($errorMsg){
                throw new Exception($skuInfo['sku'].":".$errorMsg);
            }
        }catch (Exception $e){
            $this->setErrorMessage($e->getMessage());
            return false;
        }
        return true;


    }

    // =========== start search ==================

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array();
    }

    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('system', 'No.'),
            'sku' => 'SKU',
            'seller_sku' => '线上SKU',
            'main_sku' => '主SKU',
            'item_id' => '产品ID',
            'account_id' => '账号',
            'site_id' => '站点',
            'account_name' => '账号名称',
            'type' => '类型',
            'seller_user_id' => '销售人员',
            'seller_name' => '销售人员',
            'old_price' => '标准售价',
            'old_profit_rate' => '标准利润率',
            'new_price' => '新售价',
            'new_profit_rate' => '新利润率',
            'status' => '处理状态',
            'msg' => '提示',
            'deal_date' => '监控日期',
            'last_response_time' => '处理时间',
            'update_user_id' => '处理人',
            'condition_type' => '触发条件',

        );
    }

    public function getStatusOptions($status = null)
    {
        //@todo 后续语言处理
        $statusOptions = array(
            1 => '成功',
            2 => '失败'
        );
        if ($status !== null)
            return isset($statusOptions[$status]) ? $statusOptions[$status] : '';
        return $statusOptions;
    }

    public function getTypeOptions($type = null)
    {
        $typeOptions = array(
            self::TYPE_COST_ADD => '成本增加',
            self::TYPE_COST_CUT => '成本减少',
            self::TYPE_WEIGHT_ADD => '毛重增加',
            self::TYPE_WEIGHT_CUT => '毛重减少',
        );
        if ($type !== null)
            return isset($typeOptions[$type]) ? $typeOptions[$type] : '';
        return $typeOptions;
    }

    public function addition($datas)
    {
        if (empty($datas)) return $datas;
        $siteList = EbaySite::getSiteList();
        $sellerUserList = User::model()->getPairs();
        foreach ($datas as &$data) {
            $data['account_id'] = self::$accountPairs[$data['account_id']];
            $data['site_id'] = isset($siteList[$data['site_id']]) ? $siteList[$data['site_id']] : '';

            $data['seller_name'] =  $sellerUserList && isset($sellerUserList[$data['seller_user_id']]) ? $sellerUserList[$data['seller_user_id']] : '';
            $data['update_user_id'] =  $sellerUserList && isset($sellerUserList[$data['update_user_id']]) ? $sellerUserList[$data['update_user_id']] : '';


            $data['visiupload'] = $data['status'] == 2 ? 1:0;
            $data['status'] = $this->getStatusOptions($data['status'])."<br>".$data['message'];
            if($data['type']==1 || $data['type']==2){
                $data['type'] = $this->getTypeOptions($data['type'])."<br>上次：".$data['last_product_cost']."<br>最新：".$data['new_product_cost'];
            }elseif($data['type']==3 || $data['type']==4){
                $data['type'] = $this->getTypeOptions($data['type'])."<br>上次：".$data['last_product_weight']."<br>最新：".$data['new_product_weight'];
            }

            $data['item_id'] = "<a href='http://www.ebay.com/itm/" . $data['item_id'] . "' target='__blank'>" . $data['item_id'] . "</a>";


        }
        return $datas;
    }

    /**
     * get search info
     */
    public function search()
    {
        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 'id',
        );
        $dataProvider = parent::search(get_class($this), $sort);
        $data = $this->addition($dataProvider->data);
        $dataProvider->setData($data);
        return $dataProvider;
    }

    /**
     * filter search options
     * @return type
     */
    public function filterOptions()
    {
        $type = Yii::app()->request->getParam('type');
        $status = Yii::app()->request->getParam('status');
        $siteID = Yii::app()->request->getParam('site_id');
        $result = array(
            array(
                'name' => 'sku',
                'type' => 'text',
                'search' => 'LIKE',
                'htmlOption' => array(
                    'size' => '22',
                )
            ),
            array(
                'name' => 'item_id',
                'type' => 'text',
                'search' => '=',
                'htmlOption' => array(
                    'size' => '22',
                )
            ),
            array(
                'name' => 'account_id',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getAccountList()
            ),
            array(
                'name' => 'site_id',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => EbaySite::getSiteList(),
                'value' => $siteID
            ),
            array(
                'name' => 'status',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getStatusOptions(),
                'value' => $status
            ),

            array(
                'name' => 'type',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getTypeOptions(),
                'value' => $type
            ),
        );
        return $result;
    }

    /**
     * @desc  获取公司账号
     */
    public function getAccountList()
    {
        if (self::$accountPairs == null)
            self::$accountPairs = self::model('EbayAccount')->getIdNamePairs();
        return self::$accountPairs;
    }

    // =========== end search ==================
}