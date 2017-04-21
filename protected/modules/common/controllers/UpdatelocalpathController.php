<?php
/**
 * @desc 更改图片路径
 * @author liuj
 *
 */
class UpdatelocalpathController extends UebController {
    /**
     * @desc 将图片表中的local_path从2级目录更改为4级目录
     */
    public function actionIndex() {
        //多线程按id升序修改图片的local_path
        set_time_limit(0);
        ini_set('memory_limit','2048M');
        $time = time();
        //ini_set('display_errors', true);        
        $id_begin = Yii::app()->request->getParam('id_begin');
        $id_end = Yii::app()->request->getParam('id_end');
        //$id_begin = 4000;
        //$id_end = 5000;
        
        //1.查询替换
        $flag_while = true;
        $limit = 50;
        $offset = 0;
        $count = 0;
        while($flag_while){
            $exe_time = time();
            if($exe_time - $time > 18000){
                echo '执行超过5小时';
                exit;
            }
            //查找字符串中/出现6次的数据
            $list = ProductImageAdd::model()->getDbConnection()->createCommand()
                    ->from(ProductImageAdd::tableName())
                    ->select("id,sku,local_path,LENGTH( local_path ) - LENGTH( REPLACE( local_path, '/', '' ) ) as times")
                    ->andWhere("id>={$id_begin}")
                    ->andWhere("id<{$id_end}")
                    ->andWhere("LENGTH( local_path ) - LENGTH( REPLACE( local_path, '/', '' ) )=6")
                    ->order('id asc')
                    ->limit($limit, $offset)
                    ->queryAll();
            
            
                    
            //$offset += 50;
            if (empty($list)) {
                $flag_while = false;
                break;
            }
            
            foreach ($list as $value){
                
                $id = $value['id'];
                $sku = $value['sku'];
                $local_path = $value['local_path'];
                
                $search_one = substr($sku, 0, 1);
                $search_two = substr($sku, 1, 1);
                $search_three = substr($sku, 2, 1);
                $search_four = substr($sku, 3, 1);
                $search = '/' . $search_one . '/' . $search_two . '/';
                $replace = $search . $search_three . '/' . $search_four . '/';
                
                $new_local_path = str_replace($search, $replace, $local_path, $i);
                
                if($i == 1){
                    $count++;
                    //2.批量更新
                    $data = array(
                        'local_path' => $new_local_path
                    );
                    $data_insert = array(
                        'image_id' => $id,
                        'sku' => $sku,
                        'local_path' => $local_path,
                        'new_local_path' => $new_local_path,
                    );
                    $update_result = ProductImageAdd::model()->dbConnection->createCommand()->update(ProductImageAdd::tableName(), $data, 'id = "'.$id.'"');
                    
                    //3.保存更改
                    if($update_result){
                        $insert_result = ProductImageAdd::model()->dbConnection->createCommand()->insert('market_common.ueb_product_image_add_change', $data_insert);
                    }
                }
            }
        }
        echo $count;
    }
}