<?php

/**
 * @desc Ebay库存置0
 * @author lhy
 * @since 2016-01-21
 */
class EbayZeroStockSku extends EbayModel
{
    /** @var 把库存置为0 */
    const EVENT_ZERO_STOCK = 'zero_stock';

    /** @var 恢复库存 */
    const EVENT_RESTORE_STOCK = 'restore_stock';

    //2016-02-03 add
    public static $accountPairs = array();

    const STATUS_PENGDING = 0;//待处理
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
        return 'ueb_ebay_zero_stock_sku';
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

    /**
     * @desc  检测当天是否已经运行了
     * @param unknown $sellerSku
     * @param unknown $accountID
     * @param number $siteID
     * @return boolean
     */
    public function checkHadRunningForDay($sellerSku, $accountID, $siteID = 0, $productID = NULL)
    {
        $todayStart = date("Y-m-d 00:00:00");
        $todayEnd = date("Y-m-d 23:59:59");
        $command = $this->getDbConnection()
            ->createCommand()
            ->from($this->tableName())
            ->select('id')
            ->where("seller_sku=:seller_sku AND account_id=:account_id AND site_id=:site_id",
                array(':seller_sku' => $sellerSku, ':account_id' => $accountID, ':site_id' => $siteID))
            ->andWhere("create_time>=:begin AND create_time<=:end",
                array(':begin' => $todayStart, ':end' => $todayEnd))
            ->andWhere("status=" . self::STATUS_SUCCESS);
        if ($productID != NULL) {
            $command->andWhere("product_id=:product_id", array(":product_id" => $productID));
        }
        $res = $command->queryRow();
        if ($res)
            return true;
        else
            return false;
    }

    /**
     * @param $productId
     * @param $accountId
     * @param $sku
     * @return bool
     * @author ketu.lai
     * @desc 检测当天是否有该listing更新成功
     */
    public function checkIfRanToday($productId, $accountId, $sku)
    {
        $fromTime = date('Y-m-d 00:00:00');
        $toTime = date('Y-m-d 23:59:59');

        $dateFilter =  array(':begin' => $fromTime, ':end' => $toTime);
        $whereParams = array(
            ':productId' => $productId,
            ':accountId' => $accountId,
            ':sku' => $sku,
            ':status'=> self::STATUS_SUCCESS
        );
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->select('id')
            ->where('product_id=:productId AND account_id=:accountId AND seller_sku=:sku AND status=:status AND is_restore=1', $whereParams)
            ->andWhere("create_time>=:begin AND create_time<=:end", $dateFilter);


        return $queryBuilder->queryRow() ? true : false;
    }

    /**
     * @param array $fields
     * @param $conditions
     * @param array $params
     * @author ketu.lai
     * @desc 根据条件跟新相关数据
     */
    public function updateExistsRecordWithData(array $fields, $conditions, array $params = array())
    {
        $this->getDbConnection()->createCommand()
            ->update(
                $this->tableName(),
                $fields,
                $conditions,
                $params
            );

    }


    /**
     * @desc 获取最新一条
     * @param unknown $condition
     * @param string $params
     * @return mixed
     */
    public function getLastOneByCondition($condition, $params = null)
    {
        return $this->getDbConnection()
            ->createCommand()
            ->from($this->tableName())
            ->select('*')
            ->where($condition, $params)
            ->order("id desc")
            ->queryRow();
    }
    // =========== start: 2016-02-03add search ==================

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
            'seller_sku' => '线上SKU',
            'product_id' => '产品ID',
            'account_id' => '账号',
            'site_id' => '站点',
            'account_name' => '账号名称',
            'type' => '类型',
            'status' => '处理状态',
            'create_time' => '创建时间',
            'msg' => '提示',
            'is_restore' => '是否恢复',
            'restore_time' => '恢复时间',
            'restore_num' => '恢复次数',
            'old_quantity' => Yii::t('ebay', 'Old Quantity'),
            'set_quantity' => Yii::t('ebay', 'Set Quantity'),


        );
    }

    public function getStatusOptions($status = null)
    {
        //@todo 后续语言处理
        $statusOptions = array(
            self::STATUS_PENGDING => '待处理',
            self::STATUS_SUBMITTED => '已提交',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILURE => '失败'
        );
        if ($status !== null)
            return isset($statusOptions[$status]) ? $statusOptions[$status] : '';
        return $statusOptions;
    }


    public function getRestoreStatusOptions($restoreStatus = null)
    {
        $restoreStatusOptions = array(
            0 => '待恢复',
            1 => '恢复成功',
            2 => '恢复失败',
        );
        if ($restoreStatus !== null)
            return isset($restoreStatusOptions[$restoreStatus]) ? $restoreStatusOptions[$restoreStatus] : '';
        return $restoreStatusOptions;
    }

    public function getTypeOptions($type = null)
    {
        //@todo 后续语言处理
        $typeOptions = array(
            0 => '仓库库存<=1',
            1 => '滞销、待清除',
            2 => '欠货待处理',
            3 => 'unkown',
            4 => 'amazon指定listing',
            5 => '手动导入sku',
            6 => '手动更改',
            7 => '年前21号修改',
            8 => '21号到6号修改',
            9 => '自动恢复库存调整' //STORY #2823
        );
        if ($type !== null)
            return isset($typeOptions[$type]) ? $typeOptions[$type] : '';
        return $typeOptions;
    }

    public function addtions($datas)
    {
        if (empty($datas)) return $datas;
        $siteList = EbaySite::getSiteList();
        foreach ($datas as &$data) {
            //账号名称
            $data['account_id'] = self::$accountPairs[$data['account_id']];
            if ($data['status'] == self::STATUS_FAILURE) {
                $data['is_restore'] = '-';
            } else {
                $data['is_restore'] = $this->getRestoreStatusOptions($data['is_restore']);
            }
            //状态
            $data['status'] = $this->getStatusOptions($data['status']);
            //类型
            $data['type'] = $this->getTypeOptions($data['type']);
            $data['product_id'] = "<a href='http://www.ebay.com/itm/" . $data['product_id'] . "' target='__blank'>" . $data['product_id'] . "</a>";

            //站点
            $data['site_id'] = isset($siteList[$data['site_id']]) ? $siteList[$data['site_id']] : '';
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
        $type = Yii::app()->request->getParam('type');
        $status = Yii::app()->request->getParam('status');
        $siteID = Yii::app()->request->getParam('site_id');
        $restoreStatus = Yii::app()->request->getParam('is_restore');
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
                'name' => 'seller_sku',
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
                'data' => $this->getAccountList()
            ),
            array(
                'name' => 'site_id',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => EbaySite::getSiteList(),
                'value' => $siteID
            ),
            array(
                'name' => 'status',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getStatusOptions(),
                'value' => $status
            ),

            array(
                'name' => 'type',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getTypeOptions(),
                'value' => $type
            ),
            array(
                'name' => 'is_restore',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getRestoreStatusOptions(),
                'value' => $restoreStatus
            ),
        );
        return $result;
    }

    /**
     * @desc  获取公司账号
     */
    public function getAccountList()
    {
        if (self::$accountPairs == null)
            self::$accountPairs = self::model('EbayAccount')->getIdNamePairs();
        return self::$accountPairs;
    }

    // =========== end: 2016-02-03add search ==================
}