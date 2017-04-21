<?php

class ProductToAccount extends ProductToAccountModel
{
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_product_to_account_seller_platform_eb_44';
    }

	
    /**
     * [getOneByCondition description]
     * @param  string $tableName 表名
     * @param  string $fields    字段
     * @param  string $where     条件
     * @param  mixed $order      排序
     * @author hanxy
     */
    public function getOneByCondition($tableName, $fields='*', $where='1') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($tableName)
            ->where($where)
            ->order('id DESC')
            ->limit(1);
        return $cmd->queryRow();
    }   
    
}