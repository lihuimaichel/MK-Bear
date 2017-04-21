<?php
/**
 * @desc Lazada分类
 * @author Gordon
 * @since 2015-08-13
 */
class LazadaCategory extends LazadaModel{
    
    const EVENT_NAME = 'get_categories';
    
    /** @var integer 站点ID **/
    protected $_siteID = null;
    
    /** @var integer 账号ID **/
    protected $_accountID = null;
    
    /** @var string 异常信息*/
    public $exception = null;

    /** @var integer lazada账号表ID字段 **/
    protected $_apiAccountID = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
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
        return 'ueb_lazada_category';
    }
    
    /**
     * @desc 设置站点ID
     * @param unknown $siteID
     */
    public function setSiteID($siteID) {
    	$this->_siteID = $siteID;
    }
    
    /**
     * @desc 设置账号ID
     * @param unknown $accountID
     */
    public function setAccountID($accountID) {
    	$this->_accountID = $accountID;
    }

    /**
     * @desc 设置lazada账号表ID字段
     * @param unknown $apiAccountID
     */
    public function setApiAccount($apiAccountID) {
        $this->_apiAccountID = $apiAccountID;
    }
    
    /**
     * @desc 更新分类
     */
    public function updateCategories(){
        $request = new GetCategoryTreeRequest();
        $accountID = Yii::app()->request->getParam('account_id');
        if(!$accountID){ 
            $account = LazadaAccount::getAbleAccountByOne();
            $accountID = $account['id'];
        }
        $response = $request->setSiteID($this->_siteID)->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();

        //MHelper::writefilelog('lazada/updateCategories/'.date("Ymd").'/response.txt', print_r($response,true)."\r\n");//add log for test

        if( $request->getIfSuccess() ){
            $categories = $response->Body->Categories->Category;
            //删除旧的分类
            $this->deleteAll();
            foreach($categories as $category){
                $this->saveCategory($category);
            }
            return true;
        }else{
            $this->setExceptionMessage($request->getErrorMsg());
            return false;
        }
    }
    
    /**
     * @desc 更新分类信息
     */
    public function saveRecord($params){
        return $this->dbConnection->createCommand()->replace(self::tableName(), $params);
    }
    
    public function saveCategory($category,$level = 1,$parantID = 0){
        if( isset($category->CategoryId) ){
            $params = array(
                    'category_id'       => intval($category->CategoryId),
                    'name'              => trim(addslashes($category->Name)),
                    'parent_category_id'=> $parantID,
                    'level'             => $level,
                    'timestamp'         => date('Y-m-d H:i:s'),
            );
            $this->saveRecord($params);
            if( isset($category->Children->Category) && !empty($category->Children->Category) ){
                foreach($category->Children->Category as $children){
                    $this->saveCategory($children, $level+1, $category->CategoryId);
                }
            }else{
                $updateArr = array(
                        'category_name'     => $this->getBreadcrumbCategory($category->CategoryId),
                );
                $this->dbConnection->createCommand()->update(self::tableName(), $updateArr, 'category_id = '.$category->CategoryId);
            }
        }
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
        $level = $info['level'];
        $categoryTree = array();
        $categoryTree[$level] = $info['name'];
        for($i = $level-1; $i > 0; $i--){
            $info = $this->getCategotyInfoByID($info['parent_category_id']);
            $categoryTree[$i] = $info['name'];
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
     * @param tinyint $siteID
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
            foreach($categories as $categoryID){
                $ableCategory[$categoryID]++;
            }
        }
        arsort($ableCategory);
        return array_shift(array_keys($ableCategory));
    }


    /**
     * @desc 新接口更新分类
     * @since 2016-12-16
     * @author hanxy
     */
    public function updateCategoriesNew(){
        $request = new GetCategoryTreeRequestNew();
        $response = $request->setApiAccount($this->_apiAccountID)->setRequest()->sendRequest()->getResponse();
        if( $request->getIfSuccess() ){
            $categories = $response->Body->Category;
            //删除旧的分类
            $this->deleteAll();
            foreach($categories as $category){
                $this->saveCategoryNew($category);
            }
            return true;
        }else{
            $this->setExceptionMessage($request->getErrorMsg());
            return false;
        }
    }


    /**
     * @desc 新接口保存分类
     * @since 2016-12-16
     * @author hanxy
     */
    public function saveCategoryNew($category,$level = 1,$parantID = 0){
        if(isset($category->categoryId)){
            $params = array(
                'category_id'       => intval($category->categoryId),
                'name'              => trim(addslashes($category->name)),
                'parent_category_id'=> $parantID,
                'level'             => $level,
                'timestamp'         => date('Y-m-d H:i:s')
            );
            $this->saveRecord($params);
            if( isset($category->children->Category) && !empty($category->children->Category) ){
                foreach($category->children->Category as $children){
                    $this->saveCategoryNew($children, $level+1, $category->categoryId);
                }
            }else{
                $updateArr = array('category_name' => $this->getBreadcrumbCategory($category->categoryId));
                $this->dbConnection->createCommand()->update(self::tableName(), $updateArr, 'category_id = '.$category->categoryId);
            }
        }
    }


    /**
     * @desc 获取指定分类的最小分类
     * @param int $parentCategoryID
     */
    public function getMinimumCategory($parentCategoryID){
        $data = array();
        $info = $this->getCategotyInfoByID($parentCategoryID);
        $names = $info['name'];
        $minimumCategoryInfo = $this->getListByCondition('category_id', "category_name LIKE '".$names."%'");
        foreach ($minimumCategoryInfo as $value) {
            $data[] = $value['category_id'];
        }
        return $data;
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