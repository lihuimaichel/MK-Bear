<?php

class JdBrand extends JdModel {
	const EVENT_GET_BRAND = 'get_brand';
	public function tableName(){
		
		return 'ueb_jd_brand';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	
	
	public function saveBrandData($datas, $accountId){
		if(empty($datas)) return false;
		foreach ($datas as $data){
			$addData = array(
						'account_id'	=>	$accountId,
						'brand_id'		=>	$data['brandId'],
						'brand_name'	=>	$data['brandName'],
						'cn_name'		=>	$data['cnName'],
						'en_name'		=>	$data['enName'],
						'logo'			=>	$data['logo'],
						'status'		=>	$data['status'],
						'first_char'	=>	$data['firstChar'],
						'created'		=>	isset($data['created'])?date("Y-m-d H:i:s", $data['created']):'',
						'modified'		=>	isset($data['modified'])?date("Y-m-d H:i:s", $data['modified']):'',
			);
			$checkBrand = $this->find('brand_id=:brand_id AND account_id=:account_id', 
					array(':brand_id'=>$data['brandId'], ':account_id'=>$accountId));
			if($checkBrand){
				$this->getDbConnection()->createCommand()->update($this->tableName(),
																	$addData,
																	'brand_id=:brand_id AND account_id=:account_id', 
																	array(':brand_id'=>$data['brandId'], ':account_id'=>$accountId));
			}else{
				$this->getDbConnection()->createCommand()->insert($this->tableName(), $addData);
			}
		}
		return true;
	}
}

?>