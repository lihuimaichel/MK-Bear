<?php
/**
 * @desc Warehouse sku map
 * @author 
 */
class WarehouseSkuMap extends WarehouseModel {
    
    const WARE_HOUSE_DEF = '1';//默认仓
    const WARE_HOUSE_GM  = '41';//光明仓
    const WARE_HOUSE_LB  = '14';//海外仓--乐宝
    const WARE_HOUSE_DSF = '34';//海外仓--4PX(递四方)
    const WARE_HOUSE_WYT = '58';//海外仓--万邑通
    const WARE_HOUSE_LTC = '74';//海外仓--4PX(英国路藤仓)
    const WARE_HOUSE_YSD = '61';//海外仓--易时达英国仓
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_warehouse_sku_map';
    }
    
    /**
     * @desc 根据条件获取sku列表
     * @param unknown $conditions
     * @param unknown $params
     * @param string $limits
     * @param string $select
     * @return mixed
     */
    public function getSkuListByCondition($conditions, $params, $limits = "", $select = "*"){
    	$command = $this->getDbConnection()->createCommand()
				    	->from($this->tableName())
				    	->where($conditions, $params)
				    	->select($select);
    	if($limits){
    		$limitsarr = explode(",", $limits);
    		$limit = isset($limitsarr[1]) ? trim($limitsarr[1]) : 0;
    		$offset = isset($limitsarr[0]) ? trim($limitsarr[0]) : 0;
    		$command->limit($limit, $offset);
    	}
    	return $command->queryAll();
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
     * @desc 连表查询
     * @param unknown $conditions
     * @param unknown $params
     * @param string $limits
     * @param string $select
     * @return mixed
     */
    public function getSkuListLeftJoinProductByCondition($conditions, $params, $limits = "", $select = "t.*"){
    	$command = $this->getDbConnection()->createCommand()
				    	->from($this->tableName() . " as t")
				    	->leftJoin("ueb_product." . Product::model()->tableName() . " as p", "p.sku=t.sku")
				    	->where($conditions, $params)
				    	->select($select)
    					->order("t.available_qty asc");
    	if($limits){
    		$limitsarr = explode(",", $limits);
    		$limit = isset($limitsarr[1]) ? trim($limitsarr[1]) : 0;
    		$offset = isset($limitsarr[0]) ? trim($limitsarr[0]) : 0;
    		$command->limit($limit, $offset);
    	}
    	return $command->queryAll();
    }



    public function getProductDataWithSkuListAndStockFilter(array $sku, $stockFilter= null, $warehouseId = WarehouseSkuMap::WARE_HOUSE_GM)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName() . " as t")
            ->leftJoin("ueb_product." . Product::model()->tableName() . " as p", "p.sku=t.sku")
            ->where(array('in', 'p.sku', $sku))
            ->andWhere('t.warehouse_id=:warehouseId', array(':warehouseId'=> $warehouseId));
        if (null !== $stockFilter) {
            $queryBuilder->andWhere('t.available_qty=:availableQty', array(':availableQty'=> $stockFilter));
        }
        return $queryBuilder->queryAll();
    }

    /**
     * @param $sku
     * @param $params
     * @param $select
     * @author ketu.lai
     * @todo 关联侵权表查询
     * @return mixed
     */
    public function getFilterProductDataWithSkuList(array $sku, array $params, $select = null)
    {
        $conditions = "p.sku IN (".MHelper::simplode($sku).") AND t.warehouse_id = :warehouseId AND t.available_qty>=:availableQty AND (pi.infringement=:infringement OR pi.security_level = 'A' OR ISNULL(pi.sku)) ";
        //$conditions = "t.sku IN (:sku) AND t.warehouse_id = :warehouseId AND t.available_qty>=:availableQty  ";
        $defaultSelect ="t.sku, t.warehouse_id, t.available_qty";
        if ($select) {
            $defaultSelect .= $select;
        }

        $defaultParams = array(
            ':warehouseId'=> WarehouseSkuMap::WARE_HOUSE_GM,
            ':infringement'=> ProductInfringe::INFRINGE_NORMAL,
        );

        $params = array_merge($defaultParams, $params);
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName() . " as t")
            ->leftJoin("ueb_product." . Product::model()->tableName() . " as p", "p.sku=t.sku")
            ->leftJoin("ueb_product.".ProductInfringe::model()->tableName() .' AS pi', 'p.sku = pi.sku')
            ->where($conditions, $params)
            ->select($defaultSelect);
            //->order("t.available_qty asc");
       //var_dump($params);
        echo '<br>';
        echo '仓库关联侵权表查找对应可用库存条件的SKU';
        echo '<br>';
        echo str_repeat('...', 10);
        echo '<br>';
        echo str_replace(array_keys($params), array_values($params), $queryBuilder->getText());
        echo '<br>';
        return $queryBuilder->queryAll();
    }

    
    /**
     * @desc 左联产品表，产品QE审核记录表
     * @param unknown $conditions
     * @param unknown $params
     * @param string $limits
     * @param string $select
     * @return mixed
     */
    public function getSkuListLeftJoinProductAndQERecordByCondition($conditions, $params, $limits = "", $select = "t.*"){
    	$qeTable = "ueb_product.ueb_product_qe_check_record";
    	$command = $this->getDbConnection()->createCommand()
    	->from($this->tableName() . " as t")
    	->leftJoin("ueb_product." . Product::model()->tableName() . " as p", "p.sku=t.sku")
    	->leftJoin($qeTable . " as qe", "qe.sku=t.sku")
    	->where($conditions, $params)
    	->select($select)
    	->order("t.available_qty asc");
    	if($limits){
    		$limitsarr = explode(",", $limits);
    		$limit = isset($limitsarr[1]) ? trim($limitsarr[1]) : 0;
    		$offset = isset($limitsarr[0]) ? trim($limitsarr[0]) : 0;
    		$command->limit($limit, $offset);
    	}
    	return $command->queryAll();
    }
    /**
     * @desc 查询库存<=1的sku
     * @return array 返回sku
     */
    public function getZeroStockSku($limit = 50, $offset = 0) {
    	$result =  $this->dbConnection->createCommand()
                ->select('sku')
                //Select * From yanxue8_visit Where vid ＞=(Select vid From yanxue8_visit Order By vid limit 10000,1) limit 10 
                ->from(self::tableName())
                ->where("available_qty <= 1")
                ->andWhere("warehouse_id = " . WarehouseSkuMap::WARE_HOUSE_GM)
                ->limit($limit, $offset)
                ->order('id')
                ->queryColumn();
        return $result;
    }
    
    /**
     * @desc 查询库存<=1的sku数量
     * @return int 返回数量
     */
    public function getZeroStockSkuCount() {
        $result = self::model()->count("available_qty <= 1 and warehouse_id = " . WarehouseSkuMap::WARE_HOUSE_GM);
        return $result;
    }
    
    
    /**
     * @desc 根据sku和仓库查询系统库存数量
     * @return int 返回数量
     */
    public function getAvailableBySkuAndWarehouse($sku, $warehouse_id = 41) {
        $available = self::model()->dbConnection->createCommand()
                                        ->select('available_qty')
                                        ->from(WarehouseSkuMap::model()->tableName())
                                        ->where('sku = "' . $sku . '"')
                                        ->andWhere('warehouse_id = "' . $warehouse_id . '"')
                                        ->limit(1)
                                        ->queryColumn();
        $result = empty($available) ? 0 : $available['0'];
        return $result;
    }

    
    /**
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author hanxy
     */
    public function getListOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryRow();
    }


    /**
     * @desc 海外仓数组
     */
    public static function getOverseasWarehouse(){
        return array(self::WARE_HOUSE_LB,self::WARE_HOUSE_DSF,self::WARE_HOUSE_WYT,self::WARE_HOUSE_LTC,self::WARE_HOUSE_YSD);
    }


    /**
     * @desc 连表查询日销量
     * @param unknown $conditions
     * @param unknown $params
     * @param string $limits
     * @param string $select
     * @return mixed
     */
    public function getSkuListLeftJoinProductAndSalesByCondition($conditions, $params, $limits = "", $select = "t.*", $andWhere = ''){
        $command = $this->getDbConnection()->createCommand()
                        ->from($this->tableName() . " as t")
                        ->leftJoin("ueb_product." . Product::model()->tableName() . " as p", "p.sku=t.sku")
                        ->leftJoin("ueb_sync.ueb_sku_sales as s", "s.sku=t.sku".$andWhere)
                        ->where($conditions, $params)
                        ->select($select)
                        ->order("t.available_qty asc");
        if($limits){
            $limitsarr = explode(",", $limits);
            $limit = isset($limitsarr[1]) ? trim($limitsarr[1]) : 0;
            $offset = isset($limitsarr[0]) ? trim($limitsarr[0]) : 0;
            $command->limit($limit, $offset);
        }
        return $command->queryAll();
    }

}