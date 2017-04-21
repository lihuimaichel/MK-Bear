<?php

/**
 * 产品销售关系表
 * @author yangsh
 * @since 2016-11-23
 */
class ProductToAccountSellerPlatformSlave extends ProductToAccountModel
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function getDbKey()
	{
		return 'db_oms_product_account_slave';
	}
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ueb_product_to_account_seller_platform_eb_44';
	}

	/**
	 * @desc 获取销售关系表表名
	 * @param  string $platformCode 平台code
	 * @param  int $accountID 账号id
	 * @return array
	 */
	public static function getRelationTableName($platformCode, $accountID)
	{
		$tableName = 'ueb_product_to_account.ueb_product_to_account_seller_platform_' . strtolower($platformCode);
		if ($accountID != '') {
			$tableName .= '_' . $accountID;
		}
		return $tableName;
	}

	public function getSKUSellerRelation($sku, $sellerId, $accountID, $platformCode, $site = '')
	{
		$cmd = $this->getDbConnection()->createCommand()
			->select('sku, seller_user_id as seller_id')
			->from(self::getRelationTableName($platformCode, $accountID))
			->where("sku=:sku and seller_user_id=:seller_id and account_id=:account_id and platform_code=:platform_code", array(':sku' => $sku, ':seller_id' => $sellerId, ':account_id' => $accountID, ':platform_code' => $platformCode));

		if ($site != '') {
			$cmd->andWhere("site=:site", array(':site' => $site));
		}

		$info = $cmd->queryRow();
		return $info;
	}


	/**
	 * @param $platformCode
	 * @param $accountID
	 * @param $seller_user_id
	 * @param array $product_status
	 * @param $product_multi
	 * @return array|CDbDataReader
	 *
	 * 获取主SKU数据（或者单品数据）
	 */
	public function getData($platformCode, $accountID, $seller_user_id, $product_multi = 2, $product_status = array(Product::STATUS_PRE_ONLINE, Product::STATUS_ON_SALE, Product::STATUS_WAIT_CLEARANCE))
	{
		$product_status = join("','", $product_status);
		$row = $this->getDbConnection()->createCommand()
			->select("r.sku, p.product_is_multi, r.site, r.account_id, r.seller_user_id, p.product_status")
			->from(self::getRelationTableName($platformCode, $accountID). ' r')
			->leftJoin(UebModel::model('Product')->fullTableName().' p', 'p.sku = r.sku')
			->where("r.seller_user_id =:seller_user_id", array(":seller_user_id" => $seller_user_id))
			->andWhere("p.product_is_multi = '{$product_multi}' AND p.product_status IN ('{$product_status}')")
			->queryAll();

		return $row;
	}

	/**
	 * @param $platformCode
	 * @param $accountID
	 * @param $seller_user_id
	 * @param array $product_status
	 * @return array|CDbDataReader
	 *
	 * 获取子 SKU 数据
	 *
	 */
	public function multiData($platformCode, $accountID, $seller_user_id, $product_status = array(Product::STATUS_PRE_ONLINE, Product::STATUS_ON_SALE, Product::STATUS_WAIT_CLEARANCE))
	{
		$product_status = join("','", $product_status);
		$eb_table = self::getRelationTableName($platformCode, $accountID);
		$pr_table = UebModel::model('ProductSlave')->fullTableName();
		$att_table = UebModel::model('ProductSelectAttributeSlave')->fullTableName();
		$sql = "SELECT
					pt.*, 
					pr.product_is_multi,
					pr.product_status
				FROM
				(
					SELECT
						`a`.`sku`,
						`a`.`product_id`,
						`eb`.`site`,
						`eb`.`account_id`,
						`eb`.`seller_user_id`
					FROM
						{$eb_table} AS eb
					LEFT JOIN {$pr_table} p ON p.sku = eb.sku
					LEFT JOIN {$att_table} a ON a.multi_product_id = p.id
					WHERE
						(eb.seller_user_id = '{$seller_user_id}')
					AND (
						a.sku <> ''
						AND p.product_status IN ('{$product_status}')
						AND p.product_is_multi = 2
					)
					GROUP BY
						a.sku,
						eb.site
				) AS pt
				LEFT JOIN {$pr_table} AS pr ON pt.product_id = pr.id
				WHERE 1 AND pr.product_status IN ('{$product_status}');";
		$row = 	$this->getDbConnection()->createCommand($sql)->queryAll();
		return $row;
	}

}