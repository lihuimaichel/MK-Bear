<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/3/7
 * Time: 11:35
 */

class WishTaskReport extends WishModel
{
    const TABLE_NAME = 'ueb_wish_task_report';

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

    /**
     * @param string $fields
     * @param string $where
     * @param array $params
     * @param string $order
     * @return CDbDataReader|mixed
     *
     * 根据条件获取一条记录
     */
    public function getOneByCondition($fields = '*', $where = '1', $params = array(), $order = '')
    {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where, $params);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * @param $data
     * @param $id
     * @return int
     *
     * 更新
     */
    public function updateDataByID($data, $id)
    {
        if (!is_array($id)) $id = array($id);
        return $this->getDbConnection()
            ->createCommand()
            ->update($this->tableName(), $data, "id in(" . implode(",", $id) . ")");
    }


    /**
     * @param $params
     * @return bool
     *
     * 新增数据
     */
    public function saveData($params)
    {
        $tableName = $this->tableName();
        $flag = $this->getDbConnection()
            ->createCommand()
            ->insert($tableName, $params);
        if ($flag) {
            return $this->getDbConnection()->getLastInsertID();
        }
        return false;
    }
}