<?php
/**
 * @desc 产品加权价格和毛重变化统计表
 */
class ProductFieldChangeStatistics extends SystemsModel{

    public function getDbKey()
    {
        return 'db_statistics';
    }

    const AVG_PRICE_TYPE = 1;      //加权平均价
    const PRODUCT_WEIGHT_TYPE = 2; //产品毛重    
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_product_field_change_statistics';
    }

    /**
     * getInfoByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  string $order 
     * @return array        
     */
    public function getInfoByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }  
    
    /**
     * getListByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  string $order 
     * @return array      
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }        

    /**
     * 更新数据
     */
    public function updateData($data, $conditions, $params=array()){
        return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, $conditions, $params);
    }


    /**
     * 插入数据
     */
    public function insertData($data){
        return $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    }
}