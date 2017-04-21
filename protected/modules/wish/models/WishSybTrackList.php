<?php 

class WishSybTrackList extends WishModel{

	private $_exceptionMsg = '';
	
    public static function model($className = __CLASS__) {
    	return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return 'ueb_wish_syb_track_list';
    }
    
    /**
     * @desc 保存
     * @param string $transactionID
     * @param string $orderID
     * @param array $param
     */
    public function saveOrderTraceNumberRecord($data){
        return $this->dbConnection->createCommand()->replace($this->tableName(), $data);
    }
    
    public function getExportData($beginTime = null, $endTime = null){
    	$exportData = array();
    	if(is_null($beginTime)){
    		$beginTime = date("Y-m-d 14:00:00", strtotime("-1 day"));
    	}
    	
    	if(is_null($endTime)){
    		$endTime = date("Y-m-d 14:00:00");
    	}
    	
		
    	$exportData = $this->getDbConnection()->createCommand()->from($this->tableName())
    											->where("status=0")
    											->andWhere("create_time>='{$beginTime}' and create_time<'{$endTime}'")
    											->order("id asc")
    											->queryAll();
    	if(!empty($exportData)){
    		try{
	    		$filename = 'everydaynoshipment_'.date("Y-m-dHis").rand(1000, 9999).'.csv'; //设置文件名
	    		$headArr = array(0=>'跟踪号',1=>'收件人国家二字码', 2=>'收件人国家中文', 3=>'收货人', 4=>'收货人地址', 5=>'出口日期',6=>'申报品名(英文)'
	    				,7=>'申报品名(中文)',8=>'数量',9=>'重量',10=>'申报价值',11=>'包裹号'
	    		);
	    		$uploadDir = "./uploads/downloads/";
	    		if(!is_dir($uploadDir)){
	    			mkdir($uploadDir, 0755, true);
	    		}
	    		foreach( $headArr as $key => $val ){
	    			$headArr[$key] = iconv('utf-8','gbk',$val);
	    		}
	    		$filename2 = $uploadDir.$filename;
	    		$fd = fopen($filename2, "w+");
	    		fputcsv($fd, $headArr);
	    		$data= array();
	    		foreach($exportData as $key => $val){
	    			$row = array();
	    			$row[0] = $val['trace_number'];
	    			$row[1] = $val['ship_country_code'];
	    			$row[2] = $val['ship_country_cn'];
	    			$row[3] = $val['recive_name'];
	    			$row[4] = $val['recive_address'];
	    			$row[5] = $val['ship_date'];
	    			$row[6] = $val['good_name_en'];
	    			$row[7] = $val['good_name_cn'];
	    			$row[8] = $val['quantity'];
	    			$row[9] = $val['weight'];
	    			$row[10] = $val['price'];
	    			$row[11] = $val['package_id'];
	    			foreach ($row as $k =>$v){
	    				//$row[$k] = mb_convert_encoding($v, "gbk", "utf-8");
	    				$row[$k] = iconv('utf-8','gbk',$v);
	    			}
	    			fputcsv($fd, $row);
	    		}
	    		fclose($fd);
	    		//写入日志表
	    		FileDownloadList::model()->addData(array(	'filename'		=>	$filename, 
	    													'local_path'	=>	$filename2,
	    													'create_time'	=>	date("Y-m-d H:i:s"),
	    													'create_user_id'=>	intval(Yii::app()->user->id),
	    													'platform_code'	=>	Platform::CODE_WISH
	    										));
    		}catch (Exception $e){
    			$this->setExceptionMsg($e->getMessage());
    			return false;
    		}
    	}
    	return true;
    }
    
    public function setExceptionMsg($msg){
    	$this->_exceptionMsg = $msg;
    }
    
    public function getExceptionMsg(){
    	return $this->_exceptionMsg;
    	
    }
}

?>