<?php
/**
 * @desc Ebay产品运费表
 * @author hanxy
 * @since 2016-10-14
 */
class EbayProductShipping extends EbayModel{
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_shipping';
    }


    /**
     * @desc 保存数据
     * @param unknown $data
     * @return Ambigous <number, boolean>
     */
    public function saveData($data){
        return $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    }


    /**
     * @desc 更新数据
     */
    public function updateData($data, $conditions, $where){
        return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, $conditions, $where);
    }


    /**
     * 插入运费数据
     * @param int       $accountID   
     * @param unknown   $itemID   
     * @param object    $shippingDetails  调用产品接口获取的运费xml数据
     */
    public function insertShipping($accountID, $itemID, $shippingDetails){
        $this->getDbConnection()->createCommand()->delete($this->tableName(), "item_id='{$itemID}'");
        $times = date('Y-m-d H:i:s');
        if(isset($shippingDetails->ShippingServiceOptions)){
            foreach ($shippingDetails->ShippingServiceOptions as $shipping) {
                $dataArr = array(
                    'item_id'                           => $itemID,
                    'account_id'                        => $accountID,
                    'shipping_service'                  => (string)$shipping->ShippingService,
                    'shipping_service_cost'             => isset($shipping->ShippingServiceCost)?round((float)$shipping->ShippingServiceCost,2):0,
                    'shipping_service_additional_cost'  => isset($shipping->ShippingServiceAdditionalCost)?round((float)$shipping->ShippingServiceAdditionalCost,2):0,
                    'shipping_service_priority'         => (string)$shipping->ShippingServicePriority,
                    'expedited_service'                 => ($shipping->ExpeditedService == 'true')?1:0,
                    'shipping_time_min'                 => intval($shipping->ShippingTimeMin),
                    'shipping_time_max'                 => intval($shipping->ShippingTimeMax),
                    'free_shipping'                     => ($shipping->FreeShipping == 'true')?1:0,
                    'service_option'                    => 0,
                    'update_time'                       => $times,
                    'create_time'                       => $times,
                );
                $this->saveData($dataArr);
                unset($dataArr);
            }
        }

        if(isset($shippingDetails->InternationalShippingServiceOption)){
            foreach ($shippingDetails->InternationalShippingServiceOption as $interShipping) {
                $dataArr = array(
                    'item_id'                           => $itemID,
                    'account_id'                        => $accountID,
                    'shipping_service'                  => (string)$interShipping->ShippingService,
                    'shipping_service_cost'             => isset($interShipping->ShippingServiceCost)?round((float)$interShipping->ShippingServiceCost,2):0,
                    'shipping_service_additional_cost'  => isset($interShipping->ShippingServiceAdditionalCost)?round((float)$interShipping->ShippingServiceAdditionalCost,2):0,
                    'shipping_service_priority'         => (string)$interShipping->ShippingServicePriority,
                    'ship_toLocation'                   => (string)$interShipping->ShipToLocation,
                    'service_option'                    => 1,
                    'update_time'                       => $times,
                    'create_time'                       => $times,
                );
                $this->saveData($dataArr);
                unset($dataArr);
            }
        }

        return true;
    }


    /**
     * 根据条件查询数据
     * @param string $fields    要查询的字段
     * @param string $condition 查询的条件
     * @param string $param     参数
     * @param string $order     排序
     */
    public function getListByCondition($fields,$condition,$param = '',$order = ''){
        $cmd = $this->getDbConnection()->createCommand();
        $cmd->select($fields)
            ->from($this->tableName());
        if($param){
            $cmd->where($condition,$param);
        }else{
            $cmd->where($condition);
        }
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }


    /**
     * 根据item_id获取运费
     * @param string $where 查询的条件
     * @return array
     */
    public function getShippingPriceByWhere($where){
        $shippingPriceArr = array();
        $fields = 'item_id,shipping_service_cost,service_option';
        $shippingInfo = $this->getListByCondition($fields,$where);
        if($shippingInfo){
            foreach ($shippingInfo as $shippingValue) {
                //取出本地运费
                if($shippingValue['service_option'] == 0){
                    $shippingPriceArr[$shippingValue['item_id']][] = $shippingValue['shipping_service_cost'];
                }

                //取出国际运费
                if($shippingValue['service_option'] == 1 && $shippingValue['shipping_service_cost'] > 0){
                    $shippingPriceArr[$shippingValue['item_id']][] = $shippingValue['shipping_service_cost'];
                }
            }
        }

        return $shippingPriceArr;
    }


    /**
     * 根据运费信息，更新利润和利润率表
     * @param string $where     查询运费表的条件
     * @param string $logName   添加log的event名称
     */
    public function setProfitByShippingWhere($where, $logName = ''){
        $ebayProductModel           = new EbayProduct();
        $ebayProductVariationModel  = new EbayProductVariation();
        $ebayProductProfitModel     = new EbayProductProfit();
        $ebaySalePriceConfigModel   = new EbayProductSalePriceConfig();
        $ebayCategoryModel          = new EbayCategory();
        $ebayLog                    = new EbayLog();

        //插入或者更新数据
        $insertFields = "id,product_id,item_id,category_id,site_id,account_id,main_sku,sku,sku_online,current_price,shipping_price,profit,profit_rate,update_time,create_time";

        $fields = 'id,item_id,account_id,sku,category_id,site_id,shipping_price,shipping_price_currency,current_price_currency';

        if(!$where){
            throw new Exception('查询运费表条件不能为空');            
        }

        $id = 0;
        $shippingPriceArr = $this->getShippingPriceByWhere($where);
        foreach ($shippingPriceArr as $shippingKey => $shippingValue) {
            //数组去重
            $shippingPrice[$shippingKey] = array_unique($shippingValue);
            foreach ($shippingPrice as $itemKey => $itemValue) {
    
                //取出产品信息
                $ebayProductInfo = $ebayProductModel->getListByCondition($fields,'item_id ='.$itemKey);
                foreach ($ebayProductInfo as $key => $value) {
                    //获取类目名称
                    $categoryInfo = $ebayCategoryModel->getCategotyInfoByID($value['category_id'],$value['site_id']);
                    if(!isset($categoryInfo['category_name']) || empty($categoryInfo['category_name'])){
                        continue;
                    }
                    $categoryName = $categoryInfo['category_name'];

                    $conditions = "listing_id={$value['id']}";
                    $ebayProductVariationInfo = $ebayProductVariationModel->findAll($conditions);
                    foreach ($ebayProductVariationInfo as $k => $v) {
                        //根据条件判断利润表是否存在记录
                        $profitConditions = "item_id=:item_id AND sku_online=:sku_online";
                        $profitParam = array(':item_id'=>$v['item_id'], ':sku_online'=>$v['sku_online']);
                        $productProfit = $ebayProductProfitModel->getOneByCondition('*', $profitConditions, $profitParam);
                        if($productProfit){
                            $id = $productProfit['id'];
                        }

                        //计算利润和利润率
                        $paramArr = array(
                            'sale_price'                => $v['sale_price'],
                            'sku'                       => $v['sku'],
                            'current_price_currency'    => $value['current_price_currency'],
                            'site_id'                   => $value['site_id'],
                            'account_id'                => $value['account_id'],
                            'category_name'             => $categoryName
                        );
                        $shipping_price = implode(',', $itemValue);
                        $profitAndProfitRateArr = $ebaySalePriceConfigModel->getProfitAndProfitRateByParam($itemValue,$paramArr);

                        $profit         = $profitAndProfitRateArr['profit'];
                        $profitRate     = $profitAndProfitRateArr['profit_rate'];

                        $times = MHelper::getNowTime();

                        $insertValue = "{$id},'{$value['id']}','{$v['item_id']}','{$value['category_id']}',{$value['site_id']},{$v['account_id']},'{$v['main_sku']}','{$v['sku']}','{$v['sku_online']}','{$v['sale_price']}','{$shipping_price}','{$profit}','{$profitRate}','{$times}','{$times}'";

                        $ebayProductProfitModel->insertOrUpdate($insertFields,$insertValue);
                    }
                }
            } 

            $this->updateData(array('update_status'=>1), 'item_id = :item_id', array(':item_id' => $shippingKey)); 

            if($logName){
                $eventName = $logName;
                $logParams = array(
                    'account_id'    => 0,
                    'event'         => $eventName,
                    'start_time'    => date('Y-m-d H:i:s'),
                    'response_time' => date('Y-m-d H:i:s'),
                    'create_user_id'=> Yii::app()->user->id ? Yii::app()->user->id : User::admin(),
                    'status'        => EbayLog::STATUS_SUCCESS,
                    'message'       => 'item_id:'.$shippingKey.'更新利润信息成功'
                );
                $ebayLog->savePrepareLog($logParams);
            } 

        }

        return true;
    }
    
    /**
     * @desc 获取最小运费
     * @param unknown $itemID
     * @return unknown|number
     */
    public function getMiniShippingPriceByItemID($itemID){
    	$row = $this->getDbConnection()->createCommand()
    							->from($this->tableName())
    							->select("shipping_service_cost")
    							->where("item_id='{$itemID}'")
    							->order("shipping_service_cost asc")
    							->queryRow();
    	if($row) return floatval($row['shipping_service_cost']);
    	return 0;
    }
    
}