<?php
/**
 * @desc Ebay刊登价格配置
 * @author lihy
 * @since 2016-03-28
 */
class EbayStoreCategory extends EbayModel{
	private $_errorMsg = "";
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_store_category';
    }
    
    /**
     * @desc 更新店铺分类
     * @param unknown $accountID
     * @return boolean
     */
    public function updateStoreCategory($accountID){
    	$request = new GetStoreRequest();
    	$request->setAccount($accountID);
    	$response = $request->setRequest()->sendRequest()->getResponse();
    	if($request->getIfSuccess()){
    		$this->getDbConnection()->createCommand()->delete($this->tableName(), "account_id=:account_id", array(":account_id"=>$accountID));
    		foreach ($response->Store->CustomCategories->CustomCategory as $category){
    			$data = array(
    					'account_id' => $accountID,
    					'category_id' => $category->CategoryID,
    					'parent_id' => $category->CategoryID,
    					'category_name' => $category->Name,
    					'category_order' => $category->Order,
    					'level' => 1,
    					'update_time' => date("Y-m-d H:i:s"),
    			);
    			$this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    			foreach ($category->ChildCategory as $childcategory){
    				$data = array(
    						'account_id' => $accountID,
    						'category_id' => $childcategory->CategoryID,
    						'parent_id' => $category->CategoryID,
    						'category_name' => $childcategory->Name,
    						'category_order' => $childcategory->Order,
    						'level' => 2,
    						'update_time' => date("Y-m-d H:i:s"),
    				);
    				$this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    			}
    		}
    		return true;
    	}else{
  			$this->setErrorMsg($request->getErrorMsg());
    		return false;
    	}
    }

    /**
     * @desc 获取店铺分类ID
     * @param unknown $accountID
     * @param string $parentID
     * @param number $level
     * @param string $recursive
     * @return Ambigous <multitype:, multitype:unknown >
     */
    public function getCategories($accountID, $parentID = '', $level = 1, $recursive = true){
    	$conditions = "account_id='$accountID' AND level='$level' ";
    	$parentID && $conditions .= " AND parent_id='$parentID'";
    	$i = 0;
    	do{
    		$list = $this->getDbConnection()->createCommand()
    							->from($this->tableName())
    							->where($conditions)
    							->order("category_order asc")
    							->queryAll();
    		$reget = false;
    		if(!$list && $level == 1){
    			$reget = $this->updateStoreCategory($accountID);
    		}
    		$i++;
    		if($i>=2) break;
    	}while ($reget && $i<2 && $level == 1);
    	$level++;
    	$collection = array();
    	foreach ($list as $key=>$info){
    		$collection[$info['category_id']] = $info;
    		if($recursive){
    			$children = $this->getCategories($accountID, $info['category_id'], $level);
    			if($children){
    				$collection = array_merge($collection, $children);
    			}
    		}
    	}
    	return $collection;
    }

    /**
     * @获取店铺分类数
     * @param unknown $accountID
     * @return Ambigous <Ambigous, multitype:, multitype:unknown >
     */
    public function getCategoryTree($accountID){
    	$topList = $this->getCategories($accountID, 0, 1, false);
    	if($topList){
    		foreach ($topList as &$list){
    			$list['subcategory'] = $this->getCategories($accountID, $list['category_id'], $list['level']+1, false);
    		}
    	}
    	return $topList;
    }
    /**
     * @desc 根据ID获取分类名称
     * @param int $categoryID
     * @param int $accountID
     */
    public function getCategoryNameByID($categoryID, $accountID){
        $command = $this->getDbConnection()->createCommand()
                ->from($this->tableName())
                ->where("category_id=:cateid", array(':cateid'=>$categoryID))
                ->andWhere("account_id=:account_id", array(":account_id"=>$accountID))
                ->select('category_name');
        $categoryInfo = $command->queryRow();
        return isset($categoryInfo['category_name']) ? $categoryInfo['category_name'] : false;
    }    
    
    public function setErrorMsg($errorMsg){
    	$this->_errorMsg = $errorMsg;
    }
    
    public function getErrorMsg(){
    	return $this->_errorMsg;
    }
}