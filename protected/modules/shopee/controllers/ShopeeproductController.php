<?php

/**
 * Created by PhpStorm.
 * User: ketu.lai
 * Date: 2017/3/8
 * Time: 15:20
 */
class ShopeeProductController extends UebController
{

    /**
     * 访问过滤配置
     *
     * @see CController::accessRules()
     */
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'users' => array(
                    '*'
                ),
//                'actions' => array (
//                   'pull',
//                    'index'
//                )
            )
        );
    }

    /**
     * 产品管理列表
     */
    public function actionIndex()
    {
        $attributes = ProductAttribute::model()->getAttributeOptions(ProductAttribute::PRODUCT_FEATURES_CODE);
        $options = array();
        $selectAttributeValue = Yii::app()->request->getParam('product_attribute_filter', array());
        foreach($attributes as $option) {
            $options[$option['attribute_value_id']] = array(
                'id'=>$option['attribute_value_id'],
                'name'=>str_replace("\\", "\\\\",
            $option['attribute_value_name']),
                'checked'=> in_array($option['attribute_value_id'], $selectAttributeValue)? true: false
            );
        }

        $this->render("list", array(
            "model" => new ShopeeProduct(),
            'attributeOptions'=> $options
        ));
    }

    /**
     * 所有账号拉取listing
     * @link /shopee/shopeeproduct/batch/accountId/1
     */
    public function actionBatch()
    {
        set_time_limit(3600 * 24);
        //error_reporting(2048);
        //ini_set('display_errors', 1);
        $accountIds = array();
        $accountId = Yii::app()->request->getParam('accountId', null);

        if ($accountId) {
            $accountIds = explode(",", $accountId);

            foreach ($accountIds as $accountId) {
                if ($accountId) {
                    try {
                        $shopeeLogModel = new ShopeeLog();
                        $running = $shopeeLogModel->checkRunning($accountId, ShopeeLog::EVENT_GET_PRODUCT);
                        if (!$running) {
                            throw new Exception("Event is running");
                        }

                        $logId = $shopeeLogModel->prepareLog($accountId, ShopeeLog::EVENT_GET_PRODUCT);

                        if (!$logId) {
                            throw new Exception(Yii::t('shopee', 'Log entity can not be created'));
                        }

                        try {
                            $shopeeLogModel->setRunning($logId);
                            $listing = ShopeeProduct::model()->pullListings($accountId);

                            $shopeeLogModel->setSuccess($logId, Yii::t('shopee', 'Successful, total :total listing(s)', array(':total' => $listing)));

                        } catch (\Exception $e) {
                            $shopeeLogModel->setFailure($logId, $e->getMessage());
                            throw $e;
                        }

                        unset($shopeeLogModel);

                    } catch (\Exception $e) {
                        echo $e->getMessage();
                    }
                }
            }
        } else {
            $accountList = ShopeeAccount::model()->getListByCondition('id, short_name, partner_id, site, account_name', 'status='.ShopeeAccount::STATUS_OPEN);
            $groupedAccount = array();
            foreach($accountList as $account) {
                $groupedAccount[$account['partner_id']][] = $account['id'];
            }
            foreach($groupedAccount as $accountIds) {
                Yii::app()->createUrl("/shopee/shopeeproduct/batch/accountId/"  . join(",", $accountIds));
                MHelper::runThread(Yii::app()->createUrl("/shopee/shopeeproduct/batch/accountId/"  . join(",", $accountIds)));
            }
        }
    }


    /**
     * 手动拉取listing
     */
    public function actionPull()
    {
        //ini_set("display_errors", 1);
        //error_reporting(2048);

        set_time_limit(3600);
        $itemList = array();
        if (Yii::app()->request->getIsPostRequest()) {

            foreach (Yii::app()->request->getParam('items', array()) as $key => $value) {
                if ($value) {
                    $itemList[] = $value;
                }
            }

            $accountId = Yii::app()->request->getParam('accountId');
            $account = ShopeeAccount::model()->findByPk($accountId);


            if (!$account) {
                $jsonData['message'] = Yii::t('shopee', 'Account not found');
                echo $this->failureJson($jsonData);
                Yii::app()->end();
            }


            $shopeeLogModel = ShopeeLog::model();
            $logId = $shopeeLogModel->prepareLog($accountId, ShopeeLog::EVENT_GET_PRODUCT);

            if (!$logId) {
                $jsonData['message'] = Yii::t('shopee', 'Log entity can not be created');
                echo $this->failureJson($jsonData);
                Yii::app()->end();
            }
            $shopeeLogModel->setRunning($logId);
            try {
                $listing = ShopeeProduct::model()->pullListings($accountId, $itemList);

                $shopeeLogModel->setSuccess($logId, Yii::t('shopee', 'Successful, total :total listing(s)', array(':total' => $listing)));

                $jsonData['message'] = Yii::t('shopee', 'Successful');
                echo $this->successJson($jsonData);
                Yii::app()->end();

            } catch (\Exception $e) {
                $shopeeLogModel->setFailure($logId, $e->getMessage());
            }
            $jsonData['message'] = Yii::t('shopee', 'Failed');
            echo $this->failureJson($jsonData);
            Yii::app()->end();
        }

        $accountList = ShopeeAccount::model()->getAbleAccountList();
        $this->render('pull', array(
            'accountList' => $accountList
        ));
    }

    /**
     * 检测action是否允许
     * @param $action
     * @return bool
     */
    private function checkAllowActionForUpdate($action)
    {

        $allowedAction = array(
            'updatePrice',
            'updateStock'
        );

        if (in_array($action, $allowedAction)) {
            return true;
        }
        return false;

    }

    /**
     * 显示更新dialog
     */
    public function actionUpdateForm()
    {
        $id = Yii::app()->request->getParam('id');
        $isVariant = Yii::app()->request->getParam('isVariant', false);
        $action = Yii::app()->request->getParam('action');
        if (!$this->checkAllowActionForUpdate($action)) {
            echo $this->failureJson(array('message' => Yii::t('shopee', 'Action is not allowed.')));
            Yii::app()->end();
        }
        $itemInfo = null;


        $itemInfo = ShopeeProductVariation::model()->getVariationInfoById($id, true);

        if (!$itemInfo) {
            echo $this->failureJson(array('message' => Yii::t('shopee', 'Item information is not found.')));
            Yii::app()->end();
        }

        $this->render('update-form-popup', array(
            'itemInfo' => $itemInfo,
            'isVariant' => $itemInfo['has_variation'],
            'action' => $action,
        ));
    }

    /**
     * 更新listing 价格，或者库存
     */
    public function actionUpdate()
    {
        $id = Yii::app()->request->getParam('id');
        $isVariant = Yii::app()->request->getParam('isVariant');
        $action = Yii::app()->request->getParam('action');
        $price = Yii::app()->request->getParam('price', null);
        $stock = Yii::app()->request->getParam('stock', null);
        $jsonData = array();

        if (!$this->checkAllowActionForUpdate($action)) {
            echo $this->failureJson(array('message' => Yii::t('shopee', 'Action is not allowed.')));
            Yii::app()->end();
        }

        $itemInfo = ShopeeProductVariation::model()->getVariationInfoById($id, true);


        if (!$itemInfo) {
            echo $this->failureJson(array('message' => Yii::t('shopee', 'Item information is not found.')));
            Yii::app()->end();
        }

        $accountId = $itemInfo['account_id'];
        $newValue = null;
        $oldValue = null;

        switch ($action) {
            case 'updatePrice':
                if ($price == null) {
                    echo $this->failureJson(Yii::t('shopee', 'New Price is not set'));
                    Yii::app()->end();
                }
                $newValue = $price;
                $oldValue = $itemInfo['price'];
                break;

            case 'updateStock':
                if ($stock == null) {
                    echo $this->failureJson(Yii::t('shopee', 'New stock is not set'));
                    Yii::app()->end();
                }
                $newValue = $stock;
                $oldValue = $itemInfo['stock'];
                break;
        }

        $logModel = ShopeeLog::model();
        $logId = $logModel->prepareLog($itemInfo['account_id'], $this->getEventNameByAction($action));


        if (!$logId) {
            $jsonData['message'] = Yii::t('shopee', 'Log entity can not be created');
            echo $this->failureJson($jsonData);
            Yii::app()->end();
        }
        $logModel->setRunning($logId);
        try {
            if (!$itemInfo['has_variation']) {
                ShopeeProduct::model()->updateOnlineListing($accountId, $itemInfo['listing_id'], $newValue, $action);
            } else {
                ShopeeProductVariation::model()->updateOnlineListing($accountId, $itemInfo['listing_id'], $itemInfo['variation_id'], $newValue, $action);
            }
            $logModel->setSuccess($logId, Yii::t('shopee', 'Update item(:itemId) from :old to :new', array(':itemId' => $itemInfo['listing_id'], ':old' => $oldValue, ':new' => $newValue)));
            $jsonData['message'] = Yii::t('shopee', 'Successful');
            $jsonData['callbackType'] = 'closeCurrent';
            $jsonData['navTabId'] = 'page' . UebModel::model('Menu')->getIdByUrl('/shopee/shopeeproduct/');
            echo $this->successJson($jsonData);
            Yii::app()->end();

        } catch (\Exception $e) {
            $logModel->setFailure($logId, $e->getMessage());
        }

        $jsonData['message'] = Yii::t('shopee', 'Failed');
        echo $this->failureJson($jsonData);
        Yii::app()->end();
    }


    /**
     * 批量更新listing 弹出窗口
     */
    public function actionBatchUpdateForm()
    {
        $ids = Yii::app()->request->getParam("id", "");
        $action = Yii::app()->request->getParam('action');

        if (!$this->checkAllowActionForUpdate($action)) {
            echo $this->failureJson(array('message' => Yii::t('shopee', 'Action is not allowed.')));
            Yii::app()->end();
        }

        if (!$ids) {
            $jsonData['message'] = Yii::t('shopee', 'Please select');
            $jsonData['callbackType'] = 'closeCurrent';
            echo $this->failureJson($jsonData);
            Yii::app()->end();
        }


        $listings = ShopeeProductVariation::model()->getVariationInfoByItemId(explode(',', $ids), null, true);

        $this->render('batch-update-form-popup', array(
            'action' => $action,
            'listings' => $listings
        ));

    }

    /**
     * 获取日志event name
     * @param $action
     * @return 产品更新
     */
    private function getEventNameByAction($action)
    {
        $eventName = ShopeeLog::EVENT_PRODUCT_UPDATE;
        switch ($action) {
            case 'updatePrice':
                $eventName = ShopeeLog::EVENT_PRODUCT_PRICE_UPDATE;
                break;

            case 'updateStock':
                $eventName = ShopeeLog::EVENT_PRODUCT_STOCK_UPDATE;
                break;
        }
        return $eventName;
    }

    /**
     * 批量更新listing
     */
    public function actionBatchUpdate()
    {
        try{
            $data = Yii::app()->request->getParam("data", "");
            $action = Yii::app()->request->getParam('action');

            if (!$this->checkAllowActionForUpdate($action)) {
                throw new \Exception( Yii::t('shopee', 'Action is not allowed.'));
            }


            if (!$data) {
                throw new \Exception(Yii::t('shopee', 'Please select'));
            }
            $listings = ShopeeProductVariation::model()->getVariationInfoByItemId(array_keys($data), null, true);

            $groupListingsByAccount = array();
            foreach ($listings as $listing) {
                $groupListingsByAccount[$listing['account_id']][] = $listing;
            }

            foreach ($groupListingsByAccount as $accountId => $listings) {

                foreach ($listings as $listing) {
                    if (!isset($data[$listing['variation_id']])) {
                        continue;
                    }
                    $logModel = new ShopeeLog();

                    $logId = $logModel->prepareLog($accountId, $this->getEventNameByAction($action));
                    if (!$logId) {
                        continue;
                    }

                    $logModel->setRunning($logId);

                    $value = $data[$listing['variation_id']];
                    $accountId = $listing['account_id'];
                    $isVariant = false;
                    if ($listing['has_variation']) {
                        $isVariant = true;
                    }
                    try {
                        if (!$isVariant) {
                            ShopeeProduct::model()->updateOnlineListing($accountId, $listing['variation_id'], $value['value'], $action);
                        } else {
                            ShopeeProductVariation::model()->updateOnlineListing($accountId, $listing['listing_id'], $listing['variation_id'], $value['value'], $action);
                        }
                        $logModel->setSuccess($logId);
                    } catch (\Exception $e) {
                        //$partialErrors[] = $e->getMessage();
                        $logModel->setFailure($logId, $e->getMessage());
                    }
                }
            }
            $jsonData['message'] = Yii::t('shopee', 'Successful');
            $jsonData['callbackType'] = 'closeCurrent';
            $jsonData['navTabId'] = 'page' . UebModel::model('Menu')->getIdByUrl('/shopee/shopeeproduct/');
            echo $this->successJson($jsonData);
            Yii::app()->end();
        }catch (\Exception $e) {
            echo $this->failureJson(array(
                'message'=> $e->getMessage()
            ));
        }
    }


    /**
     *  shopee平台下SKU可用库存，如果SKU可用库存数量小于等于2，调0。可用库存数量大于2，更新实际库存
     */
    public function actionAutoSetupStock()
    {
        error_reporting(2048);
        ini_set('error_reporting', 1);

        set_time_limit(3600);
        $offset = 0;
        $limit = 1000;


        $singleSku = Yii::app()->request->getParam('sku', false);
        $testAccountId = Yii::app()->request->getParam('account', 1);


        while(true) {
            if ($singleSku) {
                $listings = ShopeeProduct::model()->getDbConnection()->createCommand()->select('
                    p.id, p.sku, p.account_id, p.site_code, p.listing_id, p.name, p.seller_sku, p.main_image_url, p.status,
                    p.has_variation,
                    v.id as vid, v.variation_id, v.variation_option_name, v.variation_sku, v.sku, v.status, v.stock                    
                    ')
                    ->from(ShopeeProduct::model()->tableName() . ' AS p ')
                    ->join(ShopeeProductVariation::model()->tableName(). ' AS v', 'p.id = v.parent_id')
                    ->where('p.sku=:sku OR v.sku=:sku', array(':sku'=> $singleSku))
                    ->andWhere('p.account_id = :accountId', array(':accountId'=> $testAccountId))
                    ->queryAll();

                //$listings = ShopeeProduct::model()->getListingsBySku(array($testSku), 6);

            } else {
                $listings = ShopeeProduct::model()->chunk($limit, $offset);
            }

            $offset += $limit;

            if (!$listings) {
                break;
            }

            $listingsSku = array();

            foreach ($listings as $listing) {
                $listingsSku[$listing['sku']] = $listing;
            }

            $variantSku = array_unique(array_keys($listingsSku));

            $productStockData = WarehouseSkuMap::model()->getProductDataWithSkuListAndStockFilter($variantSku);
            $stockData = array();
            foreach ($productStockData as $data) {
                $stockData[$data['sku']] = $data['available_qty'];
            }

            $updateAction = 'updateStock';
            $indexer = 0;
            foreach ($listings as $listing) {
                //延时处理
                if ($indexer % 400 == 0) {
                    sleep(60);
                }

                if (!isset($stockData[$listing['sku']])) {
                    echo 'Stock data not found';
                    continue;
                }


                $accountId = $listing['account_id'];

                //检测是否在排除列表
                if (ShopeeStockExcludeList::model()->checkExists($listing['sku'], $accountId,
                    ShopeeStockExcludeList::STATUS_ENABLED)) {
                    echo '在排除列表里面';
                    continue;
                }

                $currentQty = $listing['stock'];

                $updateQty = $availableQty = $stockData[$listing['sku']];

                if ($availableQty <= 2) {
                    $updateQty = 0;
                }
                
                $stockUpdateLogData = array(
                    'old_qty'=> $currentQty,
                    'new_qty'=> $updateQty,
                    'listing_id'=> $listing['listing_id'],
                    'online_sku'=> $listing['variation_sku'],
                    'sku'=> $listing['sku'],
                    'account_id'=> $listing['account_id'],
                );
                $shopeeStockUpdateLogModel = new ShopeeStockUpdateLog();
                $logId = null;
                try {
                    $logId = $shopeeStockUpdateLogModel->saveInfo($stockUpdateLogData, ShopeeStockUpdateLog::UPDATE_ACTION_AUTO_SETUP_QTY_WITH_AVAILABLE_QTY);
                }catch (\Exception $e) {
                    echo $e->getMessage();
                    continue;
                }

                if (!$logId) {
                    echo 'Log not found';
                    continue;
                }
                $indexer++;
                try {
                    if (!$listing['has_variation']) {
                        ShopeeProduct::model()->updateOnlineListing($accountId, $listing['variation_id'], $updateQty, $updateAction);
                    } else {
                        ShopeeProductVariation::model()->updateOnlineListing($accountId, $listing['listing_id'], $listing['variation_id'], $updateQty, $updateAction);
                    }
                } catch (\Exception $e) {
                    echo $e->getMessage();
                    $shopeeStockUpdateLogModel->updateStatus($logId, ShopeeStockUpdateLog::LOG_STATUS_FAILED, $e->getMessage());
                    continue;
                }

                $shopeeStockUpdateLogModel->updateStatus($logId, ShopeeStockUpdateLog::LOG_STATUS_SUCCESS);
            }
            if ($singleSku) {
                break;
            }
        }

    }

    public function actionExportListingForm()
    {
        $accountList = ShopeeAccount::model()->getAvailableIdNamePairs();
        $this->render('export-form', array('accountList' => $accountList));
    }


    private function exportProductAsShopeeFormat($productInfo)
    {

        $exportData = array();
        $childExportData = array();
        $imageUrlData = array();
        $noChildExportData = array();
        $maxChildCount = 0;
        $xlsHeader = array(
            '主SKU',
            //'子SKU',
            '中文标题',
            '中文描述',
            'Included（中文）',
            '英文标题',
            '英文描述',
           // 'Included（英文）',
            '产品状态',
            '产品属性',
            '公司分类',
            '毛重',
            '成本',
            '产品尺寸（长*宽*高）',
            '包装尺寸（长*宽*高）',
            //'产品图片URL链接（9张）'
        );

        $indexer = 0;

        $oldApiAddress =ProductImageAdd::getRestfulAddress();
        $apiAddress = ProductImageAdd::getRestfulAddress(null, Platform::CODE_SHOPEE);
        foreach ($productInfo as $key => $product) {
            $imageUrls = ProductImageAdd::getImageUrlFromRestfulBySku($product['sku'], 'ft', 'normal', 100, 100, Platform::CODE_SHOPEE);

            $imageUrls = array_map(function ($i) use ($apiAddress, $oldApiAddress) {
                $imageUrl = array_shift(explode("?", $i));
                $imageUrl = str_replace($oldApiAddress, 'http://w.neototem.com', $imageUrl);
                return str_replace($apiAddress, 'http://w.neototem.com', $imageUrl);
            }, $imageUrls);

            $imageUrls = array_slice($imageUrls, 0, 9);
            $imageUrlData[$indexer] = $imageUrls;


            // description
            $val = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", function ($match){
                if(in_array(strtolower($match['TT']), array('</p>', '<p/>', '<br/>', '</br>', '<br />'))){
                    return "\n";
                }else{
                    return '';
                }
            }, $product['english_description'] );
            $val = preg_replace("/(\n){3,}|\r\n/ie", "\n", $val);
            $description = strip_tags($val);
            //included;
            $val = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", function ($match){
                if(in_array(strtolower($match['TT']), array('</p>', '<p/>', '<br/>', '</br>', '<br />'))){
                    return "\n";
                }else{
                    return '';
                }
            }, $product['english_included'] );
            $val = preg_replace("/(\n){3,}|\r\n/ie", "\n", $val);
            $included = strip_tags($val);


            $infringement = $product['infringement']? ProductInfringe::model()
                ->getProductInfringementList($product['infringement']):'';


            $attributes = ProductSelectAttribute::model()->getSkuAttributeListBySku($product['sku'], 'a.attribute_code = "product_features"');
            $productSpecialAttributes = array();
            foreach ($attributes as $attribute) {
                foreach($attribute as $k=> $v){
                    $productSpecialAttributes[] = $v;
                }
            }

            $exportData[$indexer] = array(
                'A' => $product['sku'],
                //'B' => '',//$skuInfo['sub_sku'],
                'C' => $product['chinese_title'],// 中文标题
                'D' => $product['chinese_description'], // 中文描述
                'E' => $product['chinese_included'], //included 中文
                //'F' => $skuInfo['name'],
                'F' => $product['english_title'], //英文标题
                'G' => $description  . "\n". 'Included:' . "\n" .$included, //英文描述
                //'H' => $product['english_included'],  //included 英文
                'I' => $product['security_level'] . ' ' .$infringement , //产品状态
                'J' => \join(',', $productSpecialAttributes), //产品属性
                'K' => $product['category_cn_name'], //公司分类
                'L' => $product['product_weight'], //毛重
                'M' => $product['product_cost'], //成本
                'N' => join("*", array($product['product_length'], $product['product_width'],
                 $product['product_height'])), //产品尺寸
                'O' => join("*", array($product['pack_product_length'], $product['pack_product_width'],
                 $product['pack_product_height'])), //包装尺寸
                //'P' => '', //图片链接
            );

            $attributeCodeList = array();

            if ($product['product_is_multi'] > 0) {
                $child = array();
                $attributeList = ProductSelectAttribute::model()->getSelectedAttributeValueSKUListByMainProductId($product['id']);
                foreach ($attributeList as $attribute) {

                    $child[$attribute['sku']][] = $attribute['attribute_value_name'];
                    $attributeCodeList[] = $attribute['attribute_code'];
                }

                $children = Product::model()->getFullProductInfoBySku(array_keys($child));

                if (count($children) > $maxChildCount) {
                    $maxChildCount = count($children);
                }

                if (!$children) {
                    $noChildExportData[$indexer] = true;
                }

                foreach ($children as $c) {
                    $childData = array(
                        'sku' => $c['sku'],
                       // 'price' => $c['product_cost'],
                       // 'stock'=> '10',
                    );
                    if (isset($child[$c['sku']])) {
                        $childData['Property'] = \join(',', $child[$c['sku']]);
                    }
                    $childData['price'] = $c['product_cost'];
                    $childData['stock'] = 10;
                    $childExportData[$indexer][] = $childData;
                }
            } else {
                $noChildExportData[$indexer] = true;
            }
            $indexer++;
        }

        $childrenXlsHeader =array('SKU', 'Property', 'Price', 'Stock');

        foreach(range(1, $maxChildCount) as $row) {
            $rowHeaderPrefix = '子SKU-' . $row;
            foreach ($childrenXlsHeader as $key) {
                $xlsHeader[] = $rowHeaderPrefix . '-' . $key;
            }
        }


        foreach ($noChildExportData as $index => $value) {
            foreach (range(1, $maxChildCount ) as $i) {
                foreach ($childrenXlsHeader as $key) {
                    $exportData[$index][] = '';
                }
            }
        }

        foreach ($childExportData as $index => $children) {
            foreach ($children as $values) {
                foreach ($values as $k => $v) {
                    $exportData[$index][] = $v;
                }
            }
            if (count($children) !== $maxChildCount) {
                foreach (range(1, $maxChildCount - count($children)) as $i) {
                    foreach ($childrenXlsHeader as $key) {
                        $exportData[$index][] = '';
                    }
                }
            }
        }


        foreach($imageUrlData as $index=> $imageUrls) {
            $exportData[$index] = array_merge($exportData[$index], $imageUrls);

        }

        return array(
            $xlsHeader,
            $exportData
        );
    }

    private function exportProductAsDefaultFormat($productInfo)
    {
        $exportData = array();
        $xlsHeader = array(
            '主SKU',
            '子SKU',
            '中文标题',
            '中文描述',
            'Included（中文）',
            '英文标题',
            '英文描述',
            'Included（英文）',
            '产品状态',
            '产品属性',
            '公司分类',
            '毛重',
            '成本',
            '产品尺寸（长*宽*高）',
            '包装尺寸（长*宽*高）',
            '产品图片URL链接（9张）'
        );

        $indexer = 0;

        $oldApiAddress =ProductImageAdd::getRestfulAddress();
        $apiAddress = ProductImageAdd::getRestfulAddress(null, Platform::CODE_SHOPEE);

        foreach ($productInfo as $key => $product) {

            $imageUrls = ProductImageAdd::getImageUrlFromRestfulBySku($product['sku'], 'ft', 'normal', 100, 100, Platform::CODE_SHOPEE);

            $imageUrls = array_map(function ($i) use ($apiAddress, $oldApiAddress) {
                $imageUrl = array_shift(explode("?", $i));
                $imageUrl = str_replace($oldApiAddress, 'http://w.neototem.com', $imageUrl);
                return str_replace($apiAddress, 'http://w.neototem.com', $imageUrl);
            }, $imageUrls);

            $imageUrls = array_slice($imageUrls, 0, 9);
            $infringement = $product['infringement']? ProductInfringe::model()
                ->getProductInfringementList($product['infringement']):'';
            $exportData[$indexer] = array(
                'A' => $product['sku'],
                'B' => '',//$skuInfo['sub_sku'],
                'C' => $product['chinese_title'],// 中文标题
                'D' => $product['chinese_description'], // 中文描述
                'E' => $product['chinese_included'], //included 中文
                //'F' => $skuInfo['name'],
                'F' => $product['english_title'], //英文标题
                'G' => $product['english_description'], //英文描述
                'H' => $product['english_included'],  //included 英文
                'I' => $product['security_level'] . ' ' . $infringement, //产品状态
                'J' => '', //产品属性
                'K' => $product['category_cn_name'], //公司分类
                'L' => $product['product_weight'], //毛重
                'M' => $product['product_cost'], //成本
                'N' => join("*", array($product['product_length'], $product['product_width'], $product['product_height'])), //产品尺寸
                'O' => join("*", array($product['pack_product_length'], $product['pack_product_width'], $product['pack_product_height'])), //包装尺寸
                'P' => '', //图片链接
            );
            $exportData[$indexer] = array_merge($exportData[$indexer], $imageUrls);
            $indexer++;
            if ($product['product_is_multi'] > 0) {

                $child = array();
                $attributeList = ProductSelectAttribute::model()->getSelectedAttributeValueSKUListByMainProductId($product['id']);
                foreach ($attributeList as $attribute) {
                    $child[$attribute['sku']] [] = $attribute['attribute_value_name'];
                }

                $children = Product::model()->getFullProductInfoBySku(array_keys($child));

                foreach ($children as $c) {
                    $imageUrls = ProductImageAdd::getImageUrlFromRestfulBySku($c['sku'], 'ft');
                    $imageUrls = array_map(function ($i) use ($apiAddress, $oldApiAddress) {
                        $imageUrl = array_shift(explode("?", $i));
                        $imageUrl = str_replace($oldApiAddress, 'http://w.neototem.com', $imageUrl);
                        return str_replace($apiAddress, 'http://w.neototem.com', $imageUrl);
                    }, $imageUrls);

                    $imageUrls = array_slice($imageUrls, 0, 9);
                    $infringement = $c['infringement']? ProductInfringe::model()
                        ->getProductInfringementList($c['infringement']):'';
                    $exportData[$indexer] = array(
                        'A' => $product['sku'],
                        'B' => $c['sku'],
                        'C' => $c['chinese_title'],// 中文标题
                        'D' => $c['chinese_description'], // 中文描述
                        'E' => $c['chinese_included'], //included 中文
                        //'F' => $skuInfo['name'],
                        'F' => $c['english_title'], //英文标题
                        'G' => $c['english_description'], //英文描述
                        'H' => $c['english_included'],  //included 英文
                        'I' => $product['security_level'] . ' ' . $infringement, //产品状态
                        'J' => join(",", $child[$c['sku']]), //产品属性
                        'K' => $c['category_cn_name'], //公司分类
                        'L' => $c['product_weight'], //毛重
                        'M' => $c['product_cost'], //成本
                        'N' => join("*", array($product['product_length'], $product['product_width'], $product['product_height'])), //产品尺寸
                        'O' => join("*", array($product['pack_product_length'], $product['pack_product_width'], $product['pack_product_height'])), //包装尺寸
                        'P' => '', //图片链接
                    );

                    $exportData[$indexer] = array_merge($exportData[$indexer], $imageUrls);

                    $indexer++;
                }

            }


        }

        return array(
            $xlsHeader,
            $exportData
        );

    }

    public function actionExportListing()
    {
        try {
            $uploadedFile = CUploadedFile::getInstanceByName('file');
            if (!$uploadedFile) {
                throw new \Exception('Please upload file');
            }
            $skus = array();

            if ($uploadedFile->hasError) {
                throw new \Exception('Upload file error');
            } else {
                $fp = fopen($uploadedFile->getTempName(), 'r');
                while ($content = fgetcsv($fp)) {
                    $skus[] = $content[0];
                }
            }
            // output format
            $format = Yii::app()->request->getParam('format', 'default');

            /* $skuInfos = ShopeeProduct::model()->getListingsBySku($skus);
             if (!$skuInfos) {
                 throw new \Exception("No SKU found in shopee in listing");
             }*/

            $productInfo = Product::model()->getFullProductInfoBySku($skus);
            

/*            $infoMapper = array();
            foreach ($productInfo as $product) {
                $infoMapper[$product['sku']] = $product;
            }*/

            $exportData = array();
            $xlsHeader = array();
            switch($format) {
                case 'shopee':
                    list($xlsHeader, $exportData) = $this->exportProductAsShopeeFormat($productInfo);
                    break;
                case 'default':
                default:
                   list($xlsHeader, $exportData) = $this->exportProductAsDefaultFormat($productInfo);
                   break;
            }

            Yii::import('application.vendors.PHPExcel');
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);

            foreach ($xlsHeader as $k => $v) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($k, 1, $v);
            }

            foreach ($exportData as $key => $value) {
                $key += 2;
                $indexer = 0;
                foreach ($value as $k => $v) {

                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($indexer, $key, $v);
                    $indexer++;
                }
            }
            $dateTime = new \DateTime();
            $outputFilename = "shopee_export-" . $dateTime->format("Y-m-d-h-i") . '.xls';
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $outputFilename . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
            Yii::app()->end();

            //$PHPExcel->download_excel(true);

        } catch (\Exception $e) {
            echo $this->failureJson(array('message' => $e->getMessage()));
            Yii::app()->end();
        }

    }
}

