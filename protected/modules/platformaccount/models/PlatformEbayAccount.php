<?php
/**
 * @desc ebay账号
 * @author hanxy
 * @since 2017-03-11
 */
class PlatformEbayAccount extends PlatformAccountModel{
	
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
     
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;

    /** @var string 错误信息 */
    public $_errorMessage = '';

    public $is_visible;
    public $department_id;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_platform_ebay_account';
    }   
    
    /**
     * @desc 属性翻译
     */
    public function attributeLabels() {
    	return array(
            'status'             => Yii::t('system', 'Use Status'),
            'user_name'          => '账号名称',
            'short_name'         => Yii::t('system', 'Seller Name'),
            'token_status'       => Yii::t('system', 'Token Status'),
            'token_invalid_time' => Yii::t('system', 'token_expire'),
            'update_time'        => Yii::t('system', 'Update Time'),
            'to_oms_status'      => '同步账号状态',
            'to_oms_time'        => '同步账号时间',
            'store_site'         => '店铺站点',
            'department_id'      => Yii::t('system', 'Subordinate Department'),
            'developer_id'       => '开发者账号'
    	);
    }

    public function addtions($datas){
        if(empty($datas)) return $datas;
        foreach ($datas as &$data){
            $ebayDepartmentArr = EbayAccount::model()->getDepartment();
            $depID = isset($ebayDepartmentArr[$data['department_id']])?$ebayDepartmentArr[$data['department_id']]:'';
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
                        'name' => 'store_site',
                        'type' => 'dropDownList',
                        'data' => EbaySite::getSiteList(),
                        'search' => '=',
                        'htmlOptions' => array(),
                ),
        );
        return $result;
    }


    /**
     * @desc 设置错误信息
     * @param sting $message
     */
    public function setErrorMessage($message) {
        $this->_errorMessage .= $message;
    }

    
    /**
     * @desc 获取错误信息
     * @return string
     */
    public function getErrorMessage() {
        return $this->_errorMessage;
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
     * @desc 定义URL
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/platformaccount/platformebayaccount/list');
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
    public static function getEbayAccountInfoById($id){
        return PlatformEbayAccount::model()->getDbConnection()->createCommand()
        ->select('a.user_token,d.appid,d.devid,d.certid,d.max_nums')
        ->from(self::tableName() . ' a')
        ->leftJoin(PlatformEbayDeveloperAccount::model()->tableName() . ' d', "a.developer_id = d.id")
        ->where('a.id = '.$id)
        ->queryRow();
    }


    /**
     * 获取sessionid和调整授权的url地址
     * @return array
     */
    public function getSessionIdAndUrl($accountID, $ruName){
        $data = array();
        $request = new GetSessionIDRequest;
        $request->setAccount($accountID);
        $request->setRuName($ruName);
        $response = $request->setRequest()->sendRequest()->getResponse();
        $sessionID = isset($response->SessionID)?(string)$response->SessionID:null;
        if(!$sessionID){
            $this->setErrorMessage('获取SessionID失败');
            return false;
        }

        $url = $request->_get_ebay_code_url.'&RuName='.$ruName.'&SessID='.$sessionID.'&ruparams=';
        return $data = array('SessionID' => $sessionID, 'url' => $url);
    }
}