<?php

class WishpriceupdatelogController extends UebController
{


    public function actionList()
    {
        $this->render("list", array(
            'model' => WishPriceUpdateLog::model()
        ));
    }


    // 批量定时跑
    public function actionBatchReUploadPriceUpdate()
    {
        $updateLogModel = new WishPriceUpdateLog();

        $logModel = new WishLog();

        $infos = $updateLogModel->findInfoCanReUpload();

        $totalInfo = $totalSuccessInfo = count($infos);

        $logId = $logModel->prepareLog(0, WishLog::EVENT_REUPLOAD_AUTO_PRICE_UPDATE);
        if (!$logId) {
            echo Yii::t('wish', 'System error');
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
            $updateLogModel = new WishPriceUpdateLog();
            $uploadLog = $updateLogModel->findByPk($id);

            if (!$uploadLog) {
                throw new \Exception(Yii::t('wish', 'Update info not exists'));
            }

            //运行超过两次 不操作
            if ($uploadLog['upload_times'] >= 2) {
                throw new \Exception(Yii::t('wish', 'Update info ran 2 times'));
            }
            $dateTime = new \DateTime();
            $createdAt = new \DateTime($uploadLog['created_at']);
            $dateInterval = $dateTime->diff($createdAt);
            if ($dateInterval->d >= 3) {
                throw new \Exception(Yii::t('wish', 'Can not update record max than 3 days'));
            }
            try {
                $logModel = new WishLog();
                $logId = $logModel->prepareLog(0, WishLog::EVENT_REUPLOAD_AUTO_PRICE_UPDATE);
                $logModel->setRunning($logId);
                // do upload
                $updateLogModel->reUploadUpdateInfo($uploadLog);
                $logModel->setSuccess($logId, Yii::t('wish', 'Upload successful'));
                echo $this->successJson(array(
                    'message' => Yii::t('wish', 'Upload successful')
                ));

            } catch (\Exception $e) {
                $logModel->setFailure($logId, Yii::t('wish', 'Can not update record max than 3 days'));
                throw $e;
            }

        } catch (\Exception $e) {
            echo $this->failureJson(array(
                'message' => $e->getMessage()
            ));
        }

    }

    public function actionAutoSetupPrice()
    {
        set_time_limit(3600);
        $sku = Yii::app()->request->getParam('sku');

        $run = Yii::app()->request->getParam('run');

        if (!$run && !$sku) {
            echo '测试';
            Yii::app()->end();
        }
  

        $changedProductData = array();
        $dateTime = new \DateTime();

        $timeInterval = '-1 day';
        $dateTime->modify($timeInterval);

        $productFieldChangeStatisticsModel = ProductFieldChangeStatistics::model();

        $queryBuilder = $productFieldChangeStatisticsModel->getDbConnection()->createCommand()
            ->from($productFieldChangeStatisticsModel->tableName())
            ->select('id, sku, new_field, create_time, last_field, type')
            ->where('create_time>=:createdAt', array(':createdAt' => $dateTime->format('Y-m-d H:i:s')));
        //->group('sku');
        if ($sku) {
            $queryBuilder->andWhere('sku=' . $sku);
        }

        $changedProductData = $queryBuilder->queryAll();



        $priceChangedMovement = 1;
        $weightChangedMovement = 60;

        $skuNeedUpdate = array();

        foreach ($changedProductData as $info) {
            $updateType = null;
            $priceChanged = 0;
            $weightChanged = 0;

            $newDataInfo = array(
                'new_value' => $info['new_field'],
                'old_value' => $info['last_field']
            );

            if ($info['type'] == 1) {// price
                $priceChanged = ($info['new_field'] - $info['last_field']) / 7;
            } elseif ($info['type'] == 2) { // weight
                $weightChanged = $info['new_field'] - $info['last_field'];
            }

            if ($priceChanged == 0 && $weightChanged == 0) {
                echo 'type not found';
                continue;
            }

            if (abs($weightChanged) >= $weightChangedMovement) {
                if ($weightChanged > 0) {
                    $updateType = WishPriceUpdateLog::UPDATE_TYPE_WEIGHT_UP;
                } else {
                    $updateType = WishPriceUpdateLog::UPDATE_TYPE_WEIGHT_DOWN;
                }
            }

            if (abs($priceChanged) >= $priceChangedMovement) {

                if ($priceChanged > 0) {
                    $updateType = WishPriceUpdateLog::UPDATE_TYPE_PRICE_UP;
                } else {
                    var_dump($priceChanged);
                    $updateType = WishPriceUpdateLog::UPDATE_TYPE_PRICE_DOWN;
                }
            }
            if (!$updateType) {
                continue;
            }


            $skuNeedUpdate[$info['sku']] = array_merge($newDataInfo, array(
                    'changed_at' => $info['create_time'],
                    'update_type' => $updateType
                )
            );
        }

        if (!$skuNeedUpdate) {
            echo '没有需要更改的SKU';
            Yii::app()->end();
        }


        $listings = WishListing::model()->getListingWithVariantsBySkuForAutoPrice(array_keys($skuNeedUpdate), null,
            WarehouseSkuMap::WARE_HOUSE_GM);

        if (!$listings) {
            echo '没有需要更改的LISTING';
            Yii::app()->end();
        }

        foreach ($listings as $listing) {
            try {

                $exists = WishPriceUpdateLog::model()->checkIfRanInThePeriod($listing['variation_product_id']);
                if ($exists) {
                    throw new \Exception('已经跑过了');
                }

                $product = Product::model()->getBySku($listing['sub_sku']);
                if (!$product) {
                    throw new \Exception('产品信息不存在');
                }
                $priceCal = WishProductAdd::model()->getProfitInfo($listing['price'], $listing['sub_sku'], WishProductAdd::PRODUCT_PUBLISH_CURRENCY);
                $oldProfitRate = $priceCal['profitRate'] ? str_replace('%', '', $priceCal['profitRate']) : 0;
                $oldProfitRate = $oldProfitRate/100;
                $oldSalePrice = $listing['price'];

                $productPriceData = $skuNeedUpdate[$listing['sub_sku']];
                $salePriceData = WishProductAdd::model()->getSalePrice($listing['sub_sku'], $listing['account_id']);

                if ($salePriceData['errormsg']) {
                    throw new \Exception($salePriceData['errormsg']);
                }
                $newSalePrice = $salePriceData['salePrice'];
                $newProfitRate = str_replace('%', '', $salePriceData['profitRate']);
                $newProfitRate = $newProfitRate/100;



                $sellerId = 0;
                $productSellerRelation = WishProductSellerRelation::model()->getProductSellerRelationInfoByItemIdandSKU
                ($listing['variation_product_id'], $listing['sub_sku'], $listing['online_sku']);
                if ($productSellerRelation) {
                    $sellerId = $productSellerRelation['seller_id'];
                }




                $updateLogModel = new WishPriceUpdateLog();
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
                    WishVariants::model()->updateVariantDataOnline($listing['online_sku'], $listing['account_id'], array('price' => $newSalePrice));
                    $updateLogModel->updateStatus($logId, WishPriceUpdateLog::LOG_STATUS_SUCCESS, 'successful');
                } catch (\Exception $e) {
                    $updateLogModel->updateStatus($logId, WishPriceUpdateLog::LOG_STATUS_FAILED, $e->getMessage());
                    throw $e;
                }

            } catch (\Exception $e) {
                echo $e->getMessage();
                echo '<br>';
                echo str_repeat('...', 100);
                echo '<br>';

            }
        }

    }

}