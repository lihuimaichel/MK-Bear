<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/1/20
 * Time: 10:28
 */

class ReportModel extends UebModel
{
	public function getDbKey()
	{
		return 'db_report';
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


	/**
	 * @param string $fields
	 * @param array $where
	 * @param array $params
	 * @return mixed
	 *
	 * 获取月份数据
	 */
	public function fetchMonthData($fields = "*", $where = array(), $params = array())
	{
		$cmd = $this->getDbConnection()->createCommand();
		$cmd->select($fields)
			->from($this->tableName())
			->where($where, $params);

		return $cmd->queryAll();
	}

}