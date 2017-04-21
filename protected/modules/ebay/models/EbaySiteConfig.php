<?php

/**
 * @desc Ebay站点配置表
 * @author yangsh
 * @since 2017-03-20
 */
class EbaySiteConfig extends EbayModel {

	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_site_config';
    }

    /**
     * 设置异常信息
     * @param string $message           
     */
    public function setExceptionMessage($message) {
        $this->_exceptionMsg = $message;
        return $this;
    }

    public function getExceptionMessage() {
        return $this->_exceptionMsg;
    }       

    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
     * @return array
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
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
     * @param  mixed $order  
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

}