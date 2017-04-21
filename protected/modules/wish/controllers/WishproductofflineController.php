<?php

/**
 * @desc wish下架记录
 * @author hanxy
 *
 */
class WishproductofflineController extends UebController
{

    /** @var object 模型实例 * */
    protected $_model = NULL;

    /**
     * (non-PHPdoc)
     * @see CController::init()
     */
    public function init()
    {
        $this->_model = new WishOffline();
    }

    /**
     * @desc 列表页
     */
    public function actionList()
    {
        $this->render("list", array("model" => $this->_model));
    }


    /**
     * 批量导入下架表单
     */
    public function actionBatchForm()
    {
        $accountList = WishAccount::model()->getAbleAccountList();
        $warehouseList = Warehouse::model()->getWarehousePairs();

        $this->render('batch-form', array(
            'accountList' => $accountList,
            'warehouseList' => $warehouseList
        ));
    }

    public function actionBatchOffline()
    {
        $listings = Yii::app()->request->getParam('listing', array());
        if (!$listings) {
            $data = array(
                'message'=> Yii::t('wish', 'Please select'),
            );
            echo $this->failureJson($data);
            Yii::app()->end();
        }

        $variationLists = WishListing::model()->getListingsWithByVariationIds($listings);


        if (!$variationLists) {
            $data = array(
                'message'=> Yii::t('wish', 'Please select'),
            );
            echo $this->failureJson($data);
            Yii::app()->end();
        }


        $insertData = array();
        foreach($variationLists as $listing) {
            //variation sku
            $insertData[] = array(
                'sku'           =>  $listing['sku'],
                'account_id'    =>  $listing['account_id'],
                'status'        =>	0,
                'create_user_id'=>	Yii::app()->user->id,
                'create_time'   =>	date('Y-m-d H:i:s'),
                'type'          => 1,//手工录入
            );
        }


        try{
            WishOfflineTask::model()->insertBatch($insertData);
        }catch (\Exception $e) {
            $data = array(
                'message'=> Yii::t('wish', 'Mark product disabled failed'),
            );
            echo $this->failureJson($data);
            Yii::app()->end();
        }

        $data = array(
            'message'=> Yii::t('wish', 'Successful'),
        );
        echo $this->successJson($data);
       # var_dump($listingQueue);
    }

    public function actionSearchListing()
    {
        #error_reporting(2048);
        #ini_set("display_errors", 1);

        try {

            $accountId = Yii::app()->request->getParam('account', null);
            $warehouseId = Yii::app()->request->getParam('warehouse', null);
            $sku = Yii::app()->request->getParam('sku', '');

            $skus = array();

            if ($sku) {
                $skus = explode("\n", $sku);
            }
            $uploadedFile = CUploadedFile::getInstanceByName('file');
            if ($uploadedFile) {

                if ($uploadedFile->hasError) {
                    throw new \Exception('Upload file error');
                } else {
                    $fp = fopen($uploadedFile->getTempName(), 'r');
                    while ($content = fgetcsv($fp)) {
                        $skus[] = $content[0];
                    }
                }
            }

            if (count($skus) > 1000) {
                throw new \Exception(Yii::t('wish', 'Max SKU limit'));
            }

            $skus = array_map(function ($e){
                return trim($e);
            }, $skus);

            $listings = WishListing::model()->searchListing($accountId, $skus, $warehouseId);

            $result = array();

            $warehouseList = WishOverseasWarehouse::model()->getWarehouseList();


            foreach($listings as $listing) {
                $listing['enabled'] = WishVariants::model()->getWishProductVariantStatusText($listing['enabled']);
                $listing['warehouse'] = isset($warehouseList[$listing['warehouse_id']])?$warehouseList[$listing['warehouse_id']]:"";
                if (!isset(  $result[$listing['listing_id']])) {
                    $result[$listing['listing_id']] =  $listing;
                }

                if ($listing['is_varation']) {
                    $result[$listing['listing_id']]['variations'][] = $listing;
                }
                $itemIdList[] = $listing['variation_product_id'];
                $accountIdList[] = $listing['account_id'];
            }

            $sellerUserList = User::model()->getPairs();
            $sellerList = WishProductSellerRelation::model()->findRelationByAccountAndItem($accountIdList, $itemIdList);


            $productSellerRelationInfo = array();

            foreach($sellerList as $seller) {
                $productSellerRelationInfo[$seller['item_id']] = isset($sellerUserList[$seller['seller_id']])?$sellerUserList[$seller['seller_id']] : '-';
            }

            $accountList = WishAccount::model()->getIdNamePairs();


            $this->render('partial-listing', array('listings' => $result, 'accountList'=> $accountList, 'sellerList'=> $productSellerRelationInfo, 'warehouseList'=> $warehouseList));
        } catch (\Exception $e) {
            $data = array(
                'message' => $e->getMessage()
            );
            echo $this->failureJson($data);
        }

    }


}