<?php
class LazadagetavailableController extends UebController {
	
	/**
	 * @desc 获取可用库存
	 * @author liuj
	 * @since 2016/03/04
	 */
    public function actionIndex(){
        set_time_limit(3600);
        ini_set('memory_limit','2048M');
        //ini_set('display_errors', true);
        //error_reporting(E_ALL);
        $type = Yii::app()->request->getParam('type');
        $PHPExcel = new MyExcel();
        $column_width = array(15,15,15,15,15);
        if($type == 'downloadtp'){
            //下载模板
            $column_width = array(15);
            $PHPExcel->export_excel(array('SKU'),array(),'available_tp.xls',$limit=10000,$output=1,$column_width,$isCreate=false);
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

            $str = "SKU,可用库存,在途库存,实际库存,产品状态\n";

            //获取可用库存
            $encryptSku = new encryptSku();
            $result_data = array();
            foreach ($data as $key => $rows) {
                if($key == 1){
                    continue;
                }
                $sku = trim($rows['A']);
                if (empty($sku)) {
                    continue;
                }
                $sku = MHelper::excelSkuSet($sku);
                $real_sku = $encryptSku->getRealSku($sku);
                $resultMap = WarehouseSkuMap::model()->dbConnection->createCommand()
                    ->select('available_qty,transit_qty,true_qty')
                    ->from(WarehouseSkuMap::model()->tableName())
                    ->where('sku = "' . $real_sku . '"')
                    ->andWhere('warehouse_id = "' . WarehouseSkuMap::WARE_HOUSE_GM . '"')
                    ->limit(1)
                    ->queryRow();
                $available = empty($resultMap['available_qty']) ? 0 : $resultMap['available_qty'];
                $transit = empty($resultMap['transit_qty']) ? 0 : $resultMap['transit_qty'];
                $trueQty = empty($resultMap['true_qty']) ? 0 : $resultMap['true_qty'];
                
                $result_status = Product::model()->dbConnection->createCommand()
                    ->select('product_status')
                    ->from(Product::model()->tableName())
                    ->where('sku = "' . $real_sku . '"')
                    ->limit(1)
                    ->queryColumn();
                $status = empty($result_status) ? '' : Product::getProductStatusConfig($result_status['0']);
                
                if(is_array($status)){
                    $status = '';
                }
                
                // $result_data[$key] = array(
                //     'A' => $sku,
                //     'B' => $available,
                //     'C' => $transit,
                //     'D' => $trueQty,
                //     'E' => $status,
                // );

                $str .= "\t".$sku.",".$available.",".$transit.",".$trueQty.",".$status."\n";
            }

            //导出文档名称
            $exportName = 'lazada导出sku获取可用库存'.date('Y-m-dHis').'.csv';
            $this->export_csv($exportName,$str);
            Yii::app()->end();
        }
        $this->render('upload');
        exit;
    }


    /**
     * @desc 获取sku的产品属性
     * @author hanxy
     * @since 2017-03-01
     */
    public function actionGetattribute(){
        set_time_limit(2*3600);
        ini_set('memory_limit','2048M');
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $type = Yii::app()->request->getParam('type');
        $PHPExcel = new MyExcel();
        if($type == 'downloadtp'){
            //下载模板
            $column_width = array(15);
            $PHPExcel->export_excel(array('SKU'),array(),'available_tp.xls',$limit=2000,$output=1,$column_width,$isCreate=false);
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

            $str = "SKU,产品属性\n";

            //获取sku产品属性值
            $attributeMap = ProductAttributeMap::model()->getAttributeValListArray(3);

            //获取sku产品属性
            $encryptSku = new encryptSku();
            $result_data = array();
            foreach ($data as $key => $rows) {
                if($key == 1){
                    continue;
                }
                $sku = trim($rows['A']);
                if (empty($sku)) {
                    continue;
                }
                $values = '';
                $sku = MHelper::excelSkuSet($sku);
                $real_sku = $encryptSku->getRealSku($sku);
                $resultMap = ProductSelectAttribute::model()->getDbConnection()->createCommand()
                    ->select('attribute_value_id')
                    ->from(ProductSelectAttribute::model()->tableName())
                    ->where('sku = "' . $real_sku . '"')
                    ->queryAll();
                if($resultMap){
                    foreach ($resultMap as $v) {
                        $attributeValue = isset($attributeMap[$v['attribute_value_id']])?$attributeMap[$v['attribute_value_id']]:'';
                        $values .= $attributeValue.';';
                    }
                }

                $str .= "\t".$sku.",\t".rtrim($values,';')."\n";
            }

            //导出文档名称
            $exportName = 'lazada导出sku产品属性表'.date('Y-m-dHis').'.csv';
            $this->export_csv($exportName,$str);
            Yii::app()->end();
        }
        $this->render('upload_attribute');
        exit;
    }
}