<?php
/**
 * @desc Paytm产品列表子SKU
 * @author AjunLongLive!
 * @since 2017-03-14
 */
class PaytmProductChild extends PaytmModel {
	
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_paytm_product_variation';
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
                                                    status as child_status,
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
                $return['data'][0]['child_status'] = $parentArray['status'];
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
     * @desc 插入子sku相关数据
     * @param $childSkuArray
     * @return 成功返回:{'status'=>'success','msg'=>''}，失败返回:{'status'=>'failure','msg'=>'失败的原因'}
     */
    public function updateChildSku($childSkuArray){
        $nowTime = date('Y-m-d H:i:s');
        $return = array('status'=>'success','msg'=>'');
        $childSkuArray['update_time'] = $nowTime;
        if (isset($childSkuArray) && !empty($childSkuArray)){
            $pkId = $this->getDbConnection()->createCommand()
                                            ->from($this->tableName())
                                            ->select("id")
                                            ->where("product_id=:product_id_arr",array(":product_id_arr" => $childSkuArray['product_id']))
                                            ->queryScalar();
            if($pkId){
                $isOk = $this->getDbConnection()->createCommand()
                                                ->update($this->tableName(), $childSkuArray, "id=:id", array(':id'=>$pkId));
                if (!$isOk){
                    $return['status'] = 'failure';
                }
            } else {
                $childSkuArray['creat_time'] = $nowTime;
                $isOk = $this->getDbConnection()->createCommand()
                                                ->insert($this->tableName(), $childSkuArray);
                if($isOk) {
                    $pkId = $this->getDbConnection()->getLastInsertID();
                    $return['id'] = $pkId;
                } else {
                    $return['status'] = 'failure';
                }
            }
            
        } else {
            $return['status'] = 'failure';
            $return['msg'] = '传入的更新数据不能为空！';
        }
        return $return;
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
    public function displaySettingHtml($productID) {
        //modify stock
        $settingHtml = '';
        $settingHtml .= "<input type='button' value='下架' onClick='modifyChildStatus({$productID},\"offline\")' /><br /><br />";
        $settingHtml .= "<input type='button' value='上架' onClick='modifyChildStatus({$productID},\"online\")' /><br />";
        echo $settingHtml;
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
                $childSkuArr = $this->getSkusDetailFromParams($data['product_id'],$parentThisArray);
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
                            $childSkuArr['data'][$keyChild]['child_product_id'] = $data['product_id'].','.$valChild['child_product_id'];
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
     * @desc 设置数据结构
     * @return CDbCriteria
     */
    protected function _setDbCriteria() {
        $criteria = new CDbCriteria();
        $criteria->addCondition("parent_id is null");
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