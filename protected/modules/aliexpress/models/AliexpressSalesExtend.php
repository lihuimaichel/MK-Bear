<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/1/16
 * Time: 11:09
 */
class AliexpressSalesExtend extends AliexpressModel
{
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}
	

	/**
	 * 查询的表名
	 */
	public function tableName()
	{
		return 'ueb_aliexpress_task_sales_extend';
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
	 * @param $uid
	 * @param $ids
	 * @return bool
	 *
	 * 移除原来有设置，修改之后没有此项设置的要删除掉
	 */
	public function remove($id)
	{
		$result = $this->getDbConnection()->createCommand()
			->delete(
				self::tableName(),
				"sales_id = '{$id}'"
			);

		return $result;
	}


	/**
	 * @param string $fields
	 * @param string $where
	 * @param string $order
	 * @return mixed
	 *
	 * 获取一条记录
	 */
	public function getoneByCondition($fields = '*', $where = '1', $order = '')
	{
		$cmd = $this->getDbConnection()->createCommand();
		$cmd->select($fields)
			->from($this->tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		$cmd->limit(1);
		return $cmd->queryRow();
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
		$flag = $this->getDbConnection()
			->createCommand()
			->insert($tableName, $params);
		if ($flag) {
			return $this->getDbConnection()->getLastInsertID();
		}
		return false;
	}


	/**
	 * @param $data
	 * @param null $where
	 * @return int
	 *
	 * 保存数据
	 */
	public function updateData($data, $where = null)
	{

		return $this->getDbConnection()
				->createCommand()
				->update($this->tableName(), $data, "{$where}");
	}


    /**
     * @return array|CDbDataReader
     *
     * 获取当月的汇总数据
     */
    public function fetchAllSumData()
    {
        $year = date('Y', strtotime("-1 days"));
        $month = date('m', strtotime("-1 days"));
        $rows = $this->getDbConnection()
            ->createCommand("SELECT seller_user_id, SUM(sales_amount) AS month_sale_amount, SUM(profit_amount) AS month_profit_amount FROM ".self::tableName().
                " WHERE 1 
                  AND year = '{$year}' AND month = '{$month}'
                  GROUP BY seller_user_id;")
            ->queryAll();

        return $rows;
    }

    /**
     * @return array|CDbDataReader
     *
     * 获取当年的汇总数据
     */
    public function getYearAllSumData()
    {
        $year = date('Y', strtotime("-1 days"));
        $rows = $this->getDbConnection()
            ->createCommand("SELECT seller_user_id, SUM(sales_amount) AS sales_target, SUM(profit_amount) AS profit_target FROM ".self::tableName().
                " WHERE 1 
                  AND year = '{$year}'
                  GROUP BY seller_user_id;")
            ->queryAll();

        return $rows;
    }

    /**
	 * @param array $users
	 * @param string $field
     * @param string $date_time
	 * @return int
	 *
	 * 返回当前月的数据汇总
	 */
	public function getSum($users = array(), $field = 'sales_amount', $date_time = '')
	{
		if (empty($users)) {
			return 0;
		}

        if ('' == $date_time) {
            $year = date('Y');
            $month = (1 < date("j")) ? date('m') : date('m', strtotime("-1 days"));
        } else {
            $date_arr = explode("-", $date_time);
            $year = $date_arr[0];
            $month = $date_arr[1];
        }

		$row = $this->getDbConnection()
			->createCommand(" 
 				SELECT
					SUM({$field}) AS total
				FROM
					".$this->tableName()." AS ex
				INNER JOIN (
				SELECT
					id
				FROM
					".AliexpressSalesTarget::model()->tableName()."
				WHERE
					1
				AND seller_user_id IN ('".join("','", $users)."')
			) AS ta ON ex.sales_id = ta.id
			WHERE 1 AND ex.year = '{$year}' AND month = '{$month}'")
			->queryRow();
		return isset($row['total']) ? $row['total'] : 0;
	}


    /**
     * @return bool|int
     *
     * 清除数据库的垃圾数据
     */
	public function clearData()
    {
        $sql = "    SELECT DISTINCT
                        (ex.sales_id)
                    FROM
                        ".$this->tableName()." AS ex
                    INNER JOIN ".AliexpressSalesTarget::model()->tableName()." AS sa ON ex.seller_user_id = sa.seller_user_id
                    AND ex.sales_id = sa.id
                    WHERE
                        1
                    AND (
                        ex.profit_amount <> 10000
                        OR ex.sales_amount <> 60000
                    )";
        $rows = $this->getDbConnection()->createCommand($sql)->queryAll();
        $in_array = array();
        if (!empty($rows)) {
            foreach ($rows as $k=>$v) {
                $in_array[] = $v['sales_id'];
            }
            $in_ids = join("','", $in_array);
            $delete_sql = "DELETE FROM ".$this->tableName()." WHERE 1 AND sales_id NOT IN('{$in_ids}')";
            return $this->getDbConnection()->createCommand($delete_sql)->execute();
        } else {
            return false;
        }
    }

}