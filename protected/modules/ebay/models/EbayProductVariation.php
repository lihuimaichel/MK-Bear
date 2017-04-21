<?php
/**
 * @desc Ebay产品管理
 * @author Gordon
 * @since 2015-07-31
 */
class EbayProductVariation extends EbayModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_variation';
    }

    /**
     * @desc filterByCondition
     * @param  string $fields 
     * @param  [type] $where 
     * @return [type]  
     */
    public function filterByCondition($fields="*",$where) {
        $res = $this->dbConnection->createCommand()
                    ->select($fields)
                    ->from($this->tableName().' as v')
                    ->join(EbayProduct::tableName().' as p', "v.listing_id=p.id")
                    ->where($where)
                    ->queryAll();
        return $res;
    }

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    } 

    /**
     * @desc 获取子产品信息
     * @param unknown $conditions
     * @param unknown $params
     * @param string $select
     * @return mixed
     */
    public function getProductVariantInfoByCondition($conditions, $params, $select = "*"){
    	if(empty($select)) $select = "*";
    	return $this->getDbConnection()
    	->createCommand()
    	->from($this->tableName())
    	->select($select)
    	->where($conditions, $params)
    	->queryRow();
    }
    
    /**
     * @desc 获取子产品列表
     * @param unknown $conditions
     * @param unknown $params
     * @param string $select
     * @return mixed
     */
    public function getProductVariantListByCondition($conditions, $params, $select = "*"){
    	if(empty($select)) $select = "*";
    	return $this->getDbConnection()
    	->createCommand()
    	->from($this->tableName())
    	->select($select)
    	->where($conditions, $params)
    	->queryAll();
    }
    /**
     * @desc 保存产品的多属性数据
     * @param Array $params
     */
    public function saveProductVariation($params){
    	$tableName = self::tableName();
    	$flag = $this->dbConnection->createCommand()->insert($tableName, $params);
    	if($flag) {
    		return $this->dbConnection->getLastInsertID();
    	}
    	return false;
    }
    /**
     * @desc 根据主键id更新
     * @param unknown $id
     * @param unknown $params
     */
    public function updateProductVariationByID($id, $params){
    	return $this->getDbConnection()->createCommand()->update($this->tableName(), $params, "id=:id", array(":id"=>$id));
    }



    /**
     * @param null $accountId
     * @param null $itemId
     * @return mixed
     * @author ketu.lai
     * @todo 查找所有状态为在线，可用库存为0的Listing
     */
    public function getOnlineListingsWithZeroStock($accountId, $limit = null, $offset=0, $sku = null)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName() . " AS epv")
            ->select("epv.item_id, ep.site_id, epv.sku, epv.sku_online, epv.account_id, epv.quantity_available")
            ->join(EbayProduct::model()->tableName(). " AS ep", " epv.listing_id = ep.id ")
            ->where("ep.account_id=:accountId AND ep.item_status=1 AND epv.quantity_available=0", array(":accountId"=> $accountId));


        if ($limit) {
            $queryBuilder->limit($limit, $offset);
        }
        $queryBuilder->order('epv.id DESC');
        $whereParams = array(
            ':accountId'=> $accountId
        );
        if ($sku) {
            $queryBuilder->andWhere("epv.main_sku=:sku", array(":sku"=> $sku));
            $whereParams[':sku'] = $sku;
        }



        echo '<br>';
        echo '查找所有状态为在线，可用库存为0的Listing';
        echo '<br>';
        echo str_repeat('...', 10);
        echo '<br>';
        echo str_replace(array_keys($whereParams), array_values($whereParams),$queryBuilder->getText());
        echo '<br>';
        //echo $queryBuilder->getText();

        return $queryBuilder->queryAll();
    }
}