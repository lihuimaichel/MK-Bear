<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/2/28
 * Time: 15:53
 *
 * 统计汇总临时表
 */
class AliexpressTaskTmp extends AliexpressModel
{
    //在此覆盖掉连接数据库的方法，使用新的临时数据库
    public function getDbKey()
    {
        return 'db_statistics';
    }


    const TABLE_NAME = 'ueb_aliexpress_task_tmp';

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


    /**
     * @param $platform_name
     * @param $prefix
     * @param $user_id
     * @param $type
     * @return int
     *
     *
     * 根据人员Id清空表的数据
     */
    public function delete($platform_name, $prefix, $user_id, $type = 'main')
    {
        $tableName = $this->getRelationTableName($platform_name, $prefix, $type);
        $cmd = $this->getDbConnection()->createCommand();
        return $cmd->delete($tableName, 'seller_user_id=:user_id', array(':user_id' => $user_id));
    }


    /**
     * @param $table_name
     * @return int
     *
     * 清空表
     */
    public function truncateTable($table_name)
    {
        $flag = $this->getDbConnection()
            ->createCommand("TRUNCATE table {$table_name};")
            ->execute();

        return $flag;
    }


    /**
     * @param $platform_name
     * @param $prefix
     * @param $params
     * @param $type
     * @return int|string
     *
     * 返回保存的结果
     */
    public function saveData($platform_name, $prefix, $params, $type = 'main')
    {
        $tableName = $this->getRelationTableName($platform_name, $prefix, $type);
        $flag = $this->getDbConnection()
            ->createCommand()
            ->insert($tableName, $params);
        if ($flag) {
            return $this->getDbConnection()->getLastInsertID();
        }
        return $flag;
    }


    public function insertData($platform_name, $prefix, $params, $type = 'main')
    {
        $tableName = $this->getRelationTableName($platform_name, $prefix, $type);
        $values = "(" . join("),(", $params) . ")";
        $sql = "INSERT INTO {$tableName}(sku, product_is_multi, site, account_id, seller_user_id, product_status) VALUES {$values}";
        $cmd = $this->getDbConnection()->createCommand($sql);
        return $cmd->execute();
    }


    /**
     * @param $prefix
     * @param $num
     * @return int
     *
     * 创建临时表（用于存放主SKU的临时数据）
     */
    public function createTable($prefix, $num)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `ueb_{$prefix}_task_tmp_{$num}` (
				`sku` varchar(255) DEFAULT NULL COMMENT 'SKU',
				`product_is_multi` tinyint(2) DEFAULT NULL COMMENT '0 单品 1多属性单品 2多属性组合',
				`site` varchar(32) DEFAULT NULL COMMENT '站点',
				`account_id` int(11) DEFAULT '0' COMMENT '账号Id',
				`seller_user_id` int(11) DEFAULT '0' COMMENT '销售人员Id',
				`product_status` tinyint(2) DEFAULT NULL COMMENT '3预上线4销售中6待清仓',
				 KEY `seller_sku_account_site_product` (`seller_user_id`,`sku`,`account_id`,`site`, `product_is_multi`) USING BTREE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $cmd = $this->getDbConnection()->createCommand($sql);
        return $cmd->execute();
    }

    /**
     * @param $prefix
     * @param $num
     * @return int
     *
     * 创建子sku临时表
     */
    public function createSubTable($prefix, $num)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `ueb_{$prefix}_sub_task_tmp_{$num}` (
				`sku` varchar(255) DEFAULT NULL COMMENT 'SKU',
				`product_is_multi` tinyint(2) DEFAULT NULL COMMENT '0 单品 1多属性单品 2多属性组合',
				`site` varchar(32) DEFAULT NULL COMMENT '站点',
				`account_id` int(11) DEFAULT '0' COMMENT '账号Id',
				`seller_user_id` int(11) DEFAULT '0' COMMENT '销售人员Id',
				`product_status` tinyint(2) DEFAULT NULL COMMENT '3预上线4销售中6待清仓',
				 KEY `seller_sku_account_site_product` (`seller_user_id`,`sku`,`account_id`,`site`, `product_is_multi`) USING BTREE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $cmd = $this->getDbConnection()->createCommand($sql);
        return $cmd->execute();
    }


    /**
     * @param $prefix
     * @param $num
     * @return int
     *
     * 创建单品sku临时表
     */
    public function createSingleTable($prefix, $num)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `ueb_{$prefix}_single_task_tmp_{$num}` (
				`sku` varchar(255) DEFAULT NULL COMMENT 'SKU',
				`product_is_multi` tinyint(2) DEFAULT NULL COMMENT '0 单品 1多属性单品 2多属性组合',
				`site` varchar(32) DEFAULT NULL COMMENT '站点',
				`account_id` int(11) DEFAULT '0' COMMENT '账号Id',
				`seller_user_id` int(11) DEFAULT '0' COMMENT '销售人员Id',
				`product_status` tinyint(2) DEFAULT NULL COMMENT '3预上线4销售中6待清仓',
				 KEY `seller_sku_account_site_product` (`seller_user_id`,`sku`,`account_id`,`site`, `product_is_multi`) USING BTREE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $cmd = $this->getDbConnection()->createCommand($sql);
        return $cmd->execute();
    }


    /**
     * @param $seller_user_id
     * @param $platform_name
     * @param $prefix
     * @param $type
     * @return array
     *
     * 返回指定人员对应负责的SKU数量
     */
    public function calculatorProduct($seller_user_id, $platform_name, $prefix, $type = 'main')
    {
        $data = array();
        $table = $this->getRelationTableName($platform_name, $prefix, $type);
        $cmd = $this->getDbConnection()->createCommand();
        $rows = $cmd->select("product_status, COUNT(DISTINCT(sku)) AS total")
            ->from($table)
            ->where("seller_user_id = '{$seller_user_id}' ")
            ->group("product_status")
            ->queryAll();

        if (!empty($rows)) {
            foreach ($rows as $k => $v) {
                $data[$v['product_status']] = $v['total'];
            }
        }
        return $data;
    }


    public function calculatorProductSiteStatus($seller_user_id, $platform_name, $prefix, $type = 'main', $account_id, $product_status, $site = '')
    {
        $table = $this->getRelationTableName($platform_name, $prefix, $type);
        $cmd = $this->getDbConnection()->createCommand();
        $condition = ('' != $site) ? " AND site = '{$site}'" : "";
        $rows = $cmd->select("COUNT(DISTINCT(sku)) AS total")
            ->from($table)
            ->where("seller_user_id = '{$seller_user_id}' AND account_id = '{$account_id}' AND product_status = '{$product_status}' {$condition}")
            ->queryRow();

        return $rows['total'];

    }


    /**
     * @param $platform_name
     * @param $prefix
     * @param $type
     * @return string
     *
     * 返回平台对应表名
     */
    public function getRelationTableName($platform_name, $prefix, $type = 'main')
    {
        $tableName = ('main' == $type) ? "ueb_{$platform_name}_task_tmp_{$prefix}" : (('sub' == $type) ? "ueb_{$platform_name}_sub_task_tmp_{$prefix}" : "ueb_{$platform_name}_single_task_tmp_{$prefix}");
        return $tableName;
    }
}