<?php

class WishProductUpdate extends WishModel
{
    const UPDATE_INFO_STATUS_WAIT = 0;
    const UPDATE_INFO_STATUS_RUNNING = 1;
    const UPDATE_INFO_STATUS_SUCCESS = 2;
    const UPDATE_INFO_STATUS_FAILED = 3;

    // virtual prototype for search listing page
    public $detail = array();
    public $main_upload_status = null;
    public $actionText = '';

    protected $allowUpdatedField = array(
       // 'price',
        'inventory',
        'msrp',
        'shipping'
    );


    public function tableName()
    {
        return 'ueb_wish_product_update';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function checkAllowToUpdate($field)
    {
        return in_array($field, $this->allowUpdatedField) ? true : false;
    }

    public function deleteInfo($id)
    {
        $transaction = Yii::app()->db->beginTransaction();
        try {
            WishProductVariantsUpdate::model()->deleteInfoByParent($id);
            $this->getDbConnection()->createCommand()
                ->delete(
                    $this->tableName(),
                    'id=:id',
                    array(':id' => $id)
                );
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }


    public function loadInfo($id)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->select('*')
            ->from($this->tableName() . ' AS p')
            // ->leftJoin(WishProductVariantsUpdate::model()->tableName(). ' AS v', 'p.id = v.parent_id')
            ->where('p.id=:id', array(':id' => $id));
        return $queryBuilder->queryRow();
    }


    public function saveInfo($listingId, $accountId, $listingData)
    {
        $dbConnection = Yii::app()->db;
        $transaction = $dbConnection->beginTransaction();

        try {
            $variants = array();
            if (isset($listingData['variants'])) {
                $variants = $listingData['variants'];
                unset($listingData['variants']);
            }

            $info = $this->findByAttributes(array(
                'listing_id' => $listingId,
                'account_id' => $accountId,
                'upload_status' => new CDbExpression('!=' . self::UPDATE_INFO_STATUS_SUCCESS) //array('!='=> WishProductUpdate::UPDATE_INFO_STATUS_SUCCESS)
            ));
            $dateTime = new \DateTime();
            if (!$info) {
                $listingData['account_id'] = $accountId;
                $listingData['listing_id'] = $listingId;
                $listingData['create_user_id'] = Yii::app()->user->Id;
                $listingData['create_time'] = $dateTime->format('Y-m-d H:i:s');
                $this->getDbConnection()->createCommand()->insert($this->tableName(), $listingData);
                $infoId = $this->getDbConnection()->getLastInsertID();
            } else {
                $listingData['update_user_id'] = Yii::app()->user->Id;
                $listingData['update_time'] = $dateTime->format('Y-m-d H:i:s');
                $this->getDbConnection()->createCommand()->update($this->tableName(),
                    $listingData,
                    'listing_id=:listingId AND account_id=:accountId',
                    array(
                        ':listingId' => $listingId,
                        ':accountId' => $accountId,
                    ));

                $infoId = $info['id'];
            }

            // save variants data
            foreach ($variants as $variant) {
                WishProductVariantsUpdate::model()->saveInfo($infoId, $variant);
            }

        } catch (\Exception $e) {
            $transaction->rollback();
            #echo $e->getMessage();
            throw  $e;
        }
    }

    public function updateInfoStatus($id, $status, $IncreaseUploadTimes = false)
    {
        $dateTime = new \DateTime();
        $fields = array(
            'upload_status' => $status,
            //'upload_times' => new CDbExpression( 'upload_times+1'),
            'last_upload_time' => $dateTime->format('Y-m-d H:i:s')
        );

        if ($IncreaseUploadTimes) {
            $fields['upload_times'] = new CDbExpression('upload_times+1');
        }

        $this->getDbConnection()->createCommand()->update($this->tableName(),
            $fields,
            'id=:id',
            array(':id' => $id)
        );

    }


    /**
     * @param $id update表id
     */
    public function uploadInfo($id)
    {

        $info = $this->loadInfo($id);
        if (!$info) {
            throw new \Exception('Update not found');
        }
        $infoId = $info['id'];
        $parentId = $info['id'];
        $accountId = $info['account_id'];
        $listingId = $info['listing_id'];

        // load current wish listing info

        $wishListing = WishListing::model()->getListingInfoByListingId($listingId);
        if (!$wishListing) {
            throw new \Exception('Wish Listing info not found');
        }
        $currentProductData = array(
            'name' => $wishListing['name'],
            'description' => $wishListing['description'],
            'tags' => $wishListing['tags'],
            'brand' => $wishListing['brand'],
            'main_image' => $wishListing['main_image'],
            'extra_images' => $wishListing['extra_images']
        );

        $wishVariantListings = WishVariants::model()->getWishProductVarantListByProductId($listingId);


        $currentVariantData = array();
        foreach ($wishVariantListings as $listing) {
            $currentVariantData[$listing['online_sku']] = array(
                'sku' => $listing['sku'],
                'color' => $listing['color'],
                'size' => $listing['size'],
                'inventory' => $listing['inventory'],
                'price' => $listing['price'],
                'shipping' => $listing['shipping'],
                'msrp' => $listing['msrp'],
                //'main_image'=>  $variant['main_image'],
            );
        }


        $this->updateInfoStatus($infoId, self::UPDATE_INFO_STATUS_RUNNING, true);

        $savedProductData = array(
            'name' => $info['name'],
            'description' => $info['description'],
            'tags' => $info['tags'],
            'brand' => $info['brand']
        );
        $mainProductData = array(
            'name' => '',
            'description' => '',
            'tags' => '',
            'brand' => '',
            'main_image' => '',
            'extra_images' => ''
        );

        $mainProductData = array_diff_assoc($savedProductData, $mainProductData);

        if (isset($mainProductData['main_image'])) {
            $request = new RemoveExtraImagesRequest();
            $request->setAccount($accountId)
                ->setItemId($listingId)
                ->setRequest()
                ->sendRequest()
                ->getResponse();

            if (!$request->getIfSuccess()) {
                $this->updateInfoStatus($infoId, self::UPDATE_INFO_STATUS_FAILED);
                throw new \Exception($request->getErrorMsg());
            }
            WishProductUpdateLog::model()->saveFieldLog($parentId, WishProductUpdateLog::UPDATE_ACTION_ERASE_IMAGES, null, null);
        }

        $request = new UpdateProductRequest();
        $request->setAccount($accountId)
            ->setItemId($listingId)
            ->setProductData($mainProductData)
            ->setRequest()->sendRequest()
            ->getResponse();
        if (!$request->getIfSuccess()) {
            $this->updateInfoStatus($infoId, self::UPDATE_INFO_STATUS_FAILED);
            throw new \Exception('Update product failed' . $request->getErrorMsg());
        }
        foreach ($mainProductData as $field => $data) {
            WishProductUpdateLog::model()->saveFieldLog($parentId, WishProductUpdateLog::UPDATE_ACTION_MAIN_FIELD_UPDATE, $field, $data, $currentProductData[$field]);
        }
        
        $variants = WishProductVariantsUpdate::model()->loadVariants($parentId);

        foreach ($variants as $variant) {

            WishProductVariantsUpdate::model()->updateInfoStatus($variant['id'],
                WishProductVariantsUpdate::UPDATE_VARIANT_INFO_STATUS_RUNNING, true);
            try {
                $action = $variant['upload_action'];

                $variantData = array(
                    'sku' => $variant['online_sku'],
                    'color' => $variant['color'],
                    'size' => $variant['size'],
                    'inventory' => $variant['inventory'],
                    'price' => $variant['price'],
                    'shipping' => $variant['shipping'],
                    'msrp' => $variant['msrp'],
                    //'main_image'=>  $variant['main_image'],
                );

                switch ($action) {
                    case WishProductVariantsUpdate::VARIANT_ACTION_CREATE:
                        // set parent sku to create action;
                        $variantData['parent_sku'] = $variant['parent_sku'];

                        $request = new CreateProductVariantRequest();
                        $request->setAccount($accountId);

                        $request->setUploadData($variantData)
                            ->setRequest()
                            ->sendRequest()
                            ->getResponse();

                        if (!$request->getIfSuccess()) {
                            throw new \Exception('Create product variant error' . $request->getErrorMsg());
                        }

                        WishProductUpdateLog::model()->saveFieldLog($parentId, WishProductUpdateLog::UPDATE_ACTION_VARIANT_CREATE, null,
                            \json_encode($variantData)
                        );


                        break;

                    case WishProductVariantsUpdate::VARIANT_ACTION_UPDATE:
                        //unset($variantData['sku']);
                        $request = new UpdateProductVariantRequest();
                        $request->setAccount($accountId);

                        //$request->set
                        $request->setSku($variantData['online_sku'])
                            ->setVariantData($variantData)
                            ->setRequest()
                            ->sendRequest()
                            ->getResponse();

                        if (!$request->getIfSuccess()) {
                            throw new \Exception('Update product variant error' . $request->getErrorMsg());
                        }

                        WishProductUpdateLog::model()->saveFieldLog($parentId, WishProductUpdateLog::UPDATE_ACTION_VARIANT_FIELD_UPDATE, null,
                            \json_encode($variantData),
                            \json_encode($currentVariantData[$variant['online_sku']])
                        );

                        break;


                    case WishProductVariantsUpdate::VARIANT_ACTION_DISABLE:
                        break;

                    default:
                        throw new \Exception('No action set for this variant');
                        break;
                }
                WishProductVariantsUpdate::model()->updateInfoStatus($variant['id'],
                    WishProductVariantsUpdate::UPDATE_VARIANT_INFO_STATUS_SUCCESS);

            } catch (\Exception $e) {
                WishProductVariantsUpdate::model()->updateInfoStatus($variant['id'],
                    WishProductVariantsUpdate::UPDATE_VARIANT_INFO_STATUS_FAILED);
                throw $e;
            }

        }
        $this->updateInfoStatus($infoId, self::UPDATE_INFO_STATUS_SUCCESS);

    }


    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param $uploadStatus
     */
    public function getUploadStatusText($uploadStatus)
    {
        $statusText = '';

        switch ($uploadStatus) {
            case self::UPDATE_INFO_STATUS_WAIT :
                $statusText = Yii::t('wish', 'Update is waiting');
                break;

            case self::UPDATE_INFO_STATUS_RUNNING :
                $statusText = Yii::t('wish', 'Update is running');

                break;
            case self::UPDATE_INFO_STATUS_SUCCESS :
                $statusText = Yii::t('wish', 'Update is succeed');

                break;
            case self::UPDATE_INFO_STATUS_FAILED :
                $statusText = Yii::t('wish', 'Update is failed');
                break;

        }

        return $statusText;
    }

    /**
     * Search Info
     * @param type $model
     * @param type $sort
     * @return \CActiveDataProvider
     */
    public function search($model = null, $sort = array(), $with = array(), $CDbCriteria = null)
    {

        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 'id',
            'defaultOrderDirection' => 'DESC'
        );
        $criteria = new CDbCriteria();
        $dataProvider = parent::search($this, $sort, $with, $criteria);

        $additionalData = array();

        $variantsUpdateModel = WishProductVariantsUpdate::model();

        foreach ($dataProvider->data as $key => $value) {

            $value->actionText = $this->getActionText($value);
            $variants = WishProductVariantsUpdate::model()->loadVariants($value->id);

            foreach ($variants as $variant) {
                $value->detail[] = array(
                    'sku' => $variant['sku'],
                    'upload_action' => $variantsUpdateModel->getUploadActionText($variant['upload_action']),
                    'upload_status' => $variantsUpdateModel->getUploadStatusText($variant['upload_status']),
                    'main_upload_status' => $variant['upload_status'],
                    'online_sku' => $variant['online_sku']
                );
            }
            $value->upload_status = $this->getUploadStatusText($value->upload_status);
            $additionalData[$key] = $value;
        }

        $dataProvider->data = $additionalData;
        return $dataProvider;
    }


    public function getActionText($info)
    {
        $str = '';
        if (in_array($info->upload_status, array(
            self::UPDATE_INFO_STATUS_FAILED,
            self::UPDATE_INFO_STATUS_WAIT
        ))) {
            $str .= '<a title="Confirm to upload?" target="ajaxTodo" href="' . Yii::app()->createUrl('wish/wishproductupdate/upload', array
                ('id' =>
                        $info->id))
                . '">' .
                Yii::t
                ('wish',
                    'Upload Now')
                . '</a>';
            $str .= '<br>';
            $str .= '<a  target="navTab" href="' . Yii::app()->createUrl('wish/wishproductupdate/edit', array('id' =>
                    $info->id))
                . '">' .
                Yii::t
                ('wish',
                    'Edit')
                . '</a>';
        }

        return $str;
    }

    /**
     * @return array 列表header
     */
    public function attributeLabels()
    {
        return array(
            'listing_id' => Yii::t("wish", 'Listing ID'),
            'sku' => Yii::t('wish', 'SKU'),
            'upload_status' => Yii::t('wish', 'Status'),
            'upload_times' => Yii::t('wish', 'Upload times'),
            'last_upload_time' => Yii::t('wish', 'Last upload time'),
            'name'=> Yii::t('wish', 'Name'),
            'upload_times'=> Yii::t('wish', 'Upload times'),
            'status'=> Yii::t('wish', 'Upload status'),
            'last_upload_time'=> Yii::t('wish', 'Last upload time'),
            'create_time'=> Yii::t('wish', 'Create time'),
            'create_user_id'=> Yii::t('wish', 'Create user'),
            'online_sku'=> Yii::t('wish', 'Online SKU'),
            'upload_action'=> Yii::t('wish', 'Upload action'),
            'actionText'=> Yii::t('wish', 'Action text')
        );
    }

    /**
     * 列表相关参数
     */

    public function filterOptions()
    {
        return array(
            array(
                'name' => 'listing_id',
                'alias' => 't',
                'type' => 'text',
                'label' => 'listing_id',
                'search' => '=',
                'htmlOption' => array(
                    'size' => '22',
                )
            ),
            array(
                'name' => 'upload_status',
                'type' => 'dropDownList',
                'alias' => 't',
                'search' => '=',
                //'data'=> array_flip($this->statusMapper),
                'data' => array(
                    self::UPDATE_INFO_STATUS_WAIT => $this->getUploadStatusText(self::UPDATE_INFO_STATUS_WAIT),
                    self::UPDATE_INFO_STATUS_RUNNING => $this->getUploadStatusText(self::UPDATE_INFO_STATUS_RUNNING),
                    self::UPDATE_INFO_STATUS_SUCCESS => $this->getUploadStatusText(self::UPDATE_INFO_STATUS_SUCCESS),
                    self::UPDATE_INFO_STATUS_FAILED => $this->getUploadStatusText(self::UPDATE_INFO_STATUS_FAILED),
                )
            ),

            array(
                'name' => 'last_upload_time',
                'type' => 'text',
                'search' => 'RANGE',
                'alias' => 't',
                'htmlOptions' => array(
                    'class' => 'date',
                    'dateFmt' => 'yyyy-MM-dd HH:mm:ss',
                    'style' => 'width:120px;',
                    'width' => '300px'
                ),
            ),
        );
    }
}