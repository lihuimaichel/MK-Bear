<?php
class AmazonskuexportController extends UebController {
	
	/**
	 * @desc amazon根据sku导出excel格式的产品数据
	 * @author liuj
	 * @since 2016/04/11
	 */
    public function actionIndex(){
        set_time_limit(2*3600);
        ini_set('memory_limit','2048M');
        //ini_set('display_errors', true);
        //error_reporting(E_ALL);
        $type = Yii::app()->request->getParam('type');
        $PHPExcel = new MyExcel();
        $column_width = array(10,15,18,15,80,20,15,20,10,10,10,15,10,10,10);
        if($type == 'downloadtp'){
            //下载模板
            $column_width = array(10);
            $PHPExcel->export_excel(array('SKU'),array(),'amazonskuexport_tp.xls',$limit=10000,$output=1,$column_width,$isCreate=false);
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

            //获取数据
            $result_data = array();
            $product_statistic = new AmazonProductStatistic();
            $security_level_list = $product_statistic->getProductSecurityList();
            $infringement_list = $product_statistic->getProductInfringementList();
            foreach ($data as $key => $rows) {
                if($key == 1){
                    continue;
                }
                $sku = trim($rows['A']);
                if (empty($sku)) {
                    continue;
                }
                $sku = MHelper::excelSkuSet($sku);
                $encryptSku = new encryptSku();
                $encrypt_sku = $encryptSku->getAmazonEncryptSku($sku);
                $result = Product::model()->dbConnection->createCommand()
                    ->select('p.id,p.sku,p.product_category_id,c.category_cn_name,d.title,d.description,d.included,p.product_cost,p.gross_product_weight,p.product_weight,i.security_level,i.infringement')
                    ->from(Product::model()->tableName() .' p')
                    ->leftJoin(ProductInfringe::model()->tableName() .' i', "p.sku=i.sku")
                    ->leftJoin('ueb_product_category c', "p.product_category_id=c.id")
                    ->leftJoin('ueb_product_description d', "p.id=d.product_id")
                    ->where('p.sku = "' . $sku . '"')
                    ->andWhere("d.language_code = 'english'")
                    ->queryRow();

                if($result){
                    
                    $result_chinese  = Productdesc::model()->dbConnection->createCommand()
                    ->select('title,included')
                    ->from(Productdesc::model()->tableName() )
                    ->where('product_id = "' . $result['id'] . '"')
                    ->andWhere("language_code = 'Chinese'")
                    ->queryRow();
                    //chinese title,included
                    $chinese_title = '';
                    $chinese_included = '';
                    if($result_chinese){
                        $chinese_title = $result_chinese['title'];
                        $chinese_included = $result_chinese['included'];
                    }
                    
                    //description
                    $result['description'] = strip_tags($result['description'],'<b> <br>');
                    //库存
                    $sku_stock = WarehouseSkuMap::model()->getAvailableBySkuAndWarehouse($sku,  WarehouseSkuMap::WARE_HOUSE_GM);
                    //属性
                    $attribute = '';
                    $category_id = $result['product_category_id'];
                    if(isset($category_id) && $category_id > 0){
                        $result_attribute = ProductInfringe::model()->dbConnection->createCommand()
                        ->select('av.attribute_value_name')
                        ->from('ueb_product_category_attribute' .' ca')
                        ->leftJoin(ProductAttributeValue::model()->tableName() .' av', "ca.attribute_id=av.id")
                        ->where('ca.category_id = "' . $category_id . '"')
                        ->queryColumn();
                        
                        if($result_attribute){
                            $attribute = implode(',', $result_attribute);
                        }
                    }
                    //侵权和安全级别
                    $security_level ='';
                    $infringement ='';
                    if($result['security_level']){
                        $security_level = $security_level_list[$result['security_level']];
                    }
                    if($result['infringement']){
                        $infringement = $infringement_list[$result['infringement']];
                    }
                    $result_data[$key] = array(
                        'A' => $sku,
                        'B' => $result['category_cn_name'],
                        'C' => $encrypt_sku,
                        'D' => $result['title'],
                        'E' => $result['description'],
                        'F' => $result['included'],
                        'G' => $chinese_title,
                        'H' => $chinese_included,
                        'I' => $result['product_cost'],
                        'J' => $result['gross_product_weight'],
                        'K' => $result['product_weight'],
                        'L' => $attribute,
                        'M' => $security_level,
                        'N' => $infringement,
                        'O' => $sku_stock,

                    );
                } else {
                    $result_data[$key] = array(
                        'A' => $sku
                    );
                }
            }
            $PHPExcel->export_excel(array('SKU','品类','加密sku','标题','描述','included','中文标题','中文included','产品成本cny','净重','毛重','属性','安全级别','侵权种类','可用库存'),$result_data,'amazonsku_export.xls',$limit=10000,$output=1,$column_width, $isCreate=false);
            $PHPExcel->file = $file;
            $PHPExcel->download_excel(1);
            echo $this->successJson(array('message'=>'执行完成！'));
            Yii::app()->end();
        }
        $this->render('upload');
        exit;
    }
}