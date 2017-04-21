<?php
/**
 * @desc amazon账号
 * @author hanxy
 * @since 2017-03-09
 */
class PlatformAmazonAccount extends PlatformAccountModel{
	
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
     
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;

    const SITE_CA = 'ca';
    const SITE_DE = 'de';
    const SITE_ES = 'es';
    const SITE_FR = 'fr'; 
    const SITE_IN = 'in';
    const SITE_IT = 'it';
    const SITE_JP = 'jp';
    const SITE_UK = 'uk';
    const SITE_US = 'us';

    public $is_visible;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_platform_amazon_account';
    }   
    
    /**
     * @desc 属性翻译
     */
    public function attributeLabels() {
    	return array(
            'status'             => Yii::t('system', 'Use Status'),
            'account_name'       => '账号名称',
            'country_code'       => Yii::t('amazon_product', 'Site'),
            'token_status'       => Yii::t('system', 'Token Status'),
            'token_invalid_time' => Yii::t('system', 'token_expire'),
            'update_time'        => Yii::t('system', 'Update Time'),
            'to_oms_status'      => '同步账号状态',
            'to_oms_time'        => '同步账号时间',
            'merchant_id'        => 'Merchant ID',
            'access_key'         => 'AWS Access Key ID',
            'secret_key'         => 'Secret Key',
            'department_id'      => Yii::t('system', 'Subordinate Department'),
    	);
    }

    public function addtions($datas){
        if(empty($datas)) return $datas;
        foreach ($datas as &$data){
            $depID = $this->getDeparment($data['department_id'], 'amazon');
            if(is_array($depID)){
                $data['department_id'] = '';
            }else{
                $data['department_id'] = $depID;
            }

            $data['is_visible'] = 0;
            if($data['status'] == 1){
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
                // array(
                //         'name' => 'site_id',
                //         'type' => 'dropDownList',
                //         'data' => LazadaSite::getSiteList(),
                //         'search' => '=',
                //         'htmlOptions' => array(),
                // ),
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
     * @desc 定义URL
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/platformaccount/platformamazonaccount/list');
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
    public static function getAmazonAccountInfoById($id){
        return PlatformAmazonAccount::model()->getDbConnection()->createCommand()->select('*')->from(self::tableName())->where('id = '.$id)->queryRow();
    }


    /**
     * @desc 获取站点列表
     */
    public static function getSiteList($key = null){
        $siteList = array(
            self::SITE_CA => Yii::t('amazon_product', 'CA Site'),
            self::SITE_DE => Yii::t('amazon_product', 'DE Site'),
            self::SITE_ES => Yii::t('amazon_product', 'ES Site'),
            self::SITE_FR => Yii::t('amazon_product', 'FR Site'),
            self::SITE_IN => Yii::t('amazon_product', 'IN Site'),
            self::SITE_IT => Yii::t('amazon_product', 'IT Site'),
            self::SITE_JP => Yii::t('amazon_product', 'JP Site'),
            self::SITE_UK => Yii::t('amazon_product', 'UK Site'),
            self::SITE_US => Yii::t('amazon_product', 'US Site'),
        );
        if (!is_null($key) && array_key_exists($key, $siteList))
            return $siteList[$key];
        return $siteList;
    }
}