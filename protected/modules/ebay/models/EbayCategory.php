<?php
/**
 * @desc Ebay分类管理
 * @author Gordon
 * @since 2015-07-25
 */
class EbayCategory extends EbayModel{
    
    /**@var 事件名称*/
    const EVENT_NAME = 'get_category';
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var int 站点ID*/
    public $_siteID = 0;
    
    /** @var string 异常信息*/
    public $_exception = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_category';
    }

    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
        $this->_accountID = $accountID;
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->_exception = $message;
    }
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
    	return $this->_exception;
    }
    /**
     * @desc 设置站点ID
     */
    public function setSite($site){
        $this->_siteID = $site;
    }
    
    /**
     * @desc 获取分类
     */
    public function getCategories(){
        $accountID = $this->_accountID;
        $siteID = $this->_siteID;
        $request = new GetCategoriesRequest();
        $request->setSiteID($siteID);
        $response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
        if( $request->getIfSuccess() ){//交互成功
            //保存分类信息
            foreach( $response->CategoryArray->Category as $category ){
            	$tempID = $siteID;
            	$realSiteID = $siteID;
            	if($siteID == EbaySite::EBAY_MOTOR_SITEID){//eBay Motors
            		$tempID = 0;
            	}
                $params = array(
                    'site_id' 		=> $tempID,
                    'real_site_id' 	=> $realSiteID,
                    'category_id' 	=> $category->CategoryID,
                    'parent_id' 	=> $category->CategoryParentID,
                    'category_name' => $category->CategoryName,
                    'level' 		=> $category->CategoryLevel,
                    'auto_pay' 		=> $category->AutoPayEnabled == 'true' ? 1 : 0,
                    'best_offer' 	=> $category->BestOfferEnabled == 'true' ? 1 : 0,
                    'timestamp'     => date('Y-m-d H:i:s'),
                );
                $res = $this->saveCategoryRecord($params);
            }
        }else{
            $this->setExceptionMessage($request->getErrorMsg());
            echo "siteID: {$siteID},accountID:{$accountID}<br/>";
            echo $request->getErrorMsg(), "<br/>";
            return false;
        }
        return true;
    }
    
    /**
     * @desc 保存数据
     * @param array $params
     */
    public function saveCategoryRecord($params){
        return $this->dbConnection->createCommand()->replace(self::tableName(), $params);
    }
    
    /**
     * @desc 根据站点ID获取分类
     * @param tinyInt $siteID
     */
    public function getCategoriesBySiteID($siteID, $parentId=''){
        $where = '';
        if(!$parentId){
            $where = 'level = 1';
        }else{
            $where = 'parent_id = '.$parentId;
        }
        $list = $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('site_id = '.$siteID)
                ->andWhere($where)
                ->queryAll();
        $categories = array();
        foreach($list as $item){
            $categories[$item['category_id']] = $item;
        }
        return $categories;
    }
    
    /**
     * @desc 根据SKU和站点ID获取历史分类
     * @param string $sku
     * @param tinyint $siteID
     */
    public function getHistoryCategoriesBySiteID($sku,$siteID){
        $categoryIDs = array();
        $includeVariation = true;//包含查询子sku
        $listings = EbayProduct::model()->getListingBySku($sku,$siteID,$includeVariation);
        foreach($listings as $item){
            if( !isset($categoryIDs[$item['category_id']]) ){
                $categoryIDs[$item['category_id']] = $this->getBreadcrumbCategory($item['category_id'], $siteID);
            }
        }
        return $categoryIDs;
    }
    
    /**
     * @desc 获取分类的面包屑导航
     * @param int $id
     */
    public function getBreadcrumbCategory($id, $siteID){
        $info = $this->getCategotyInfoByID($id, $siteID);
        $level = $info['level'];
        $categoryTree = array();
        $categoryTree[$level] = $info['category_name'];
        for($i = $level-1; $i > 0; $i--){
            $info = $this->getCategotyInfoByID($info['parent_id'], $siteID);
            $categoryTree[$i] = $info['category_name'];
        }
        ksort($categoryTree);
        return implode('->', $categoryTree);
    }
    
    /**
     * @desc 获取分类树
     * @param unknown $id
     * @param unknown $siteID
     * @return multitype:unknown mixed
     */
    public function getCategoryTreeByCategoryId($id, $siteID){
    	$info = $this->getCategotyInfoByID($id, $siteID);
    	$level = $info['level'];
    	$categoryTree = array();
    	$categoryTree[$info['category_id']] = $info;
    	for($i = $level-1; $i > 0; $i--){
    		$info = $this->getCategotyInfoByID($info['parent_id'], $siteID);
    		$categoryTree[$info['parent_id']] = $info;
    	}
    	return $categoryTree;
    }
    /**
     * @desc 获取分类信息
     * @param int $id
     * @param tinyint $siteID
     */
    public function getCategotyInfoByID($id, $siteID){
        return $this->dbConnection->createCommand()
                    ->select('*')
                    ->from(self::tableName())
                    ->where('category_id = "'.$id.'"')
                    ->andWhere('site_id = '.$siteID)
                    ->queryRow();
    }
    
    /**
     * @desc 获取推荐分类列表
     * @param unknown $keyword
     * @return boolean
     */
    public function getSuggestCategoryList($accountID, $siteID, $keyword, $sku = ""){
    	if(empty($keyword)) return false;
    	$suggestCategoryModel = new EbaySkuSuggestCategory();
    	if($sku){
    		$res = $suggestCategoryModel->getSuggestCategoryBySkuAndSite($sku, $siteID);
    		if($res){
    			return json_decode($res['suggest_category'], true);
    		}
    	}
    	
    	$request = new GetSuggestedCategoriesRequest();
    	$request->setAccount($accountID);
    	$request->setSiteID($siteID);
    	$request->setQuery($keyword);
    	$response = $request->setRequest()->sendRequest()->getResponse();
    	if($request->getIfSuccess()){
    		$categories = array();
    		foreach ($response->SuggestedCategoryArray->SuggestedCategory as $suggestedCategory){
    			$categoryid = trim($suggestedCategory->Category->CategoryID);
    			$categorynames = array();
    			foreach ($suggestedCategory->Category->CategoryParentName as $categoryParentName){
    				$categorynames[] = trim($categoryParentName);
    			}
    			$categorynames[] = trim($suggestedCategory->Category->CategoryName);
    			if(strpos(implode(' -> ',$categorynames),'Fine Jewellery') || strpos(implode(' -> ',$categorynames),'Fine Jewelry')){
    				continue;
    			}
    			$categories[$categoryid] = array(
    					'categoryid' => $categoryid,
    					'categoryname' => implode(' -> ',$categorynames),
    			);
    		}
    		//保存
    		if($sku){
    			$suggestCategoryModel->saveSuggestCategory(array(
    															'sku'		=>	$sku,
    															'site_id'	=>	$siteID,
    															'suggest_category'	=>	json_encode($categories),
    															'last_time'		=>	date("Y-m-d")
    													));
    		}
    		return $categories;
    	}
    	$this->setExceptionMessage(Yii::t("ebay", "Not find the category, No result return!"));
    	return false;
    }
    
    /**
     * @desc 检测对应的分类是否在指定类目下
     * @param unknown $siteID
     * @param unknown $categoryId
     * @param unknown $parentCategoryId
     * @return boolean
     */
    public function checkCategoryWithInParentCategory($siteID, $categoryId, $parentCategoryId){
    	$categoryTree = $this->getCategoryTreeByCategoryId($categoryId, $siteID);
    	if(empty($categoryTree)) return false;
    	$flag = false;
    	foreach ($categoryTree as $cateID=>$cate){
    		if($parentCategoryId == $cateID){
    			$flag = true;
    			break;
    		}
    	}
    	return $flag;
    }
    /**
     * @desc 根据ID获取分类名称
     * @param unknown $categoryID
     */
    public function getCategoryNameByID($categoryID, $siteID = ''){
    	$command = $this->getDbConnection()->createCommand()->from($this->tableName())->where("category_id=:cateid", array(':cateid'=>$categoryID))->select('category_name');
    	if($siteID !== '') $command->andWhere("site_id=:site_id", array(":site_id"=>$siteID));
    	$categoryInfo = $command->queryRow();
    	return isset($categoryInfo['category_name']) ? $categoryInfo['category_name'] : false;
    }
    /**
     * @desc  获取分类站点ID
     * @param unknown $siteID
     * @param unknown $categoryID
     * @return Ambigous <string, mixed, unknown>
     */
    public function getCategorySiteId($siteID, $categoryID){
    	return $this->dbConnection->createCommand()
    				->select('real_site_id')
    				->from(self::tableName())
    				->where('category_id = "'.$categoryID.'"')
    				->andWhere('site_id = '.$siteID)
    				->queryScalar();
    }

    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  string $order 
     * @return array        
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }     

    /**
     * getListByCondition
     * @param  string $fields
     * @param  string $where 
     * @param  string $order 
     * @return array      
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }      
}