<?php 

class WishOrderNotMatchList extends WishModel{

	public $_exceptionMsg;
	
    public static function model($className = __CLASS__) {
    	return parent::model($className);
    
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return 'ueb_wish_order_not_match_list';
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
    
    /**
     * @desc 检查是否存在追踪号
     * @param unknown $traceNumber
     * @param unknown $shipCode
     * @return boolean
     */
    public function checkTraceNumberExists($orderID){
    	$info = $this->getDbConnection()->createCommand()
    					->from($this->tableName())
    					->select("id")
    					->where("order_id=:order_id", array(":order_id"=>$orderID))
    					->queryRow();
    	if($info) return true;
    	return false;
    }
    
    // =============== Export csv Start ===================
    public function getExportCsv($beginTime = null, $endTime = null){
    	if(is_null($endTime)){
    		$endTime = date("Y-m-d 15:00:00");
    	}
    	if(is_null($beginTime)){
    		$beginTime = date("Y-m-d 15:00:00", strtotime("-1 day"));
    	}
    	
    	
    	$exportData = $this->getDbConnection()->createCommand()->from($this->tableName())
					    	->where("create_time>='{$beginTime}' and create_time<'{$endTime}'")
					    	->order("id asc")
					    	->queryAll();
    	if($exportData){
    		try{
    			$accountList = WishAccount::model()->getIdNamePairs();
	    		$filename = 'wishordernomatch_'.date("Y-m-dHis").rand(1000, 9999).'.csv'; //设置文件名
	    		$headArr = array(0=>'订单号',1=>'账号', 2=>'平台订单号', 3=>'收货国家二字码', 4=>'UTC付款时间', 5=>'生成日期');
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
	    		foreach ($exportData as $data){
	    			$row = array();
	    			$row[0] = $data['order_id'];
	    			$row[1] = isset($accountList[$data['account_id']]) ? $accountList[$data['account_id']] : '';
	    			$row[2] = $data['platform_order_id'];
	    			$row[3] = $data['ship_country'];
	    			$row[4] = $data['paytime'];
	    			$row[5] = $data['create_time'];
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
    
    // =============== Export csv End ===================
    
    public function setExceptionMsg($msg){
    	$this->_exceptionMsg = $msg;
    }
    
    public function getExceptionMsg(){
    	return $this->_exceptionMsg;
    
    }
}

?>