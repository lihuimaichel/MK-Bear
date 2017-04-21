<?php

/**
 * @desc 订单sku归属表
 * @author yangsh
 * @since 2016-07-29
 */
class OrderSkuOwner extends OrdersModel {

    /**
     * [model description]
     * @param  [type] $className [description]
     * @return [type]            [description]
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order_sku_owner';
    }

    /**
     * @desc   getOneByCondition
     * @param  string $fields 
     * @param  string $conditions  
     * @param  array $params  
     * @param  mixed $order 
     * @return array        
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $conditions, $params=array(), $order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName());
        if (!empty($params)) {
            $cmd->where($conditions, $params);
        } else {
            $cmd->where($conditions);
        }
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * @desc   getListByCondition
     * @param  string $fields 
     * @param  string $conditions  
     * @param  array $params  
     * @param  mixed $order 
     * @return array        
     * @author yangsh
     */
    public function getListByCondition($fields='*', $conditions, $params=array(), $order='', $limit='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName());
        if (!empty($params)) {
            $cmd->where($conditions, $params);
        } else {
            $cmd->where($conditions);
        }
        $order != '' && $cmd->order($order);
        $limit != '' && $cmd->limit($limit);
        return $cmd->queryAll();
    }    

    /**
     * @desc   判断是否存在一条记录
     * @param  string  $platformCode    
     * @param  string  $platformOrderId 
     * @param  string  $onlineSku      
     * @return boolean 
     * @author yangsh
     * @since 2016/08/10              
     */
    public function isExist($platformCode,$platformOrderId,$onlineSku) {
        //参数验证
        $validateMsg = '';
        if ($platformCode == '') {
            $validateMsg .= 'platform_code is empty;';
        }
        if ($platformOrderId == '') {
            $validateMsg .= 'platform_order_id is empty;';
        }
        if ($onlineSku == '') {
            $validateMsg .= 'online_sku is empty;';
        }     
        if ($validateMsg != '') {
            $requestParams = array('platform_code'=>$platformCode,'platform_order_id'=>$platformOrderId,'online_sku'=>$onlineSku,);
            $rtn = array('errorCode'=>'100','errorMsg'=>$validateMsg,'data'=>array('request_params'=>$requestParams));
            return $rtn;
        }
        $where = "platform_code='{$platformCode}' and platform_order_id='{$platformOrderId}' and online_sku='{$onlineSku}'";
        $row = $this->getOneByCondition('id',$where);
        return array('errorCode'=>'0','errorMsg'=>'ok', 'data'=> empty($row)?0:$row['id']);
    }

    /**
     * @desc 插入订单sku与销售关系数据
     * @param array $data ['platform_code'=>'','platform_order_id'=>'','online_sku'=>'','account_id'=>'','site'=>'','sku'=>'','item_id'=>'','order_id'=>'']
     * @return array
     * @author yangsh
     * @since 2016/08/10
     */
    public function addRow($data) {
        $platformCode       = isset($data['platform_code']) ? $data['platform_code'] : '';//必须
        $platformOrderId    = isset($data['platform_order_id']) ? $data['platform_order_id'] : '';//必须
        $onlineSku          = isset($data['online_sku']) ? $data['online_sku'] : '';//必须
        $accountId          = isset($data['account_id']) ? $data['account_id'] : '';//必须
        $site               = isset($data['site']) ? $data['site'] : '';//必须
        $sku                = isset($data['sku']) ? $data['sku'] : '';//必须
        //参数验证
        $validateMsg = '';
        if ($platformCode == '') {
            $validateMsg .= 'platform_code is empty;';
        }
        if ($platformOrderId == '') {
            $validateMsg .= 'platform_order_id is empty;';
        }
        if ($onlineSku == '') {
            $validateMsg .= 'online_sku is empty;';
        }   
        if ($accountId == '') {
            $validateMsg .= 'account_id is empty;';
        }     
        if ($sku == '') {
            $validateMsg .= 'sku is empty;';
        }                   
        if ($validateMsg != '') {
            $rtn = array('errCode'=>'100','errMsg'=>$validateMsg);
            return $rtn;
        }
        //格式化数据
        $entity['platform_code']        = $platformCode;
        $entity['platform_order_id']    = $platformOrderId;
        $entity['online_sku']           = $onlineSku;
        $entity['account_id']           = $accountId;
        $entity['site']                 = $site;
        $entity['sku']                  = $sku;
        //刊登号
        $itemId                         = '';
        if (isset($data['item_id'])) {
            switch ($platformCode) {
                case Platform::CODE_LAZADA:
                    $itemId             = implode('-',array($entity['site'],$entity['account_id'],$entity['online_sku'])); //lazada对应站点-账号old id-在线sku
                                        
                    break;
                default:
                    $itemId             = $data['item_id'];//ebay对应item_id,aliexpress对应主产品id,amazon对应Asin码,wish对应主产品id
                    break;
            }
        }
        $entity['item_id']              = $itemId;
        //系统订单号
        $orderID                        = '';
        if (isset($data['order_id'])) {
            $orderID                    = $data['order_id'];
        }
        $entity['order_id']             = $orderID;
        $entity['copoun_price']         = isset($data['copoun_price']) ? $data['copoun_price'] : 0;//优惠金额
        $entity['freight_price']        = isset($data['freight_price']) ? $data['freight_price'] : 0;//运费
        
        //where
        $where = "platform_code='{$platformCode}' and platform_order_id='{$platformOrderId}' and online_sku='{$onlineSku}'";
        $info = $this->getOneByCondition('id',$where);
        $isOk = true;
        if (empty($info)) {
            $entity['created_at'] = date('Y-m-d H:i:s');
            $entity['updated_at'] = date('Y-m-d H:i:s');
            //MHelper::printvar($entity,false);
            //MHelper::writefilelog('oms_save.txt',print_r($entity ,true)."\r\n");
            $isOk = $this->dbConnection->createCommand()->insert($this->tableName(),$entity);
        }
        if ($isOk) {
            $rtn = array('errorCode'=>'0','errorMsg'=>'ok');
        } else {
            $rtn = array('errorCode'=>'101','errorMsg'=>'更新或插入失败');
        }
        //MHelper::printvar($rtn,false);
        return $rtn;
    }

    /**
     * 更新系统订单号
     * @return boolean
     */
    public function updateOrderId($data,$id) {
        return $this->dbConnection->createCommand()->update($this->tableName(),$data,"id={$id}");
    }

}