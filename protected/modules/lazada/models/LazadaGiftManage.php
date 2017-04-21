<?php
/**
 * @desc LazadaGiftManage
 * @author Yangsh
 * @since 2016-11-01
 */
class LazadaGiftManage extends LazadaModel{
    
    const STATUS_OPEN = 0;//开启
    const STATUS_SHUTDOWN = 1;//停用

    public $account;
    public $create_user;
    public $update_user;
    public $sys_sku;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_gift_manage';
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
     * @desc 启用或停用
     * @param string $ids 
     * @param int $status
     * @return boolean
     */
    public function openOrShutDown($ids, $status)
    {
        if(!in_array($status,array(self::STATUS_OPEN,self::STATUS_SHUTDOWN))) {
            return false;
        }
        return $this->updateAll(array(
            'is_delete'      => $status,
            'update_user_id' => (int)Yii::app()->user->id,
            'update_time'    => date('Y-m-d H:i:s'),
        ), "id in ( {$ids} )");
    }
    
    /**
     * @desc 属性翻译
     */
    public function attributeLabels()
    {
        return array(
            'num'         => Yii::t('system', 'No.'),
            'account_id'  => '账号',
            'account'     => '账号',
            'sku'         => '在线sku',
            'sys_sku'     => '系统sku',
            'gift_sku'    => '赠品sku',
            'is_delete'   => '状态',
            'create_user' => '添加人',
            'create_time' => '添加时间',
            'update_user' => '修改人',
            'update_time' => '修改时间',
        );
    }

    /**
     * @desc 定义URL
     */
    public static function getIndexNavTabId()
    {
        return Menu::model()->getIdByUrl('/lazada/lazadagiftmanage/list');
    }

    /**
     * @desc 获取状态
     */
    public static function getStatusList($status = '')
    {
        $statusArr = array(
            self::STATUS_OPEN     => '启用',
            self::STATUS_SHUTDOWN => '停用'
        );
        if ($status === '') {
            return $statusArr;
        } else {
            return $statusArr[$status];
        }
    }

    public static function getAccountList() {
        $accountList = LazadaAccount::model()->getListByCondition("id,short_name","status=1","site_id asc");
        foreach ($accountList as $v) {
            $idNameArr[$v['id']] = $v['short_name'];
        }
        return $idNameArr;
    }

    /**
     * 显示状态值
     *
     * @param type $status
     */
    public static function getStatusLable($status)
    {
        if ($status == self::STATUS_OPEN) {
            echo '<font color="green" >启用</font>';
        } else {
            echo '<font color="red" >停用</font>';
        }
    }

    public function getAccountOptions($accountId = null){
        $accountOptions = self::getAccountList();
        if($accountId !== null)
            return isset($accountOptions[$accountId])?$accountOptions[$accountId]:'';
        return $accountOptions;
    }  

    public function getStatusOptions($status = null){
        $statusOptions = self::getStatusList();
        if($status !== null)
            return isset($statusOptions[$status])?$statusOptions[$status]:'';
        return $statusOptions;
    }

    /**
     * @return array search filter (name=>label)
     */
    public function filterOptions()
    {
        return array(
            array(
                'name' => 'account_id',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getAccountOptions(),
            ),
            array(
                'name' => 'is_delete',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getStatusOptions(),
            ),
            array(
                'name'   => 'sku',
                'type'   => 'text',
                'search' => 'LIKE',
                'alias'  => 't',
            ), 
            array(
                'name'   => 'sys_sku',
                'type'   => 'text',
                'search' => 'LIKE',
                'alias'  => 't',
            ),             
            array(
                'name'   => 'gift_sku',
                'type'   => 'text',
                'search' => 'LIKE',
                'alias'  => 't',
            ),
        );
    }

    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions()
    {
        return array('create_time','update_time');
    }

    /**
     * search SQL
     * @return $array
     */
    protected function _setCDbCriteria()
    {
        // $criteria = new CDbCriteria();
        // $criteria->select = '*';
        // return $criteria;
        return null;
    }

    /**
     * @return $array
     */
    public function search()
    {
        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 'id',
        );
        $criteria = null;
        $criteria = $this->_setCDbCriteria();
        $dataProvider = parent::search(get_class($this), $sort, array(), $criteria);

        $data = $this->addition($dataProvider->data);
        $dataProvider->setData($data);
        return $dataProvider;
    }

    /**
     * @return $array
     */
    public function addition($data) {
        $userList = array();
        foreach ($data as $key => $val) {
            if($val['create_user_id']) {
                $userList[$val['create_user_id']] = '';
            }
            if($val['update_user_id']) {
                $userList[$val['update_user_id']] = '';
            }            
        }
        $userList = User::model()->getSpecificPairs(array_keys($userList));
        $idNameArr = self::getAccountList();
        foreach ($data as $key => $val) {
            $data[$key]->account = isset($idNameArr[$val->account_id]) ? $idNameArr[$val->account_id] : '';
            $data[$key]->create_user = isset($userList[$val['create_user_id']]) ?$userList[$val['create_user_id']]:'--';
            $data[$key]->update_user = isset($userList[$val['update_user_id']]) ?$userList[$val['update_user_id']]:'--';
            $data[$key]->update_time = !isset($userList[$val['update_user_id']]) ?'--':$val['update_time'];
        }
        return $data;
    }    


}