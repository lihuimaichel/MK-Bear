<?php

class JdCustompropTemplate extends JdModel {
	const EVENT_NAME = 'get_customprop_temp';
	public function tableName(){
		
		return 'ueb_jd_customprop_template';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	
	
	public function saveTemplateData($datas, $accountId){
		if(empty($datas)) return false;
		foreach ($datas as $data){
			$addData = array(
						'account_id'	=>	$accountId,
						'temp_id'	=>	$data['id'],
						'title'		=>	$data['title'],
						'status'	=>	$data['status']
					
			);
			$checkExists = $this->find('temp_id=:temp_id AND account_id=:account_id', 
					array(':temp_id'=>$data['id'], ':account_id'=>$accountId));
			if($checkExists){
				$this->getDbConnection()->createCommand()->update($this->tableName(),
																	$addData,
																	'temp_id=:temp_id AND account_id=:account_id', 
																	array(':temp_id'=>$data['id'], ':account_id'=>$accountId));
			}else{
				$this->getDbConnection()->createCommand()->insert($this->tableName(), $addData);
			}
		}
		return true;
	}
}

?>