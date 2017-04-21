<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/13
 * Time: 17:18
 */

class TaskPaneProfitGroupSale extends ReportModel
{

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
		return 'dm_dim_task_pane_com_profit_group_sale';
	}

    /**
     * @param string $fields
     * @param string $where
     * @param array $params
     * @param string $order
     * @return mixed
     *
     * 根据条件获取一条数据
     */
    public function getOneByCondition($fields = '*', $where = '1', $params = array(), $order = '')
    {
        $cmd = $this->getDbConnection()->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where, $params);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }


    /**
     * @param string $fields
     * @param string $where
     * @param string $order
     * @return array|CDbDataReader
     *
     * 根据条件获取数据
     */
    public function getDataByCondition($fields = '*', $where = '1', $order = '')
    {
        $cmd = $this->getDbConnection()->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }
}