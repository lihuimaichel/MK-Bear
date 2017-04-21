<?php

/**
 * @desc pm库存记录
 * @author qzz
 * @since 2017-04-06
 */
class PriceministerStockLog extends PriceministerModel
{
    const STATUS_PENDING = 0;//待处理
    const STATUS_SUBMITTED = 1;//已提交
    const STATUS_SUCCESS = 2;//成功
    const STATUS_FAILURE = 3;//失败

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
        return 'ueb_pm_stock_log';
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
            'sku' => 'SKU',
            'product_id' => '产品ID',
            'account_id' => '账号',
            'status' => '处理状态',
            'create_time' => '创建时间',
            'msg' => '提示',
            'old_quantity' => '原库存',
            'set_quantity' => '现库存',
            'create_user_id' => '操作人',
            'import_id' => 'importID',
            'update_time' => '更新时间',
        );
    }

    public function getStatusOptions($status = null)
    {
        //@todo 后续语言处理
        $statusOptions = array(
            self::STATUS_PENDING => '待处理',
            self::STATUS_SUBMITTED => '已提交',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILURE => '失败'
        );
        if ($status !== null)
            return isset($statusOptions[$status]) ? $statusOptions[$status] : '';
        return $statusOptions;
    }


    public function addtions($datas)
    {
        if (empty($datas)) return $datas;
        $sellerUserList = User::model()->getPairs();
        $accountList = PriceministerAccount::getIdNamePairs();
        foreach ($datas as &$data) {
            $data['status'] = $this->getStatusOptions($data['status']);
            $data['account_id'] = isset($accountList[$data['account_id']]) ? $accountList[$data['account_id']] : '-';
            $data['create_user_id'] =  $sellerUserList && isset($sellerUserList[$data['create_user_id']]) ? $sellerUserList[$data['create_user_id']] : '';
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
        $status = Yii::app()->request->getParam('status');
        $result = array(
            array(
                'name' => 'sku',
                'type' => 'text',
                'search' => 'LIKE',
                'htmlOption' => array(
                    'size' => '22',
                )
            ),
            array(
                'name' => 'product_id',
                'type' => 'text',
                'search' => '=',
                'htmlOption' => array(
                    'size' => '22',
                )
            ),
            array(
                'name' => 'account_id',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => PriceministerAccount::getIdNamePairs(),
            ),
            array(
                'name' => 'status',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getStatusOptions(),
                'value' => $status
            ),

        );
        return $result;
    }

    // =========== end: search ==================
}