<?php
class LazadagetlistingaccountController extends UebController {
	
	/**
	 * @desc 根据sku获取线上账号
	 * @author liuj
	 * @since 2016/04/13
	 */
    public function actionIndex(){
        set_time_limit(1800);
        ini_set('memory_limit','2048M');
        //ini_set('display_errors', true);
        //error_reporting(E_ALL);
        $type = Yii::app()->request->getParam('type');
        $PHPExcel = new MyExcel();
        $column_width = array(15,30);
        if($type == 'downloadtp'){
            //下载模板
            $column_width = array(15);
            $PHPExcel->export_excel(array('SKU'),array(),'listingaccount_tp.xls',$limit=10000,$output=1,$column_width,$isCreate=false);
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

            //根据sku获取线上账号
            $result_data = array();
            $str = "SKU,账号\n";
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
                    $result_account = LazadaProduct::model()->dbConnection->createCommand()
                        ->select('p.sku,a.short_name')
                        ->from(LazadaProduct::model()->tableName().' p')
                        ->leftJoin(LazadaAccount::model()->tableName().' a', "p.account_id=a.account_id and p.site_id=a.site_id")
                        ->where('p.sku = "' . $sku . '"')
                        ->queryAll();
                    if(!$result_account){
                        continue;
                    }

                    foreach ($result_account as $value){
                        $str .= "\t".$value['sku'].",".$value['short_name']."\n";
                        // $result_data[] = array(
                        //     'A' => $value['sku'],
                        //     'B' => $value['short_name'],
                        // );
                    }
                }
            }

            //导出文档名称
            $exportName = 'listingaccount'.date('Y-m-dHis').'.csv';
            $this->export_csv($exportName,$str);

            // $PHPExcel->export_excel(array('SKU','账号'),$result_data,'listingaccount.xls',$limit=10000,$output=1,$column_width, $isCreate=false);
            // $PHPExcel->file = $file;
            // $PHPExcel->download_excel(1);
            // echo $this->successJson(array('message'=>'执行完成！'));
            Yii::app()->end();
        }
        $this->render('upload');
        exit;
    }
}