<?php

/**
 * @desc Wish account relations
 */
class WishaccountrelationsController extends UebController
{

    public function actionIndex()
    {
        $this->render("list", array(
            'model' => new WishAccountRelations()
        ));
    }

    public function actionSave()
    {
        try {
            $departmentId = Yii::app()->request->getParam('department');
            $accountId = Yii::app()->request->getParam('account');
            $datePeriod = Yii::app()->request->getParam('datePeriod');
            $sellerIds = Yii::app()->request->getParam('seller');

            if (!$departmentId || !$accountId || !$datePeriod || !$sellerIds) {
                throw new \Exception(Yii::t('wish', 'Please input required parameters'));
            }

            $dateTime = new \DateTime();
            $expiredTime = clone $dateTime;
            $datePeriod = abs($datePeriod);
            $expiredTime->modify('+ '.$datePeriod.' day');

            foreach($sellerIds as $id) {
                $seller = User::model()->findByPk($id);
                if (!$seller) {
                    continue;
                }

                $relationData = array(
                    'department_id' => $departmentId,
                    'account_id' => $accountId,
                    'created_at' => $dateTime->format('Y-m-d H:i:s'),
                    'expired_at' =>$expiredTime->format('Y-m-d H:i:s'),
                    'seller_id'=> $id,
                    'status'=> WishAccountRelations::ACCOUNT_RELATION_ENABLE_STATUS
                );
                WishAccountRelations::model()->saveInfo($relationData);
            }
            echo $this->successJson(array(
               'message'=> Yii::t('wish', 'Successful'),
                'navTabId'=> 'page'.Menu::model()->getIdByUrl('wish/wishaccountrelations/'),
                'callbackType'=> 'closeCurrent',
            ));


        } catch (\Exception $e) {
            echo $this->failureJson(array(
                    'message' => $e->getMessage()
                )
            );
        }
    }

    public function actionAccountList($departmentId)
    {
        try {

            $sellerList = User::model()->findUserListByDepartmentId($departmentId);
            if (!$sellerList) {
                throw new \Exception(Yii::t('wish', 'Can not find seller list'));
            }
            $sellerIds = array_map(function ($e){
                return $e['id'];
            }, $sellerList);


            $sellerAccountList = ProductMarketersManager::model()->findSellerAccountsByPlatform(Platform::CODE_WISH,
                $sellerIds);


            $accountList = WishAccount::model()->getAccountInfoByIds(array_keys($sellerAccountList));
            if (!$accountList) {
                throw new \Exception(Yii::t('wish', 'Can not find account list'));
            }
            $accounts = array();
            foreach($accountList as $account) {
                $accounts[] = array(
                    'id'=> $account['id'],
                    'short_name'=> $account['account_name']
                );
            }

            $relations = WishAccountRelations::model()->findRelationsByDepartmentId($departmentId);
            $relationList = array();
            foreach ($relations as $relation) {
                $relationList[$relation['account_id']][] = $relation['seller_id'];
            }
            echo $this->successJson(array(
                'accountList' => $accounts,
                'sellerList' => $sellerList,
                'relationList'=> $relationList,
            ));
        } catch (\Exception $e) {
            echo $this->failureJson(array(
                    'message' => $e->getMessage()
                )
            );
        }

    }


    public function actionAdd()
    {
        $departments = Department::model()->getDepartmentByKeywords('wish');
        //$accounts = WishAccount::model()->getIdNamePairs();
        $accounts = array();
        $groups = array();

        $datePeriod = array(
            '10' => Yii::t('wish', '10 days'),
            '20' => Yii::t('wish', '20 days'),
            '30' => Yii::t('wish', '30 days'),
            '60' => Yii::t('wish', '60 days'),
            '90' => Yii::t('wish', '90 days')
        );
        $this->render('add', array(
            'departments' => $departments,
            'accounts' => $accounts,
            'groups' => $groups,
            'datePeriod' => $datePeriod
        ));
    }

    public function actionDelete()
    {
        $ids = Yii::app()->request->getParam('ids');
        if ($ids) {
            $ids = explode(',', $ids);
            foreach($ids as $id) {
                WishAccountRelations::model()->deleteByPk($id);
            }
        }
        echo $this->successJson(array(
            'message'=> Yii::t('wish', 'Successful'),
            'navTabId'=> 'page'.Menu::model()->getIdByUrl('wish/wishaccountrelations/'),
        ));


    }

}