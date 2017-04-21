<?php
Yii::import('application.components.*');
Yii::import('application.modules.systems.components.*');
Yii::import('application.modules.systems.models.*');
Yii::import('application.modules.users.components.*');
Yii::import('application.modules.users.models.*');
Yii::import('application.modules.products.components.*');
Yii::import('application.modules.products.models.*');
Yii::import('application.modules.warehouses.components.*');
Yii::import('application.modules.warehouses.models.*');
Yii::import('application.modules.orders.components.*');
Yii::import('application.modules.orders.models.*');
Yii::import('application.modules.common.components.*');
Yii::import('application.modules.common.models.*');
Yii::import('application.modules.ebay.components.*');
Yii::import('application.modules.ebay.models.*');
Yii::import('application.modules.aliexpress.components.*');
Yii::import('application.modules.aliexpress.models.*');
Yii::import('application.modules.amazon.components.*');
Yii::import('application.modules.amazon.models.*');
Yii::import('application.modules.wish.components.*');
Yii::import('application.modules.wish.models.*');
Yii::import('application.modules.lazada.components.*');
Yii::import('application.modules.lazada.models.*');
/**
 * @desc 订单sku归属表
 * @author yangsh
 * @since 2016-07-29
 */
class OrderSkuOwnerChild extends OrderSkuOwner {

    /**
     * [model description]
     * @param  [type] $className [description]
     * @return [type]            [description]
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }  

    /**
     * @desc 获取销售员ID
     * @param  string $platformCode 
     * @param  string $itemID       
     * @param  string $onlineSku    
     * @return integer
     */
    public function getItemSellerID($platformCode, $itemID, $onlineSku) {
        switch ($platformCode) {
            case Platform::CODE_EBAY:
                $res = EbayProductSellerRelation::model()->getItemSellerID($itemID, $onlineSku);
                break;
            case Platform::CODE_ALIEXPRESS:
                $res = AliexpressProductSellerRelation::model()->getItemSellerID($itemID, $onlineSku);
                break;
            case Platform::CODE_AMAZON:
                $res = AmazonProductSellerRelation::model()->getItemSellerID($itemID, $onlineSku);
                break;
            case Platform::CODE_KF:
                $res = WishProductSellerRelation::model()->getItemSellerID($itemID, $onlineSku);
                break;
            case Platform::CODE_LAZADA:
                $res = LazadaProductSellerRelation::model()->getItemSellerID($itemID, $onlineSku);
                break;
            default:
                $res = null;
                break;
        }
        if (!empty($_GET['debug'])) MHelper::printvar($res,false);
        return isset($res['data']['sellerID']) ? $res['data']['sellerID'] : 0;
    }

    public function getLazadaGroupAccountID($oldAccountId) {
        return LazadaAccount::model()->dbConnection->createCommand()
                                    ->select('account_id')
                                    ->from(LazadaAccount::model()->tableName())
                                    ->where("old_account_id = {$oldAccountId}")
                                    ->queryScalar();        
    }

    /**
     * @desc 更新订单sku与销售关系数据
     * @param  array $data
     * @param  integer $id
     * @return array
     * @author yangsh
     * @since 2016/08/10
     */
    public function updateRow($data,$id) {
        $platformCode       = isset($data['platform_code']) ? $data['platform_code'] : '';//必须
        $platformOrderId    = isset($data['platform_order_id']) ? $data['platform_order_id'] : '';//必须
        $onlineSku          = isset($data['online_sku']) ? $data['online_sku'] : '';//必须
        $accountId          = isset($data['account_id']) ? $data['account_id'] : '';//必须
        $site               = isset($data['site']) ? $data['site'] : '';
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
        //ebay对应item_id,aliexpress对应主产品id,amazon对应Asin码,wish对应主产品id,
        //lazada对应站点-账号分组id-在线sku
        $itemId                         = '';
        if (isset($data['item_id'])) {
            switch ($platformCode) {
                case Platform::CODE_LAZADA:
                    $itemId             = implode('-',array($entity['site'],$entity['account_id'],$entity['online_sku']));//lazada对应站点-账号old id-在线sku
                    break;
                default:
                    $itemId             = $data['item_id'];
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
        //获取开发ID、跟单ID
        $productRoleInfo                = ProductRole::model()->getRoleUserIdBySku($sku);
        $entity['developer_id']         = !empty($productRoleInfo['product_developers']) ? $productRoleInfo['product_developers'] : 0;
        $entity['documentary_id']       = !empty($productRoleInfo['purchaser']) ? $productRoleInfo['purchaser'] : 0;       
        if (empty($data['seller_id'])) {
            if ($itemId != '') {
                //echo $platformCode, '--',$entity['item_id'], '--',$onlineSku,"<br>\r\n";
                $entity['seller_id']    = $this->getItemSellerID($platformCode, $entity['item_id'], $onlineSku);
            }else {
                $entity['seller_id']    = 0;
            }
        } else {
            $entity['seller_id']        = $data['seller_id'];
        }
        //where
        $where = "id='{$id}'";
        $info = $this->getOneByCondition('id',$where);
        $isOk = true;
        if (!empty($info)) {
            $entity['updated_at'] = $data['created_at'];//date('Y-m-d H:i:s');
            //if (!empty($_GET['debug'])) MHelper::printvar($entity,false);
            $isOk = $this->dbConnection->createCommand()->update($this->tableName(),$entity,$where);
        }
        if ($isOk) {
            $rtn = array('errorCode'=>'0','errorMsg'=>'ok');
        } else {
            $rtn = array('errorCode'=>'101','errorMsg'=>'更新或插入失败');
        }
        return $rtn;
    }

    /**
     * @desc   异步更新数据
     * @param  string $orderID 
     * @return boolean
     * @author yangsh
     * @since 2016/08/10
     */
    public function asyncUpdateData($orderID='',$day=1) {
        $day = intval($day);
        $maxDay = 7;//最多一周
        if ($day > $maxDay) {
            $day = $maxDay;
        }
        $timeDiff = date('Y-m-d H:i:s', time() - $day * 86400 );//超过二天不再更新
        $where = "is_delete=0 and seller_id=0 and updated_at > '{$timeDiff}' ";
        $orderID != '' && $where .= " and order_id='{$orderID}' ";
        if (!empty($_GET['debug'])) {
            echo $where."<br>";
        }
        $lists = $this->getListByCondition('*',$where,null,'id desc','5000');
        if (!empty($_GET['debug'])) {
            MHelper::printvar($lists,false);
        }
        if (empty($lists)) {
            return array('errorCode'=>'101', 'errorMsg'=>'No Data for update');
        }
        $flag = true;
        foreach ($lists as $data) {
            $response = $this->updateRow($data,$data['id']);
            if ($response['errorCode'] != '0') {
                $flag = false;
            }
        }
        if ($flag) {
            $rtn = array('errorCode'=>'0','errorMsg'=>'ok');
        } else {
            $rtn = array('errorCode'=>'102','errorMsg'=>'更新失败');
        }
        return $rtn;
    }    

}