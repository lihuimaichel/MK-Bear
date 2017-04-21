<?php

class WishProductUpdateLog extends WishModel
{
    const UPDATE_ACTION_ERASE_IMAGES = 'erase_images';
    const UPDATE_ACTION_MAIN_FIELD_UPDATE = 'main_field_update';
    const UPDATE_ACTION_VARIANT_FIELD_UPDATE = 'variant_field_update';
    const UPDATE_ACTION_VARIANT_CREATE = 'variant_create';
    const UPDATE_ACTION_VARIANT_DISABLE = 'variant_disable';

    public function tableName()
    {
        return 'ueb_wish_product_update_log';
    }
   
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function loadByParent($parentId)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->select('*')
            ->from($this->tableName())
            // ->leftJoin(WishProductVariantsUpdate::model()->tableName(). ' AS v', 'p.id = v.parent_id')
            ->where('parent_id=:parentId', array(':parentId' => $parentId));
        return $queryBuilder->queryRow();
    }

    public function saveFieldLog($parentId, $updateAction, $field, $newValue, $oldValue = null)
    {
        $dateTime = new \DateTime();
        $data = array(
            'parent_id'=> $parentId,
            'updated_field'=> $field,
            'update_action'=> $updateAction,
            'new_value'=> $newValue,
            'old_value'=> $oldValue,
            'uploaded_at'=> $dateTime->format('Y-m-d H:i:s')
        );
        $this->getDbConnection()->createCommand()
            ->insert($this->tableName(), $data);
    }
}