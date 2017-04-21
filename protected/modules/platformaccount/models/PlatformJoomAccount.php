<?php
/**
 * @desc joom账号
 * @author hanxy
 * @since 2017-03-07
 */
class PlatformJoomAccount extends PlatformAccountModel{
	
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
     
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;

    public $refresh_token_status;
    public $redirect_uri;
    public $is_visible;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_platform_joom_account';
    }   
    
    /**
     * @desc 属性翻译
     */
    public function attributeLabels() {
    	return array(
            'status'               => Yii::t('system', 'Use Status'),
            'account'              => '账号名称',
            'account_name'         => Yii::t('system', 'Seller Name'),
            'token_status'         => Yii::t('system', 'Token Status'),
            'token_invalid_time'   => Yii::t('system', 'token_expire'),
            'update_time'          => Yii::t('system', 'Update Time'),
            'to_oms_status'        => Yii::t('system', 'To OMS Status'),
            'to_oms_time'          => Yii::t('system', 'To OMS Time'),
            'department_id'        => Yii::t('system', 'Subordinate Department'),
            'refresh_token_status' => 'refresh token状态',
            'secret_key'           => 'App Secret',
    	);
    }

    public function addtions($datas){
        if(empty($datas)) return $datas;
        foreach ($datas as &$data){
            $depID = $this->getDeparment($data['department_id'], 'joom');
            if(is_array($depID)){
                $data['department_id'] = '';
            }else{
                $data['department_id'] = $depID;
            }

            $data['is_visible'] = 0;
            if(($data['token_status'] == 0 || strtotime($data['token_invalid_time']) <= time()) && $data['status'] == 1){
                $data['is_visible'] = 1;
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
        $dataProvider->setData($data);
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
                        'name'=>'account_name',
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
                        'name'=>'department_id',
                        'type'=>'dropDownList',
                        'search'=>'=',
                        'data'=>$this->getDeparment(null, 'joom'),
                        'value'=>$departmentId
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
        return Menu::model()->getIdByUrl('/platformaccount/platformjoomaccount/list');
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
     * @desc帐号授权第一步获取CODE
     * @return boolean
     */
    public function joomAccountAuthorize($accountId, $redirectUri){
        $request = new GetJoomAccountCodeRequest();
        $request->setRedirectUri($redirectUri);
        $response = $request->setAccount($accountId)->setRequest();
        return $response;
    }


    /**
     * 验证access_token是否有效
     */
    public function isAccessTokenEffective($accountID){
        $isEffective = false;
        $request = new JoomListAllProductsRequest();
        $request ->setAccount($accountID);
        $request ->setStartIndex(0);
        $request ->setLimit(1);
        $request->setSinceTime(date('Y-m-d', strtotime('-1 days')));
        $response = $request->setRequest()->sendRequest()->getResponse();
        if($request->getIfSuccess()){
            $isEffective = true;
        }

        return $isEffective;
    }


    /**
     * @desc 根据账号ID获取账号信息
     * @param int $id
     */
    public static function getJoomAccountInfoById($id){
        return PlatformJoomAccount::model()->getDbConnection()->createCommand()->select('*')->from(self::tableName())->where('id = '.$id)->queryRow();
    }
}