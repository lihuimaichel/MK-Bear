<?php
/**
 * @desc Aliexpress分类
 * @author wx
 * @since 2015-09-11
 */
class AliexpressCategory extends AliexpressModel{
    
    const EVENT_NAME = 'get_categories';
    const EVENT_NAME_CATE_SUGGEST = 'get_categories_suggest';
    
    /** @var int 账号ID*/
    protected $_accountID = null;
    
    /** @var string 异常信息*/
    protected $exception = null;
    
    /** @var string 关键字 **/
    protected $_keyword = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
    	$this->_accountID = $accountID;
    }
    
    /**
     * @desc  设置分类id
     * @param string $keyword
     */
    public function setKeyword($keyword) {
    	$this->_keyword = $keyword;
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){}
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_category';
    }
    
    /**
     * @desc 更新分类
     */
    public function updateCategories( $params = array(),$createLevel = 1,$parantID = 0 ){
    	$accountID = $this->_accountID;
        if(!$accountID){ 
            $account = AliexpressAccount::getAbleAccountByOne();
            $accountID = $account['id'];
        }
        $request = new GetCategoryRequest();
        foreach($params as $col=>$val){
        	switch ($col){
        		case 'cateId':
        			$request->setCateId($val);
        			break;
        	}
        }

        $levelOneCategoryArray = array();

        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        if( $request->getIfSuccess() ){
        	$beforeTime = date("Y-m-d H:i:s", time()-12*3600);
            $categories = $response->aeopPostCategoryList;
            foreach($categories as $category){
                $this->saveCategory($category,$createLevel,$parantID);

                //获取一级类目category_id
                if(intval($category->level) == 1){
                    $levelOneCategoryArray[] = $category->id;
                }
            }
            //删除对应层级过时的类目
            $this->deleteCategoriesBeforeTime($beforeTime, $parantID);

            //删除与aliexpress不存在的一级目录
            if($params['cateId'] == 0 && $levelOneCategoryArray){
                $levelOneArray = array();
                //取出所有一级栏目category_id
                $levelOneResult = $this->getCategoryByCreateLevel();
                foreach ($levelOneResult as $key => $value) {
                    $levelOneArray[] = $value['category_id'];
                }

                $diffCategory = array_diff($levelOneArray, $levelOneCategoryArray);
                if($diffCategory){
                    $stringUdiffCategory = implode(',', $diffCategory);
                    $this->deleteLevelOneCategoriesBeforeTimeAndCategoryId($beforeTime, $stringUdiffCategory);
                }
            }
            
            return true;
        }else{
            $this->setExceptionMessage($request->getErrorMsg());
            return false;
        }
    }
    
    /**
     * @desc 更新信息
     */
    public function saveRecord($params){
        return $this->dbConnection->createCommand()->replace(self::tableName(), $params);
    }
    
    public function saveCategory($category,$createLevel = 1,$parantID = 0){
        if( isset($category->id) ){
            $data = array(
                    'category_id'       => intval($category->id),
                    'en_name'           => trim(addslashes($category->names->en)),
                    'parent_category_id'=> $parantID,
                    'level'             => intval($category->level),
                    'timestamp'         => date('Y-m-d H:i:s'),
            		'cn_name'         	=> trim( addslashes(isset($category->names->zh)?$category->names->zh:'') ),
            		'is_leaf'			=> intval($category->isleaf),
            		'create_level'		=> $createLevel,
            );
            $flag = $this->saveRecord($data);
            if( $flag && isset($category->isleaf) && empty($category->isleaf) ){
            	$params = array(
            			'cateId'=>intval($category->id),
            	);
            	$this->updateCategories( $params,$createLevel + 1,intval($category->id));
            }else{
                $updateArr = array(
                        'category_name'     => $this->getBreadcrumbCategory( intval($category->id) ),
                );
                $this->dbConnection->createCommand()->update(self::tableName(), $updateArr, 'category_id = '.intval($category->id));
            }
        }
    }
    
    /**
     * @desc 获取推荐分类
     */
    public function updateCategorySuggest(){
    	$accountID = $this->_accountID;
    	if(!$accountID){
    		$account = AliexpressAccount::getAbleAccountByOne();
    		$accountID = $account['id'];
    	}
    	$request = new GetCategorySuggestRequest();
    	$request->setKeyword($this->_keyword);
    	$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
    	
    	if( $request->getIfSuccess() ){
    		$categoryList = array();
    		$categoryIds = $response->cateogryIds;
    		foreach($categoryIds as $key => $categoryId){
    			$retTmp = $this->getCategotyInfoByID($categoryId);
    			$categoryList[$key]['category_id'] = $retTmp['category_id'];
    			$categoryList[$key]['en_name'] = $retTmp['en_name'];
    			$categoryList[$key]['cn_name'] = $retTmp['cn_name'];
    			$categoryList[$key]['category_name'] = $this->getBreadcrumbCnAndEn($retTmp['category_id'],'->');
    		}
    		return array('flag'=>1,'categoryList'=>$categoryList);
    	}else{
    		$this->setExceptionMessage($request->getErrorMsg());
    		return array('flag'=>0);
    	}
    }
    
    /**
     * @desc 删除过时的分类信息
     * @param unknown $beforeTime
     * @param number $parentId
     * @return boolean|Ambigous <number, boolean>
     */
    public function deleteCategoriesBeforeTime($beforeTime, $parentId = 0){
    	if($parentId <= 0) return false;
    	$where = "timestamp<'{$beforeTime}' AND parent_category_id=:parent_category_id";
    	$params = array(':parent_category_id'=>$parentId);
    	return $this->dbConnection->createCommand()->delete(self::tableName(), $where, $params);
    }

    /**
     * @desc 删除过时的一级分类信息
     * @param unknown $beforeTime
     * @param string $categoryId
     * @return boolean|Ambigous <number, boolean>
     */
    public function deleteLevelOneCategoriesBeforeTimeAndCategoryId($beforeTime, $categoryId = ''){
        $sql = "DELETE FROM ".self::tableName()." WHERE timestamp<'{$beforeTime}' AND create_level = 1 AND category_id IN({$categoryId})";
        $this->dbConnection->createCommand($sql)->execute();
    }
    
    /**
     * @desc 根据父分类获取子分类
     * @param number $parentID
     */
    public function getCategoriesByParentID($parentID=0){
        $where = 'parent_category_id = '.$parentID;
        $list = $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where($where)
                ->queryAll();
        $categories = array();
        foreach($list as $item){
            $categories[] = $item;
        }
        return $categories;
    }
    
    /**
     * @desc 获取分类的面包屑导航
     * @param int $id
     */
    public function getBreadcrumbCategory($id, $separate = '->'){
        $info = $this->getCategotyInfoByID($id);
        $createLevel = $info['create_level'];
        $categoryTree = array();
        $categoryTree[$createLevel] = $info['en_name'];
        for($i = $createLevel-1; $i > 0; $i--){
            $info = $this->getCategotyInfoByID($info['parent_category_id']);
            $categoryTree[$i] = $info['en_name'];
        }
        ksort($categoryTree);
        return implode($separate, $categoryTree);
    }

    /**
     * @desc 获取分类的面包屑导航（包括中文英文）
     * @param int $id
     */
    public function getBreadcrumbCategoryCnEn($id, $separate = '->'){
        $info = $this->getCategotyInfoByID($id);
        $createLevel = $info['create_level'];
        $categoryTree = array();
        $categoryTree[$createLevel] = $info['en_name'];
        for($i = $createLevel-1; $i > 0; $i--){
            $info = $this->getCategotyInfoByID($info['parent_category_id']);
            $categoryTree[$i] = $info['cn_name'].'('.$info['en_name'].')';
        }
        ksort($categoryTree);
        return implode($separate, $categoryTree);
    }    
    
    /**
     * @desc 获取指定分类的顶级分类
     * @param int $id
     */
    public function getTopCategory($id){
        $info = $this->getCategotyInfoByID($id);
        $level = $info['level'];
        for($i = $level-1; $i > 0; $i--){
            $info = $this->getCategotyInfoByID($info['parent_category_id']);
        }
        return $info['category_id'];
    }
    
    /**
     * @desc 获取分类信息
     * @param int $id
     */
    public function getCategotyInfoByID($id){
        return $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('category_id = "'.$id.'"')
                ->queryRow();
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }
    
    /**
     * @desc 根据分类等级获取分类
     * @param number $level
     * @return Ambigous <NULL, unknown, multitype:unknown Ambigous <unknown, NULL> , mixed, multitype:unknown >
     */
    public function getCategoryByLevel($level = 1) {
    	return $this->findAll("level = :level", array(':level' => $level));
    }  

    /**
     * @desc 根据分类等级获取分类
     * @param number $level
     * @return Ambigous <NULL, unknown, multitype:unknown Ambigous <unknown, NULL> , mixed, multitype:unknown >
     */
    public function getCategoryByCreateLevel($level = 1) {
    	return $this->findAll("create_level = :level", array(':level' => $level));
    }    
    
    /**
     * @desc 获取全部分类
     * @param number $level
     * @return Ambigous <NULL, unknown, multitype:unknown Ambigous <unknown, NULL> , mixed, multitype:unknown >
     */
    public function getCategoryList( $field = '*' ) {
    	$ret = $this->dbConnection->createCommand()
    			->select($field)
    			->from($this->tableName())
    			->queryAll();
    	return $ret;
    }
    
    /**
     * @desc 获取parent_category_id = 0的所有分类
     * @param int $category_id
     * @param int $level
     */
    public function getCategoryByCondit($category_id = 0, $level = 1){
    	$result =  $this->dbConnection->createCommand()
    			->select('category_id,name')
    			->from(self::tableName())
    			->where('parent_category_id = "'.$category_id.'"')
    			->andWhere("level = '".$level."'")
    			->queryAll();
    	$category_arr = array();
    	if(!empty($result))
    	{
    		foreach($result as $key=>$value)
    		{
    			$category_arr[$value['category_id']] = $value['name'];
    		}
    	}
    	return $category_arr;
    }
    
    /**
     * @desc 获取分类的面包屑导航(包含中英文)
     * @param int $id
     */
    public function getBreadcrumbCnAndEn($id, $separate = '->'){
    	$info = $this->getCategotyInfoByID($id);
    	$createLevel = $info['create_level'];
    	$categoryTree = array();
    	$categoryTree[$createLevel] = $info['en_name'].'('.$info['cn_name'].')';
    	for($i = $createLevel-1; $i > 0; $i--){
    		$info = $this->getCategotyInfoByID($info['parent_category_id']);
    		$categoryTree[$i] = $info['en_name'].'('.$info['cn_name'].')';
    	}
    	ksort($categoryTree);
    	return implode($separate, $categoryTree);
    }
    
    /**
     * @desc 关键字匹配分类ID
     * @param string $keyWords
     */
    public function getCategoryIDByKeyWords($keyWords){
    	$excludeWords = array('for','to','and','or');
    	$ableCategory = array();
    	foreach($keyWords as $word){
    		if( in_array($word, $excludeWords) || strlen($word) <= 2 || strpos($word, '"')!==false || strpos($word, "'")!==false ){
    			continue;
    		}
    		$categories = $this->dbConnection->createCommand()->select('category_id')->from(self::tableName())->where('category_name LIKE "%'.$word.'%"')->queryColumn();
    		foreach($categories as $categoryId){
    			if(!empty($categoryId)) $categoryData = array('keyword' => $word , 'categoryId' => $categoryId);break;
    		}
    	}
    	return $categoryData;
    }
    
    /**
     * @desc 根据分类ID获取分类信息
     * @param unknown $ids
     * @return Ambigous <multitype:, mixed, CActiveRecord, NULL, multitype:unknown Ambigous <CActiveRecord, NULL> , multitype:unknown >
     */
    public function getCategoryByIds($ids) {
    	return $this->findAll("category_id in (" . implode(',', $ids) . ")");
    }
    
    /**
     * @desc 查找子分类
     * @param unknown $categoryID
     * @return Ambigous <multitype:, mixed, CActiveRecord, NULL, multitype:unknown Ambigous <CActiveRecord, NULL> , multitype:unknown >
     */
    public function getSubCategory($categoryID) {
    	return $this->findAll("parent_category_id = :category_id", array(':category_id' => $categoryID));
    }
    
    
    /**
     * @desc 根据关键词获取建议分类
     * @return boolean|multitype:number multitype:Ambigous <multitype:multitype:unknown, multitype:multitype:unknown  multitype:unknown unknown mixed  >  |multitype:number
     */
    public function getSuggestCategoryByKeyWord(){
    	if(empty($this->_keyword)) return false;

    	$accountID = $this->_accountID;
    	if(!$accountID){
    		$account = AliexpressAccount::getAbleAccountByOne();
    		$accountID = $account['id'];
    	}
    	$request = new GetCategorySuggestRequest();
    	$request->setKeyword($this->_keyword);
    	$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
    	 
    	if( $request->getIfSuccess() ){
    		$categoryList = array();
    		$categoryIds = $response->cateogryIds;
    		foreach($categoryIds as $key => $categoryId){
    			$categoryList[$key] = $this->getCategoryTreeByCategoryId($categoryId);
    		}
    		return $categoryList;
    	}else{
    		$this->setExceptionMessage($request->getErrorMsg());
    		return false;
    	}
    	
    }
    
    /**
     * @desc 通过分类ID获取分类树信息
     * @param unknown $id
     * @return multitype:multitype:unknown  multitype:unknown mixed
     */
    private function getCategoryTreeByCategoryId($id){
    	$info = $this->getCategotyInfoByID($id);
    	$createLevel = $info['create_level'];
    	$categoryTree = array();
    	$categoryTree[$createLevel] = array('category_id'=>$id, 'en_name'=>$info['en_name'], 'cn_name'=>$info['cn_name']);
    	for($i = $createLevel-1; $i > 0; $i--){
    		$topCategoryID = $info['parent_category_id'];
    		$info = $this->getCategotyInfoByID($info['parent_category_id']);
    		$categoryTree[$i] = array('category_id'=>$topCategoryID, 'en_name'=>$info['en_name'], 'cn_name'=>$info['cn_name']);
    	}
    	ksort($categoryTree);
    	return $categoryTree;
    }


    /**
     * @desc 获取产品分类的目录
     */
    public function getCategoryDataByparentCategoryId($parentCategoryId){
        $categoryData = array();
        $categoryList = $this->getCategoriesByParentID($parentCategoryId);
        if($categoryList){
            foreach ($categoryList as $key => $value) {
                $categoryData[] = array('id'=>$value['category_id'], 'name'=>$value['en_name'].'('.$value['cn_name'].')');
            }
        }

        return $categoryData;
    }


    /**
     * @desc 关键字匹配分类ID和分类名-导航
     * @param string $keyWords
     */
    public function getCategoryIDAndCategoryNameByKeyWords($word){
        $excludeWords = array('for','to','and','or');
        if( in_array($word, $excludeWords) || strlen($word) <= 2 || strpos($word, '"')!==false || strpos($word, "'")!==false ){
            return false;
        }

        $categoriesArr   = array();
        $categoriesIDArr = array();
        $totalArr        = array();
        $where           = '';
        $aliProductModel = new AliexpressProduct(); 
        $byProductCateArr = array();

        $categories = $this->dbConnection->createCommand()->select('category_id')->from(self::tableName())->where('category_name LIKE "%'.$word.'%"')->queryAll();
        if(!$categories){

            //搜索产品表里的标题
            $productWhere = ' subject LIKE "%'.$word.'%"';
            $productInfo = $aliProductModel->getListByCondition('category_id',$productWhere);
            if($productInfo){
                foreach ($productInfo as $proVal) {
                    $byProductCateArr[] = $proVal['category_id'];
                }
            }

            $wordArr = explode(' ', $word);
            foreach ($wordArr as $k => $v) {
                $where = ' en_name LIKE "%'.$v.'%" AND is_leaf = 1';
                $categories = $this->dbConnection->createCommand()->select('category_id')->from(self::tableName())->where($where)->queryAll();
                if($categories){
                    foreach ($categories as $catVal) {
                        $categoriesIDArr[] = $catVal['category_id'];
                    }
                }
            }
            
            $categoriesIDArr = array_unique($categoriesIDArr);
            krsort($categoriesIDArr);

            //合并数组
            $totalArr = array_unique(array_merge($byProductCateArr,$categoriesIDArr));

            foreach ($totalArr as $value) {
                $categoryName = $this->getBreadcrumbCnAndEn($value);
                $categoriesArr[] = array('category_id'=>$value, 'category_name'=>$categoryName);
            }
        }else{
            foreach ($categories as $key => $value) {
                $categoryName = $this->getBreadcrumbCnAndEn($value['category_id']);
                $categoriesArr[] = array('category_id'=>$value['category_id'], 'category_name'=>$categoryName);
            }
        }

        return $categoriesArr;
    }


    /**
     * @desc 获取菜单对应ID
     * @return integer
     */
    public static function getIndexNavTabId() {
        return UebModel::model('Menu')->getIdByUrl('/aliexpress/aliexpresscategory/index');
    }


    /**
     * @desc 获取指定分类的二级分类
     * @param int $id
     */
    public function getTwoCategory($id){
        $info = $this->getCategotyInfoByID($id);
        $level = $info['level'];
        for($i = $level-1; $i > 0; $i--){
            if($i == 1){
                break;
            }
            $info = $this->getCategotyInfoByID($info['parent_category_id']);
        }
        return $info['category_id'];
    }


    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return [type]         [description]
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='',$group='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $group != '' && $cmd->group($group);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }
}