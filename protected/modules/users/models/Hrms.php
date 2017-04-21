<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/1/13
 * Time: 16:06
 */
class Hrms extends UsersModel
{
	public static function model($className = __CLASS__)
	{
		return parent::model($className); 
	}

	/**
	 * @desc 表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName()
	{
		return 'ueb_hrms';
	}
}