<?php

/**
 * @desc Ebay帐号共享
 * @author qzz
 * @since 2014-04-13
 */
class EbayAccountShare extends EbayModel
{

    public $status = null;
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName()
    {
        return 'ueb_ebay_account_share';
    }

    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where
     * @param  string $order
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
     * @param  string $order
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

    /*
     * 通过userId获取绑定的帐号关系
     */
    public function getAccountBySellerId($userId)
    {
        $cmd = $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->select('account_id')
            ->where('seller_id = :seller_id', array(':seller_id'=> $userId))
            ->andWhere('end_time > :end_time',array(':end_time'=>date('Y-m-d H:i:s')));

        $list = $cmd->queryAll();
        $data = array();
        foreach($list as $info){
            $data[] = $info['account_id'];
        }
        return $data;

    }
    /**
     * @desc 保存信息
     * @param unknown $params
     * @return boolean|Ambigous <number, boolean>
     */
    public function saveData($params)
    {
        if (empty($params)) return false;
        return $this->getDbConnection()->createCommand()
            ->insert($this->tableName(), $params);
    }

    /**
     * @desc 更新
     * @param unknown $data
     * @param unknown $id
     * @return Ambigous <number, boolean>
     */
    public function updateDataByID($data, $id)
    {
        if (!is_array($id)) $id = array($id);
        return $this->getDbConnection()
            ->createCommand()
            ->update($this->tableName(), $data, "id in(" . implode(",", $id) . ")");
    }

    // =========== start: search ==================

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array();
    }

    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('system', 'No.'),
            'account_id' => '账号',
            'seller_id' => '销售人员',
            'department_id' => '部门',
            'status' => '状态',
            'create_time' => '创建时间',
            'end_time' => '失效时间',
        );
    }



    public function addtions($datas)
    {
        if (empty($datas)) return $datas;

        $accountLists = EbayAccount::model()->getIdNamePairs();
        $departmentLists = EbayAccount::model()->getDepartment();
        foreach ($datas as &$data) {

            $data['account_id'] = isset($accountLists[$data['account_id']])?$accountLists[$data['account_id']]:'-';
            $data['department_id'] = isset($departmentLists[$data['department_id']])?$departmentLists[$data['department_id']]:'-';
            if(strtotime($data['end_time'])>time()){
                $data['status'] = '正常';
            }else{
                $data['status'] = '失效';
            }
        }
        return $datas;
    }


    /**
     * get search info
     */
    public function search()
    {
        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 'id',
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
    public function filterOptions()
    {

        $result = array(
            array(
                'name' => 'account_id',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => EbayAccount::model()->getIdNamePairs(),
            ),
            array(
                'name' => 'department_id',
                'type' => 'dropDownList',
                'data' => EbayAccount::model()->getDepartment(),
                'search' => '=',
            ),
            array(
                'name'      =>  'seller_id',
                'type'      =>  'dropDownList',
                'data'      =>  EbayProductAdd::model()->getCreateUserOptions(),
                'search'    =>  '=',

            ),

        );
        return $result;
    }


    // =========== end: search ==================
}