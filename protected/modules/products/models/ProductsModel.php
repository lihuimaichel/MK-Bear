<?php
/**
 * @desc 产品模块
 * @author Gordon
 */
class ProductsModel extends UebModel {
	const STATUS_WAIT = 1;
	const STATUS_PROCESS = 2;
	const STATUS_SCUCESS = 3;

    /**
     * @desc 规定数据库
     */
    public function getDbKey() {
        return 'db_oms_product';
    }


    public function getOneDataByCondition($fields = '*', $where = '1', $order = '')
    {
        $cmd = $this->getDbConnection()->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

}