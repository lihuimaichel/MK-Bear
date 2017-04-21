<?php

/**
 * @desc 产品刊登
 * @author lihy
 *
 */
class JoomproductaddlistController extends UebController
{
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'users' => '*',
                'actions' => array('index'))
        );
    }

    /**
     * @desc 待刊登产品列表
     */
    public function actionIndex()
    {
        $model = new JoomProductAdd;
        $disabledCheckbox = isset($_REQUEST['upload_status']) && $_REQUEST['upload_status'] == JoomProductAdd::JOOM_UPLOAD_SUCCESS ? true : false;
        $this->render('index', array(
            'model' => $model,
            'disabledCheckbox' => $disabledCheckbox
        ));
    }

    /**
     * @desc 批量删除
     * @throws Exception
     */
    public function actionBatchdel()
    {
        try {
            $ids = Yii::app()->request->getParam('ids');
            if (empty($ids)) {
                throw new Exception(Yii::t('joom_listing', 'No chose any one'));
            }
            $ids = explode(",", $ids);
            $productAddModel = new JoomProductAdd;
            if ($productAddModel->deleteProductAddInfoByIds($ids, 'upload_status!=:upload_status', array(':upload_status' => JoomProductAdd::JOOM_UPLOAD_SUCCESS))) {
                //删除子产品
                $productVariantsAddModel = new JoomProductVariantsAdd;
                $productVariantsAddModel->deleteProductVariantsAddInfoByAddIds($ids);
                echo $this->successJson(array(
                    'message' => Yii::t('system', 'Delete successful'),
                ));
                Yii::app()->end();
            }
            throw new Exception(Yii::t('system', 'Delete failure'));
        } catch (Exception $e) {
            echo $this->failureJson(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * 上传CSV文件窗口
     */
    public function actionUploadCsvForm()
    {
        $this->render('import-csv-form');
    }

    /**
     * 上传csv文件
     */
    public function actionUploadCsv()
    {
        try {
            $uploadedFile = CUploadedFile::getInstanceByName('file');
            if (!$uploadedFile) {
                throw new \Exception('Please upload file');
            }

            if ($uploadedFile->hasError) {
                throw new \Exception('Upload file error');
            } else {


                $this->processUploadedFile20170324($uploadedFile);

/*                switch ($uploadedFile->getName()) {

                    case '宠物店铺-批量刊登.xlsx':
                        $this->processUploadedFile20170324($uploadedFile);
                        break;

                    default:
                        throw new \Exception('Invalid file');
                }*/


            }
        } catch (\Exception $e) {

        }
    }

    private function processUploadedFile20170324($uploadedFile)
    {

        set_time_limit(3600);
        //ini_set('error_reporting', 1);
        //error_reporting(2048);

        /**
         * @param $node
         * @return string
         * remove html tag
         */

        $description = '';
        $loop = function ($node, &$description) use (&$loop, &$description) {

            if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $node) {
                    $loop($node);
                }
            } else {
                $prefix = "\n";

                if ($node->parentNode && in_array($node->parentNode->nodeName, array('div', 'p', 'br'))) {
                    $description .= $node->nodeValue . $prefix;
                } else {
                    $description .= $node->nodeValue;
                }
            }

            return $description;
        };


        Yii::import('application.vendors.PHPExcel');

        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($uploadedFile->getTempName());

        $objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
        $rows = array();

        for ($row = 2; $row <= $highestRow; ++$row) {
            for ($col = 0; $col <= $highestColumnIndex; ++$col) {
                $rows[$row][$col] = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
        }


        $accountList = JoomAccount::model()->getIdNamePairs();
        $accountList = array_flip($accountList);
        $indexer = 0;
        foreach ($rows as $key => $value) {

            $indexer++;
            try {

                list($store, $sku, $tags, $title) = $value;
                echo '开始导入' . $sku;
                echo '<br>';

                if (!isset($accountList[$store])) {
                    throw new \Exception('账号不存在');
                }

                $accountId = $accountList[$store];

                // check if in upload processing;

                $addInfo = JoomProductAdd::model()->getProductAddInfo('parent_sku=:parentSku AND account_id=:accountId', array(':parentSku' => trim($sku), ':accountId' => $accountId));

                if ($addInfo) {
                    throw new \Exception('产品已经刊登');
                }
                $productInfo = array();
                try {
                    $productInfo = Product::model()->getFullProductInfoBySku(trim($sku));
                }catch (\Exception $e){
                    throw new \Exception('产品信息不存在');
                    continue;
                }
                if (!$productInfo) {
                    throw new \Exception('产品信息不存在');
                }
                $sku = $productInfo['sku'];
                $description = "Description:\n" . $productInfo['english_description'];
                $description .= "\nIncluded:\n" . $productInfo['english_included'];

                /*
                $dom = new \DOMDocument();
                $dom->loadHTML('<div id="remove-tag">' . $description . '</div>');
                $body = $dom->getElementById('remove-tag');

                $description = "";
                $loop($body, $description);*/


                $val = preg_replace_callback("/(?<TT><\/?(?<TAG>\w+).*?>)/im", function ($match){
                    if(in_array(strtolower($match['TT']), array('</p>', '<p/>', '<br/>', '</br>', '<br />'))){
                        return "\n";
                    }else{
                        return '';
                    }
                }, $description);
                $val = preg_replace("/(\n){2,}|\r\n/ie", "\n", $val);
                $description = strip_tags($val);



                $imagePath = ProductImageAdd::getImagesPathFromRestfulBySkuAndType($sku, null, Platform::CODE_JOOM);

                if (!$imagePath) {
                    throw new \Exception('图片信息不存在');


                }
                $mainImage = '';
                if (!isset($imagePath['zt'])) {
                    $mainImage = array_shift($imagePath['ft']);
                } else {
                    $mainImage = array_shift($imagePath['zt']);
                }

                if (!$mainImage) {
                    throw new \Exception('主图不存在');

                }


                $data = array(
                    'parent_sku' => $sku,
                    'subject' => $title,
                    'detail' => $description,
                    'tags' => rtrim($tags, ','),
                    'brand' => '',
                    'main_image' => $mainImage,
                    'extra_images' => join("|", $imagePath['ft']),
                    'product_is_multi' => $productInfo['product_is_multi'],
                    'variants' => array()
                );
                $variantData = array();

                $isSpecial = Product::model()->getAttributeBySku($sku, 'product_features');

                /**
                 * @param $productCost
                 * @param $isSpecial
                 * @return mixed
                 */
                $calculatePrice = function ($productInfo) use ($isSpecial) {

                    if ($productInfo['avg_price'] <= 0) {
                        $productCost = $productInfo['product_cost'];   //加权成本
                    } else {
                        $productCost = $productInfo['avg_price'];      //产品成本
                    }

                    $productCost = JoomProductAdd::model()->productCostToPublishCurrency($productCost);
                    $profitRate = 0.10;
                    $shippingCode = '';

                    if ($productCost < 5 && !$isSpecial) {
                        $shippingCode = 'cm_zp_pcxb';
                    } elseif ($productCost < 5 && $isSpecial) {
                        $shippingCode = 'cm';
                    } elseif ($productCost >= 5 && !$isSpecial) {
                        $shippingCode = 'ghxb_zp_gh';
                    } elseif ($productCost >= 5 && $isSpecial) {
                        $shippingCode = 'ghxb';
                    }

                    $salePrice = JoomProductAdd::model()->getSalePriceWithProfitRate($productInfo['sku'], $profitRate,
                        $shippingCode);

                    if (in_array($shippingCode, array('cm_zp_pcxb', 'cm')) && $salePrice->getSalePrice() >= 5) {
                        if ($isSpecial) {
                            $shippingCode = 'ghxb';
                        } else {
                            $shippingCode = 'ghxb_zp_gh';
                        }

                        $salePrice = JoomProductAdd::model()->getSalePriceWithProfitRate($productInfo['sku'], $profitRate,
                            $shippingCode);
                    }
                    return $salePrice;
                };

                $child = array();
                $attributeList = ProductSelectAttribute::model()->getSelectedAttributeValueSKUListByMainProductId($productInfo['id']);
                foreach ($attributeList as $attribute) {
                    if ($attribute['attribute_showtype_value'] == 'checkbox') {
                        $child[$attribute['sku']][$attribute['attribute_code']][] = $attribute['attribute_value_name'];
                    } else {
                        $child[$attribute['sku']][$attribute['attribute_code']] = $attribute['attribute_value_name'];
                    }
                }

                if ($child) {
                    $children = Product::model()->getFullProductInfoBySku(array_keys($child));

                    foreach ($children as $c) {

                        $imagePath = ProductImageAdd::getImagesPathFromRestfulBySkuAndType($c['sku'], null, Platform::CODE_JOOM);

                        $childMainImage = '';
                        if (!isset($imagePath['zt'])) {
                            $childMainImage = array_shift($imagePath['ft']);
                        } else {
                            $childMainImage = array_shift($imagePath['zt']);
                        }
                        $salePrice = $calculatePrice($c);
                       /* echo $c['sku'];
                        echo '<br>';
                        echo $salePrice->getCalculateDescription();
                        echo '<br>';
                        echo str_repeat('...', 100);
                        echo '<br>';
                        echo '<br>';*/
                        $variantData[] = array(
                            'sku' => $c['sku'],
                            'inventory' => 1000,
                            'price' => $salePrice->getSalePrice(),
                            'shipping' => 0,
                            'market_price' => round($salePrice->getSalePrice() * 1.8, 2),
                            'color' => $child[$c['sku']]['color'],
                            'size' => $child[$c['sku']]['size'],
                            'main_image' => $childMainImage,
                        );
                    }
                    //exit();
                } else {
                    $salePrice = $calculatePrice($productInfo);
                    $variantData[] = array(
                        'sku' => $productInfo['sku'],
                        'inventory' => 1000,
                        'price' => $salePrice->getSalePrice(),
                        'shipping' => 0,
                        'market_price' => round($salePrice->getSalePrice() * 1.8, 2)
                    );
                }

                $data['variants'] = $variantData;
                $productAddModel = JoomProductAdd::model();
                $success = $productAddModel->saveJoomAddData(array($accountId => $data), null, JoomProductAdd::ADD_TYPE_BATCH);

                if (!$success) {
                    throw new \Exception( $productAddModel->getErrorMsg());
                }

                echo '导入成功';
                echo '<br>';
                echo str_repeat('...', 20);
                echo '<br>';

            }catch (\Exception $e) {
                echo $e->getMessage();
                echo '<br>';
                echo str_repeat('...', 20);
                echo '<br>';
            }
        }
        echo '总共处理' . $indexer;
        echo '<br>';


    }
}