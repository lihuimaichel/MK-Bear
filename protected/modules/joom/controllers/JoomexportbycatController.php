<?php
class JoomexportbycatController extends UebController {
	
	/**
	 * @desc joom根据分类导出excel格式的产品数据
	 * @author liuj
	 * @since 2016/04/26
	 */
    public function actionIndex(){
        set_time_limit(10*3600);
        ini_set('memory_limit','2048M');
        //ini_set('display_errors', true);
        //error_reporting(E_ALL);
        $cat_id = Yii::app()->request->getParam('cat_id');
        $PHPExcel = new MyExcel();
        $column_width = array(10,15,18,15,80);
        
        //分类
        if($cat_id){
            
            //待售中
            $result_data = array();
            $result_selling = Product::model()->dbConnection->createCommand()
                ->select('c.sku,co.en_name,co.cls_name,d.title,d.description')
                ->from('ueb_product.ueb_product_category_sku_old c')
                ->leftJoin('ueb_product_category_old co', "co.id=c.classid")
                ->leftJoin('ueb_product p', "c.sku=p.sku")
                ->leftJoin('ueb_product_description d', "p.id=d.product_id")
                ->where('c.classid = "' . $cat_id . '"')
                ->andWhere("p.product_status = 4")
                ->andWhere("d.language_code = 'english'")
                //->limit(10)
                ->queryAll();

            
            
            //待清仓
            $result_waiting = Product::model()->dbConnection->createCommand()
                ->select('c.sku,co.en_name,co.cls_name,d.title,d.description')
                ->from('ueb_product.ueb_product_category_sku_old c')
                ->leftJoin('ueb_product_category_old co', "co.id=c.classid")
                ->leftJoin('ueb_product p', "c.sku=p.sku")
                ->leftJoin('ueb_product_description d', "p.id=d.product_id")
                ->leftJoin('ueb_warehouse.ueb_warehouse_sku_map w', "c.sku=w.sku")
                ->where('c.classid = "' . $cat_id . '"')
                ->andWhere("p.product_status = 4")
                ->andWhere("d.language_code = 'english'")
                ->andWhere('w.warehouse_id = 41')
                ->andWhere("w.available_qty >0")
                //->limit(10)
                ->queryAll();
            foreach ($result_selling as $result){
                //description
                $result['description'] = strip_tags($result['description']);
                $result_data[] = array(
                    'A' => $result['sku'],
                    'B' => $result['en_name'],
                    'C' => $result['cls_name'],
                    'D' => $result['title'],
                    'E' => $result['description'],
                );
            }
            
            foreach ($result_waiting as $result){
                //description
                $result['description'] = strip_tags($result['description']);
                $result_data[] = array(
                    'A' => $result['sku'],
                    'B' => $result['en_name'],
                    'C' => $result['cls_name'],
                    'D' => $result['title'],
                    'E' => $result['description'],
                );
            }
            
            $file_name = 'categroy_'.$cat_id.'.xls';
            $PHPExcel->export_excel(array('SKU','英文分类','中文分类','标题','描述'),$result_data,$file_name,$limit=50000,$output=1,$column_width, $isCreate=false);
            $PHPExcel->file = 'categroy_'.$cat_id;
            $PHPExcel->download_excel(1);
            echo $this->successJson(array('message'=>'执行完成！'));
            Yii::app()->end();
        }
        exit;
    }
}