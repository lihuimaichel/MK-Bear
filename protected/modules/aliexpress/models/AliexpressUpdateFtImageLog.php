<?php

class AliexpressUpdateFtImageLog extends AliexpressModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_update_ft_image_log';
    }


    /**
     * 插入或者更新数据
     */
    public function insertData($insertData){
        $sql = 'INSERT INTO '.self::tableName().
            ' (product_id,upload_status,upload_message,upload_time,update_status,update_message,update_nums,operator,operate_time,operate_message) 
            VALUES (\''.implode('\',\'', $insertData).'\') ON DUPLICATE KEY UPDATE 
            upload_status=VALUES(upload_status), 
            upload_message=VALUES(upload_message), 
            upload_time=VALUES(upload_time), 
            update_status=VALUES(update_status), 
            update_message=VALUES(update_message), 
            update_nums=VALUES(update_nums),
            operator=VALUES(operator),
            operate_time=VALUES(operate_time),
            operate_message=VALUES(operate_message)';
        return $this->getDbConnection()->createCommand($sql)->execute();
    }


    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return [type]         [description]
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where)
            ->limit(1);
        return $cmd->queryRow();
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
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $group != '' && $cmd->group($group);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }


    // ============================= search ========================= //
    
    public function search(){
        $sort = new CSort();
        $sort->attributes = array('defaultOrder'=>'id');
        $dataProvider = parent::search($this, $sort, '', $this->_setdbCriteria());
        $dataProvider->setData($this->_additions($dataProvider->data));
        return $dataProvider;
    }
    /**
     * @desc  设置条件
     * @return CDbCriteria
     */
    private function _setdbCriteria(){
        $cdbcriteria = new CDbCriteria();
        $cdbcriteria->select = '*';
        
        return $cdbcriteria;
    }
    
    private function _additions($datas){
        return $datas;
    }
    
    
    public function filterOptions(){
        return array(                
                array(
                    'name'   =>  'product_id',
                    'type'   => 'text',
                    'search' =>  '=',
                ),
        );
    }
    
    
    public function attributeLabels(){
        return array(                
            'upload_status'              => '上传图片是否成功',
            'upload_message'             => '上传图片信息',
            'product_id'                 => '速卖通产品ID', 
            'upload_time'                => '上传图片时间',
            'update_status'              => '更新图片是否成功',
            'update_message'             => '更新图片信息',
            'operator'                   => '操作人',
            'operate_time'               => '操作时间',
            'operate_message'            => '操作信息'         
        );
    }
    
    // ============================= end search ====================//
    

    /**
     * 是否成功状态
     */
    public function getStatus($status = null){
        $statusOptions = array(0=>'失败', 1=>'成功', 2=>'待更新');
        if($status !== null){
            return isset($statusOptions[$status])?$statusOptions[$status]:'';
        }
        return $statusOptions;
    }
}