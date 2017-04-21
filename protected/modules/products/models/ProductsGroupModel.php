<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/1
 * Time: 11:33
 *
 */

class ProductsGroupModel extends ProductsModel
{
	const GROUP_SALE = 2;
	const GROUP_LEADER = 1;

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * 查询的表名
	 */
	public function tableName()
	{
		return 'ueb_seller_user_to_job';
	}

}