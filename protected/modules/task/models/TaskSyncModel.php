<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/6
 * Time: 18:00
 */
class TaskSyncModel extends UebModel
{
	/**
	 * @desc 规定数据库
	 */
	public function getDbKey()
	{
		return 'db_sync';
	}

	const TABLE_NAME = 'ueb_product_platform_listing';

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
		return self::TABLE_NAME;
	}


	public function fullTableName()
    {
        return "market_sync.ueb_product_platform_listing";
    }


	/**
	 * @param $seller_user_id
	 * @param $platform
	 * @param $platform_name
	 * @param $prefix
	 * @param $type
	 * @return mixed
	 *
	 * 计算已刊登的数量
	 */
	public function calcutorListing($seller_user_id, $platform, $platform_name, $prefix, $type = 'main')
	{
		$table = $this->getRelationTableName($platform_name, $prefix, $type);
		$otherCondition = in_array($platform, array(
			Platform::CODE_EBAY,
			Platform::CODE_AMAZON,
			Platform::CODE_LAZADA,
			Platform::CODE_SHOPEE)) ? " AND l.site = t.site " : " ";

		$cmd = $this->getDbConnection()->createCommand();
		$row = $cmd->select("COUNT(DISTINCT(l.sku)) AS total")
			->from("market_sync.ueb_product_platform_listing l")
			->join("{$table} t", "l.sku = t.sku AND l.account_id = t.account_id {$otherCondition}")
			->where(" t.seller_user_id = '{$seller_user_id}' AND l.online_status = 1 AND l.platform_code = '" . strtoupper($platform) . "'")
			->queryRow();

		return $row['total'];
	}


	public function calcutorSiteStatusListing($seller_user_id, $platform, $platform_name, $prefix, $type = 'main', $account_id = 1, $status = '', $site = '')
	{
		$table = $this->getRelationTableName($platform_name, $prefix, $type);
		$otherCondition = in_array($platform, array(
			Platform::CODE_EBAY,
			Platform::CODE_AMAZON,
			Platform::CODE_LAZADA,
			Platform::CODE_SHOPEE)) ? " AND l.site = t.site " : " ";

		$condition_string = " AND l.account_id = '{$account_id}'";
		if ('' != $status) {
			$condition_string .= " AND t.product_status = '{$status}'";
		}

		if ('' != $site) {
			if (in_array($platform, array(
				Platform::CODE_EBAY,
				Platform::CODE_AMAZON,
				Platform::CODE_LAZADA,
				Platform::CODE_SHOPEE))) {
				$condition_string .= " AND t.site = '{$site}'";
			}
		}

		$cmd = $this->getDbConnection()->createCommand();
		$row = $cmd->select("COUNT(DISTINCT(l.sku)) AS total")
			->from("market_sync.ueb_product_platform_listing l")
			->join("{$table} t", "l.sku = t.sku AND l.account_id = t.account_id {$otherCondition}")
			->where(" t.seller_user_id = '{$seller_user_id}' {$condition_string} AND l.online_status = 1 AND l.platform_code = '" . strtoupper($platform) . "'")
			->queryRow();

		return $row['total'];
	}


	public function getRelationTableName($platform_name, $prefix, $type = 'main')
	{
		$tableName = ('main' == $type) ? "market_statistics.ueb_{$platform_name}_task_tmp_{$prefix}" : (('sub' == $type) ? "market_statistics.ueb_{$platform_name}_sub_task_tmp_{$prefix}" : "market_statistics.ueb_{$platform_name}_single_task_tmp_{$prefix}");
		return $tableName;
	}


    /**
     * @param $params
     * @param $platform_name
     * @param $platform
     * @param $prefix
     * @param string $type
     * @return array|CDbDataReader
     *
     * 导出待刊登的数据
     */
	public function exportWaitListing($params, $platform_name, $platform, $prefix, $type = 'main')
    {
        $export_table  = $this->getRelationTableName($platform_name, $prefix, $type);
        $table_name = $this->fullTableName();
        $seller_user_id = $params['seller_user_id'];
        $account_id = $params['account_id'];
        $site = $params['site'];
        $site_condition = ('' != $params['site']) ? " AND tmp.site = '{$site}'" : " ";
        $site_where = ('' != $params['site']) ? " AND t.site = '{$site}'" : " ";
        $site_equre = ('' != $params['site']) ? " AND t.site = l.site" : " ";
        $sql = "
                SELECT
	              tmp.sku
                FROM
                    {$export_table} AS tmp
                WHERE 1
                AND tmp.seller_user_id = '{$seller_user_id}'
                AND tmp.account_id = '{$account_id}'
                {$site_condition}
                AND tmp.sku NOT IN (
                  	SELECT
                        t.sku
                    FROM
                        {$export_table} AS t
                    INNER JOIN {$table_name} AS l 
                      ON t.sku = l.sku
                      AND t.account_id = l.account_id
                      {$site_equre}
                    WHERE 1
                      AND t.seller_user_id = '{$seller_user_id}'
                      AND t.account_id = '{$account_id}'
                      AND l.platform_code = '{$platform}'
                      {$site_where}                      
                )
        ";
        return $this->getDbConnection()->createCommand($sql)->queryAll();
    }

}