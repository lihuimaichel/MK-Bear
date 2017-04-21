<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/3/11
 * Time: 17:13
 */
class TaskLogModel extends UebModel
{
    /**
     * @desc 规定数据库
     */
    public function getDbKey()
    {
        return 'db_dashboard';
    }

    const TABLE_NAME = 'ueb_task_log';

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

    public function saveData($params)
    {
        $tableName = $this->tableName();
        $flag = $this->dbConnection->createCommand()->insert($tableName, $params);
        if ($flag) {
            return $this->dbConnection->getLastInsertID();
        }
        return false;
    }
}