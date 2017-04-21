<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/3/15
 * Time: 17:12
 */
class TaskPaneMovingRateGroup extends ReportModel
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
        return 'dm_dim_task_pane_moving_rate_group';
    }
}