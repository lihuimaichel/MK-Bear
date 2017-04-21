<?php
/**
 * @desc Lazada刊登多属性
 * @author Gordon
 * @since 2015-08-20
 */
class LazadaProductAddVariation extends LazadaModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return array validation rules for model variation.
     */
    public function rules(){}
    
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_product_add_variation';
    }
    
    /**
     * @desc 多属性主信息  存储
     */
    public function saveRecord($addID, $sku, $seller_sku, $parentSku, $price, $sale_price, $sale_price_start, $sale_price_end, $size, $is_parent){

        $flag = $this->dbConnection->createCommand()->insert(self::tableName(),array(
            'product_add_id'    => $addID,
            'sku'               => trim(addslashes($sku)),
            'seller_sku'        => trim(addslashes($seller_sku)),
            'parent_sku'        => trim(addslashes($parentSku)),
            'price'             => floatval($price),
            'sale_price'        => floatval($sale_price),
            'sale_price_start'  => $sale_price_start,
            'sale_price_end'    => $sale_price_end,
            'size'              => $size,
            'is_parent'         => $is_parent,
        ));
        if( $flag ){
            return $this->dbConnection->getLastInsertID();
        }else{
            return false;
        }
    }
    
    /**
     * @desc 根据添加id获取多属性信息
     */
    public function getVariationByAddID($addID){
        //$addID = 29;
        return $this->dbConnection->createCommand()
            ->select('*')
            ->from(self::tableName().' v')
            ->leftJoin(LazadaProductAddVariationAttribute::tableName() .' a', 'v.id = a.variation_id')
            ->where('add_id = '.$addID)
            ->queryAll();
    }
    
    /**
     * @desc 根据添加id获取多属性中的第一条父sku
     */
    public function getParentVariationByAddID($addID){
        //$addID = 29;
        return $this->dbConnection->createCommand()
            ->select('*')
            ->from(self::tableName().' v')
            ->leftJoin(LazadaProductAddVariationAttribute::tableName() .' a', 'v.id = a.variation_id')
            ->where('add_id = ' . $addID . " and v.is_parent = 1")
            ->queryRow();
    }
    /**
     * @desc 根据添加id获取多属性中的父sku之外的所有sku.
     */
    public function getSonVariationByAddID($addID){
        //$addID = 29;
        return $this->dbConnection->createCommand()
            ->select('v.*,a.name,a.value')
            ->from(self::tableName().' v')
            ->leftJoin(LazadaProductAddVariationAttribute::tableName() .' a', 'v.id = a.variation_id')
            ->where('a.add_id = ' . $addID . " and v.is_parent = 0")
            ->queryAll();
    }
    
    /**
     * @desc 获取需要上传的待刊登多属性记录variation表id
     */
    public function getNeedUploadVariationRecord($accountID, $type='info'){
        $status = array();
        if($type == 'image'){
            $status = array(LazadaProductAdd::UPLOAD_STATUS_IMGFAIL);
            return $this->dbConnection->createCommand()
                ->select('av.id')
                ->from(self::tableName() . ' av')
                ->join(LazadaProductAdd::tableName() .' a', 'av.product_add_id = a.id')
                ->where('av.status IN ('.MHelper::simplode($status).')')
                ->andWhere('a.account_id = '.$accountID)
                ->limit(LazadaProductAdd::MAX_NUM_PER_TASK)
                ->queryColumn();
        } else {
            $status_default = LazadaProductAdd::UPLOAD_STATUS_DEFAULT;
            $status_parent_success = LazadaProductAdd::UPLOAD_STATUS_PARENT_SUCCESS;
            $parent_variation =  $this->dbConnection->createCommand()
                ->select('av.id')
                ->from(self::tableName() . ' av')
                ->join(LazadaProductAdd::tableName() .' a', 'av.product_add_id = a.id')
                ->where("av.status = {$status_default}")
                ->andWhere('a.account_id = '.$accountID)
                ->limit(LazadaProductAdd::MAX_NUM_PER_TASK)
                ->queryColumn();
            $son_variation = $this->dbConnection->createCommand()
                ->select('av.id')
                ->from(self::tableName() . ' av')
                ->join(LazadaProductAdd::tableName() .' a', 'av.product_add_id = a.id')
                ->where("av.status = {$status_parent_success}")
                ->andWhere('a.account_id = '.$accountID)
                ->limit(LazadaProductAdd::MAX_NUM_PER_TASK)
                ->queryColumn();
            $variation = array_merge($parent_variation, $son_variation);
            return $variation;
        }
    }
    
    /**
     * @desc 根据多属性id获取详细信息以及add表信息
     */
    public function getVariationAddByVariationID($VariationID){
        return self::model()->dbConnection->createCommand()
            ->select('a.*, av.*, a.sku as a_sku')
            ->from(self::tableName().' av')
            ->join(LazadaProductAdd::tableName() .' a', 'av.product_add_id = a.id')
            ->where('av.id = ' . $VariationID)
            ->queryRow();
    }
    
    /**
     * @desc 标记多属性产品刊登状态
     * @param string $feedID
     * @param tinyInt $status
     * @param string $message
     */
    public function markVariationStatusByFeedID($feedID, $status, $message = ''){
        $return = $this->dbConnection->createCommand()->update(self::tableName(), array(
                'status'            => $status,
                'upload_message'    => $message,
        ),'feed_id = "'.$feedID.'"');
        
        //根据feed_id和is_parent = 1查出需要改状态的子sku并修改状态
        if($status == LazadaProductAdd::UPLOAD_STATUS_IMGFAIL){
            $parent_list = $this->dbConnection->createCommand()
                ->select('product_add_id, seller_sku')
                ->from(self::tableName())
                ->where('feed_id = "'.$feedID.'"')
                ->andWhere('is_parent=1')
                ->queryAll();
            
            foreach ($parent_list as $info){
                $return = $this->dbConnection->createCommand()->update(self::tableName(), array(
                    'status'        => LazadaProductAdd::UPLOAD_STATUS_PARENT_SUCCESS,
                ),"product_add_id ='{$info['product_add_id']}' and parent_sku= '{$info['seller_sku']}' and is_parent=0 and status=0");
            }
        }
        return $return;
    }
    
    /**
     * @desc 根据feedID查找刊登记录
     */
    public function getVariationRecordByFeedID($feedID){
        return $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('feed_id = "'.$feedID.'"')
                ->queryAll();
    }
    
    /**
     * @desc 根据sku标记variation状态
     * @param array $skus
     * @param int $accountID
     * @param tinyint $status
     * @param string $message
     */
    public function markStatusBySkusAndFeed($skus, $feed_id, $status, $message = ''){
        //标记variation状态
        $return = $this->dbConnection->createCommand()->update(self::tableName(), array(
            'status'            => $status,
            'upload_message'    => $message,
        ),'product_id IN ('.MHelper::simplode($skus).") AND feed_id = '".$feed_id."'");
        
        //标记子sku状态为父sku已上传UPLOAD_STATUS_PARENT_SUCCESS
        if($status == LazadaProductAdd::UPLOAD_STATUS_IMGFAIL){
            
            $parent_list = $this->dbConnection->createCommand()
                ->select('product_add_id, seller_sku')
                ->from(self::tableName())
                ->where('feed_id = "'.$feed_id.'"')
                ->andWhere('is_parent=1')
                ->andwhere('product_id IN ('.MHelper::simplode($skus).')')
                ->queryAll();
            
            foreach ($parent_list as $info){
                $return = $this->dbConnection->createCommand()->update(self::tableName(), array(
                    'status'        => LazadaProductAdd::UPLOAD_STATUS_PARENT_SUCCESS,
                ),"product_add_id ='{$info['product_add_id']}' and parent_sku= '{$info['seller_sku']}' and is_parent=0 and status=0");
            }
        }
        return $return;
    }
    
    /**
     * @desc 删除指定的待刊登variation表及属性值
     * @param int $id variation_id
     * @return $result 操作成功返回true,操作失败返回false
     */
    public function deleteVariationById($id){
        // 事务
        $transaction = Yii::app()->db->beginTransaction();
        try {
            //删除add_variation表数据
            UebModel::model('LazadaProductAddVariation')->getDbConnection()->createCommand()->delete(LazadaProductAddVariation::model()->tableName(), " id = $id");
            //删除add_variation_attribute表数据
            UebModel::model('LazadaProductAddVariationAttribute')->getDbConnection()->createCommand()->delete(LazadaProductAddVariationAttribute::model()->tableName(), " variation_id = $id");
            $transaction->commit();
            $result = true;
        } catch (Exception $e) {
            $transaction->rollback();
            $result = false;
        }
        return $result;
    }


    /**
     * @desc 根据条件获取单条数据
     * @param unknown $fields
     * @param unknown $conditions
     * @param string $param
     * @return mixed
     */
    public function getProductAddInfo($fields, $conditions, $param = null){
        return $this->getDbConnection()->createCommand()
                                ->select($fields)
                                ->from(self::tableName().' AS v')
                                ->leftJoin(LazadaProductAdd::model()->tableName().' AS p', 'p.id = v.product_add_id')
                                ->where($conditions, $param)
                                ->queryRow();
    }
}