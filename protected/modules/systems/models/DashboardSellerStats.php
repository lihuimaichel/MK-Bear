<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/1/3
 * Time: 20:52
 */

class DashboardSellerStats extends SystemsModel
{

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function getDbKey()
	{
		return 'db_dashboard';
	}

	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
            return 'ueb_dashboard_seller_stats';
	}





	/**
	 * @param string $fields
	 * @param string $where
	 * @param array $params
	 * @param string $order
	 * @return CDbDataReader|mixed
	 *
	 * 根据条件获取一条记录
	 */
	public function getOneByCondition($fields = '*', $where = '1', $params = array(), $order = '')
	{
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from($this->tableName())
			->where($where, $params);
		$order != '' && $cmd->order($order);
		$cmd->limit(1);
		return $cmd->queryRow();
	}


	/**
	 * @param $users
	 * @return array|CDbDataReader
	 */
	public function getSumData($users)
	{
		$table = $this->tableName();
		$date = date('Y-m-d');
		$ids = is_array($users) ? join(",", $users) : $users;
		$sql = "SELECT sku_amount, seller_user_id FROM {$table} WHERE 1
				AND seller_user_id IN ({$ids})
				AND date_time = '{$date}'
				GROUP BY seller_user_id
				";
		$rows = $this->getDbConnection()->createCommand("{$sql}")->queryAll();
		$data = array();
		if (!empty($rows)) {
			foreach ($rows as $k => $v) {
				$data[$v['seller_user_id']] = $v['sku_amount'];
			}
		}
		return $data;
	}


	/**
	 * @param $users
	 * @return array|CDbDataReader
	 *
	 * 按销售分组查询
	 */
	public function sumDataBySellerGroup($users)
	{
		$table = $this->tableName();
		$date  = date('Y-m-d');
		$ids = is_array($users) ? join(",", $users) : $users;
		$sql = "SELECT seller_user_id, SUM(was_listing) AS was_listing, SUM(pre_listing) AS pre_listing, 
					   SUM(wait_listing) AS wait_listing, SUM(collect_amount) AS collect_amount, 
					   SUM(view_amount) AS view_amount, SUM(y_sales) AS y_sales, SUM(y_earn) AS y_earn, 
					   SUM(y_orders) AS y_orders, SUM(y_shipped) AS y_shipped, 
					   SUM(t_sales) AS t_sales, SUM(t_earn) AS t_earn, SUM(t_orders) AS t_orders, 
					   SUM(t_shipped) AS t_shipped FROM {$table} WHERE 1
					   AND date_time = '{$date}'
					   AND seller_user_id IN ({$ids})
					   GROUP BY seller_user_id
					   ";
		$rows = $this->getDbConnection()->createCommand("{$sql}")->queryAll();
		return $rows;
	}


	/**
	 * @param string $group_by
	 * @return array|CDbDataReader
	 *
	 * 统计按组排序
	 */
	public function sumDataByGroup($group_by = "group_id")
	{
		$table = $this->tableName();
		$date  = date('Y-m-d');
		$sql = "SELECT {$group_by}, SUM(was_listing) AS was_listing, SUM(pre_listing) AS pre_listing, 
					   SUM(wait_listing) AS wait_listing, SUM(collect_amount) AS collect_amount, 
					   SUM(view_amount) AS view_amount, SUM(y_sales) AS y_sales, SUM(y_earn) AS y_earn, 
					   SUM(y_orders) AS y_orders, SUM(y_shipped) AS y_shipped, 
					   SUM(t_sales) AS t_sales, SUM(t_earn) AS t_earn, SUM(t_orders) AS t_orders, 
					   SUM(t_shipped) AS t_shipped FROM {$table} WHERE 1
					   AND date_time = '{$date}'
					   GROUP BY {$group_by}
					   ";
		$rows = $this->getDbConnection()->createCommand("{$sql}")->queryAll();
		return $rows;
	}


	public function fetchDataBySeller()
	{
		$table = $this->tableName();
		$date = date('Y-m-d');
		$sql = "SELECT
					SUM(was_listing) AS was_listing,
					SUM(pre_listing) AS pre_listing,
					SUM(wait_listing) AS wait_listing,
					SUM(sku_amount) AS sku_amount,
					SUM(sku_main_amount) AS sku_main_amount,
					SUM(collect_amount) AS collect_amount,
					SUM(view_amount) AS view_amount,
					seller_user_id,
					platform
				FROM
					{$table}
				WHERE 1
				AND 0 < group_id
				AND date_time = '{$date}'
				GROUP BY 
				seller_user_id,
				platform";

		$rows = $this->getDbConnection()->createCommand("{$sql}")->queryAll();
		return $rows;
	}

	/**
	 * @return array|CDbDataReader
	 *
	 * 按部门分组汇总数据
	 */
	public function fetchDataByDepGroup()
	{
		$table = $this->tableName();
		$date = date('Y-m-d');
		$sql = "SELECT
					SUM(was_listing) AS was_listing,
					SUM(pre_listing) AS pre_listing,
					SUM(wait_listing) AS wait_listing,
					SUM(sku_amount) AS sku_amount,
					SUM(sku_main_amount) AS sku_main_amount,
					SUM(collect_amount) AS collect_amount,
					SUM(view_amount) AS view_amount,
					group_id,
					platform,
					department_id
				FROM
					{$table}
				WHERE 1
				AND 0 < group_id
				AND date_time = '{$date}'
				GROUP BY 
				group_id,
				platform,
				department_id";

		$rows = $this->getDbConnection()->createCommand("{$sql}")->queryAll();
		return $rows;
	}

	/**
	 * 按站点为纬度汇总数据
	 * @return array|CDbDataReader
	 */
	public function fetchDataByDepSite()
	{
		$table = $this->tableName();
		$date = date('Y-m-d');
		$sql = "SELECT
					SUM(was_listing) AS was_listing,
					SUM(pre_listing) AS pre_listing,
					SUM(wait_listing) AS wait_listing,
					SUM(sku_amount) AS sku_amount,
					SUM(sku_main_amount) AS sku_main_amount,
					SUM(collect_amount) AS collect_amount,
					SUM(view_amount) AS view_amount,
					site_id,
					site_name,					
					department_id,
					platform
				FROM
					{$table}
				WHERE 1
				AND 0 < group_id
				AND date_time = '{$date}'
				GROUP BY 
				site_name,					
				department_id";

		$rows = $this->getDbConnection()->createCommand("{$sql}")->queryAll();
		return $rows;
	}



	/**
	 * @param $users
	 * @param $product_status
	 * @return array|CDbDataReader
	 *
	 * 根据状态，返回相应账号的信息
	 */
	public function fetchDataByStatus($users, $product_status)
	{
		$table = $this->tableName();
		$date  = date('Y-m-d');
		$sql = "SELECT seller_user_id, account_id, account_name, site_name, SUM(sku_amount) AS sku_amount, 
				SUM(sku_main_amount) AS sku_main_amount, SUM(was_listing) AS was_listing,
				SUM(wait_listing) AS wait_listing, SUM(pre_listing) AS pre_listing,
				SUM(view_amount) AS view_amount, SUM(collect_amount) AS collect_amount, listing_sale_rate, 
				optimization_sale_rate, y_sales, y_sales_rate, y_earn, y_orders, 
				t_sales, t_sales_rate, t_earn, t_orders FROM {$table}
				WHERE 1 AND seller_user_id IN('".join("','", $users)."') AND date_time = '{$date}' 
				AND product_status IN ('".join("','", $product_status)."')
				GROUP BY seller_user_id, account_name, site_name
				";
		$rows = $this->getDbConnection()->createCommand("{$sql}")->queryAll();
		return $rows;
	}


	public function calculate($platform = Platform::CODE_EBAY)
	{
		$date = date('Y-m-d');
		$sql = "UPDATE ".$this->tableName()." SET wait_listing = IF((sku_amount - was_listing) > 0, (sku_amount - was_listing), 0)
				WHERE 1 AND date_time = '{$date}' AND platform = '{$platform}';";

		$this->getDbConnection()->createCommand("{$sql}")->execute();
	}

	/**
	 * @param $params
	 * @return bool
	 *
	 * 新增数据
	 */
	public function saveData($params)
	{
		$tableName = $this->tableName();
		$flag = $this->dbConnection
			->createCommand()
			->insert($tableName, $params);
		if ($flag) {
			return $this->dbConnection->getLastInsertID();
		}
		return false;
	}


	/**
	 * @param $params
	 * @param $id
	 * @return int
	 */
	public function update($params, $id)
	{
		return $this->getDbConnection()->createCommand()
			->update(
				$this->tableName(),
				$params,
				" id = '{$id}'"
			);
	}

	/**
	 * @param $params
	 * @param $condition
	 * @return int
	 */
	public function updateByCondition($params, $condition)
	{
		return $this->getDbConnection()->createCommand()
			->update(
				$this->tableName(),
				$params,
				$condition
			);
	}

}