<?php

/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 17:14
 */
class ShopeeProductVariation  extends ShopeeModel
{

    const LISTING_VARIATION_STATUS_NORMAL = 1;
    const LISTING_VARIATION_STATUS_DELETED = 2;


    const SHOPEE_LISTING_VARIATION_STATUS_NORMAL = 'MODEL_NORMAL';
    const SHOPEE_LISTING_VARIATION_STATUS_DELETED = 'MODEL_DELETE';

    private $statusMapper = array(
        self::SHOPEE_LISTING_VARIATION_STATUS_NORMAL => self::LISTING_VARIATION_STATUS_NORMAL,
        self::SHOPEE_LISTING_VARIATION_STATUS_DELETED => self::LISTING_VARIATION_STATUS_DELETED,
    );
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_shopee_product_variation';
    }


    public function getStatusMapper($status = null, $flip = false)
    {
        $statusMapper = $this->statusMapper;
        if ($flip) {
            $statusMapper = array_flip($statusMapper);
        }
       if (null != $status) {
            return isset($statusMapper[$status])?$statusMapper[$status]:null;
       }

        return $statusMapper;
    }
    public function saveVariationInfo($parentId, $variations)
    {

        $createdAtTime = new \DateTime();
        $updatedAtTime = clone $createdAtTime;
        $currentDateTime = clone $createdAtTime;


        foreach($variations as $variation) {

            $data = array(
                'parent_id'=> $parentId,
                'variation_id'=> $variation->variation_id,
                'variation_option_name'=> $variation->name,
                'variation_sku'=> $variation->variation_sku,
                'sku'=> $variation->variation_sku,
                'price'=> $variation->price,
                'stock'=> $variation->stock,
                'status'=> $this->statusMapper[$variation->status],
                'created_at'=> $createdAtTime->setTimestamp($variation->create_time)->format("Y-m-d H:i:s"),
                'last_updated_at'=> $currentDateTime->format('Y-m-d H:i:s')
            );

            if ($variation->update_time) {
                $data[ 'updated_at'] = $updatedAtTime->setTimestamp($variation->update_time)->format("Y-m-d H:i:s");
            }
            try {
                $variationInfo = $this->getVariationInfoByItemId($variation->variation_id, $parentId);
                if ($variationInfo) {

                    $this->getDbConnection()->createCommand()->update($this->tableName(), $data, 'id=:id', array(':id' => $variationInfo['id']));
                } else {
                    $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
                }
            }catch (\Exception $e) {
                continue;
            }
        }
        return null;
    }

    /**
     * @param $parentIds
     * @param bool $withGroupSet 是否按父ID 合并返回
     * @param $filters
     * @return array|CDbDataReader
     */
    public function getVariationInfoByParentIds($parentIds, $filters = array(), $withGroupSet = false)
    {
        $parentIds = (array) $parentIds;


        $queryBuilder = $this->getDbConnection()->createCommand()->select('*')
            ->from($this->tableName())
            ->where('parent_id in ('.MHelper::simplode($parentIds).')');

        if ($filters) {
            foreach($filters as $field=> $value) {
                if (is_scalar($value)) {
                    $queryBuilder->andWhere($field . '=' . $value);
                } elseif (is_array($value)) {
                    /**
                     *  ['op'=>'value'] format
                     */
                    foreach($value as $op=> $v) {
                        $queryBuilder->andWhere($field . $op. $v);
                    }
                }
            }
        }

        $variations =  $queryBuilder->queryAll();

        if ($withGroupSet) {

            $result = array();
            foreach($variations as $variation) {
                $result[$variation['parent_id']][] = $variation;
            }
            return $result;
        }

        return $variations;

    }
    /**
     * @param $variationId
     * @param null $parentId
     * @param boolean $withParent
     * @return CDbDataReader|mixed
     */
    public function getVariationInfoByItemId($variationId, $parentId = null, $withParent = false)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()->select('pv.*')
            ->from($this->tableName(). ' AS pv');
        if (null != $parentId) {
            $queryBuilder->andWhere('pv.parent_id =:parentId', array(':parentId'=> $parentId));
        }
        if ($withParent) {
            $queryBuilder->leftJoin(ShopeeProduct::model()->tableName(). ' AS p', 'pv.parent_id = p.id');
            $queryBuilder->select('pv.*,  p.listing_id, p.name, p.account_id, p.site_code, p.has_variation, p.currency');
        }
        if (is_array($variationId)) {
            $queryBuilder->where('pv.variation_id IN ('.MHelper::simplode($variationId).')');

        } else {
            $queryBuilder->where('pv.variation_id=:variationId', array(':variationId' => $variationId));

        }

        if (null != $parentId) {
            $queryBuilder->andWhere('pv.parent_id =:parentId', array(':parentId'=> $parentId));
        }
        if (is_array($variationId)) {
            return $queryBuilder->queryAll();
        }
        return $queryBuilder->queryRow();
    }

    public function getVariationInfoById($id, $withParent = false)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()->select('pv.*')
            ->from($this->tableName() . ' AS pv')
            ->where('pv.id=:id', array(':id'=> $id));

        if ($withParent) {
            $queryBuilder->leftJoin(ShopeeProduct::model()->tableName(). ' AS p', 'pv.parent_id = p.id');
            $queryBuilder->select('pv.*, p.listing_id, p.currency, p.name, p.account_id, p.site_code, p.has_variation');
        }
        //echo $queryBuilder->getText();

        return $queryBuilder->queryRow();
    }

    /**
     * @param $listingId
     * @param $newValue
     * @param $updateType
     * @throws Exception
     * 更新在线listing数据
     */
    public function updateOnlineListing($accountId, $parentListingId, $listingId, $newValue, $updateType)
    {
        switch ($updateType) {
            case 'updatePrice':
                $request = new UpdateVariationPriceRequest();
                /**
                 *  comment to test
                 */
                $response = $request->setItemId($parentListingId)->setPrice($newValue)->setVariationId($listingId)->setAccount($accountId)->setRequest()->sendRequest()->getResponse();

                if (!$request->getIfSuccess()) {
                    throw new \Exception(Yii::t('shopee', 'Update (:itemId) price failed', array(':itemId' => $listingId)).$request->getErrorMsg());
                }

                $this->updateListing(array('price'=> $newValue), $listingId);

                break;
            case 'updateStock':

                $request = new UpdateVariationStockRequest();
                /**
                 *  comment to test
                 */
                $response = $request->setItemId($parentListingId)->setVariationId($listingId)->setQty($newValue)->setAccount($accountId)->setRequest()->sendRequest()->getResponse();

                if (!$request->getIfSuccess()) {
                    throw new \Exception(Yii::t('shopee', 'Update (:itemId) stock failed', array(':itemId' => $listingId)).$request->getErrorMsg());
                }

                $this->updateListing(array('stock'=> $newValue), $listingId);

                break;
            default:
                throw new \Exception("Please special update type");
        }
    }

    /***
     * 更新系统listing variation 表
     */
    public function updateListing($updateData = array(), $listingId, $parentId = null)
    {
        $whereCondition = " variation_id =".$listingId;
        if ($parentId) {
            $whereCondition .= " AND parent_id = ".$parentId;
        }
        $dateTime = new \DateTime();
        $updateData['last_updated_at'] = $dateTime->format('Y-m-d H:i:s');
        return $this->getDbConnection()->createCommand()->update($this->tableName(), $updateData, $whereCondition);
    }
}