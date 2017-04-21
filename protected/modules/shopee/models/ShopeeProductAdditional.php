<?php

/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 17:14
 */
class ShopeeProductAdditional extends ShopeeModel
{

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
        return 'ueb_shopee_product_additional';
    }

    /**
     * @param $parentId
     * @param $item
     */
    public function saveItemAdditionalInfo($parentId, $item)
    {
        $data = array(
            'parent_id'=> $parentId,
            'description' => $item->description,
        );

        $info = $this->getListingAdditionalInfoByParentId($parentId);
        if ($info) {
            $this->getDbConnection()->createCommand()->update($this->tableName(), $data, 'id=:id', array(':id' => $info['id']));
        } else {
            $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
        }

    }

    /**
     * @param $parentId
     * @return CDbDataReader|mixed
     */
    public function getListingAdditionalInfoByParentId($parentId)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()->select('*')
            ->from($this->tableName())
            ->where('parent_id =:parentId', array(':parentId'=> $parentId));
        $info = $queryBuilder->queryRow();

        return $info;
    }

}