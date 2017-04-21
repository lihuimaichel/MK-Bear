<?php

/**
 * Created by PhpStorm.
 * User: ketu.lai
 * Date: 2017/3/8
 * Time: 15:20
 */
class ShopeestocklogController extends UebController
{


    /**
     * 产品管理列表
     */
    public function actionIndex()
    {
        $this->render("list", array(
            "model" => ShopeeStockUpdateLog::model()
        ));
    }

}

