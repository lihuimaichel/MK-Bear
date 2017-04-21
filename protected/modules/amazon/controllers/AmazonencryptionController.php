<?php

class AmazonencryptionController extends UebController
{

    /**
     * @desc 通过Excel对文件SKU进行加密
     *
     */
    public function actionIndex()
    {
        ini_set('display_errors', true);
        error_reporting(E_ALL);
        set_time_limit(3600);
        $column_width = array(30,30);
        $type=Yii::app()->request->getParam('type');
        $excelData = new MyExcel();
        if($type == 'downloadtp'){
            //下载模板
            $column_width = array(30);
            $excelData->export_excel(array('SKU'),array(),'encryptskutp.xls',$limit=10000,$output=1,$column_width,$isCreate=false);
            Yii::app()->end();
        }
        //判断是否上传文件
        if ($_FILES) {
            Yii::import('application.vendors.MyExcel.php');
            if ($_FILES['csvfilename1']['tmp_name']) {
                $file = $_FILES['csvfilename1']['tmp_name'];
                //获取文件数据
                $data = $excelData->get_excel_con($file);

                //对数据进行加密
                foreach ($data as $key => $rows) {
                    if ($key == 1) {
                        continue;
                    }
                    $sku = trim($rows['A']);

                    if (empty($sku)) {
                        continue;
                    }
                    $sku = MHelper::excelSkuSet($sku);
                    $encryptSku = new encryptSku();
                    $encryptAfterSku = $encryptSku->getAmazonEncryptSku($sku);    //amazon加密
                    $result_data[] = array(
                        'A' => $sku,
                        'B' => $encryptAfterSku,
                    );
                }
                $excelData->export_excel(array('原始SKU', '加密SKU'), $result_data, 'encryptsku.xls', $limit = 10000,$output=1,$column_width);
            } else {
                $file = $_FILES['csvfilename2']['tmp_name'];
                //获取文件数据
                $data = $excelData->get_excel_con($file);
                if (count($data) > 1500){
                    echo '请不要超过1500条数据，分批处理。';
                    Yii::app()->end();
                }

                //对数据进行解密
                $encryptSku = new encryptSku();
                $result_data = array();
                foreach ($data as $key => $rows) {
                    if ($key == 1) {
                        continue;
                    }
                    $sku = trim($rows['A']);
                    if (empty($sku)) {
                        continue;
                    }
                    $DecryptSku = $encryptSku->getAmazonRealSku2($sku); //解密
                    $result_data[$key] = array(
                        'A' => $sku,
                        'B' => $DecryptSku,
                    );
                }
                $excelData->export_excel(array('加密SKU', '原始SKU'), $result_data, 'realsku.xls', $limit = 10000,$output=1,$column_width);
            }
            $excelData->file = $file;
            $excelData->download_excel(1);
            Yii::app()->end();            
        }
        $this->render("encryption");
    }
}




?>