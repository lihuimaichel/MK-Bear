<?php
/**
 * @desc lazada账号
 * @author hanxy
 * @since 2017-03-07
 */
class PlatformLazadaAccount extends PlatformAccountModel{
	
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
     
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;

    public $account_id;
    public $department_id;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_platform_lazada_account';
    }   
    
    /**
     * @desc 属性翻译
     */
    public function attributeLabels() {
    	return array(
            'status'             => Yii::t('system', 'Use Status'),
            'seller_name'        => '账号名称',
            'account_id'         => '账号名称',
            'short_name'         => Yii::t('system', 'Seller Name'),
            'token_status'       => Yii::t('system', 'Token Status'),
            'token_invalid_time' => Yii::t('system', 'token_expire'),
            'update_time'        => Yii::t('system', 'Update Time'),
            'to_oms_status'      => '同步账号状态',
            'to_oms_time'        => '同步账号时间',
            'api_key'            => 'API KEY',
            'api_url'            => 'API URL',
            'site_id'            => Yii::t('system', 'Site'),
            'department_id'      => Yii::t('system', 'Subordinate Department'),
            'email'              => '邮箱'
    	);
    }

    public function addtions($datas){
        if(empty($datas)) return $datas;
        foreach ($datas as &$data){
            $depID = $this->getDeparment($data['department_id'], 'lazada');
            if(is_array($depID)){
                $data['department_id'] = '';
            }else{
                $data['department_id'] = $depID;
            }
        }
        return $datas;
    }
    
    
    /**
     * get search info
     */
    public function search() {
        $sort = new CSort();
        $sort->attributes = array(
                'defaultOrder'  => 'id',
        );
        $dataProvider = parent::search(get_class($this), $sort, '', $this->setSearchDbCriteria());
        $data = $this->addtions($dataProvider->data);
        $dataProvider->setData($dataProvider->data);
        return $dataProvider;
    }

    /**
     * @desc 设置搜索条件
     * @return CDbCriteria
     */
    public function setSearchDbCriteria(){
        $cdbcriteria = new CDbCriteria();
        $cdbcriteria->select = '*';

        $UserDepartmentIDs = Department::model()->getRelationDepartment();
        if($UserDepartmentIDs){
            $cdbcriteria->addCondition("department_id IN (".$UserDepartmentIDs.")");
        }

        return $cdbcriteria;
    }
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        $status = Yii::app()->request->getParam('status');
        $tokenStatus = Yii::app()->request->getParam('token_status');
        $departmentId = Yii::app()->request->getParam('department_id');
        $result = array(
                array(
                        'name'=>'short_name',
                        'type'=>'text',
                        'search'=>'LIKE',
                        'htmlOption' => array(
                                'size' => '22',
                        )
                ),
                array(
                        'name'=>'status',
                        'type'=>'dropDownList',
                        'search'=>'=',
                        'data'=>$this->getAccountStatus(),
                        'value'=>$status
                ),
                array(
                        'name'=>'token_status',
                        'type'=>'dropDownList',
                        'search'=>'=',
                        'data'=>$this->getAccountStatus(),
                        'value'=>$tokenStatus
                ),
                array(
                        'name' => 'site_id',
                        'type' => 'dropDownList',
                        'data' => LazadaSite::getSiteList(),
                        'search' => '=',
                        'htmlOptions' => array(),
                ),
        );
        return $result;
    }


    /**
     * @desc 获取状态列表
     * @param integer $status
     * @return multitype:
     */
    public static function getStatus($status = null){
        if ($status == 0) {
            echo '<font color="red">失效</font>';
        } else {
            echo '<font color="#33CC00">有效</font>';
        }
    }


    /**
     * @desc 获取同步oms状态列表
     * @param integer $status
     * @return multitype:
     */
    public static function getOmsStatus($status = null){
        if ($status == 0) {
            echo '<font color="red">未同步</font>';
        } else {
            echo '<font color="#33CC00">已同步</font>';
        }
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
     * 是否生效状态
     */
    public static function getAccountStatus($status = null){
        $statusOptions = array(0=>'失效', 1=>'有效');
        if($status !== null){
            return isset($statusOptions[$status])?$statusOptions[$status]:'';
        }
        return $statusOptions;
    }


    /**
     * 账号名称
     */
    public static function getAccount($id = null){
        $accountOptions = array();
        $accountInfo = PlatformLazadaAccountList::model()->getListByCondition('id,account_name','id > 0',array(),'id asc');
        foreach ($accountInfo as $value) {
            $accountOptions[$value['id']] = $value['account_name'];
        }
        if($id !== null){
            return isset($accountOptions[$id])?$accountOptions[$id]:'';
        }
        return $accountOptions;
    }


    /**
     * @desc 定义URL
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/platformaccount/platformlazadaaccount/list');
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


    /**
     * @desc 根据账号ID获取账号信息
     * @param int $id
     */
    public static function getLazadaAccountInfoById($id){
        return PlatformLazadaAccount::model()->getDbConnection()->createCommand()->select('*')->from(self::tableName())->where('id = '.$id)->queryRow();
    }
}