<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/3/3
 * Time: 17:19
 */
class WaitOptSite extends ReportModel
{
    public $user_id;

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
        return 'dm_dim_task_wait_opt_site';
    }
}