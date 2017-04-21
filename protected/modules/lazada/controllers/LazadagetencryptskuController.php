<?php
class LazadagetencryptskuController extends UebController {
	
	/**
	 * @desc lazada批量加密sku
	 * @author liuj
	 * @since 2016/04/19
	 */
    public function actionIndex(){
        ini_set('memory_limit','2048M');
        //ini_set('display_errors', true);
        //error_reporting(E_ALL);
        $type = Yii::app()->request->getParam('type');
        $PHPExcel = new MyExcel();
        $column_width = array(30,15,30);
        if($type == 'downloadtp'){
            //下载模板
            $column_width = array(30);
            $PHPExcel->export_excel(array('SKU'),array(),'encryptsku_tp.xls',$limit=10000,$output=1,$column_width,$isCreate=false);
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

            $str = "原始SKU,账号,加密SKU\n";

            //解密sku
            $encryptSku = new encryptSku();
            $result_data = array();
            $all_sku = array();
            $account_list = LazadaAccount::model()->getDbConnection()->createCommand()
                            ->from(LazadaAccount::tableName())
                            ->select('short_name')
                            ->where('account_id !=1 ')
                            ->andWhere('site_id=1')
                            ->queryColumn();
            foreach ($data as $key => $rows) {
                if($key == 1){
                    continue;
                }
                $sku = trim($rows['A']);
                if (empty($sku)) {
                    continue;
                }
                $sku = MHelper::excelSkuSet($sku);
                $explode_sku = explode('.', $sku);
                
                foreach ( $account_list as $short_name){
                    if( !isset( $all_sku[ $explode_sku[0]][$short_name] ) ){
                        $sellersku = $encryptSku->getEncryptSku($sku);
                        $encrypt_explode_sku = explode('.', $sellersku);
                        $all_sku[ $explode_sku[0]][$short_name] = $encrypt_explode_sku[0];
                        
                    } else {
                        if(isset($explode_sku[1])){
                            $sub_sku = '.' . $explode_sku[1];
                        } else {
                            $sub_sku = '';
                        }
                        $sellersku = $all_sku[$explode_sku[0]][$short_name] . $sub_sku;
                    }

                    $str .= "\t".$sku.",".$short_name.",".$sellersku."\n";

                    // $result_data[] = array(
                    //     'A' => $sku,
                    //     'B' => $short_name,
                    //     'C' => $sellersku,
                    // );
                }
            }
            // $PHPExcel->export_excel(array('原始SKU','账号','加密SKU'),$result_data,'encryptsku.xls',$limit=10000,$output=1,$column_width,$isCreate=false);
            // $PHPExcel->file = $file;
            // $PHPExcel->download_excel(1);
            // exit;
            
            $exportName = 'lazada导出加密sku'.date('Y-m-dHis').'.csv';
            $this->export_csv($exportName,$str);
            Yii::app()->end();
        }
        $this->render('upload');
        exit;
    }
}