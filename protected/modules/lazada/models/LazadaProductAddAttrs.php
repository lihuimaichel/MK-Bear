<?php
/**
 *
 * @Todo Lazada多属性刊登，修改
 * @author liht
 * @since 2015-11-25
 *
 */
class LazadaProductAddAttrs extends LazadaModel{

    const IS_CHILD_SKU = 1;//1为子sku，添加请求记录时使用
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){}
    
    
    /**
     * @Todo 设置数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_product_add_variation';
    }

    /**
     * @Todo 保存刊登数据
     * @param array $param
     */
    public function saveRecord($param){

        $flag = $this->dbConnection->createCommand()->insert(self::tableName(), $param);
        if( $flag ){
            return $this->dbConnection->getLastInsertID();
        }else{
            return false;
        }

    }
    /*
     * @Todo 根据$product_add_id获取子sku属性
     * @param String $product_add_id
     */
    public function getChildSkusByPid($product_add_id='') {

        if(empty($product_add_id))  return false;

        return $this->dbConnection->createCommand()
                ->select('*')
                ->where('product_add_id=:product_add_id', array(':product_add_id'=>$product_add_id))
                ->from(self::tableName())
                ->queryAll();

    }

    /**
     * @Todo 标记产品刊登状态
     * @param string $feedID
     * @param tinyInt $status
     * @param string $message
     */
    public function markStatusByFeedID($feedID, $status, $message = ''){
        $this->dbConnection->createCommand()->update(self::tableName(), array(
            'status'            => $status,
            'upload_message'    => $message,
        ),'feed_id = "'.$feedID.'"');
    }

    /**
     * @Todo 根据feedID查找刊登记录
     * @param string $feedID
     */
    public function getRecordByFeedID($feedID){
        return $this->dbConnection->createCommand()
            ->select('*')
            ->from(self::tableName())
            ->where('feed_id = "'.$feedID.'"')
            ->queryAll();
    }

    /**
     * @Todo 根据sku标记状态
     * @param array $skus
     * @param int $accountID
     * @param tinyint $status
     * @param string $message
     */
    public function markStatusBySkus($skus, $accountID, $siteID, $status, $message = ''){
        $this->dbConnection->createCommand()->update(self::tableName(), array(
            'status'            => $status,
            'upload_message'    => $message,
        ),'product_id IN ('.MHelper::simplode($skus).') AND account_id = '.$accountID . ' and site_id = ' . $siteID);
    }

    /**
     * @Todo 设置子sku上传任务正在运行
     */
    public function setRunning($id){
        $this->dbConnection->createCommand()->update(self::tableName(), array(
            'status'        => LazadaProductAdd::UPLOAD_STATUS_RUNNING,
        ), 'id = '.$id);
    }

    /**
     * @Todo 设置子sku图片上传任务正在运行
     */
    public function setImageRunning($id){
        $this->dbConnection->createCommand()->update(self::tableName(), array(
            'status'        => LazadaProductAdd::UPLOAD_STATUS_IMGRUNNING,
        ), 'id = '.$id);
    }

    /*
     * @Todo 记录子sku图片上传失败
     *
     */
    public function ImageUploadFail($id,$errorMess='') {
        $this->dbConnection->createCommand()->update(self::tableName(), array(
            'upload_message'    => $errorMess,
            'status'            => self::UPLOAD_STATUS_IMGFAIL,
        ), 'id = '.$id);
    }

}