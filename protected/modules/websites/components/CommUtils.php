<?php
class CommUtils {
	
	const DELIMITER = ',';
	/**
	 * 执行多线程的一些方法  注意这里没加载登录信息 
	 * @return Ambigous <boolean, multitype:string >
	 */
	
	static function runMutilThreads($urls){
		//
		if(!class_exists('Http_MultiRequest')) throw new Exception('not found class.');
		$m = new Http_MultiRequest();
		$m->setUrls($urls);
		$data = $m->exec();
		return $data;
	}
	static function OutputMutilThreadsResult($results) {
		foreach ($results as $result){
			echo $result;
		}
	}
	
	static function log($file,$message){
		$fp = @fopen($file,"ab+");
		@fwrite($fp,$message);
		@fclose($fp);
	}
	/**
	 * 分割字符串产生 可以在 sql 中使用的字符串 添加单引号
	 * @example 在Yii $model->findAll($condition);
	 * 但是不适合 $model->findAll('id=:id',array(':id'=>$Id))
	 * @param String $string
	 * @param String $string default ','
	 */
	static function generateConditonPart($string,$delimiter=','){
		$idarray = explode($delimiter,$string);
		//new
		$newidarray = array();
		foreach ($idarray as $id) {
			if(!empty($id)){$newidarray[] = "'".$id."'";}
		}
		unset($idarray);
		$idsCondition = implode($delimiter, $newidarray);
		return $idsCondition;
	}
	public static function getDbNameByModelName($modelName){
		global $env;
		$dbkey = UebModel::model($modelName)->getDbKey();
		if($env instanceof Env){
			return $env->getDbNameByDbKey($dbkey);
		}
		throw new UebException('Can not find config env instance');
	}
	/*************************************** String ************************************************************************/
	/** 判断是否有中文 - 兼容gb2312,utf-8
	 * @param String $str
	 * @param string $encode
	 * @return boolean
	 */
	static function checkSimpleChinese($str,$encode = 'UTF-8'){
		return preg_match("/[\x7f-\xff]+/x", $str) ? true : false;
	}
} 