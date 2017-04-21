<?php
class OrderUpdateLog extends Order {

    /** @var string [ExceptionMsg] */
    protected $_ExceptionMsg    = null;
    
    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'ueb_order_update_log';
    }      

    /**
     * 设置异常信息
     * @param string $message           
     */
    public function setExceptionMessage($message) {
        $this->_ExceptionMsg = $message;
        return $this;
    }

    public function getExceptionMessage() {
        return $this->_ExceptionMsg;
    }    

    /**
     * getOneByCondition 
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
     * @return array       
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * getListByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  mixed $order  
     * @return array       
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
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

    /**
     * @desc 添加订单修改记录
     * @param string $orderId
     * @param string $logMsg
     */
    public function addOrderUpdateLog($orderId,$logMsg) {
        $data = array(
            'order_id'       => $orderId,
            'update_content' => $logMsg,
            'create_time'    => date("Y-m-d H:i:s"), 
            'create_user_id' => (int)Yii::app()->user->id,
        );
        MHelper::printvar($data,false);
        return $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    }     

}