<?php

class ShopeeStockUpdateLog extends ShopeeModel {

    const UPDATE_ACTION_AUTO_SETUP_QTY_WITH_AVAILABLE_QTY = 1;

    const LOG_STATUS_WAITING = 0;
    const LOG_STATUS_SUCCESS = 1;
    const LOG_STATUS_FAILED = 2;

	/**
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_shopee_stock_update_log';
	}

	public static function model($className = __CLASS__) {
		return parent::model($className);		
	}


	public function checkIfRanInThePeriod($listingId, $action =self::UPDATE_ACTION_AUTO_SETUP_QTY_WITH_AVAILABLE_QTY, $timeInterval = '-1 day', $accountId = null)
    {
        $dateTime = new \DateTime();
        $dateTime->modify($timeInterval);

        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->select('id')
            ->where('listing_id=:listingId', array(':listingId'=> $listingId))
            ->andWhere('update_action=:updateAction', array(':updateAction'=>$action ))
            ->andWhere('status='. self::LOG_STATUS_SUCCESS)
            ->andWhere('created_at>=:createdAt',  array(':createdAt'=> $dateTime->format('Y-m-d H:i:s')));
            //->andWhere(array('>', 'created_at', $dateTime->format('Y-m-d H:i:s')));

        return $queryBuilder->queryRow()? true :false;
    }


    public function saveInfo(array $insertData, $updateAction)
    {
        if (!isset($insertData['created_at'])) {
            $dateTime = new \DateTime();
            $insertData['created_at'] = $dateTime->format('Y-m-d H:i:s');
        }
        $insertData['update_action'] = $updateAction;

        $this->getDbConnection()->createCommand()
            ->insert(
                $this->tableName(),
                $insertData
            );
        return $this->getDbConnection()->getLastInsertID();
    }

    public function updateStatus($id, $status, $message = null)
    {
        $updateData = array(
            'status'=> $status,
            'msg'=> $message
        );
        $this->getDbConnection()->createCommand()
            ->update($this->tableName(),
                $updateData,
                'id=:id',
                array(':id'=> $id)
                );
    }


    /**************************************************************/
    /**
     * 列表显示相关函数
     */
    /**************************************************************/
    /**
     * Search Info
     * @param type $model
     * @param type $sort
     * @return \CActiveDataProvider
     */

    public function getStatusText($status)
    {
        $statusText = '';
        switch ($status){
            case self::LOG_STATUS_WAITING:
                $statusText = Yii::t('shopee', 'Waiting');
                break;
            case self::LOG_STATUS_SUCCESS:
                $statusText = Yii::t('shopee', 'Successful');

                break;
            case self::LOG_STATUS_FAILED:
                $statusText = Yii::t('shopee', 'Failed');

                break;
        }
        return $statusText;
    }

    public function getUpdateActionText($action)
    {
        $actionText = '';
        switch ($action){
            case self::UPDATE_ACTION_AUTO_SETUP_QTY_WITH_AVAILABLE_QTY:
                $actionText = Yii::t('shopee', 'Set out of stock if system available stock is lower then 2, otherwise update the available Qty to listing');
                break;

        }
        return $actionText;
    }

    public function search($model = null, $sort = array(), $with = array(), $CDbCriteria = null)
    {

        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 'id',
            'defaultOrderDirection' => 'DESC'
        );

        $criteria = new CDbCriteria();
 /*       $criteria->select = 't.id, t.account_id,  IF(pv.stock=0, 1, 2) AS stock_status, t.site_code, t.listing_id, t.name, t.seller_sku, t.sku, t.main_image_url, t.has_variation, t.status, t.created_at, t.stock, t.price';
        $criteria->join = 'LEFT JOIN ' . ShopeeProductVariation::model()->tableName() . ' AS pv ON (t.id = pv.parent_id)';

        $criteria->group = 't.id';*/

        $accountList = ShopeeAccount::model()->getIdNamePairs();

        $dataProvider = parent::search($this, $sort, $with, $criteria);
        $result = array();
        foreach($dataProvider->data as $key=> $value) {
            $value->account_id = $accountList[$value->account_id];
            $value->status = $this->getStatusText($value->status);
            $value->update_action = $this->getUpdateActionText($value->update_action);


        }

        return $dataProvider;
    }
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
            'id'                            => Yii::t('system', 'No.'),
            'sku'							=>	'SKU',
            'online_sku'					=>	'线上SKU',
            'account_id'					=>	'账号',
            'account_name'					=>	'账号名称',
            'listing_id'=> '产品ID',
            'update_action'							=>	'类型',
            'status'						=>	'处理状态',
            'create_time'					=>	'创建时间',
            'msg'							=>	'提示',
            'is_restored'					=>	'是否恢复',
        );
    }
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        $type = Yii::app()->request->getParam('type');
        $status = Yii::app()->request->getParam('status');
        $restoreStatus = Yii::app()->request->getParam('is_restore');
        $result = array(
            array(
                'name'=>'sku',
                'type'=>'text',
                'search'=>'LIKE',
                'htmlOption' => array(
                    'size' => '22',
                )
            ),
            array(
                'name'=>'online_sku',
                'type'=>'text',
                'search'=>'LIKE',
                'htmlOption' => array(
                    'size' => '22',
                )
            ),
            array(
                'name'=>'listing_id',
                'type'=>'text',
                'search'=>'=',
                'htmlOption' => array(
                    'size' => '22',
                )
            ),
            array(
                'name'=>'account_id',
                'type'=>'dropDownList',
                'search'=>'=',
                'data'=> ShopeeAccount::model()->getIdNamePairs()
            ),

            array(
                'name'=>'status',
                'type'=>'dropDownList',
                'search'=>'=',
                'data'=> array(
                    self::LOG_STATUS_SUCCESS=> '成功',
                    self::LOG_STATUS_WAITING => '待上传',
                    self::LOG_STATUS_FAILED=> '失败'
                ),
                'value'=>$status
            ),

            array(
                'name'=>'update_action',
                'type'=>'dropDownList',
                'search'=>'=',
                'data'=> array(
                    self::UPDATE_ACTION_AUTO_SETUP_QTY_WITH_AVAILABLE_QTY=> $this->getUpdateActionText(self::UPDATE_ACTION_AUTO_SETUP_QTY_WITH_AVAILABLE_QTY)
                ),
                'value'=>$type
            ),

            array(
                'name'=>'is_restored',
                'type'=>'dropDownList',
                'search'=>'=',
               'data'=>  array(
                   0=>'待恢复',
                   1=>'恢复成功',
                   2=>'恢复失败',
               ),
               // 'value'=>$restoreStatus
            ),
        );
        return $result;
    }
}