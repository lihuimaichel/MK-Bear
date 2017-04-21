<?php
class LazadagetsellerskuController extends UebController {
	
	/**
	 * @desc 根据sku获取线上sku
	 * @author liuj
	 * @since 2016/04/13
	 */
    public function actionIndex(){
        set_time_limit(3600);
        ini_set('memory_limit','2048M');
        //ini_set('display_errors', true);
        //error_reporting(E_ALL);
        $type = Yii::app()->request->getParam('type');
        $PHPExcel = new MyExcel();
        $column_width = array(15,30,15);
        if($type == 'downloadtp'){
            //下载模板
            $column_width = array(15);
            $PHPExcel->export_excel(array('SKU'),array(),'sellersku_tp.xls',$limit=10000,$output=1,$column_width,$isCreate=false);
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

            $str = "SKU,线上SKU,账号\n";

            //获取马来西亚站点各账号线上sku
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
                if($sku){
                    $result_sellersku = LazadaProduct::model()->dbConnection->createCommand()
                        ->select('p.sku,p.seller_sku,a.short_name')
                        ->from(LazadaProduct::model()->tableName().' p')
                        ->leftJoin(LazadaAccount::model()->tableName().' a', "p.account_id=a.account_id and p.site_id=a.site_id")
                        ->where('p.sku = "' . $sku . '"')
                        ->andWhere('p.site_id = "' . LazadaSite::SITE_MY . '"')
                        ->queryAll();
                    foreach ($result_sellersku as $value){
                        $str .= "\t".$value['sku'].",".$value['seller_sku'].",".$value['short_name']."\n";
                        // $result_data[] = array(
                        //     'A' => $value['sku'],
                        //     'B' => $value['seller_sku'],
                        //     'C' => $value['short_name'],
                        // );
                    }
                }
            }
            //var_dump($result_sellersku);exit;
            // $PHPExcel->export_excel(array('SKU','线上sku','账号'),$result_data,'sellersku.xls',$limit=10000,$output=1,$column_width, $isCreate=false);
            // $PHPExcel->file = $file;
            // $PHPExcel->download_excel(1);
            // echo $this->successJson(array('message'=>'执行完成！'));
            $exportName = 'lazada导出线上sku'.date('Y-m-dHis').'.csv';
            $this->export_csv($exportName,$str);
            Yii::app()->end();
        }
        $this->render('upload');
        exit;
    }
}