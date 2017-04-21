<?php
class AmazontracknumexportController extends UebController {
	/**
	 * @desc amazon根据sku导出excel格式的产品数据
	 * @author liuj
	 * @since 2016/04/11
	 */
    public function actionIndex(){
    	ini_set('display_errors', true);
    	error_reporting(E_ERROR);
        set_time_limit(2*3600);
        ini_set('memory_limit','2048M');
        $type = Yii::app()->request->getParam('type');
        $PHPExcel = new MyExcel();
        //$column_width = array(10,15,18,15,80,20,15,20,10,10,10,15,10,10,10);
        if($type == 'downloadtp'){
            //下载模板
            $column_width = array(10);
            $PHPExcel->export_excel(array('跟踪号'),array(''),'amazontracknum_export.xls',$limit=10000,$output=1,$column_width,$isCreate=false);
            //$PHPExcel->download_excel(1);
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
            $i = 0;
            foreach ($data as $key => $rows) {
                if($key == 1) continue;//第一行跳出
                $trackNum = trim($rows['A']);
                if(empty($trackNum)) continue;//跟踪号为空跳出
                //查询跟踪号相关的订单信息
                $orderIdList = OrderPackage::model()
			                	->getDbConnection()->createCommand()
			                    ->select('op.track_num,od.order_id')
			                    ->from(OrderPackage::model()->tableName().' op')
			                    ->leftJoin(OrderPackageDetail::model()->tableName().' od', "op.package_id = od.package_id")
			                    ->where('op.track_num = "'.$trackNum.'"')
			                    ->andWhere("op.platform_code = 'AMAZON'")
			                    ->group('od.order_id')
			                    ->order('od.order_id')
			                    ->queryAll();
                
                if (empty($orderIdList)) continue;
                
                $orderIdArr = array();
                foreach ($orderIdList as $akey => $aval){
                	$orderIdArr[] = $aval['order_id'];
                }
                
                $orderInfo	= Order::model()
                				->getDbConnection()->createCommand()
                				->select('platform_code,account_id,order_id')
                				->from(Order::model()->tableName())
                				->where(array('in','order_id',$orderIdArr))
                				->queryAll();
                
                //var_dump($orderInfo);exit;
                //拼装数据
                foreach ($orderInfo as $bkey => $bval){
                	$result_data[$i] = array(
                		'track_num'		=> $trackNum,
                		'platform_code'	=> $bval['platform_code'],
                		'account_id'	=> $bval['account_id'],
                		'order_id'		=> $bval['order_id'],
                	);
                	$i++;
                }
            }
            //拼装EXCEL导出数据
            //var_dump($i,$result_data);exit;
            $excelData = array();
            foreach ($result_data as $ckey=>$cval){
            	$excelData[$ckey] = array(
            			'A' => $cval['track_num'],
            			'B' => $cval['platform_code'],
            			'C' => AmazonAccount::model()->getAccountNameById($cval['account_id']),
            			'D' => $cval['order_id']
            	);
            }
            
            $PHPExcel->export_excel(array('跟踪号','销售平台','账号','订单号'),$excelData,'amazontracknum_export.xls',$limit=10000,$output=1,$column_width, $isCreate=false);
            $PHPExcel->file = $file;
            //$PHPExcel->download_excel(1);
            echo $this->successJson(array('message'=>'执行完成！'));
            Yii::app()->end();
        }
        $this->render('upload');
        exit;
    }
}