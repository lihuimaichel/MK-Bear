<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/3/16
 * Time: 11:04
 */
class WaitOptAccount extends ReportModel
{
    public $account_id;

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
        return 'dm_dim_task_wait_opt_account';
    }
}