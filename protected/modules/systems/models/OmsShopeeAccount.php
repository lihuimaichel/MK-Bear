<?php

class OmsShopeeAccount extends SystemsModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_shopee_account';
    }

    public function getAccountInfoById($id)
    {
        // iid 注意 这个坑#24
        $queryBuilder = $this->getDbConnection()->createCommand()->select('iid as id')
            ->from($this->tableName())
            ->where('iid=:id', array(':id'=> $id));

        return $queryBuilder->queryRow();
    }

    public function saveInfo($accountInfo)
    {
        if (isset($accountInfo['id'])) {
            $accountInfo['iid'] = $accountInfo['id'];
            unset($accountInfo['id']);
        }
        $success = $this->getDbConnection()->createCommand()->insert(
            $this->tableName(),
            $accountInfo
        );
        if (!$success) {
            throw new \Exception('Create account info failed');
        }
    }

    public function updateInfo($id, $accountInfo)
    {
        $success = $this->getDbConnection()->createCommand()->update(
            $this->tableName(),
            $accountInfo,
            'iid=:id',
            array(':id'=> $id)
        );
        if (!$success) {
            throw new \Exception('Save account info failed');
        }
    }
}