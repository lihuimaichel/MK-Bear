<?php

class ShopeeStockExcludeList extends ShopeeModel {

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

	/**
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_shopee_offline_exclude_list';
	}

	public static function model($className = __CLASS__) {
		return parent::model($className);		
	}


	public function getStatusOptions()
    {
        return array(
            self::STATUS_ENABLED,
            self::STATUS_DISABLED
        );
    }

	public function checkExists($sku, $accountId, $status = null) {
	    $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->select('id')
            ->where('sku=:sku', array(':sku'=> $sku))
            ->andWhere('account_id=:account', array(':account'=> $accountId));
        if (null !== $status && in_array($status, $this->getStatusOptions())) {
            $queryBuilder->andWhere('status=:status', array(
                ':status'=> $status
            ));
        }

	    return $queryBuilder->queryRow()? true:false;

    }

    public function saveInfo($data)
    {
        $dateTime = new \DateTime();
        $data['created_at'] = $dateTime->format('Y-m-d H:i:s');
        $data['created_by'] = Yii::app()->user->id;
        $this->getDbConnection()->createCommand()
            ->insert($this->tableName(), $data);

        return $this->getDbConnection()->getLastInsertID();
    }

    public function deleteInfo($id)
    {
        $this->getDbConnection()->createCommand()->delete(
            $this->tableName(),
            'id=:id',
            array(':id'=> $id)
        );
    }


    public function updateStatus($id, $status)
    {
        $dateTime = new \DateTime();
        $updateData = array(
            'status'=> $status,
            'updated_at'=> $dateTime->format('Y-m-d H:i:s')
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
            case self::STATUS_ENABLED:
                $statusText = Yii::t('shopee', 'Enable');
                break;
            case self::STATUS_DISABLED:
                $statusText = Yii::t('shopee', 'Disabled');

                break;
        }
        return $statusText;
    }


    public function search($model = null, $sort = array(), $with = array(), $CDbCriteria = null)
    {

        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 'id',
            'defaultOrderDirection' => 'DESC'
        );

        $criteria = new CDbCriteria();


        $accountList = ShopeeAccount::model()->getIdNamePairs();

        $dataProvider = parent::search($this, $sort, $with, $criteria);
        $result = array();
        foreach($dataProvider->data as $key=> $value) {
            $value->account_id = $accountList[$value->account_id];
            $value->status = $this->getStatusText($value->status);
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
                    self::STATUS_DISABLED=>  $this->getStatusText(self::STATUS_DISABLED),
                    self::STATUS_ENABLED => $this->getStatusText(self::STATUS_ENABLED),
                ),
                'value'=>$status
            ),

        );
        return $result;
    }
}