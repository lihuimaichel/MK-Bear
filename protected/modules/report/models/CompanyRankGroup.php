<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/1/19
 * Time: 17:01
 */

class CompanyRankGroup extends ReportModel
{

	public $user_id;

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function getDbKey()
	{
		return 'db_report';
	}

	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
		return 'dm_dim_company_sales_rank_group';
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
	 * @return mixed
	 * 统计符合条件的记录数
	 */
	public function totalAmount()
	{
		$condition = " del_flag = 1";
		$row = $this->getOneByCondition("COUNT(sales_id) AS total", $condition);
		return $row['total'];
	}

}