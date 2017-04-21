<?php

/**
 * @desc lazada调价记录
 * @author hanxy
 * @since 2017-03-31
 */
class LazadaChangePriceRecord extends LazadaModel
{

    public static $accountPairs = array();
    public $errorMeaage = null;
    public $account_name;
    public $is_visible;
    public $change_where;

    const TYPE_COST_ADD = 1;//成本增加
    const TYPE_COST_CUT = 2;//成本减少
    const TYPE_WEIGHT_ADD = 3;//毛重增加
    const TYPE_WEIGHT_CUT = 4;//毛重减少
    const TYPE_OLD_PRICE_NUMS = 100;  //用作计算老价格的利润率

    /**@var 改价状态*/
    const CHANGE_PRICE_STATUS_DEFAULT = 0; //默认值
    const CHANGE_PRICE_STATUS_SUCCESS = 1; //成功
    const CHANGE_PRICE_STATUS_FAIL    = 2; //失败

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
        return 'ueb_lazada_change_price_record';
    }

    /**
     * @desc 设置报错信息
     * @param string $message
     */
    public function setErrorMessage($message){
        $this->errorMeaage = $message;
    }

    /**
     * @desc 获取报错信息
     */
    public function getErrorMessage(){
        return $this->errorMeaage;
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
     *
     * @desc 判断是否达到调价条件
     * @param type int 1成本 2毛重
     * @param newField int 新值
     * @param lastField int 上一次值
     * @param siteID int 站点
     * @param $sku string sku
     * @return int 条件类型
     */
    public function changePriceCondition($type,$sku,$siteID,$newField = null,$lastField = null)
    {
        /*
         * 
        加权平均成本变化幅度超过>5%
            针对于所有站点，sku的加权平均成本增加或减少的幅度超过5%，就表示需要进行调整价格。低于5%的检测产品毛重变化条件。
      产品毛重变化
            MY：1、如果新变化的重量原来小于510g的，现在变为大于等于510g，
                2、如果新变化的重量原来是大于等于510g，现在变为小于510g，
                3、其他条件重量变化超过150g，
        重新计算运费
            ID：1、如果新变化的重量原来小于1010g的，现在变为大于等于1010g，
                2、如果新变化的重量原来是大于等于1010g，现在变为小于1010g，
                3、其他情况下重量变化超过50g，
        重新计算运费
            PH、TH：重量变化超过100g，重新计算运费
            SG：1、如果新变化的重量原来小于1490g的，现在变为大于等于1490g，
                2、如果新变化的重量原来是大于等于1490g，现在变为小于1490g，
                3、其他情况下重量变化超过100g，
        重新计算运费
            VN：重量变化大于等于100g

         */
        
        $conditionType = 0;
        $poorCostRate = round(abs($newField-$lastField)/$lastField,2);
        if($type==1 && $poorCostRate > 0.05){//成本类型
            $conditionType = self::TYPE_COST_ADD;
            if($newField < $lastField){
                $conditionType = self::TYPE_COST_CUT;
            }
        }elseif ($type == self::TYPE_OLD_PRICE_NUMS) {
            //计算老价格的利润率
            $conditionType = self::TYPE_OLD_PRICE_NUMS;
        }
        else {//毛重类型
            $weight = abs($newField-$lastField);
            //VN站点
            if($weight >= 100 && $siteID == 6){
                if($newField > $lastField){
                    $conditionType = self::TYPE_WEIGHT_ADD;
                }else{
                    $conditionType = self::TYPE_WEIGHT_CUT;
                }
            }elseif($weight > 100 && $siteID == 5){
                //PH站点重量变化超过100g，重新计算运费
                if($newField > $lastField){
                    $conditionType = self::TYPE_WEIGHT_ADD;
                }else{
                    $conditionType = self::TYPE_WEIGHT_CUT;
                }
            }elseif($weight > 100 && $siteID == 4){
                //TH站点重量变化超过100g，重新计算运费
                if($newField > $lastField){
                    $conditionType = self::TYPE_WEIGHT_ADD;
                }else{
                    $conditionType = self::TYPE_WEIGHT_CUT;
                }
            }elseif($lastField < 1010 && $newField >= 1010 && $siteID == 3){
                //ID站点如果新变化的重量原来小于1010g的，现在变为大于等于1010g
                $conditionType = self::TYPE_WEIGHT_ADD;
            }elseif($lastField >= 1010 && $newField < 1010 && $siteID == 3){
                //ID站点如果新变化的重量原来是大于等于1010g，现在变为小于1010g，
                $conditionType = self::TYPE_WEIGHT_CUT;
            }elseif($weight > 50 && $siteID == 3){
                //ID站点其他情况下重量变化超过50g，
                if($newField > $lastField){
                    $conditionType = self::TYPE_WEIGHT_ADD;
                }else{
                    $conditionType = self::TYPE_WEIGHT_CUT;
                }
            }elseif($lastField < 1490 && $newField >=1490 && $siteID == 2){
                //SG站点如果新变化的重量原来小于1490g的，现在变为大于等于1490g
                $conditionType = self::TYPE_WEIGHT_ADD;
            }elseif($lastField >= 1490 && $newField < 1490 && $siteID == 2){
                //SG站点如果新变化的重量原来是大于等于1490g，现在变为小于1490g
                $conditionType = self::TYPE_WEIGHT_CUT;
            }elseif($weight > 100 && $siteID == 2){
                //SG站点其他情况下重量变化超过100g
                if($newField > $lastField){
                    $conditionType = self::TYPE_WEIGHT_ADD;
                }else{
                    $conditionType = self::TYPE_WEIGHT_CUT;
                }
            }elseif($lastField < 510 && $newField >= 510 && $siteID == 1){
                //MY站点如果新变化的重量原来小于510g的，现在变为大于等于510g
                $conditionType = self::TYPE_WEIGHT_ADD;
            }elseif($lastField >= 510 && $newField < 510 && $siteID == 1){
                //MY站点如果新变化的重量原来是大于等于510g，现在变为小于510g
                $conditionType = self::TYPE_WEIGHT_CUT;
            }elseif($weight > 150 && $siteID == 1){
                //MY站点其他条件重量变化超过150g
                if($newField > $lastField){
                    $conditionType = self::TYPE_WEIGHT_ADD;
                }else{
                    $conditionType = self::TYPE_WEIGHT_CUT;
                }
            }else{
                $conditionType = 0;
            }
        }

        $output = array();
        if($conditionType > 0){
            //通过站点查找币种
            $currency = LazadaSite::getCurrencyBySite($siteID);
            $model_lazadaproduct = new LazadaProduct();
            $dataParam = array(
                ':platform_code'         => Platform::CODE_LAZADA,
                ':profit_calculate_type' => SalePriceScheme::PROFIT_SYNC_TO_SALE_PRICE
            );
            $schemeWhere = 'platform_code = :platform_code AND profit_calculate_type = :profit_calculate_type';
            $salePriceScheme = SalePriceScheme::model()->getSalePriceSchemeByWhere($schemeWhere,$dataParam);
            if (!$salePriceScheme) {
                $tplParam = $model_lazadaproduct->tplParam;
            } else {
                $tplParam = array(
                    'standard_profit_rate'  => $salePriceScheme['standard_profit_rate'],
                    'lowest_profit_rate'    => $salePriceScheme['lowest_profit_rate'],
                    'floating_profit_rate'  => $salePriceScheme['floating_profit_rate'],
                );
            }

            //计算卖价，获取描述
            $priceCal = new CurrencyCalculate();
            //设置参数值
            if($conditionType != self::TYPE_OLD_PRICE_NUMS){
                $priceCal->setProfitRate($tplParam['standard_profit_rate']);//设置利润率
            }else{
                $priceCal->setSalePrice($lastField);
            }
            
            $priceCal->setCurrency($currency);//币种
            $priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
            $priceCal->setSku($sku);//设置sku
            $priceCal->setSiteID($siteID);//设置站点

            $output['conditionType'] = $conditionType;
            $output['price']         = $priceCal->getSalePrice();//获取卖价
            // $output['profit']        = $priceCal->getProfit();//获取利润
            $output['profitRate']    = $priceCal->getProfitRate();//获取利润率
            // $output['desc']          = $priceCal->getCalculateDescription();//获取计算详情
        }
        
        return $output;
    }


    /**
     *
     * @desc 更新价格接口
     * @param array skuInfo sku信息
     * @return array
     */
    public function changePrice($skuInfo){
        if(!$skuInfo){
            return false;
        }

        $insertParams = array();
        $insertArr = array();
        if($skuInfo['sale_start_date'] == '0000-00-00 00:00:00'){
            $insertArr = array(
                'SellerSku' => $skuInfo['seller_sku'],
                'Price'     => $skuInfo['new_price']
            );

            $updateLazadaProductArr = array('price'=>$skuInfo['new_price']);
        }else{
            $salePrice = round($skuInfo['new_price'] / 0.5, 2);
            $insertArr = array(
                'SellerSku'     => $skuInfo['seller_sku'],
                'Price'         => $salePrice,
                'SalePrice'     => $skuInfo['new_price'],
                'SaleStartDate' => date('Y-m-d',strtotime($skuInfo['sale_start_date'])),
                'SaleEndDate'   => date('Y-m-d',strtotime($skuInfo['sale_end_date']))
            );

            $updateLazadaProductArr = array('price'=>$salePrice, 'sale_price'=>$skuInfo['new_price']);
        }

        $insertParams[] = $insertArr;
        $request = new UpdatePriceQuantityRequestNew();
        $request->setSkus($insertParams);
        $request->push();
        $response = $request->setApiAccount($skuInfo['account_auto_id'])->setRequest()->sendRequest()->getResponse();
        if($request->getIfSuccess()){
            LazadaProduct::model()->getDbConnection()->createCommand()->update(LazadaProduct::model()->tableName(), $updateLazadaProductArr, "id = '{$skuInfo['id']}'");
            return true;
        }else{
            $msg = isset($response->Body->Errors->ErrorDetail->Message)?$response->Body->Errors->ErrorDetail->Message:'失败,但没有获取到错误信息';
            $errormsg = (string)$msg;
            $this->setErrorMessage($errormsg);
            return false;
        }
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
            'id'                 => Yii::t('system', 'No.'),
            'sku'                => 'SKU',
            'seller_sku'         => '线上SKU',
            'parent_sku'         => '主SKU',
            'product_id'         => '产品ID',
            'account_id'         => '账号',
            'site_id'            => '站点',
            'account_name'       => '账号名称',
            'type'               => '类型',
            'seller_user_id'     => '销售人员',
            'old_price'          => '标准售价',
            'old_profit_rate'    => '标准利润率',
            'new_price'          => '新售价',
            'new_profit_rate'    => '新利润率',
            'status'             => '处理状态',
            'message'            => '处理信息',
            'deal_date'          => '监控日期',
            'create_time'        => '操作时间',
            'last_response_time' => '最新操作时间',
            'update_user_id'     => '处理人',
            'change_where'       => '触发条件',
            'run_count'          => '运行次数',
        );
    }

    public function getStatusOptions($status = null)
    {
        //@todo 后续语言处理
        $statusOptions = array(
            0 => '待处理',
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
        $siteList = LazadaSite::getSiteList();
        foreach ($datas as &$data) {
            //账号名称
            $accountInfo = LazadaAccount::model()->getApiAccountByIDAndSite($data['account_id'], $data['site_id']);
            $data['account_name'] = $accountInfo['seller_name'];
            //站点
            $data['site_id'] = isset($siteList[$data['site_id']]) ? $siteList[$data['site_id']] : '';
            //根据type判断触发条件
            $data['change_where'] = '';
            if(in_array($data['type'], array(self::TYPE_COST_ADD, self::TYPE_COST_CUT))){
                $data['change_where'] = '最新价格: '.$data['new_product_cost'].'<br>上次价格: '.$data['last_product_cost'];
            }elseif (in_array($data['type'], array(self::TYPE_WEIGHT_ADD, self::TYPE_WEIGHT_CUT))) {
                $data['change_where'] = '最新重量: '.$data['new_product_weight'].'<br>上次重量: '.$data['last_product_weight'];
            }

            $data['old_profit_rate'] = $data['old_profit_rate'] . '%';
            $data['new_profit_rate'] = $data['new_profit_rate'] . '%';

            $data['is_visible'] = 0;
            $createTime = strtotime($data['create_time']) + 3*24*3600;
            if($data['status'] != self::CHANGE_PRICE_STATUS_SUCCESS && time() <= $createTime){
                $data['is_visible'] = 1;
            }
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
                'name' => 'product_id',
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
                'data' => LazadaAccount::model()->getAccountList(Yii::app()->request->getParam('site_id'))
            ),
            array(
                'name' => 'site_id',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => LazadaSite::getSiteList(),
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
            array(
                'name'          => 'deal_date',
                'type'          => 'text',
                'search'        => 'RANGE',
                'htmlOptions'   => array(
                        'class'    => 'date',
                        'dateFmt'  => 'yyyy-MM-dd',
                ),
            ),
        );
        return $result;
    }

    // =========== end search ==================
    

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return [type]         [description]
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->getDbConnection()->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }
}