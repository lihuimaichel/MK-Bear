<?php
/**
 * @desc lazada总账号
 * @author hanxy
 * @since 2017-03-20
 */
class PlatformLazadaAccountList extends PlatformAccountModel{
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_platform_lazada_account_list';
    }   
    
    /**
     * @desc 属性翻译
     */
    public function attributeLabels() {
    	return array(
            'account_name'   => '账号名称',
            'create_user_id' => '创建人',
            'create_time'    => '创建时间',
            'modify_user_id' => '修改人',
            'modify_time'    => '修改时间',
    	);
    }
   
    
    /**
     * get search info
     */
    public function search() {
        $sort = new CSort();
        $sort->attributes = array(
                'defaultOrder'  => 'id',
        );
        $dataProvider = parent::search(get_class($this), $sort);
        $dataProvider->setData($dataProvider->data);
        return $dataProvider;
    }
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        $result = array(
                array(
                        'name'=>'account_name',
                        'type'=>'text',
                        'search'=>'LIKE',
                        'htmlOption' => array(
                                'size' => '22',
                        )
                ),
        );
        return $result;
    }


    /**
     * 更新数据
     */
    public function updateData($data, $conditions, $params){
        return $this->getDbConnection()->createCommand()->update(self::tableName(), $data, $conditions, $params);
    }


    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return [type]         [description]
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }


    /**
     * @desc   getListByCondition
     * @param  string $fields 
     * @param  string $conditions  
     * @param  array $params  
     * @param  mixed $order 
     * @return array        
     * @author yangsh
     */
    public function getListByCondition($fields='*', $conditions, $params=array(), $order='') {
        $cmd = $this->getDbConnection()->createCommand();
        $cmd->select($fields)
            ->from(self::tableName());
        if (!empty($params)) {
            $cmd->where($conditions, $where);
        } else {
            $cmd->where($conditions);
        }
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }


    /**
     * @desc 定义URL
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/platformaccount/platformlazadaaccountlist/list');
    }


    /**
     * 更新数据
     */
    public function insertData($data){
        $flag = $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
        if( $flag ){
            return $this->getDbConnection()->getLastInsertID();
        }
        return false;
    }
}