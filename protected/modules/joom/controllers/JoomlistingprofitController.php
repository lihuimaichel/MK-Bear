<?php

/**
 * User: ketu.lai <ketu.lai@gmail.com>
 * Date: 2017/4/18 16:34
 */
class JoomlistingprofitController extends UebController
{

    public function actionCalculateProfitCron()
    {
        set_time_limit(3600 * 4);
        ini_set('memory_limit', -1);
        $joomListingModel = new  JoomListing();
        $joomListingProfitModel = new JoomListingProfit();
        $joomProductAddModel = new JoomProductAdd();
        try {
            $limit = 500;
            $offset = 0;
            while (true) {
                $listings = $joomListingModel->getListingWithVariants($limit, $offset);


                foreach ($listings as $listing) {
                    $priceObject = $joomProductAddModel->getListingProfit($listing['sub_sku'], $listing['price']);

                    $profit = $priceObject->getProfit();
                    $profitRate = $priceObject->getProfitRate();
                    if (!$profitRate) {
                        continue;
                    }
                    $profitData = array(
                        'profit'=> $profit,
                        'profit_rate'=> $profitRate,
                        'sku'=> $listing['sub_sku'],
                        'sale_price'=> $listing['price']
                    );
                    $joomListingProfitModel->createOrUpdate($listing['variation_product_id'],
                        $listing['account_id'], $profitData);
                }

                if (count($listings) != $limit) {
                    break;
                }
                $offset += $limit;

            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}