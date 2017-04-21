<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/4/12
 * Time: 9:47
 *
 * 订单表
 */

class DashboardOrders extends SystemsModel
{

    public $user_id;

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function getDbKey()
    {
        return 'db_dashboard';
    }

    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName()
    {
        return 'ueb_dashboard_task_orders';
    }


    /**
     * @param string $fields
     * @param string $where
     * @param array $params
     * @param string $order
     * @return mixed
     *
     * 根据条件获取一条数据
     */
    public function getOneByCondition($fields = '*', $where = '1', $params = array(), $order = '')
    {
        $cmd = $this->getDbConnection()->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where, $params);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
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
            return $this->dbConnection->getLastInsertID();
        }
        return false;
    }


    /**
     * @param $params
     * @param $id
     * @return int
     */
    public function update($params, $id)
    {
        return $this->getDbConnection()->createCommand()
            ->update(
                $this->tableName(),
                $params,
                " id = '{$id}'"
            );
    }
}