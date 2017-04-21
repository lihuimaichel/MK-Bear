<?php
class PlatformShopeeAccount extends PlatformAccountModel{
	
    /** @var tinyint 账号状态开启*/
    const STATUS_OPEN = 1;
     
    /** @var tinyint 账号状态关闭*/
    const STATUS_SHUTDOWN = 0;



    const SYNC_TO_OMS_DONE = 1;
    const SYNC_TO_OMS_PENDING = 0;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_platform_shopee_account';
    }


    public function checkAccountByName($name, $site)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->select('id')
            ->from($this->tableName())
            ->where('account_name=:accountName', array(':accountName'=> $name))
            ->andWhere('site_code=:siteCode', array(':siteCode'=> $site));

        return $queryBuilder->queryRow();


    }

    public function saveAccountInfo($accountInfo, $id = null)
    {
        if (null === $id) {

            $success = $this->getDbConnection()->createCommand()->insert(
                $this->tableName(),
                $accountInfo
            );

            if (!$success) {
                throw new \Exception(Yii::t('platformaccount', 'Can not save account info'));
            }
            return $this->getDbConnection()->getLastInsertID();
        } else {
            $success = $this->getDbConnection()->createCommand()->update(
                $this->tableName(),
                $accountInfo,
                'id=:id',
                array(':id'=> $id)
            );

            if (!$success){
                throw new \Exception(Yii::t('platformaccount', 'Can not update account info'));
            }
        }
    }
    /**
     * 获取账号ID => ACCOUNT NAME对
     * @return array
     */
    public function getIdNamePairs() {
        $pairs = array();
        $queryBuilder = $this->getDbConnection()
            ->createCommand()
            ->select("id, account_name")
            ->from($this->tableName())
            ->order("account_name asc");

            foreach ($queryBuilder->queryAll() as $row)
                $pairs[$row['id']] = $row['account_name'];

        return $pairs;
    }

    public function getStatusOptions()
    {
        return array(
            self::STATUS_OPEN=> Yii::t('platformaccount', 'Status Open'),
            self::STATUS_SHUTDOWN=> Yii::t('platformaccount', 'Status Close')
        );
    }

    public function getSiteList()
    {
        return array(
            'my'=> 'my',
            'tw'=> 'tw',
            'sg'=> 'sg',
            'id'=> 'id',
            'th'=> 'th'
        );
    }

    /*
     * 列表相关函数
     */
    public function search($model = null, $sort = array(), $with = array(), $criteria = null) {
        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder'  => 'id',
        );
        $dataProvider = parent::search($this, $sort, $with, $criteria);
        #$data = $this->addtions($dataProvider->data);
        #$dataProvider->setData($data);
        return $dataProvider;
    }

    public function attributeLabels() {
        return array(
            'account_name'=> Yii::t('platformaccount', 'Account Name'),
            'status'             => Yii::t('platformaccount', 'Status'),
            'short_name'         => Yii::t('platformaccount', 'Short Name'),
            'token_status'       => Yii::t('platformaccount', 'Token Status'),
            'updated_at'        => Yii::t('platformaccount', 'Update Time'),
            'to_oms_status'      => Yii::t('platformaccount', 'Sync Status'),
            'to_oms_time'        => Yii::t('platformaccount', 'Sync Time'),
            'client_secret'         => Yii::t('platformaccount', 'Client Secret'),
            'site_code'         => Yii::t('platformaccount', 'Site Code'),
            'open_time'=> Yii::t('platformaccount', 'Open time'),
            'created_at'=> Yii::t('platformaccount', 'Created At')
        );
    }

    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        $status = Yii::app()->request->getParam('status');
        $tokenStatus = Yii::app()->request->getParam('token_status');
        $departmentId = Yii::app()->request->getParam('department_id');
        $result = array(
            array(
                'name' => 'account_name',
                'type' => 'dropDownList',
                //'expr' => 'IF(p.account_id=0, 1, 2)',
                //'expr'=>" left(account_name,   POSITION('.' IN account_name) - 1)",
                'search' => '=',
                'data' => function (){
                    $accountList = PlatformShopeeAccount::model()->getIdNamePairs();
                    $result = array();
                    foreach($accountList as $id=> $name) {
                        list($account, $site) = explode('.', $name);
                        $result[$account] = $account;
                    }

                    return $result;
                }
            ),
            array(
                'name' => 'site_code',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getSiteList(true)
            ),
            array(
                'name'=>'status',
                'type'=>'dropDownList',
                'search'=>'=',
                'data'=>$this->getAccountStatus(),
                'value'=>$status
            ),

        );
        return $result;
    }

}