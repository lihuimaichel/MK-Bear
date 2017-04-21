<?php
/**
 * @desc Ebay账号restful扩展表
 * @author yangsh
 * @since 2017-03-23
 */
class EbayAccountRestful extends EbayModel {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_account_restful';
    }

    /**
     * @desc 通过ebay账号ID获取账号信息
     * @param  int $accountID 
     * @return array
     */
    public function getByAccountID($accountID) {
      return $this->dbConnection->createCommand()
                  ->from($this->tableName())
                  ->where("account_id={$accountID}")
                  ->queryRow();
    }

    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  string $order 
     * @return array        
     */
    public function getOneByCondition($fields='a.*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName(). ' as a')
            ->join(EbayAccount::tableName().' as b',"a.account_id=b.id")
            ->where("b.status=".EbayAccount::STATUS_OPEN)
            ->andWhere($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }     

    /**
     * getListByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  string $order 
     * @return array      
     */
    public function getListByCondition($fields='a.*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName(). ' as a')
            ->join(EbayAccount::tableName().' as b',"a.account_id=b.id")
            ->where("b.status=".EbayAccount::STATUS_OPEN)
            ->andWhere($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }

    /**
     * 更新数据
     */
    public function updateData($data, $conditions, $params=array()){
        return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, $conditions, $params);
    }

    /**
     * 插入数据
     */
    public function insertData($data){
        return $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    }

}