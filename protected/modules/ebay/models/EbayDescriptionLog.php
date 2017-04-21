<?php
/**
 * Created by PhpStorm.
 * Date: 2017/1/4
 * Time: 16:25
 *
 */
class EbayDescriptionLog extends EbayModel
{
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
		return 'ueb_ebay_description_log';
	}

}