<?php
class LazadagetrealskuController extends UebController {
	
	/**
	 * @desc lazada批量解密sku
	 * @author liuj
	 * @since 2016/03/03
	 */
    public function actionIndex(){
        ini_set('memory_limit','2048M');
        //ini_set('display_errors', true);
        //error_reporting(E_ALL);
        $type = Yii::app()->request->getParam('type');
        $PHPExcel = new MyExcel();
        $column_width = array(0=>30,1=>15);
        if($type == 'downloadtp'){
            //下载模板
            $column_width = array(30);
            $PHPExcel->export_excel(array('加密SKU'),array(),'realsku_tp.xls',$limit=10000,$output=1,$column_width,$isCreate=false);
            $PHPExcel->download_excel(1);
            Yii::app()->end();
        }
        //上传文件
        if($_FILES){
            if(empty($_FILES['csvfilename']['tmp_name'])){
                    echo $this->failureJson(array('message'=>"文件上传失败"));
                    Yii::app()->end();
            }
            $file = $_FILES['csvfilename']['tmp_name'];
            
            //excel处理
            Yii::import('application.vendors.MyExcel');
            $data = $PHPExcel->get_excel_con($file);

            $str = "加密SKU,原始SKU\n";

            //解密sku
            $encryptSku = new encryptSku();
            $result_data = array();
            foreach ($data as $key => $rows) {
                if($key == 1){
                    continue;
                }
                $sellersku = trim($rows['A']);
                if (empty($sellersku)) {
                    continue;
                }
                $sellersku = MHelper::excelSkuSet($sellersku);
                $sku = $encryptSku->getRealSku($sellersku);

                $str .= $sellersku.",\t".$sku."\n";

                // $result_data[$key] = array(
                //     'A' => $sellersku,
                //     'B' => $sku,
                // );
            }

            // $PHPExcel->export_excel(array('加密SKU','原始SKU'),$result_data,'realsku.xls',$limit=10000,$output=1,$column_width,$isCreate=false);
            // $PHPExcel->file = $file;
            // $PHPExcel->download_excel(1);
            // exit;
            
            //导出文档名称
            $exportName = 'lazada导出原始sku'.date('Y-m-dHis').'.csv';
            $this->export_csv($exportName,$str);
            Yii::app()->end();
        }
        $this->render('upload');
        exit;
    }
}