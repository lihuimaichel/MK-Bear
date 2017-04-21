<?php
/**
 * @desc Ebay产品利润和利润率model
 * @author hanxy
 * @since 2016-10-12
 */
class EbayProductProfit extends EbayModel{
		
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_profit';
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
        return $cmd->queryRow();
    }


    /**
     * @des   插入或者更新
     * @param string $insertFields 字段
     * @param string $insertValue  值
     */
    public function insertOrUpdate($insertFields,$insertValue){
        $sql =  "INSERT INTO ".self::tableName()." (".$insertFields.") 
                VALUES (".$insertValue.") ON DUPLICATE KEY UPDATE 
                current_price = VALUES(current_price), 
                update_time = VALUES(update_time), 
                shipping_price = VALUES(shipping_price),
                profit = VALUES(profit),
                profit_rate = VALUES(profit_rate)";
        return $this->getDbConnection()->createCommand($sql)->execute();
    }


    /**
     * @desc   获取不同价格的列表
     * @param  string $fields 
     * @param  string $conditions  
     * @param  array $params  
     * @param  mixed $order 
     * @return array        
     * @author yangsh
     */
    public function getDifferentPriceByCondition($fields='*', $conditions, $params=array(), $order='',$limit='',$offset='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(EbayProductVariation::model()->tableName().' AS p')
            ->leftJoin(EbayProduct::model()->tableName().' AS t', 'p.listing_id = t.id AND p.main_sku = t.sku')
            ->leftJoin($this->tableName().' AS f', 'p.item_id = f.item_id AND p.sku_online = f.sku_online');
        if (!empty($params)) {
            $cmd->where($conditions, $params);
        } else {
            $cmd->where($conditions);
        }
        if ($limit !='' && $offset !== '') {
            $cmd->limit($limit, $offset);
        }
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }

}