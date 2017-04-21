<?php

class PlatformshopeeaccountController extends UebController
{

    /**
     * 显示账号列表
     */
    public function actionList()
    {
        $this->render('list', array('model' => new PlatformShopeeAccount()));
    }

    /**
     * 添加账号
     */
    public function actionAdd()
    {
        //获取开发者账号列表
        $siteList = PlatformShopeeAccount::model()->getSiteList();
        $statusList = PlatformShopeeAccount::model()->getStatusOptions();

        //获取所属部门
        $departmentList = Department::model()->getDepartmentByKeywords('shopee');
        $this->render("add", array(
            'siteList' => $siteList,
            'statusList' => $statusList,
            'departmentList'=> $departmentList,
        ));
    }


    /**
     * 反向同步到账号
     */
    public function actionPullAccount()
    {
        $accountList = ShopeeAccount::model()->getListByCondition();

        $dateTime = new \DateTime();
        foreach($accountList as $account) {
            echo $account['id'];
            //$info = PlatformShopeeAccount::model()->checkAccountByName( array_shift(\explode('.',
            // $account['account_name'])),$account['site']);

            $info = PlatformShopeeAccount::model()->findByPk($account['id']);
            $id = null;


            $accountInfo  = array(
                'account_name'=> array_shift(\explode('.', $account['account_name'])),
                'site_code'=> $account['site'],
                'short_name'=> $account['short_name'],
                'shop_id'=> $account['shop_id']? $account['shop_id']:'',
                'partner_name'=> $account['partner_name'],
                'partner_id'=> $account['partner_id'],
                'client_secret'=> $account['secret_key'],
                'status'=> $account['status'] != null ?$account['status']: 1,
                'access_token'=> $account['access_token'],
                'service_url'=> $account['service_url'],
                'department_id'=> $account['department_id'],
                'created_at'=> $dateTime->format('Y-m-d H:i:s')
            );
            if ($info) {
                $id = $info['id'];
                $accountInfo['id'] = $info['id'];
            }
            PlatformShopeeAccount::model()->saveAccountInfo($accountInfo, $id);
        }
    }

    /**
     * 同步到shopee 管理账号管理页面
     */
    public function actionSync()
    {
        try {
            $ids = Yii::app()->request->getParam('ids');
            if (!$ids) {
                throw new \Exception(Yii::t('platformaccount', 'Account info not exists'));
            }
            $ids = \explode(',', $ids);
            foreach ($ids as $id) {
                $accountModel = new PlatformShopeeAccount();
                $accountInfo = $accountModel->findByPk($id);
                if (!$accountInfo) {
                    throw new \Exception(Yii::t('platformaccount', 'Account info not exists'));
                }
                $info = array(
                    'account_name' => \join('.', array(
                        $accountInfo['account_name'],
                        $accountInfo['site_code']
                    )),
                    'site' => $accountInfo['site_code'],
                    'short_name' => $accountInfo['short_name'],
                    'shop_id' => $accountInfo['shop_id'],
                    'partner_name' => $accountInfo['partner_name'],
                    'partner_id' => $accountInfo['partner_id'],
                    'secret_key' => $accountInfo['client_secret'],
                    'service_url'=> $accountInfo['service_url'],
                    'access_token'=> $accountInfo['access_token'],
                    'department_id'=> $accountInfo['department_id'],
                    'open_time'=> $accountInfo['open_time'],
                    'status' => $accountInfo['status'],
                    'k3_cloud_status'=> 1,
                );

                ShopeeAccount::model()->updateOrCreate($info);

                $dateTime = new \DateTime();
                $updateStatus = array(
                    'to_oms_status'=> PlatformShopeeAccount::SYNC_TO_OMS_DONE,
                    'to_oms_time'=> $dateTime->format('Y-m-d H:i:s')
                );
                PlatformShopeeAccount::model()->saveAccountInfo($updateStatus, $accountInfo['id']);
            }
            echo $this->successJson(array(
                'message' => Yii::t('platformaccount', 'Sync account successful'),
                'navTabId'=>  'page'.Menu::model()->getIdByUrl('/platformaccount/platformshopeeaccount/list')
                //'callbackType'=> 'closeCurrent'
            ));
        } catch (\Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
            Yii::app()->end();
        }
    }

    /**
     * 编辑账号
     */
    public function actionEditForm($id)
    {

        try {
            $accountModel = new PlatformShopeeAccount();
            $accountInfo = $accountModel->findByPk($id);
            $statusList = PlatformShopeeAccount::model()->getStatusOptions();
            if (!$accountInfo) {
                throw new \Exception(Yii::t('platformaccount', 'Account info not exists'));
            }

            //获取所属部门
            $departmentList = Department::model()->getDepartmentByKeywords('shopee');
            $this->render('edit', array(
                'accountInfo' => $accountInfo,
                'statusList' => $statusList,
                'departmentList'=>$departmentList
            ));

        } catch (\Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
            Yii::app()->end();
        }
    }
    /**
     * 重新授权账号
     */
    public function actionRetokenForm($id)
    {

        try {
            $accountModel = new PlatformShopeeAccount();
            $accountInfo = $accountModel->findByPk($id);
             if (!$accountInfo) {
                throw new \Exception(Yii::t('platformaccount', 'Account info not exists'));
            }

            $this->render('retoken', array(
                'accountInfo' => $accountInfo,
             ));

        } catch (\Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
            Yii::app()->end();
        }
    }

    public function actionUpdateToken()
    {
        try {
            $accountModel = new PlatformShopeeAccount();

            $id = Yii::app()->request->getParam('id');
            $accountInfo = $accountModel->findByPk($id);
            if (!$accountInfo) {
                throw new \Exception(Yii::t('platformaccount', 'Account info not exists'));
            }


            $partnerName = Yii::app()->request->getParam('partner_name');
            $partnerId = Yii::app()->request->getParam('partner_id');
            $clientSecret = Yii::app()->request->getParam('client_secret');
            $accessToken = Yii::app()->request->getParam('access_token', '');
            $serviceUrl = Yii::app()->request->getParam('service_url', '');

            if (!$partnerName === '' || !$partnerId === '' || !$clientSecret === '') {
                throw new \Exception(Yii::t('platformaccount', 'Required parameters not found'));
            }

            $dateTime = new \DateTime();
            $saveInfo = array(
                'partner_name' => $partnerName,
                'partner_id' => $partnerId,
                'client_secret' => $clientSecret,
                'access_token' => $accessToken,
                'service_url' => $serviceUrl,
                'to_oms_status'=> PlatformShopeeAccount::SYNC_TO_OMS_PENDING,
                'updated_at' => $dateTime->format('Y-m-d H:i:s'),
                'modify_user_id' => Yii::app()->user->id
            );

            $accountModel->saveAccountInfo($saveInfo, $accountInfo['id']);

            echo $this->successJson(array(
                'message' => Yii::t('platformaccount', 'Save account successful'),
                'callbackType'=> 'closeCurrent'
            ));
        } catch (\Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
            Yii::app()->end();
        }
    }

    public function actionUpdate()
    {
        try {
            $accountModel = new PlatformShopeeAccount();

            $id = Yii::app()->request->getParam('id');
            $accountInfo = $accountModel->findByPk($id);
            if (!$accountInfo) {
                throw new \Exception(Yii::t('platformaccount', 'Account info not exists'));
            }


            $shortName = Yii::app()->request->getParam('short_name');
            $status = Yii::app()->request->getParam('status');
/*            $partnerName = Yii::app()->request->getParam('partner_name');
            $partnerId = Yii::app()->request->getParam('partner_id');*/
            $departmentId = Yii::app()->request->getParam('department');
            $openTime = Yii::app()->request->getParam('open_time');


            if ($shortName === '' || $status === '' || $departmentId
                    === '' || $openTime =='') {
                throw new \Exception(Yii::t('platformaccount', 'Required parameters not found'));
            }
            $shortNameExists = $accountModel->findByAttributes(array(
                'short_name' => $shortName,
                'id' => new CDbExpression('!= ' . $accountInfo['id'])
            ));
            if ($shortNameExists) {
                throw new \Exception(Yii::t('platformaccount', 'Account with short name(:shortName)exists', array
                (':shortName' => $shortName)));
            }

            $dateTime = new \DateTime();
            $saveInfo = array(
                'short_name' => $shortName,
                'status' => $status,
                'department_id'=> $departmentId,
                'open_time'=> $openTime,
                'to_oms_status'=> PlatformShopeeAccount::SYNC_TO_OMS_PENDING,
                'updated_at' => $dateTime->format('Y-m-d H:i:s'),
                'modify_user_id' => Yii::app()->user->id
            );

            $accountModel->saveAccountInfo($saveInfo, $accountInfo['id']);

            echo $this->successJson(array(
                'message' => Yii::t('platformaccount', 'Save account successful'),
                'callbackType'=> 'closeCurrent'
            ));
        } catch (\Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
            Yii::app()->end();
        }
    }

    /**
     * 保存账号
     */
    public function actionSave()
    {

        try {
            $name = Yii::app()->request->getParam('name');
            $shortName = Yii::app()->request->getParam('short_name');
            $site = Yii::app()->request->getParam('site');
            $status = Yii::app()->request->getParam('status');
            $shopId = Yii::app()->request->getParam('shop_id');
            $partnerName = Yii::app()->request->getParam('partner_name');
            $partnerId = Yii::app()->request->getParam('partner_id');
            $clientSecret = Yii::app()->request->getParam('client_secret');
            $accessToken = Yii::app()->request->getParam('access_token', '');
            $serviceUrl = Yii::app()->request->getParam('service_url', '');
            $departmentId = Yii::app()->request->getParam('department');
            $openTime = Yii::app()->request->getParam('open_time');
            if ($name === '' || $shortName === '' || $site === '' || $status === '' || $shopId === '' ||
                $partnerName === '' || $partnerId === '' ||
                $clientSecret === '' || $openTime === ''
            ) {
                throw new \Exception(Yii::t('platformaccount', 'Required parameters not found'));
            }


            $accountModel = new PlatformShopeeAccount();
            $shopIdExists = $accountModel->findByAttributes(array(
                'shop_id' => $shopId
            ));

            if ($shopIdExists) {
                throw new \Exception(Yii::t('platformaccount', 'Account with shop id(:shopId)exists', array
                (':shopId' => $shopId)));
            }
            $shortNameExists = $accountModel->findByAttributes(array(
                'short_name' => $shortName
            ));
            if ($shortNameExists) {
                throw new \Exception(Yii::t('platformaccount', 'Account with short name(:shortName)exists', array
                (':shortName' => $shortName)));
            }
            $fullName = \join('.', array($name, $site));
            $nameExists = $accountModel->findByAttributes(array(
                'account_name' => $fullName
            ));
            if ($nameExists) {
                throw new \Exception(Yii::t('platformaccount', 'Account with name(:name) exists', array
                (':name' => $name)));
            }


            $dateTime = new \DateTime();
            $accountInfo = array(
                'account_name' => $name,
                'short_name' => $shortName,
                'site_code' => $site,
                'shop_id' => $shopId,
                'partner_name' => $partnerName,
                'partner_id' => $partnerId,
                'client_secret' => $clientSecret,
                'department_id'=> $departmentId,
                'status' => $status,
                'access_token' => $accessToken,
                'service_url' => $serviceUrl,
                'open_time'=> $openTime,
                'created_at' => $dateTime->format('Y-m-d H:i:s'),
                'create_user_id' => Yii::app()->user->id
            );

            $accountId = $accountModel->saveAccountInfo($accountInfo);

            echo $this->successJson(array(
                'message' => Yii::t('platformaccount', 'Add account successful'),
                'callbackType'=> 'closeCurrent'
            ));
        } catch (\Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
            Yii::app()->end();
        }


    }
}