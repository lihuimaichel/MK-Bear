<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/3/3
 * Time: 16:36
 */
class WaitOptGroup extends ReportModel
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
        return 'dm_dim_task_wait_opt_group';
    }
}