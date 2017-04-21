<?php
class ProductTitleWord extends ProductsModel{
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class 
	 */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'ueb_product_title_word';
	}
	
	/**
	 * get sku by title word
	* @author:Nick 2013-9-30
	*/
	public function getSkuByTitleWord($title, $lang_code='english'){

		if($title=='') return array();
		if(strpos($title,'+')){
			$titleArr = explode('+', trim($title));
		}else{
			$titleArr = explode(' ', trim($title));
		}
		$titleNum = count($titleArr);
		$data = $this->getDbConnection()->createCommand()
				->select("sku, count(title_word)/".$titleNum." *100 AS rate")
				->from(self::tableName())
				->where(array('IN','title_word',$titleArr))
				->andwhere('language_code =:language_code',array(':language_code'=>$lang_code))
				->order('rate desc')->group('sku')
				->queryAll();
		$list = array();
		if($data){
			foreach($data as $key=>$val){
				if(strpos($title,'+')){
					if($val['rate']==100.0000){
						$list[]=$val['sku'];
					}
				}else{
					$list[]=$val['sku'];
				}
			}
		}
		return $list;
	}
	
	/**
	 * save sku and title
	 * @author Nick 2013-10-6
	 */
	public function saveSkuAndTitle($arr){
		$model = new self();
		$titleArr = array_unique(explode(' ', trim($arr['title'])));
		try {
			$this->getDbConnection()->createCommand()
			->delete(self::tableName(), "sku ='".$arr['sku']."' and language_code = '".$arr['language_code']."'");
		}catch (Exception $e) {
			echo $e->getMessage(); exit;
		}
		
		foreach($titleArr as $value){
			$model->setAttribute('sku', $arr['sku']);
			$model->setAttribute('title_word', addslashes($value));
			$model->setAttribute('language_code', $arr['language_code']);
			$model->save();
		}
		
		if(isset($arr['childSku'])){
			foreach ($arr['childSku'] as $val){
				$subSkutitleArr = array_unique(explode(' ', trim($val['title'])));
				try {
					$this->getDbConnection()->createCommand()
					->delete(self::tableName(), "sku ='".$val['sku']."' and language_code = '".$arr['language_code']."'");
				}catch (Exception $e) {
					echo $e->getMessage(); exit;
				}
				foreach($subSkutitleArr as $v){
					$model->setAttribute('sku', $val['sku']);
					$model->setAttribute('title_word', addslashes($v));
					$model->setAttribute('language_code', $arr['language_code']);
					$model->save();
				}
			}			
		}
	}
	
}
?>