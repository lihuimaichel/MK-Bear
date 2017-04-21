<?php

class JoompriceupdatelogController extends UebController
{


    public function actionList()
    {
        $this->render("list", array(
            'model' => JoomPriceUpdateLog::model()
        ));
    }


    // 批量定时跑
    public function actionBatchReUploadPriceUpdate()
    {
        $updateLogModel = new JoomPriceUpdateLog();

        $logModel = new JoomLog();

        $infos = $updateLogModel->findInfoCanReUpload();

        $totalInfo = $totalSuccessInfo = count($infos);

        $logId = $logModel->prepareLog(0, JoomLog::EVENT_REUPLOAD_AUTO_PRICE_UPDATE);
        if (!$logId) {
            echo Yii::t('joom', 'System error');
            Yii::app()->end();
        }
        $logModel->setRunning($logId);

        foreach ($infos as $info) {
            try {
                $updateLogModel->reUploadUpdateInfo($info);
            } catch (\Exception $e) {
                --$totalSuccessInfo;
                echo $e->getMessage();
            }
        }

        $logModel->setSuccess($logId, sprintf('Total: %s, Success:%s', $totalInfo, $totalSuccessInfo));
        echo 'done';
    }

    // 手动出发单个
    public function actionReUploadPriceUpdate($id)
    {
        try {
            $updateLogModel = new JoomPriceUpdateLog();
            $uploadLog = $updateLogModel->findByPk($id);

            if (!$uploadLog) {
                throw new \Exception(Yii::t('joom', 'Update info not exists'));
            }

            //运行超过两次 不操作
            if ($uploadLog['upload_times'] >= 2) {
                throw new \Exception(Yii::t('joom','Update info ran 2 times'));
            }

            $dateTime = new \DateTime();
            $createdAt = new \DateTime($uploadLog['created_at']);
            $dateInterval = $dateTime->diff($createdAt);
            if ($dateInterval->d >= 2){
                throw new \Exception(Yii::t('joom','Can not update record max than 2 days'));
            }

            // do upload
            $updateLogModel->reUploadUpdateInfo($uploadLog);

            echo $this->successJson(array(
                'message'=> Yii::t('joom', 'Upload successful')
            ));
        }catch (\Exception $e) {
            echo $this->failureJson(array(
                'message'=> $e->getMessage()
            ));
        }

    }

    public function actionAutoSetupPrice()
    {
        set_time_limit(3600);
        $sku = Yii::app()->request->getParam('sku');

        $changedProductData = array();
        $dateTime = new \DateTime();

        $timeInterval = '-1 day';
        $dateTime->modify($timeInterval);

        $productFieldChangeStatisticsModel = ProductFieldChangeStatistics::model();

        $queryBuilder = $productFieldChangeStatisticsModel->getDbConnection()->createCommand()
            ->from($productFieldChangeStatisticsModel->tableName())
            ->select('id, sku, new_field, create_time, last_field, type')
            ->where('create_time>=:createdAt', array(':createdAt' => $dateTime->format('Y-m-d H:i:s')));
        //->andWhere('sku = 99784.01');
        //->group('sku');
        if ($sku) {
            $queryBuilder->andWhere('sku = '.$sku);
        }

        $changedProductData = $queryBuilder->queryAll();



        $priceChangedMovement = 5;
        $weightChangedMovement = 20;

        $skuNeedUpdate = array();

        foreach($changedProductData as $info) {
            $updateType = null;
            $priceChanged = 0;
            $weightChanged = 0;
            $newDataInfo = array(
                'new_value'=>$info['new_field'],
                'old_value'=> $info['last_field']
            );
            if ($info['type'] == 1) {// price
                $priceChanged = ($info['new_field'] - $info['last_field']) / $info['last_field'] * 100;

            } elseif($info['type'] == 2) { // weight
                $weightChanged = ($info['new_field'] - $info['last_field']) / $info['last_field'] * 100;
            }
            if ($priceChanged == 0 && $weightChanged == 0) {
                echo 'type not found';
                continue;
            }


            if ( abs($weightChanged) >= $weightChangedMovement) {
                if ($weightChanged > 0) {
                    $updateType = JoomPriceUpdateLog::UPDATE_TYPE_WEIGHT_UP;
                } else {
                    $updateType = JoomPriceUpdateLog::UPDATE_TYPE_WEIGHT_DOWN;
                }
            }
            if ( abs($priceChanged) >= $priceChangedMovement) {
                if ($priceChanged > 0) {
                    $updateType = JoomPriceUpdateLog::UPDATE_TYPE_PRICE_UP;
                } else {
                    $updateType = JoomPriceUpdateLog::UPDATE_TYPE_PRICE_DOWN;
                }
            }
            if(!$updateType) {
                continue;
            }

            $skuNeedUpdate[$info['sku']] = array_merge($newDataInfo,array(
                'changed_at'=> $info['create_time'],
                'update_type'=> $updateType
                )
            );
        }
        if (!$skuNeedUpdate) {
            echo '没有需要更改的SKU';
            Yii::app()->end();
        }

        $listings = JoomListing::model()->getListingWithVariantsBySkuForAutoPrice(array_keys($skuNeedUpdate));

        if (!$listings) {
            echo '没有需要更改的LISTING';
            Yii::app()->end();
        }
        foreach($listings as $listing) {

            try {
                $exists = JoomPriceUpdateLog::model()->checkIfRanInThePeriod($listing['variation_product_id']);
                if ($exists) {
                    throw new \Exception('已经跑过了');
                }

                $product = Product::model()->getBySku($listing['sub_sku']);
                if (!$product) {
                    throw new \Exception('产品信息不存在');
                }

                $productPriceData = $skuNeedUpdate[$listing['sub_sku']];


                $sellerId = 0;
                $productSellerRelation = JoomProductSellerRelation::model()->getProductSellerRelationInfoByItemIdandSKU
                ($listing['variation_product_id'], $listing['sub_sku'], $listing['online_sku']);
                if ($productSellerRelation) {
                    $sellerId = $productSellerRelation['seller_id'];
                }

                $oldSalePrice = $listing['price'];
                $priceCal = JoomProductAdd::model()->getListingProfit($listing['sub_sku'], $listing['price']);
                $oldProfitRate = $priceCal->getProfitRate();
                $oldProfitRate = $oldProfitRate ? $oldProfitRate : 0;




                $salePriceData = JoomProductAdd::model()->getSalePrice($listing['sub_sku']);

                if ($salePriceData['errormsg']) {
                    throw new \Exception($salePriceData['errormsg']);
                }
                $newSalePrice = $salePriceData['salePrice'];
                $newProfitRate = $salePriceData['profitRate'];
                /*
                $salePriceData = JoomProductAdd::model()->getSalePriceWithProfitRate($listing['sub_sku'], $oldProfitRate);

                $newSalePrice = $salePriceData->getSalePrice();
                if (!$newSalePrice) {
                    throw new \Exception($salePriceData->getErrorMessage());
                }
                $newProfitRate = $salePriceData->getProfitRate();*/


                $updateLogModel = new JoomPriceUpdateLog();
                $logId = null;
                try {
                    $updateLogModel->updateExpiredInfo($listing['variation_product_id'], $listing['account_id']);
                    $logId = $updateLogModel->saveInfo(
                        array(
                            'listing_id' => $listing['variation_product_id'],
                            'sku' => $listing['sub_sku'],
                            'online_sku' => $listing['online_sku'],
                            'account_id' => $listing['account_id'],
                            //'site_id' => $listing['site_id'],
                            'seller_name'=> $sellerId,
                            'old_listing_price' => $oldSalePrice,
                            'changed_at' => $productPriceData['changed_at'],
                            'old_profit_rate' => $oldProfitRate,
                            'upload_times' => 1,
                            'new_listing_price' => $newSalePrice,
                            'new_profit_rate' => $newProfitRate,
                            'old_value' => $productPriceData['old_value'],
                            'new_value' => $productPriceData['new_value']
                        ),
                        $productPriceData['update_type']
                    );
                } catch (\Exception $e) {
                    throw $e;
                }

                try {
                    JoomVariants::model()->updateVariantDataOnline($listing['online_sku'], $listing['account_id'], array('price' => $newSalePrice));
                    $updateLogModel->updateStatus($logId, JoomPriceUpdateLog::LOG_STATUS_SUCCESS, 'successful');
                } catch (\Exception $e) {
                    $updateLogModel->updateStatus($logId, JoomPriceUpdateLog::LOG_STATUS_FAILED, $e->getMessage());
                    throw $e;
                }

            }catch (\Exception $e) {
                echo $e->getMessage();
                echo '<br>';
                echo str_repeat('...', 100);
                echo '<br>';

            }
        }

    }

}