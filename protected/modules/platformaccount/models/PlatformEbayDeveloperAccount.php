<?php
/**
 * @desc ebay开发者账号
 * @author hanxy
 * @since 2017-03-06
 */
class PlatformEbayDeveloperAccount extends PlatformAccountModel{
	
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
     
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_platform_ebay_developer_account';
    }   
    
    /**
     * @desc 属性翻译
     */
    public function attributeLabels() {
    	return array(
            'status'       => Yii::t('system', 'Use Status'),
            'account_name' => '账号名称',
            'appid'        => 'APPID',
            'devid'        => 'DEVID',
            'certid'       => 'CERTID',
            'ru_name'      => 'RUNAME',
            'max_nums'     => '调用次数',
            'create_time'  => '创建时间',
            'modify_time'  => '更新时间'
    	);
    }

    public function addtions($datas){
        if(empty($datas)) return $datas;
        // foreach ($datas as &$data){
            
        // }
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
        $dataProvider = parent::search(get_class($this), $sort);
        $data = $this->addtions($dataProvider->data);
        $dataProvider->setData($data);
        return $dataProvider;
    }
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        $status = Yii::app()->request->getParam('status');
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
                        'data'=>$this->getDeveloperAccountStatus(),
                        'value'=>$status
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
    public static function getDeveloperAccountStatus($status = null){
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
        return Menu::model()->getIdByUrl('/platformaccount/platformebaydeveloperaccount/list');
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
    public static function getWishAccountInfoById($id){
        return PlatformWishAccount::model()->getDbConnection()->createCommand()->select('*')->from(self::tableName())->where('id = '.$id)->queryRow();
    }


    public function getDeveloperAccountList($ID = null)
    {

        $data = array();
        $command = $this->getDbConnection()->createCommand()
            ->from(self::tableName())
            ->select("id, account_name");
        if (!empty($siteID)) {
            $command->where("id = :id", array(':id' => $ID));
        }
        $res = $command->queryAll();
        if (!empty($res)) {
            foreach ($res as $row)
                $data[$row['id']] = $row['account_name'];
        }
        return $data;
    }
}