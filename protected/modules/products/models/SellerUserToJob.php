<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/9
 * Time: 16:35
 */
class SellerUserToJob extends ProductsModel
{
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return "ueb_seller_user_to_job";
	}

	public function teamList()
	{

	}


	public function sellerInfo($fields = "*", $seller_user_id)
	{
		$row = self::model()->getDbConnection()
				->createCommand()
				->select($fields)
				->from(self::tableName())
				->where("seller_user_id = {$seller_user_id} AND is_del = 0")
				->queryRow();
		return $row;
	}

	/**
	 * @return array
	 * 获取组长id列表
	 */
	public function teamLeaderIds()
	{
		$user = self::model()->getDbConnection()
			->createCommand()
			->select("seller_user_id")
			->from(self::tableName())
			->where('job_id = 1 AND is_del = 0')
			->queryAll();
		if (!$user) {
			return array();
		}

		$return = array();
		foreach ($user as $k => $v) {
			$return[] = $v['seller_user_id'];
		}
		return $return;
	}


	public function relations()
	{
		return array(
			'group' => array(self::BELONGS_TO, 'SellerToGroupName', array('group_id' => 'id')),
			'seller' => array(self::HAS_ONE, 'User', array('id' => 'seller_user_id')),
		);
	}

}