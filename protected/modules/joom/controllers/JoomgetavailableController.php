<?php
class JoomgetavailableController extends UebController {
	
	/**
	 * @desc 获取可用库存
	 * @author liuj
	 * @since 2016/03/04
	 */
    public function actionIndex(){
        set_time_limit(5*3600);
        ini_set('memory_limit','2048M');
        //ini_set('display_errors', true);
        //error_reporting(E_ALL);
        $type = Yii::app()->request->getParam('type');
        $PHPExcel = new MyExcel();
        $column_width = array(15,15,15);
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
                $result_available = WarehouseSkuMap::model()->dbConnection->createCommand()
                    ->select('available_qty')
                    ->from(WarehouseSkuMap::model()->tableName())
                    ->where('sku = "' . $real_sku . '"')
                    ->andWhere('warehouse_id = "' . WarehouseSkuMap::WARE_HOUSE_GM . '"')
                    ->limit(1)
                    ->queryColumn();
                $available = empty($result_available) ? 0 : $result_available['0'];
                
//                $result_status = Product::model()->dbConnection->createCommand()
//                    ->select('product_status')
//                    ->from(Product::model()->tableName())
//                    ->where('sku = "' . $real_sku . '"')
//                    ->limit(1)
//                    ->queryColumn();
//                $status = empty($result_status) ? '' : Product::getProductStatusConfig($result_status['0']);
                
//                if(is_array($status)){
//                    $status = '';
//                }
                
                $result_data[$key] = array(
                    'A' => $sku,
                    'B' => $available,
                    'C' => $real_sku,
                );
            }

            $PHPExcel->export_excel(array('加密SKU','可用库存','sku'),$result_data,'available.xls',$limit=10000,$output=1,$column_width,$isCreate=false);
            $PHPExcel->file = $file;
            $PHPExcel->download_excel(1);
            echo $this->successJson(array('message'=>'执行完成！'));
            Yii::app()->end();
        }
        $this->render('upload');
        exit;
    }
}