<?php

class JdCategory extends JdModel {
	const EVENT_GET_CATEGORY = 'get_category';
	public function tableName(){
		
		return 'ueb_jd_category';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	/**
	 * @desc 保存接口下来的数据
	 * @param unknown $categoryData
	 * @param unknown $accountId
	 * @return boolean
	 */
	public function saveCategoryData($categoryData, $accountId){
		if(empty($categoryData)) return false;
		$levelOne = isset($categoryData['levelOne']) ? $categoryData['levelOne'] : array();
		$levelTwo = isset($categoryData['levelTwo']) ? $categoryData['levelTwo'] : array();
		$levelThree = isset($categoryData['levelThree']) ? $categoryData['levelThree'] : array();
		$this->_saveCategoryInfo($levelOne, $accountId, 1);
		$this->_saveCategoryInfo($levelTwo, $accountId, 2);
		$this->_saveCategoryInfo($levelThree, $accountId, 3);
		return true;
	}
	
	private function _saveCategoryInfo($newCategory, $accountId, $level){
		if($newCategory){
			foreach ($newCategory as $category){
				$addData = array(
						'cat_id' 	=> 	$category['catId'],
						'account_id'=> $accountId,
						'cat_level'	=>	$category['catLevel'],
						'cat_name'	=>	$category['catName'],
						'cat_name_en'=>	$category['catNameEn'],
						'parent_id'	=>	$category['parentId'],
						'sort_order'=>	$category['sortOrder'],
						'status'	=>	$category['status'],
				);
				$categoryId = $category['catId'];
				//判断是否存在
				$categoryInfo = $this->find('cat_id=:cat_id AND account_id=:account_id',array(':cat_id'=>$categoryId, ':account_id'=>$accountId));
				if($categoryInfo){
					//update
					$this->getDbConnection()->createCommand()->update($this->tableName(), $addData,
							'id=:id AND cat_id=:cat_id AND account_id=:account_id',
							array(':id'=>$categoryInfo->id, ':cat_id'=>$categoryId, ':account_id'=>$accountId));
						
				}else{
					//add
					$this->getDbConnection()->createCommand()->insert($this->tableName(), $addData);
				}
			}
		}
	}
	/**
	 * @desc 根据账号id获取第三级分类列表
	 * @param unknown $accountId
	 * @return mixed
	 */
	public function getThreeCategoryListByAccountId($accountId){
		return $this->getDbConnection()->createCommand()
								->from($this->tableName())
								->where('account_id=:account_id AND cat_level=:cat_level', array(':account_id'=>$accountId, ':cat_level'=>3))
								->queryAll();
	}
	
	/**
	 * @desc 获取推荐分类
	 * @param unknown $sku
	 * @return Ambigous <NULL, mixed, string, unknown>|unknown|Ambigous <NULL, unknown, mixed, string>
	 */
	public function getRecommendCategoryIds($sku) {
		$recommendCategoryID = null;
		//查找该平台已经刊登过的listing
		$listings = JdProduct::model()->getOnlineListingBySku($sku);
		if (!empty($listings)) {
			foreach ($listings as $listing) {
				return $listing['category_id'];
			}
		}
		//查找待刊登列表里面的记录
		$addInfos = JdProductAdd::model()->getAddInfosBySku($sku);
		if (!empty($addInfos)) {
			foreach ($addInfos as $addInfo)
				return $addInfo['category_id'];
		}
		//查找速卖通平台已经刊登的listing
		$listings = AliexpressProduct::model()->getOnlineListingBySku($sku);
		if (!empty($listings)) {
			$categoryID = null;
			foreach ($listings as $listing) {
				$categoryID = $listing['category_id'];
				break;
			}
			if (!empty($categoryID)) {
				//用速卖通的分类名去京东分类里面搜索相近的分类
				$categoryInfo = AliexpressCategory::model()->getCategotyInfoByID($categoryID);
				if (!empty($categoryInfo)) {
					$categoryName = $categoryInfo['en_name'];
					$recommendCategoryID = $this->dbConnection->createCommand()
						->select("cat_id")
						->from(self::tableName())
						->where("cat_name_en like '%" . addslashes($categoryName) . "%'")
						->queryScalar();
					if (!empty($recommendCategoryID))
						return $recommendCategoryID;
					//打散词组用单个单词去搜索
					$categoryName = str_replace(array("&"), '', $categoryName);
					$keywords = explode(' ', $categoryName);
					$tmpKeywords = array();
					foreach ($keywords as $val) {
						$val = trim($val);
						if ($val == '') continue;
						$tmpKeywords[] = $val;
					}
					$recommendCategoryID = $this->getCategoryIDByKeyWords($tmpKeywords);
					if (!empty($recommendCategoryID)) return $recommendCategoryID;
				}
			}	
		}
		
		//将标题拆成单词去搜索
		$productDescInfo = Productdesc::model()->getDescriptionInfoBySkuAndLanguageCode($sku, 'english');
		if (!empty($productDescInfo)) {
			$title = $productDescInfo['title'];
			$keyWordsTitle = explode(' ', $title);
			//获取ebay的分类名
			$keyWordsEbay = Product::model()->getPlatformCategoryBySku($sku, Platform::CODE_EBAY);
			$keywords = array_merge($keyWordsTitle,$keyWordsEbay);
			$recommendCategoryID = $this->getCategoryIDByKeyWords($keywords);
			if (!empty($recommendCategoryID)) return $recommendCategoryID;
		}
		return $recommendCategoryID;
	}
	
	/**
	 * @desc 根据关键字搜索分类ID
	 * @param unknown $keyWords
	 * @return NULL
	 */
	public function getCategoryIDByKeyWords($keyWords) {
		$categoryID = null;
		$excludeWords = array('for','to','and','or');
		$ableCategory = array();
		foreach($keyWords as $word){
			if( in_array($word, $excludeWords) || strlen($word) <= 2 || strpos($word, '"')!==false || strpos($word, "'")!==false ){
				continue;
			}
			$categoryID = $this->dbConnection->createCommand()->select('cat_id')->from(self::tableName())->where('cat_name_en LIKE "%'.$word.'%"')->andWhere("cat_level = 3")->queryScalar();
			if (!empty($categoryID)) return $categoryID;
		}
		return $categoryID;	
	}
	
	/**
	 * @desc 查找父类ID
	 * @param unknown $categoryID
	 */
	public function getParentCategoryID($categoryID) {
		return $this->dbConnection->createCommand()
		->select("parent_id")
		->from(self::tableName())
		->where("cat_id = :cat_id", array(':cat_id' => $categoryID))
		->queryScalar();
	}
	
	/**
	 * @desc 根据分类ID获取分类名称
	 * @param unknown $categoryID
	 * @return Ambigous <mixed, string, unknown>
	 */
	public function getCategoryNameByCategoryID($categoryID) {
		return $this->dbConnection->createCommand()
			->select("cat_name_en")
			->from(self::tableName())
			->where("cat_id = :category_id", array(':category_id' => $categoryID))
			->queryScalar();
	}
}

?>