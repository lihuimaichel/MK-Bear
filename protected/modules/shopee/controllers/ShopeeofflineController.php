<?php

/**
 * Created by PhpStorm.
 * User: ketu.lai
 * Date: 2017/3/8
 * Time: 15:20
 */
class ShopeeofflineController extends UebController
{

    /**
     * 产品管理列表
     */
    public function actionExclude()
    {
        $this->render("exclude", array(
            "model" => new ShopeeStockExcludeList(),
        ));
    }

    /**
     * 更改状态
     * @param $status
     */
    public function actionUpdateExcludeStatus($status)
    {
        try {

            if (!in_array($status,  ShopeeStockExcludeList::model()->getStatusOptions())) {
                throw new \Exception('Please special status');
            }

            $ids = Yii::app()->request->getParam('ids');
            $ids = explode(",", $ids);
            if (!$ids) {
                throw new \Exception('Please select sku');
            }

            foreach ($ids as $id) {

                ShopeeStockExcludeList::model()->updateStatus($id, $status);
            }

            echo $this->successJson(array(
                'message' => Yii::t('shopee', 'Successful'),
            ));
            Yii::app()->end();

        }catch (\Exception $e) {
            echo $this->failureJson(array(
                'message' => $e->getMessage()
            ));
        }
    }

    public function actionDeleteExcludeList()
    {
        try {
            $ids = Yii::app()->request->getParam('ids');
            $ids = explode(",", $ids);
            if (!$ids) {
                throw new \Exception('Please select sku');
            }

            foreach ($ids as $id) {

                ShopeeStockExcludeList::model()->deleteInfo($id);
            }

            echo $this->successJson(array(
                'message' => Yii::t('shopee', 'Successful'),
            ));
            Yii::app()->end();

        }catch (\Exception $e) {
            echo $this->failureJson(array(
                'message' => $e->getMessage()
            ));
        }
    }
    /**
     * 显示导入对话框
     */
    public function actionShowImportExcludeListForm()
    {
        $accountList = ShopeeAccount::model()->getAvailableIdNamePairs();

        $this->render("import-form", array('accountList'=> $accountList));
    }

    /**
     * 导入sku排除列表
     */
    public function actionImportExcludeList()
    {
        try {
            $skuList = Yii::app()->request->getParam('sku');
            $skuList = str_replace("\r\n", "\n", $skuList);
            $skuList = explode("\n", $skuList);

            $uploadedFile = CUploadedFile::getInstanceByName('file');
            if ($uploadedFile) {

                if ($uploadedFile->hasError) {
                    throw new \Exception('Upload file error');
                } else {
                    Yii::import('application.vendors.PHPExcel');
                    $objReader = PHPExcel_IOFactory::createReader('Excel2007');
                    $objReader->setReadDataOnly(true);
                    $objPHPExcel = $objReader->load($uploadedFile->getTempName());

                    $objWorksheet = $objPHPExcel->getActiveSheet();
                    $highestRow = $objWorksheet->getHighestRow();

                    $rows = array();

                    for ($row = 1; $row <= $highestRow; ++$row) {
                        $rows[$row] = $objWorksheet->getCellByColumnAndRow(0, $row)->getValue();
                    }

                    $skuList = array_merge($skuList, $rows);
                }
            }

            $accounts = Yii::app()->request->getParam('account', array(0));

            $excludeModel = new ShopeeStockExcludeList();

            foreach ($skuList as $sku) {

                $sku = trim($sku);

                foreach ($accounts as $account) {
                    if ($excludeModel->checkExists($sku, $account)) {
                        continue;
                    }
                    try {
                        $excludeModel->saveInfo(array(
                            'sku' => $sku,
                            'account_id' => $account,
                        ));
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }


            echo $this->successJson(array(
                'message' => Yii::t('shopee', 'Successful'),
            ));
            Yii::app()->end();


        } catch (\Exception $e) {
            echo $this->failureJson(array(
                'message' => $e->getMessage()
            ));
        }
    }

}

