<?php

class AliexpressOrderHeaderTwo extends AliexpressModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_order_header_2';
    }
    

    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return [type]         [description]
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='',$group='') {
        $aliAccountModel = new AliexpressAccount();
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName().' AS h')
            ->leftJoin($aliAccountModel->tableName().' AS a', 'a.account = h.account_id')
            ->where($where);
        $group != '' && $cmd->group($group);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }    
}