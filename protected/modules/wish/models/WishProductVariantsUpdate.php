<?php

class WishProductVariantsUpdate extends WishModel
{


    // 子SKU 操作定义
    const VARIANT_ACTION_CREATE = 1;  //create
    const VARIANT_ACTION_UPDATE = 2; // update
    const VARIANT_ACTION_DISABLE = 3; //disable


    const UPDATE_VARIANT_INFO_STATUS_WAIT = 0;
    const UPDATE_VARIANT_INFO_STATUS_RUNNING = 1;
    const UPDATE_VARIANT_INFO_STATUS_SUCCESS = 2;
    const UPDATE_VARIANT_INFO_STATUS_FAILED = 3;

    public function tableName()
    {
        return 'ueb_wish_product_variants_update';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function loadVariants($parentId)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->select('*')
            ->from($this->tableName())
            ->where('parent_id=:parentId', array(':parentId'=> $parentId));

        return $queryBuilder->queryAll();
    }

    public function updateInfoStatus($id, $status, $IncreaseUploadTimes = false)
    {
        $dateTime = new \DateTime();
        $fields = array(
            'upload_status'=> $status,
           // 'upload_times' =>  new CDbExpression( 'upload_times+1'),
            'last_upload_time'=> $dateTime->format('Y-m-d H:i:s')
        );

        if ($IncreaseUploadTimes) {
            $fields['upload_times'] = new CDbExpression( 'upload_times+1');
        }

        $this->getDbConnection()->createCommand()->update($this->tableName(),
            $fields,
            'id=:id',
            array(':id'=> $id)
        );

    }


    public function saveInfo($parentId, $variantData)
    {

        if (!isset($variantData['sku'])) {
            throw new \Exception('Variant SKU not set');
        }
        $sku = $variantData['sku'];
        $info = $this->findByAttributes(array('parent_id' => $parentId, 'sku' => $sku));
        if (!$info) {
            $variantData['parent_id'] = $parentId;
            $this->getDbConnection()->createCommand()->insert($this->tableName(), $variantData);
        } else {
            $this->getDbConnection()->createCommand()->update($this->tableName(),
                $variantData,
                'parent_id=:parentId AND sku=:sku',
                array(
                    ':parentId'=> $parentId,
                    ':sku'=> $sku,
                ));
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param $uploadStatus
     */
    public function getUploadStatusText($uploadStatus)
    {
        $statusText = '';

        switch ($uploadStatus){
            case self::UPDATE_VARIANT_INFO_STATUS_WAIT :
                $statusText = Yii::t('wish', 'Update is waiting');
                break;

            case self::UPDATE_VARIANT_INFO_STATUS_RUNNING :
                $statusText =Yii::t('wish', 'Update is running');

                break;
            case self::UPDATE_VARIANT_INFO_STATUS_SUCCESS :
                $statusText =  Yii::t('wish', 'Update is succeed');

                break;
            case self::UPDATE_VARIANT_INFO_STATUS_FAILED :
                $statusText =Yii::t('wish', 'Update is failed');
                break;

        }

        return $statusText;
    }

    public function getUploadActionText($uploadAction)
    {
        $actionText = '';
        switch ($uploadAction){
            case self::VARIANT_ACTION_CREATE :
                $actionText = Yii::t('wish', 'Create Variant');
                break;
            case self::VARIANT_ACTION_UPDATE :
                $actionText =Yii::t('wish', 'Update Variant');
                break;
            case self::VARIANT_ACTION_DISABLE :
                $actionText =  Yii::t('wish', 'Disable Variant');
                break;
        }

        return $actionText;
    }


    public function deleteInfoByParent($parentId)
    {
        $this->getDbConnection()->createCommand()
            ->delete(
                $this->tableName(),
                'parent_id=:parentId',
                array(':parentId' => $parentId)
            );
    }
}