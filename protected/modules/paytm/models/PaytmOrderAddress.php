<?php
/**
 * @desc Paytm订单拉取
 * @author yangsh
 * @since 2017-03-02
 */
class PaytmOrderAddress extends PaytmModel{
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_paytm_order_address';
    }


    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  string $order 
     * @return array        
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
     * getListByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  string $order 
     * @return array        
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
     * 按平台订单号删除
     * @param  string $orderID 
     * @return boolean
     */
    public function deleteByOrderID($orderID) {
        return $this->getDbConnection()->createCommand()
                    ->delete($this->tableName(),"order_id='{$orderID}'");
    }

    /**
     * 批量保存订单收件信息
     * @param  array $data
     * @return mixed
     */
    public function insertData($data) {
        $isOk = $this->getDbConnection()->createCommand()
                    ->insert($this->tableName(),$data);
        if($isOk) {
            return $this->getDbConnection()->getLastInsertID();
        }                    
        return false;
    } 

}