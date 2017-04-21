<?php

/**
 * @desc Wish listing update
 */
class WishproductupdateController extends UebController
{


    public function actionList()
    {
        $this->render("list", array(
            'model' => new WishProductUpdate()
        ));
    }

    // 上传产品更新信息
    public function actionUpload($id)
    {
        $updateInfo = WishProductUpdate::model()->loadInfo($id);
        if (!$updateInfo) {

            echo $this->failureJson(array(
                'message'=> Yii::t('wish', 'Update info not exists')
            ));
            Yii::app()->end();
        }

        if ($updateInfo['upload_status'] == WishProductUpdate::UPDATE_INFO_STATUS_SUCCESS) {
            echo $this->failureJson(array(
                'message'=> Yii::t('wish', 'Update info already uploaded')
            ));
            Yii::app()->end();
        }
        $logModel = WishLog::model();

        $logId = $logModel->prepareLog($updateInfo['account_id'], WishLog::EVENT_UPLOAD_PRODUCT);
        if (!$logId){
            echo $this->failureJson(array(
                'message'=> Yii::t('wish', 'System error.')
            ));
            Yii::app()->end();
        }

        // set running
        $logModel->setRunning($logId);

        try {
            WishProductUpdate::model()->uploadInfo($id);
            $logModel->setSuccess($logId, Yii::t('wish', 'Update info upload successful.'));
            echo $this->successJson(array(
                'message'=> Yii::t('wish', 'Update info save successful.')
            ));
        } catch (\Exception $e) {
            $logModel->setFailure($logId, $e->getMessage());
            //echo $e->getMessage();
            echo $this->failureJson(array(
                'message'=> Yii::t('wish',  $e->getMessage())
            ));
        }
    }


    public function actionBatchDelete()
    {
        try {
            $ids = Yii::app()->request->getParam('ids');

            if (!$ids) {
                throw new \Exception('Ids not set');
            }

            $ids = explode(',', $ids);
            $wishUpdateModel = WishProductUpdate::model();

            foreach($ids as $id) {
                $info = $wishUpdateModel->loadInfo($id);
                if (!$info || $info['upload_status'] != WishProductUpdate::UPDATE_INFO_STATUS_WAIT) {
                    continue;
                }
                // delete update info
                $wishUpdateModel->deleteInfo($id);
            }

            echo $this->successJson(array(
                'message'=> Yii::t('wish',  'Delete successful.')
            ));


        }catch (\Exception $e) {
            echo $this->failureJson(array(
                'message'=> Yii::t('wish',  $e->getMessage())
            ));
        }
    }


    public function actionBatchUpload()
    {
        try {
            $ids = Yii::app()->request->getParam('ids');

            if (!$ids) {
                throw new \Exception('Ids not set');
            }

            $ids = explode(',', $ids);
            $wishUpdateModel = WishProductUpdate::model();

            foreach($ids as $id) {
                $info = $wishUpdateModel->loadInfo($id);
                if (!$info || $info['upload_status'] == WishProductUpdate::UPDATE_INFO_STATUS_SUCCESS) {
                    continue;
                }
                // delete update info
                $wishUpdateModel->uploadInfo($id);
            }

            echo $this->successJson(array(
                'message'=> Yii::t('wish',  'Upload successful.')
            ));

        }catch (\Exception $e) {
            echo $this->failureJson(array(
                'message'=> Yii::t('wish',  $e->getMessage())
            ));
        }
    }

    public function actionEdit($id)
    {
        $info = WishProductUpdate::model()->findByPk($id);

        $listingInfo = WishListing::model()->getListingInfoByListingId($info['listing_id']);

        $updatedInfo = array(
            'sku'=> $listingInfo['sku'],
            'listing_id'=> $info->listing_id,
            'name'=> $info->name,
            'description'=>$info->description,
            'tags'=> $info->tags,
            'brand'=>$info->brand,
            'main_image'=>$info->main_image,
            'extra_images'=> $info->extra_images
        );

        /**@ 获取产品信息*/
        // 更改为拉取JAVA组图片API接口 ketu.lai
        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($updatedInfo['sku'], null, 'normal', 100, 100,
            Platform::CODE_WISH);

        /**
         * 修复java api接口无主图返回问题
         */
        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
            $skuImg['zt'] = $skuImg['ft'];
        }

        $updatedInfo['tags'] = explode(',', $updatedInfo['tags']);

        $selectedImg = array();

        $selectedImg[] = array_shift(explode('?', $updatedInfo['main_image']));
        $selectedImg = array_merge($selectedImg, explode('|', $updatedInfo['extra_images']));




        $variations = WishProductVariantsUpdate::model()->loadVariants($info['id']);

        $marketAttributeList = AttributeMarketOmsMap::model()->getOmsAttrIdsByPlatAttrName(Platform::CODE_WISH, 0);
        $attributeList = array();
        foreach ($marketAttributeList as $attribute) {
            $attributeList[] = $attribute['platform_attr_name'];
        }

        return $this->render('edit', array(
            'info'=> $info,
            'skuInfo'=> $updatedInfo,
            'skuImg'=>$skuImg,
            'selectedImg'=> $selectedImg,
            'variations'=> $variations,
            'attributeList'=> $attributeList,

        ));
    }

    public function actionSaveEdit()
    {

        try {
            $id = Yii::app()->request->getParam('id');

            $variants = Yii::app()->request->getParam('variants', array());
            $skuInfo = Yii::app()->request->getParam('skuInfo', array());
            $skuImage = Yii::app()->request->getParam('skuImage', array());
            $uploadedImages = Yii::app()->request->getParam('uploadedImages', '');

            $wishUpdateModel = WishProductUpdate::model();
            $wishVariantUpdateModel = WishProductVariantsUpdate::model();
            $updateInfo = $wishUpdateModel->findByPk($id);


            if (!$updateInfo) {
                throw new \Exception('Info not exists');
            }
            //$listingInfo = WishListing::model()->getListingInfoByListingId($updateInfo['listing_id']);

            $existsVariants = $wishVariantUpdateModel->loadVariants($updateInfo['id']);
            $existsVariantMapper = array();
            foreach ($existsVariants as $variant) {
                $existsVariantMapper[$variant['sku']] = $variant['online_sku'];
            }


            $hasImageModified = false;
            $remoteImages = array();

            $mainProductData = array(
                //'id'=> $productInfo['product_id'],
                'name' => $skuInfo['subject'],
                'description' => $skuInfo['detail'],
                'tags' => join(',', $skuInfo['tags']),
                'brand' => $skuInfo['brand'],
                //'main_image'=> array_shift($remoteImages),
                //'extra_images'=> join('|', $remoteImages)
            );

            if ($skuImage) {
                $remoteImages = ProductImageAdd::getImagesFromRemoteAddressByFileName(array_values($skuImage), $updateInfo['sku'], $updateInfo['account_id'], Platform::CODE_WISH);
                if (count($skuImage) != count($remoteImages)) {
                    throw new \Exception("Get remote images from API failed");
                }

            } else {
                $remoteImages = array_map(function ($k) {
                    return array_shift(explode('?', $k));
                }, explode(",", $uploadedImages));
            }
            if ($remoteImages) {
                $mainProductData['main_image'] = array_shift($remoteImages);
                $mainProductData['extra_images'] = join('|', $remoteImages);
            }
            $variantNeedUpdate = array();
            $variantNeedCreate = array();
            $variantNeedDisable = array();

            foreach ($variants as $variant) {
                if (!isset($variant['action'])) {
                    throw new \Exception("Action parameter lost for this variant");
                }
                switch ($variant['action']) {
                    case 'create':
                        $skuEncrypt = new encryptSku();
                        $variantNeedCreate[] = array(
                            'parent_sku' => $updateInfo['online_sku'],
                            'sku' => $variant['sku'],
                            'online_sku' => $skuEncrypt->getEncryptSku($variant['sku']),
                            'color' => $variant['color'],
                            'size' => $variant['size'],
                            'inventory' => $variant['inventory'],
                            'price' => $variant['price'],
                            'shipping' => $variant['shipping'],
                            'msrp' => $variant['msrp'],
                            'upload_action' => WishProductVariantsUpdate::VARIANT_ACTION_CREATE,
                            //'main_image'=> ''
                        );
                        break;

                    case 'update':
                        $variantNeedUpdate[] = array(
                            'online_sku' => $existsVariantMapper[$variant['sku']],
                            'sku' => $variant['sku'],
                            'color' => $variant['color'],
                            'size' => $variant['size'],
                            'inventory' => $variant['inventory'],
                            'price' => $variant['price'],
                            'shipping' => $variant['shipping'],
                            'msrp' => $variant['msrp'],
                            //'upload_action' => WishProductVariantsUpdate::VARIANT_ACTION_UPDATE,
                            //'main_image'=> ''
                        );
                        break;
                    case 'disable':
                        //beiyong
                        break;
                    default:
                        throw new \Exception("Action parameter lost for this variant");
                        break;
                }
            }

            $variantsData = array_merge($variantNeedCreate, $variantNeedUpdate, $variantNeedDisable);

            $mainProductData['variants'] = $variantsData;
            WishProductUpdate::model()->saveInfo($updateInfo['listing_id'], $updateInfo['account_id'], $mainProductData);
            echo $this->successJson(
                array(
                    'message'=> 'Successful'
                )
            );

        }catch (\Exception $e) {
            echo $e->getMessage();
            echo $this->failureJson(
                array('message'=> $e->getMessage())
            );
        }

    }
}