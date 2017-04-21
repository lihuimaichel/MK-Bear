<?php

/**
 * User: ketu.lai <ketu.lai@gmail.com>
 * Date: 2017/4/18 16:12
 */
class JoomListingProfit extends JoomModel
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
        return 'ueb_joom_listing_profit';
    }

    public function findByListingIdAndAccountId($listingId, $accountId)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->where('listing_id=:listingId', array(':listingId'=> $listingId))
            ->andWhere('account_id=:accountId', array(':accountId'=> $accountId));
        return $queryBuilder->queryRow();
    }

    public function createProfitInfo($profitData) {
        $this->getDbConnection()->createCommand()->insert(
            $this->tableName(),
            $profitData
        );
    }
    public function updateProfitInfo($id, $profitData)
    {
        $this->getDbConnection()->createCommand()->update(
            $this->tableName(),
            $profitData,
            'id=:id',
            array(':id'=> $id)
        );
    }

    public function createOrUpdate($listingId, $accountId, $profitData)
    {
        $dateTime = new \DateTime();

        $profitData['updated_at'] = $dateTime->format('Y-m-d H:i:s');

        $exists = $this->findByListingIdAndAccountId($listingId, $accountId);
        if ($exists) {
            $this->updateProfitInfo($exists['id'], $profitData);
        } else {
            $this->createProfitInfo(array_merge($profitData, array(
                'listing_id'=> $listingId,
                'account_id'=> $accountId
            )));
        }
    }

}