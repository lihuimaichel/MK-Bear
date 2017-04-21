<?php
/**
 * @desc   Aliexpress海外仓配置表
 * @author AjunLongLive!
 * @time   20170329
 */
class AliexpressWarehouseConfig extends AliexpressModel {

    public $send_warehouse;

    public static function model($className = __CLASS__) {     
        return parent::model($className);
    }

    public function tableName() {
        return 'ueb_aliexpress_warehouse_config';
    }

    
    /**
     * @desc 获取仓库键值对
     * @return array
     */
    public static function getWarehouseList(){
        $list = array();
        $model = new self();
        $WarehouseList = $model->findAll();
        foreach ($WarehouseList as $val){
            $list[$val['warehouse_id']] = array('name'=>$val['warehouse_name']);
        }
        return $list;
    }    


    
    
    /**
     * @desc 根据条件获取多条数据
     * @param unknown $fields
     * @param unknown $conditions
     * @param string $param
     * @return mixed
     */
    public function getListByCondition($fields, $conditions, $param = null){
        return $this->getDbConnection()->createCommand()
                                ->select($fields)
                                ->from($this->tableName())
                                ->where($conditions, $param)
                                ->queryAll();
    }     

    /**
     * @desc 根据条件更新数据
     * @param string $condition
     * @param array $updata
     * @return boolean
     */
    public function updateListByCondition($conditions, $updata){
        if(empty($conditions) || empty($updata)) return false;
        return $this->getDbConnection()->createCommand()
                    ->update($this->tableName(), $updata, $conditions);
    }    
   
    /**
     * @desc 删除配置数据
     * @param unknown $condition
     * @return Ambigous <number, boolean>
     */
    public function deleteWarehouseConfig($condition){
    	return $this->getDbConnection()->createCommand()
    				->delete($this->tableName(), $condition);
    }

    /**
     * @desc 新增刊登
     * @param array $data
     * @return int
     */
    public function addWarehouseAdd($data){
        if(empty($data)) return false;
        $id = 0;
        $ret = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
        if($ret) $id = $this->getDbConnection()->getLastInsertID();
        return $id;
    }
    
    /**
     * 根据条件获取信息
     */
    public function getInfoByCondition($where) {
        if (empty($where)) return false;
        return $this->getDbConnection()->createCommand()
                    ->select('*')
                    ->from($this->tableName())
                    ->where($where)
                    ->queryRow();
    }    


}