<?php

class WishPriceUpdateLog extends WishModel {

    const UPDATE_TYPE_PRICE_UP = 1; //加权平均价增加
    const UPDATE_TYPE_PRICE_DOWN = 2; //加权平均价-
    const UPDATE_TYPE_WEIGHT_UP = 3;
    const UPDATE_TYPE_WEIGHT_DOWN = 4;

    const LOG_STATUS_WAITING = 0;
    const LOG_STATUS_SUCCESS = 1;
    const LOG_STATUS_FAILED = 2;

    public $actionText = '';

	/**
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_wish_price_update_log';
	}

	public static function model($className = __CLASS__) {
		return parent::model($className);		
	}


	public function findInfoCanReUpload($timeInterval = '-1 day', $accountId = null, $maxUploadTimes = 2)
    {
        $dateTime = new \DateTime();
        $dateTime->modify($timeInterval);
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->select('*')
            ->where('upload_status!=:uploadStatus', array(':uploadStatus'=> self::LOG_STATUS_SUCCESS))
            ->andWhere('upload_times < :uploadTimes', array(':uploadTimes'=> $maxUploadTimes))
            ->andWhere('created_at>=:createdAt',  array(':createdAt'=> $dateTime->format('Y-m-d H:i:s')));

        return $queryBuilder->queryAll();
    }


    public function updateExpiredInfo($listingId, $accountId, $timeInterval = '-3 day')
    {
        $dateTime = new \DateTime();
        $currentDate = clone $dateTime;
        $dateTime->modify($timeInterval);

        $updateData = array(
            'upload_status'=> self::LOG_STATUS_FAILED,
            'upload_times'=> 2,
            'updated_at'=> $currentDate->format('Y-m-d H:i:s'),
            'message'=> Yii::t('wish', 'Has latest info within 3 days')
        );
        $this->getDbConnection()->createCommand()->update(
            $this->tableName(),
            $updateData,
            'listing_id =:listingId and account_id =:accountId
             AND upload_status !=:uploadStatus
             AND created_at >= :createdAt',
            array(
                ':listingId'=> $listingId,
                'accountId'=> $accountId,
                ':uploadStatus'=> self::LOG_STATUS_SUCCESS,
                ':createdAt'=> $dateTime->format('Y-m-d H:i:s')
            )
        );

    }

    /**
     * @param $uploadLog update log data array
     */
	public function reUploadUpdateInfo($uploadLog)
    {
        $lastUserId = Yii::app()->user->id;
        $logId = $uploadLog['id'];
        try {

            WishVariants::model()->updateVariantDataOnline($uploadLog['online_sku'], $uploadLog['account_id'], array('price'
            => $uploadLog['new_listing_price']));
            $this->updateStatus($logId, self::LOG_STATUS_SUCCESS, 'successful', true, $lastUserId);

        } catch (\Exception $e) {
            $this->updateStatus($logId, self::LOG_STATUS_FAILED, $e->getMessage(),
                true, $lastUserId);
            throw $e;
        }
    }

	public function checkIfRanInThePeriod($listingId, $timeInterval = '-1 day', $accountId = null)
    {
        $dateTime = new \DateTime();
        $dateTime->modify($timeInterval);

        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->select('id')
            ->where('listing_id=:listingId', array(':listingId'=> $listingId))
            //->andWhere('upload_type=:updateAction', array(':updateAction'=>$action ))
           // ->andWhere('upload_status='. self::LOG_STATUS_SUCCESS)
            ->andWhere('created_at>=:createdAt',  array(':createdAt'=> $dateTime->format('Y-m-d H:i:s')));
        return $queryBuilder->queryRow()? true :false;
    }


    public function saveInfo(array $insertData, $updateType = null)
    {
        if (!isset($insertData['created_at'])) {
            $dateTime = new \DateTime();
            $insertData['created_at'] = $dateTime->format('Y-m-d H:i:s');
        }
        if ($updateType) {
            $insertData['update_type'] = $updateType;
        }

        $this->getDbConnection()->createCommand()
            ->insert(
                $this->tableName(),
                $insertData
            );
        return $this->getDbConnection()->getLastInsertID();
    }

    public function updateStatus($id, $status, $message = '', $incUploadTimes = false, $updateUser = null)
    {
        $dateTime = new \DateTime();
        $updateData = array(
            'upload_status'=> $status,
            'message'=> $message,
            'updated_at'=>$dateTime->format('Y-m-d H:i:s')
        );
        if ($incUploadTimes) {
            $updateData['upload_times']= new CDbExpression('upload_times+1');
        }
        if (null !== $updateUser) {
            $updateData['last_user_id'] = $updateUser;
        }
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
                $statusText = Yii::t('wish', 'Waiting');
                break;
            case self::LOG_STATUS_SUCCESS:
                $statusText = Yii::t('wish', 'Successful');

                break;
            case self::LOG_STATUS_FAILED:
                $statusText = Yii::t('wish', 'Failed');

                break;
        }
        return $statusText;
    }

    public function getUpdateTypeText($action)
    {
        $actionText = '';
        switch ($action){
            case self::UPDATE_TYPE_PRICE_UP:
                $actionText = Yii::t('wish', 'Update price up');
                break;
            case self::UPDATE_TYPE_PRICE_DOWN:
                $actionText = Yii::t('wish', 'Update price down');
                break;
            case self::UPDATE_TYPE_WEIGHT_UP:
                $actionText = Yii::t('wish', 'Update price weight up');
                break;
            case self::UPDATE_TYPE_WEIGHT_DOWN:
                $actionText = Yii::t('wish', 'Update price weight down');
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
        $accountList = WishAccount::model()->getIdNamePairs();
        $sellerList = User::model()->getWishUserList();
        $dataProvider = parent::search($this, $sort, $with, $criteria);
        foreach($dataProvider->data as $key=> $value) {
            $value->seller_name = isset($sellerList[$value->seller_name])? $sellerList[$value->seller_name]:"";
            $value->account_id = isset($accountList[$value->account_id])?$accountList[$value->account_id]:"";
            $value->update_type = $this->getUpdateTypeFullText($value);
            $value->actionText = $this->getActionText($value);
            $value->upload_status = $this->getStatusText($value->upload_status);

            $value->new_profit_rate = $value->new_profit_rate * 100 . '%';
            $value->old_profit_rate = $value->old_profit_rate * 100 . '%';
            if ($value->last_user_id) {
                $user = User::model()->findByPk($value->last_user_id);
                $value->last_user_id  = $user->user_full_name;
            }

        }

        return $dataProvider;
    }

    public function getUpdateTypeFullText($info)
    {
        $fullText = '';
        $fullText .= $this->getUpdateTypeText($info->update_type);
        $fullText .='<br>';
        switch ($info->update_type) {
            case self::UPDATE_TYPE_PRICE_UP:
            case self::UPDATE_TYPE_PRICE_DOWN:
                $fullText .= Yii::t('wish', 'Old Product Cost:');
                $fullText .= $info->old_value;
                $fullText .='<br>';
                $fullText .= Yii::t('wish', 'New Product Cost:');
                $fullText .= $info->new_value;

                break;
            case self::UPDATE_TYPE_WEIGHT_UP:
            case self::UPDATE_TYPE_WEIGHT_DOWN:
                $fullText .= Yii::t('wish', 'Old Product Weight:');
                $fullText .= $info->old_value;
                $fullText .='<br>';
                $fullText .= Yii::t('wish', 'New Product Weight:');
                $fullText .= $info->new_value;
                break;
        }

        return $fullText;
    }

    public function getActionText($value)
    {
        $dateTime = new \DateTime();
        $createdAt = new \DateTime($value->created_at);
        $dateInterval = $dateTime->diff($createdAt);

        if ($dateInterval->d < 3 && $value->upload_status != self::LOG_STATUS_SUCCESS  && $value->upload_times < 2
        ) {
            return '<a target="ajaxTodo" href="'.Yii::app()->createUrl('wish/wishpriceupdatelog/reuploadpriceupdate',
                    array
                ('id'=>
                    $value->id)).'">'
                .Yii::t('wish', 'Reload')
                .'</a>';
        }
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
            'update_type'							=>	'类型',
            'upload_status'						=>	'处理状态',
            'created_at'					=>	'创建时间',
            'updated_at'					=>	'更新时间',
            'upload_times'=> '上传次数',
            'action'=> '操作',
            'seller_name'=> '销售人员',
            'last_user_id'=> '最后操作人员',
            'message'							=>	'提示',
            'old_listing_price'					=>	Yii::t('wish', 'Old Listing Price'),
            'old_profit_rate'					=>	Yii::t('wish', 'Old Listing Profit'),
            'new_listing_price'					=>	Yii::t('wish', 'New Listing Price'),
            'new_profit_rate'					=>	Yii::t('wish', 'New Listing Profit'),
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
                'data'=> WishAccount::model()->getIdNamePairs()
            ),

            array(
                'name'=>'upload_status',
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
                'name'=>'update_type',
                'type'=>'dropDownList',
                'search'=>'=',
                'data'=> array(
                    self::UPDATE_TYPE_PRICE_UP=> $this->getUpdateTypeText(self::UPDATE_TYPE_PRICE_UP),
                    self::UPDATE_TYPE_PRICE_DOWN=> $this->getUpdateTypeText(self::UPDATE_TYPE_PRICE_DOWN),
                    self::UPDATE_TYPE_WEIGHT_UP=> $this->getUpdateTypeText(self::UPDATE_TYPE_WEIGHT_UP),
                    self::UPDATE_TYPE_WEIGHT_DOWN=> $this->getUpdateTypeText(self::UPDATE_TYPE_WEIGHT_DOWN),
                ),
                'value'=>$type
            ),


        );
        return $result;
    }
}