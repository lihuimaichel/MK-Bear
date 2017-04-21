<?php
/**
 * Model Helper Class
 * @package Application.components
 * @auther Bob <zunfengke@gmail.com>
 */
class MHelper {
    
    /**
     * get msg type config
     */
    public static function getMsgTypeConfig() {
        static $data = array();
        if ( empty($data) ) {
            $list = MsgType::model()->findAll('status=:status', array(':status' => MsgType::ENABLED_STATUS));
            $data = array();          
            foreach ($list as $key => $val) {
                $data[$val['code']] = $val['name'];
            }
            $data[MsgType::PERSONAL_MSG_CODE] = Yii::t('system', 'Personal Message');
        }        
        
        return $data;
    }
    
    /**
     * get msg type
     * 
     * @param type $type
     * @return type
     * @throws CException
     */
    public static function getMsgType($type) {
       $data = self::getMsgTypeConfig();
       
       if (! isset($data[$type]) ) {
           throw new CException(Yii::t('excep','"{class}"  config infomation does not exists or errors.', array(
                '{class}'=> get_class($this),								
            )));
       }
       
       return $data[$type];
    }
    
    /**
     * get user id - name pairs
     * 
     * @staticvar array $pairs
     * @return type
     */
    public static function getUserPairs() {
        static $pairs = array();
        if ( empty($pairs) ) {
            $pairs = UebModel::model('user')
                ->queryPairs('id,user_full_name');
        }
        
        return $pairs;        
    }
    
    /**
     * get user name 
     * 
     * @param type $id
     * @return type
     * @throws CException
     */
    public static function getUsername($id) {
    	
    	if (empty($id)) return '';
        $data = self::getUserPairs();

        if (! isset($data[$id]) ) {
        	//echo '<font color="red">unknown</font>';
            //throw new CException(Yii::t('excep','config infomation does not exists or errors.'));
            return '';
       	}else{
       		return $data[$id];
       	}
    }
    
    public static function getRefundConfig($type=null){
    	
    	if (empty($type)) return '';
    	$reason = new reasonList();
    	$reasonList = $reason->getResonList();
    	$reasonConfig = $reasonList[reasonList::REASON_TYPE];
    	if ($type !==  null) {
    		return $reasonConfig[$type];
    	}
    	
    	
    }
    
    public static function getResendConfig($type){
    	$reason = new reasonList(reasonList::RESEND_TYPE);
    	$reasonList = $reason->getResonList();
    	return $reasonList[$type];
    }
    

    /**
     * get data type
     */
    
    public static function getDataType($type='') {
    	$options = array(
    			''=>Yii::t('system', 'Please Select'),
    			'datetime'=>Yii::t('system', 'Data time'),
    			'checkbox'=>Yii::t('system', 'Checkbox'),  
    			'select'=>Yii::t('system', 'Select'),
    			'nums'=>Yii::t('system', 'Nums'),
    			'input'=>Yii::t('system', 'Input'),  			
    	);
    	if(!empty($type)) {
			return $options[$type];
    	}
    	return $options;
    }    
 
    
    /**
     * get user info
     * @return $data
     */
    public static function getUserList($user_id=0) {
    	static $data = array();
		if ( empty($data) ) {
    		$list = Yii::app()->db->createCommand()
    		->select('id,user_name,user_full_name')
    		->from(User::model()->tableName())
    		->order("id asc")
    		->queryAll();
    		$data =array();
    		foreach($list as $key=>$val){
    			$data[$val['id']] =$val['user_full_name'];
    		}
    	}
    	if(!empty($user_id)) {
    		return $data[$user_id];
    	}
    	return $data;
    	 
    }
 
    
    /**
     *  format update field log
     * 
     * @param string $label
     * @param string $oldValue
     * @param string $value
     * @return string
     */
    public static function formatUpdateFieldLog($label, $oldValue, $value) {
        return Yii::t('system', '{label} : {oldval} to {val}<br/> ', array(
                    'label' => $label, 'oldval' => $oldValue, 'val' => $value));
    }
    
    /**
     * format insert field log
     * 
     * @param string $label
     * @param string $value
     * @return string
     */
    public static function formatInsertFieldLog($label, $value) {
        return Yii::t('system', '{label} : {val}<br/>', array(
                    'label' => $label,  'val' => $value));
    }
    
    /**
     *  format delete log
     * 
     * @param string $label
     * @param string $value
     * @return string 
     */
    public static function formatDeleteLog($label, $value ) {
        return Yii::t('system', '{label} : {val} delete<br/>', array(
                    'label' => $label,  'val' => $value));
    }
    
     /**
     * get table names config
     */
    public static function getTableNamesConfig() {
        static $result = array();
        if ( empty($result) ) {
             $dbNamesConfig = Configuration::getDbNamesConfig();
            foreach ($dbNamesConfig as $key => $val) {
                $tableNames = self::getTableNamesByDbKey($key);
                $result[$key] = $tableNames;               
            }
        }
       
        return $result;
    }
    
    /**
     * get table names by db key
     * @param type $dbKey
     * @return array
     */
    public static function getTableNamesByDbKey($dbKey) {            
        return Yii::app()->getComponent($dbKey)->schema->getTableNames();
    }
    
    /**
     * get model by table namne
     * 
     * @param string $tableName
     * @return type
     */
    public static function getModelByTableName($tableName) {
        $className = self::getModelNameByTableName($tableName);
        return UebModel::model($className);
    }
    
    /**
     * get model name by table name
     * 
     * @param string $tableName
     * @return string
     */
    public static function getModelNameByTableName($tableName) {
        $className = "";
        $tableModelMap = Yii::app()->params['tableToModel'];
        if ( strpos($tableName, ".") !== false ) {//ueb_product.ueb_product
        	$dbNameAndTableName = explode('.', $tableName);
        	$tableName = $dbNameAndTableName[1];//clear db name
        }
        
        if ( ! empty($tableModelMap) && isset($tableModelMap[$tableName])) {
            $className = $tableModelMap[$tableName]; 
        } else {
            if ( strpos($tableName, "_") !== false ) {
                $arr = explode("_", $tableName);
                array_shift($arr);
                foreach ( $arr as $val ) {
                    $className .= ucfirst($val);
                }
            } else {
                $className = ucfirst($tableName);
            }
        }
        
        return $className;
    }


    /**
     * get columns pairs by table name
     * 
     * @param string $tableName
     * @return type
     */
    public static function getColumnsPairsByTableName($tableName) {
        $result = array();
        $model = self::getModelByTableName($tableName);
        $columnsArr = $model->getMetaData()->columns;
        foreach ($columnsArr as $column => $columnObj) {
            $result[$column] = empty($columnObj->comment) ? $column : $columnObj->comment;
        }
        
        return $result;
    }
    /**
     * get columns List by table name
     * 
     * @param string $tableName
     * @return array('id','name'...);
     */
    public static function getColumnsArrByTableName($tableName) {
        $result = array();
        $model = self::getModelByTableName($tableName);
        $columnsArr = $model->getMetaData()->columns;
        foreach ($columnsArr as $column => $columnObj) {
            $result[] = $column;
        }
        
        return $result;
    }
    
    /**
     * create key - value
     * 
     * @param  object $list
     * @param string $key
     * @param string $value
     * @return array $result
     */
    public static function createKeyValue($list, $key, $value = ''){
        if(! $value){
            $value = $key;
        }
        
        $result = array(); 
        foreach($list as $val){
            $result[$val[$key]] = $val[$value];
        }
        
        return $result;
    }
    
    /**
     * create value group
     * 
     * @param object $list
     * @param array $keys
     * @return array $result
     */
    public static function createValueGroup($list, $keys = array()) {        
        $result = array();
        foreach($list as $val){
            foreach ($keys as $key) {
                $result[$key][] = $val[$key];
            }            
        }
        
        return $result;
    }

    /*
     * get the days between date
     * @$fromDate:2012-8-8 12:12:12
     * @$toDate:2013-8-8 12:12:12
     * return $days:int
     */
    public static function getDayFromDateToDate($fromDate,$toDate){
    	$fromTime =  self::getYmdFromYmdHis($fromDate);
    	$toTime =  self::getYmdFromYmdHis($toDate);
    	$unix = $fromTime - $toTime;
    	$days = $unix/86400;
    	return $days;
    }

    /*
     * get Y-m-d from Y-m-d H:i:s
     * @$date:2013-11-18 9:45:45
     * return timestamp:  strtotime('2013-11-18')
     */
    public static function getYmdFromYmdHis($date){
    	$unix = strtotime($date);
    	$date = date("Y-m-d",$unix);
    	return strtotime($date);
    }

    /*
     * array to object
     * @param array() $e
     * return object
     */
    public static function arrayToObject($e){
    	if( gettype($e)!='array' ) return;
    	foreach($e as $k=>$v){
    		if( gettype($v)=='array' || getType($v)=='object' )
    			$e[$k]=(object)self::arrayToObject($v);
    	}
    	return (object)$e;
    }
    

    /*
     * object to array
    * @param: object $e
    * return array
    */
    public static function objectToArray($e){
    	$e=(array)$e;
    	foreach($e as $k=>$v){
    		if( gettype($v)=='resource' ) return;
    		if( gettype($v)=='object' || gettype($v)=='array' )
    			$e[$k]=(array)self::objectToArray($v);
    	}
    	return $e;
    }
    
    public static function getCurrentHour() {
        return substr(date('Y-m-d H:i:s'), 11, 2);
    }
	
	/**
	 * 
	 * get one or more fields by SearchCondition
	 * @param mixed(Array/String) $fields
	 */
	public static function getFieldsBySearchCondition($fields){
		if($fields!=null){
			$fields_str = '';
			if(is_array($fields)){
				$fields_str = '';
				foreach($fields_str as $value){
					$fields_str .= $value.',';
				}
				$fields = trim($fields_str);
			}
		}else{
			$fields = '*';
		}
		return $fields;
	}
	public static function runThread($url,$hostname='',$port=80) {
		if(!$hostname){
			$hostname=$_SERVER['HTTP_HOST'];
		}
		$fp=fsockopen($hostname,$port,$errno,$errstr,600);
		if (!$fp)
		{
			echo "$errstr ($errno)<br />\n";
			return;
		}
		fputs($fp,"GET ".$url."\r\n");
		fclose($fp);
	}
	public static function runThreads($url,$hostname='',$port=80) {
		if(!$hostname){
			$hostname=$_SERVER['HTTP_HOST'];
		}
		$fp=fsockopen($hostname,$port,$errno,$errstr,600);
		if (!$fp)
		{
			echo "$errstr ($errno)<br />\n";
			return;
		}
		fputs($fp,"GET ".$url."\r\n");
		while (!feof($fp)){
			echo fgets($fp,2048);
		}
		
		fclose($fp);
	}

	public static function runThreadSOCKET($urls,$hostname='',$port=80) {
		if(!$hostname){
			$hostname=$_SERVER['HTTP_HOST'];
		}
		if(!is_array($urls)){
			$urls = (array)$urls;
		}
		foreach ($urls as $url) {
			$fp=fsockopen($hostname, $port,  $errno, $errstr, 18000);
			stream_set_blocking ( $fp, true );
			stream_set_timeout ( $fp, 18000 );
			fputs($fp,"GET ".$url."\r\n");
			fclose($fp);
		}
	}

    /**
     * 以socket方式发送请求
     * @param  string   $url     
     * @param  array    $post   
     * @param  string   $cookie 
     * @param  string   $host 
     * @param  integer  $port    
     * @param  integer  $timeout  
     * @param  boolean  $block 
     * @param  boolean  $response 
     * @return boolean|mixed
     * @author yangsh
     * @since  2016-06-11  
     */
    public static function runThreadBySocket($url, $post='', $limit=0, $cookie='', $ip='', $timeout=1800, $block=false, $response=false) {
        $return = '';
        $uri = parse_url($url);
        if (empty($uri)) {
            return false;
        }
        isset($uri['host'])  || $uri['host']  = '';
        isset($uri['path'])  || $uri['path']  = '';
        isset($uri['query']) || $uri['query'] = '';
        isset($uri['port'])  || $uri['port']  = '';
        $host = $uri['host'];
        $path = $uri['path'] ? $uri['path'] . ($uri['query'] ? '?' . $uri['query'] : '') : '/';
        $port = !empty($uri['port']) ? $uri['port'] : 80;
        if ($post) {//post方式
            $data = http_build_query($post);
            $out = "POST {$url} HTTP/1.1\r\n";  
            $out .= "Accept: */*\r\n";
            $out .= "Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3\r\n";
            $out .= "Content-type:application/x-www-form-urlencoded\r\n";  
            $out .= "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0\r\n";
            $out .= "Host:{$host}\r\n";  
            $out .= "Content-length:".strlen($data)."\r\n";  
            $out .= "Connection:close\r\n";
            $out .= "Cache-Control: no-cache\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
            $out .= "{$data}";   
        } else {//get方式
            $out = "GET {$url} HTTP/1.1\r\n";  
            $out .= "Host: {$host}\r\n";  
            $out .= "Connection:close\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
        }
        // create connect  
        $fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        if(!$fp){  
            return false;//note $errstr : $errno \r\n
        }
        //集阻塞/非阻塞模式流,$block==true则应用流模式
        stream_set_blocking($fp, $block);
        //设置流的超时时间
        stream_set_timeout($fp, $timeout);
        @fwrite($fp, $out);
        //从封装协议文件指针中取得报头／元数据
        $status = stream_get_meta_data($fp);
        //timed_out如果在上次调用 fread() 或者 fgets() 中等待数据时流超时了则为 TRUE,下面判断为流没有超时的情况
        if ($response && !$status['timed_out']) {
            while (!feof($fp)) {
                if (($header = @fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
                    break;
                }
            }
            $stop = false;
            //如果没有读到文件尾
            while (!feof($fp) && !$stop) {
                //看连接时限是否=0或者大于8192  =》8192  else =》limit  所读字节数
                $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                $return .= $data;
                if ($limit) {
                    $limit -= strlen($data);
                    $stop = $limit <= 0;
                }
            }
        }
        @fclose($fp);
        return $return;
    }

    /**
     * 按平台保存description
     * @param  string $filename     
     * @param  string $description  
     * @param  string $platformCode 
     * @return boolean              
     */
    public static function saveDescription($filename,$description,$platformCode) {
        $path = Yii::getPathOfAlias('webroot').'/data/description/'.$platformCode.'/';
        return self::writefilelog($filename,$description,$path,'wb');//覆盖写
    }

    /**
     * 按平台账号关键字获取描述内容
     * @param  string $platformCode 
     * @param  string $accountID    
     * @param  string $key          
     * @return string               
     */
    public static function getDescription($platformCode, $accountID, $key) {
        $path = Yii::getPathOfAlias('webroot').'/data/description/'.$platformCode.'/';
        $filename = $path . '/' . $accountID . '/' . $key. '.html';
        return file_exists($filename) ? file_get_contents($filename) : '';
    }

    /**
     * printvar for test
     * @param  mixed  $var      
     * @param  boolean $is_exit 
     * @author yangsh
     * @since  2016-06-12
     */
    public static function printvar($var, $is_exit=true) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
        if ($is_exit){
            die("#### printvar end ####");
        }
    }

    /**
     * mkdirs 
     * @param  string $dirname 
     * @return boolean
     * @author yangsh
     * @since  2016-06-12
     */
    public static function mkdirs($dirname) {
        $dirname = rtrim($dirname,'/');
        if (!file_exists($dirname)) {
            mkdir($dirname,0755,true);
        }
        return true;
    }

    /**
     * writefilelog 
     * @param  string $filename
     * @param  string $data     
     * @return boolean
     * @author yangsh
     * @since  2016-06-12
     */
    public static function writefilelog($filename, $data, $path='', $mode=null) {
        $logPath = Yii::getPathOfAlias('webroot').'/log/';
        if ($path != '') {
            $logPath = $path;
        }
        $file = $logPath . $filename;//文件全名
        $dirname = dirname($file);
        if (!is_dir($dirname)) {
            self::mkdirs($dirname);
        }
        if (is_null($mode)) {
            $mode = 'ab';//默认追加写
        }
        $fp = fopen($file, $mode);
        if ( flock($fp,LOCK_EX)) {
            $flag = fwrite($fp, $data);
            flock($fp, LOCK_UN);  
            if (!$flag) {
                return false;
            }
        } else {
            echo "Couldn't get the lock!";
            return false;
        }
        fclose($fp);
        return true;
    }    
	
	//copy array
	public static function copyArray($array,$copy_keys,&$newArray) {
		!is_array($copy_keys) && $copy_keys = explode(',',$copy_keys);
	
		foreach ($copy_keys as $key){
			$newArray[$key] = $array[$key]; 
		}
	}
	/**
	 * 
	 * make a time to formate of Greenwich
	 * @param Int $time
	 */
	public static function getGreenwichTime($time=''){
		if(!$time){
			$time = time();
		}
		return $time - 28800;
	}

    /**
     * get format log
     * @param $log
     * @return array
     */
    public static function formatLog($log){
        // {Model:Att} {oldVal} to {newVal}
        $key = $oldVal = $newVal = '';
        preg_match_all("/{([^}]*)}/", $log, $matches);
        if (isset($matches[1][0])){
            $key = isset($matches[1][0])  ? $matches[1][0] : '';
            $oldVal = isset($matches[1][1]) ? $matches[1][1] : '';
            $newVal = isset($matches[1][2]) ? $matches[1][2] : '';

            $modelName = strstr($key,':',true);
            $att   = substr(strstr($key,':'),1);


            if (class_exists(ucfirst($modelName))) {
                $modelObj = UebModel::model($modelName);
                $log = str_replace(
                    array('{','}',$modelName,$att,' to '),
                    array(
                        '',
                        '',
                        '<font color=blue>'. $modelObj->getAttributeLabel($modelName).'</font>',
                        '<font color=green>'.$modelObj->getAttributeLabel($att).'</font>',
                        ' '.Yii::t('orderlog', 'to').' ',
                    ),
                    $log
                );

                if (method_exists($modelObj, 'replaceLogMsg')) {
                    $replaceArr = $modelObj->replaceLogMsg($att,$oldVal,$newVal);
                    if (!empty($replaceArr)) {
                        $log = str_replace($replaceArr[0],$replaceArr[1], $log);
                    }
                }
            }
        }

        return $log;
    }
    
    public static function getNowTime(){
    	return date('Y-m-d H:i:s');
    } 
    
    
    /**
     * add by Tom 2014-02-18
     * 从源对象中复制若干Key，形成新的对象
     * @param Ojbect $sourceObj
     * @param String $keys
     * @retrun Object
     */
   public  static function copyObject($sourceObj,$targetKeys,$scoureKeys=''){
    	$targetObj = null;
    	!is_array($targetKeys) && $targetKeys = explode(',',$targetKeys);
    	if(!empty($scoureKeys)){
    		!is_array($scoureKeys) && $scoureKeys = explode(',',$scoureKeys);
    	}
  		
		foreach ($targetKeys as $key=>$value){
			
			//echo $value.'--'.$sourceObj->$scoureKeys[$key].'<br/>';
			$targetObj->$value = $sourceObj->$scoureKeys[$key];
		}
		return $targetObj;
    }
    
   public static function simplode($ids) {
		return "'".implode("','", $ids)."'";
   }
   /**
    * 
    * add By Tom 2014-02-21
    * @param mix $source
    * @param String $keyword
    */
   public static function newArrayByKey($source,$keyword){
   		$newArray = array();
   		foreach($source as $key=>$value){
   			
	   			if(is_array($value)){
	   				$newArray[] = $value[$keyword];
	   			}elseif(is_object($value)){
	   				$newArray[] = $value->$keyword;
	   			}
   			
   		}
   		if($newArray) $newArray = array_unique($newArray);
   		return $newArray;
   }
   
   /**
    * 
    * 功能:将正常的时间转成速卖通请求的时间格式
    * 例子: 2014-02-24 00:00:00 将转成 02/24/2014 00:00:00
    * @param date $time
    */
   public static function formateAliexpressTime($time){
   		$tmp = explode(' ', $time);
        $data = explode('-', $tmp[0]);
        $time = $data[1] . '/' . $data[2] . '/' . $data[0];
        return $time.' '.$tmp[1];
    
   }
   
   /**
    * add By Tom 2014-02-24
    * 功能 将一个时间戮格式的变量转化成正常的日期格式
    */
   public static function getDateFormateByUnixTime($time){
   		return date('Y-m-d H:i:s',$time);
   }
   
   public static function getDbNameByModelName($modeName){
   		$dbkey = UebModel::model($modelName)->getDbKey();
   		$env = new Env();
   		
   		return $env->getDbNameByDbKey($dbkey);
   }
   /**
    * 
    */
	public static function getDateFormat($timeType){
		$format = '';
		switch ($timeType){
			case ExcelSchemeColumn::_MONTH:
				$format = '%Y/%m';
				break;
			case ExcelSchemeColumn::_DAY:
				$format = '%Y/%m/%d';
				break;
			case ExcelSchemeColumn::_WEEK:
				$format = '%w';
				break;
			case ExcelSchemeColumn::_YEAR:
				$format = '%Y/%m';
				break;
			case ExcelSchemeColumn::_HOUR:
				$format = '%Y/%m/%d %H';
				break;
			default:
				$format = '%Y/%m';
		}	
		return $format;
	}
	
	/**
	 *
	 * const _DAY = 1;
	 const _WEEK = 2;
	 const _MONTH = 3;
	 const _YEAR = 4;
	 */
	public static function getDateDiff( $timeType = ExcelSchemeColumn::_MONTH){
		$date = array();
		switch ($timeType){
			case ExcelSchemeColumn::_DAY:
				$time_s=mktime(0,0,0,date('m'),date('d'),date('Y'));
				$time_e=mktime(23,59,59,date('m'),date('d'),date('Y'));
				$date[] = date('Y-m-d H:i:s',$time_s);
				$date[] = date('Y-m-d H:i:s',$time_e);
				break;
			case ExcelSchemeColumn::_WEEK:
				$date[] = date("Y-m-d 00:00:00",strtotime("last week"));
				$date[] = date("Y-m-d 23:59:59",strtotime("this week"));;
				break;
			case ExcelSchemeColumn::_MONTH:
				$time_s=mktime(0,0,0,date('m'),1,date('Y'));
				$time_e=mktime(23,59,59,date('m'),date('t'),date('Y'));
	
				$date[] = date('Y-m-d H:i:s',$time_s);
				$date[] = date('Y-m-d H:i:s',$time_e);
				break;
			case ExcelSchemeColumn::_YEAR:
				$time_s=mktime(0,0,0,1,1,date('Y')-1);
	
				$date[] = date('Y-m-d 00:00:00',$time_s);
				$date[] = date('Y-m-d H:i:s',time());
				 
				break;
			default:
				 
		}
		return $date;
	}
	
	public static function getWeek($day=null){
		$config = array(
			1 => Yii::t('common','Monday'),
			2 => Yii::t('common','Tuesday'),
			3 => Yii::t('common','Wednesday'),
			4 => Yii::t('common','Thursday'),
			5 => Yii::t('common','Friday'),
			6 => Yii::t('common','Satursday'),
			0 => Yii::t('common','Sunday'),
		);
		if ($day !== null) return $config[$day];
		return $config;
	}
	
	public static function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
    	return ((float)$usec + (float)$sec);
	}
	
	/**
	 * @desc 获取用户信息列表
	 * @param number $user_id
	 * @return array
	 */
	public static function getUserInfoList($user_id=0) {
		static $data = array();
		if ( empty($data) ) {
			$list = User::model()->getUserData();
			$data =array();
			foreach($list as $key=>$val){
				$data[$val['id']] =$val['user_full_name'];
			}
		}
		if(!empty($user_id)) {
			return $data[$user_id];
		}
		return $data;
	
	}
	
	/**
	 * @desc 根据当前用户角色获取用户列表
	 * @param string or array: $role_code
	 * @return multitype:
	 */
	public static function getUserByRole($role_code){
		$userList =array();
		if(isset($role_code) && !empty($role_code) && is_string($role_code)){
			$role_code = array($role_code);
		}
		if(User::isAdmin()){
			$roles = $role_code;
			$userList = AuthAssignment::model()->getUlist($roles,1);
		}else{
			//获取当前角色
			$roleL = User::getLoginUserRoles();
			//获取当前角色子角色
			$childRole = AuthItemChild::getChildRoleByParent($roleL);
			if($childRole){//如果存在，则取所有子角色人员
				$roles = array_merge($role_code, $childRole);
				$userList = AuthAssignment::model()->getUlist($roles,1);
			}else{
				$userList = User::getUserNameArrById(Yii::app()->user->id);
			}
		}
		return $userList;
	}
	
	/**
	 * @desc 获取平台账号 author wx
	 * @param unknown $platform
	 * @param string $account_id
	 */
	public static function getPlatformAccount($platform,$account_id = null){
		$arr =array();
		switch($platform){
			case Platform::CODE_EBAY:
				$ebay = UebModel::model('EbayAccount')->findAll();
				foreach($ebay as $key =>$val){
					$arr[$val['id']] = $val['short_name'];
				}
				if($account_id){
					return $arr[$account_id];
				}else{
					return $arr;
				}
				break;
			case Platform::CODE_NEWFROG:
				return false;
				break;
			case Platform::CODE_YESFOR:
				return false;
				break;
			case Platform::CODE_ALIEXPRESS:
				$aliexpress = UebModel::model('AliexpressAccount')->findAll();
				foreach($aliexpress as $key => $val){
					$arr[$val['id']] = $val['short_name'];
				}
				if($account_id){
					return $arr[$account_id];
				}else{
					return $arr;
				}
				break;
			case Platform::CODE_NEWFROG:
				return false;
				break;
			case Platform::CODE_WISH:
				$wish = UebModel::model('WishAccount')->getAllWishAccounts();
				foreach($wish as $key => $val){
					$arr[$val['wish_id']] = $val['account_name'];
				}
				if($account_id){
					return $arr[$account_id];
				}else{
					return $arr;
				}
				return false;
				break;
			case Platform::CODE_AMAZON:
				$amoazon = UebModel::model('AmazonAccount')->findAll();
				foreach($amoazon as $key => $val){
					$arr[$val['id']] = $val['short_name'];
				}
				if($account_id){
					return $arr[$account_id];
				}else{
					return $arr;
				}
				break;
			case Platform::CODE_LAZADA:
				$lazada = UebModel::model('LazadaAccount')->findAll();
				foreach($lazada as $key => $val){
					$arr[$val['id']] = $val['seller_name'];
				}
				if($account_id){
					return $arr[$account_id];
				}else{
					return $arr;
				}
				break;
		}
	}
	
	/**
	 * @desc 将aliexpress时间字符串转化成北京时间
	 * @param string $string
	 * @return string
	 */
	static public function aliexpressTimeToBJTime($string = '') {
		$year = substr($string, 0, 4);
		$month = substr($string, 4, 2);
		$day = substr($string, 6, 2);
		$timezone = substr($string, -5);
		$hour = substr($string, 8, 2);
		$minute = substr($string, 10, 2);
		$second = substr($string, 12, 2);
		$microsecond = substr($string, 14, 3);
		$timeInterval = (8 - (int)str_replace('0', '', $timezone)) * 3600;
		$timestamp = mktime($hour, $minute, $second, $month, $day, $year);
		$timestamp += $timeInterval;
		return date('Y-m-d H:i:s', $timestamp);
	}
        
        /**
	 * @desc 将excel中带小数点的sku取两位小数
	 * @param string $sku
	 * @return string
	 */
	static public function excelSkuSet($sku = '') {
            if (empty($sku)) {
                return FALSE;
            }
            $round = strripos($sku, '.');
            if( $round ){
                $dec = substr($sku, 0, $round);
                $point = substr($sku, $round);
                $point_length = strlen($point);
                $point = round($point,2);
                if($point_length <= 4){
                    return $sku;
                } elseif(strlen($point) <= 3) {
                    $point .= '0';
                }
                $point = substr($point, 1);
                if($point == '.07000000000000001'){
                    $point = '.07';
                }
                $sku = $dec.$point;
            }
            return $sku;
	}

    /**
     * 数据分组
     * @param  array  $data            
     * @param  integer $numbersPerGroup 
     * @return array
     * @author yangsh
     * @since 2016-06-08
     */
    public static function getGroupData($data, $numbersPerGroup=100 ) {
        $i = 1;
        $groupIndex = 0;
        foreach ($data as $key => $value) {
            if ($i%$numbersPerGroup == 0) {
                $groupData[$groupIndex++][] = $value;
            } else {
                $groupData[$groupIndex][] = $value;
            }
            $i++;
        }
        return $groupData;
    }

    /**
     * 数组转化为查询语句
     * @param array $data
     * @return string
     * @author yangsh
     * @since 2016-06-08
     */
    function array2sql($array) {
        $sql_array = array ();
        foreach ($array AS $_k => $_v) {
            if (empty ($_k)) {
                continue;
            }
            $_v = trim($_v);
            if (ctype_digit($_v) && preg_match("/^[1-9][0-9]+$/", $_v)) {
                $sql_array[] = "`{$_k}`={$_v}";
            } elseif(preg_match("/\s*{$_k}\s*\+\s*\d+\s*$/", $_v)){//匹配表字段加
                $sql_array[] = "`{$_k}`={$_v}";
            } else {
                $sql_array[] = "`{$_k}`='{$_v}'";
            }
        }
        return implode(',', $sql_array);
    }    

    /**
     * 数组值统一大小写
     * @param   array  $array
     * @param   int    $type    1 大写，2小写
     * @return  array
     */
    static public function getNewArray($array,$type=1) {
        foreach ($array as $key => $value) {
            $value2 = trim($value);
            $type == 1 && $value2 = strtoupper($value);
            $type == 2 && $value2 = strtolower($value);
            $array[$key] = $value2;
        }
        return $array;
    }

    /**
     * 二维数组排序
     * @param   array  $array
     * @param   key    $sort_key    二维数组排序的键
     * @param   SORT_ASC    $sort_order   排序方式
     * @return  array
     */
    static public function custom_array_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }

}
?>
