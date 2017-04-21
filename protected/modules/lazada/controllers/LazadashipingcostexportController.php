<?php
class LazadashipingcostexportController extends UebController {
	
	/**
	 * @desc lazada物流费用导出
	 * @author liuj
	 * @since 2016/02/24
	 */
    public function actionIndex(){
        set_time_limit(3*3600);
        ini_set('memory_limit','2048M');
        //ini_set('display_errors', true);
        //error_reporting(E_ALL);
        $type = Yii::app()->request->getParam('type');
        $PHPExcel = new MyExcel();
        if($type == 'downloadtp'){
            //下载模板
            $PHPExcel->export_excel(array('SKU','成本','重量','Malaysia','ghxb_jr','运费'),array(),'shipingcost_tp.xls',$limit=10000,$output=1,$column_width=array(),$isCreate=false);//,'Malaysia','ghxb_jr'
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

            $str = "SKU,成本,重量,运费\n";

            //计算物流费用
            $result_data = array();
            $priceCal = new CurrencyCalculate();
            $priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
            $encryptSku = new encryptSku();
            foreach ($data as $key => $rows) {
                if($key == 1){
                    $country = trim($rows['D']);
                    $shipCode = trim($rows['E']);
                    continue;
                }
                $sku = trim($rows['A']);
                if (empty($sku)) {
                    continue;
                }
                $sku = MHelper::excelSkuSet($sku);
                $real_sku = $encryptSku->getRealSku($sku);
                $priceCal->setSku($real_sku);//设置sku
                $productCost    = round($priceCal->getProductCost(), 2);      //产品成本
                //重量
                $weight = 0;
                if(isset($priceCal->productWeight) && $priceCal->productWeight){
                    $weight = $priceCal->productWeight;
                }
                
                $attributes = $priceCal->sku ? Product::model()->getAttributeBySku($priceCal->sku, 'product_features') : array();//属性
                $shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
                    "platform_code"=> Platform::CODE_LAZADA,
                    'country'   => $country,
                    'attributeid'   => $attributes,
                    'warehouse' => WarehouseSkuMap::WARE_HOUSE_DEF
                ));
                if($shipFee <= 0){
                    $shipFee = Logistics::model()->getShipFee($shipCode, $weight, array(
                        "platform_code"=> Platform::CODE_LAZADA,
                        'country'   => $country,
                        'attributeid'   => $attributes,
                        'warehouse' => WarehouseSkuMap::WARE_HOUSE_GM
                    ));
                }

                $str .= "\t".$sku.",".$productCost.",".$weight.",".$shipFee."\n";

                // $result_data[$key] = array(
                //     'A' => $sku,
                //     'B' => $productCost,
                //     'C' => $weight,
                //     'F' => $shipFee,
                // );
                $priceCal->productCost = '';
    	        $priceCal->productWeight = '';
            }

            // $PHPExcel->export_excel(array('SKU','成本','重量',$country,$shipCode,'运费'),$result_data,'shipingcost_export.xls',$limit=10000,$output=1,$column_width=array(),$isCreate=false);
            // $PHPExcel->file = $file;
            // $PHPExcel->download_excel(1);
            // echo $this->successJson(array('message'=>'执行完成！'));
            // Yii::app()->end();
            
            $exportName = 'lazada导出运费'.date('Y-m-dHis').'.csv';
            $this->export_csv($exportName,$str);
            Yii::app()->end();
        }
        $this->render('upload');
        exit;
    }
}