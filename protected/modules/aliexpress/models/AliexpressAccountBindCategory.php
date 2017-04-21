<?php
/**
 * @desc Aliexpress用户绑定分类数据
 * @author AjunLongLive!
 * @since 2017-03-01
 */
class AliexpressAccountBindCategory extends AliexpressModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_account_category';
    }
  
    
    /**
     * @desc 属性翻译
     */
    public function attributeLabels() {
    	return null;
    }
    
    
	/**
	 * @return array search filter (name=>label)
	 */
	public function filterOptions() {
		$tmpStatus = isset($_REQUEST['status']) ? $_REQUEST['status'] : "";
		if( $tmpStatus === '' ){
			$tmpStatus = '';
		}else if( $tmpStatus === '0' ){
			$tmpStatus = self::STATUS_SHUTDOWN;
		}else if( trim($tmpStatus) === '1'){
			$tmpStatus = self::STATUS_OPEN;
		}
		$result = array(
				array(
						'name'     		 => 'short_name',
						'type'     		 => 'text',
						'search'   		 => 'LIKE',
						'alias'    		 => 't',
				),
				array(
					'name'          => 'status',
					'type'          => 'dropDownList',
					'search'        => '=',
					'data'          => $this->getStatus(),
					'htmlOptions'   => array(),
					'value'			=> $tmpStatus,
       				'alias'			=> 't'
				)
		);
	
		return $result;
	
	}
    /**
     * search SQL
     * @return $array
     */
    protected function _setCDbCriteria() {

    	return NULL;
    }
    /**
     * @return $array
     */
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'  => 'short_name',
    			'defaultDirection'	=>	'ASC'
    	);
    	$criteria = null;
    	$criteria = $this->_setCDbCriteria();
    	$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);
    
    	$data = $this->addition($dataProvider->data);
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    
    /**
     * @return $array
     */
    public function addition($data){
    	foreach ($data as $key => $val){
    		$data[$key]->num 	  = $val['id'];
    	}
    	return $data;
    }
    
    /**
     * @desc 获取用户绑定的各级分类名称列表
     * @param int $accountID  用户id  
     * @param int $level      绑定的分类级
     * @return string  返回绑定的分类名称文本
     */
    public function getBindCategoryList($accountID,$level,$type = 'normal'){
            $result = $this->dbConnection->createCommand()
                            ->select('*')
                            ->from($this->tableName())
                            ->where("account_id = $accountID")
                            ->queryRow();
        
        if (!$result){
            return false;
        } else {
            switch($level){
                case 1:
                    $eachStyle = "\n";
                    $type == 'account_list' && $eachStyle = "<br />";
                    $categoryIds = $result['first_category'];
                    if (!empty($categoryIds)){
                        $categoryIdsArr = explode(',',$categoryIds);                        
                        if (count($categoryIdsArr) > 0){
                            $categoryNames = '';
                            foreach ($categoryIdsArr as $categoryId){
                                if (!empty($categoryId)){
                                    $categoryInfo = AliexpressCategory::model()->getCategotyInfoByID($categoryId);
                                    if ($categoryInfo) $categoryNames .= "{$categoryInfo['en_name']}({$categoryInfo['cn_name']})$eachStyle";
                                }
                            }
                            if ($type == 'account_list'){
                                echo $categoryNames;
                            } else {
                                return $categoryNames;
                            }                            
                        } else {
                            return false;
                        }                       
                    } else {
                        return false;
                    }
                    break;
                case 2:
                    $categoryIds = $result['second_category'];
                    if (!empty($categoryIds)){
                        $categoryIdsArr = explode(',', $categoryIds);
                        if (count($categoryIdsArr) > 0){
                            $categoryNames = '';
                            foreach ($categoryIdsArr as $categoryId){
                                $categoryInfo = AliexpressCategory::model()->getCategotyInfoByID($categoryId);
                                if ($categoryInfo) $categoryNames .= "{$categoryInfo['en_name']}({$categoryInfo['cn_name']})\n";
                            }
                            return $categoryNames;
                        } else {
                            return false;
                        }
                         
                    } else {
                        return false;
                    }
                    break;
                case 3:
                    $categoryIdsThird  = $result['third_category'];
                    $categoryIdsSecond = $result['second_category'];
                    $all_third_categorys_explode = '';
                    $all_third_categorys_Arr = array();
                    if (!empty($categoryIdsThird)){
                        $all_third_categorys_Arr = explode(',', $categoryIdsThird);
                    }
                    if (!empty($categoryIdsSecond)){
                        $categoryIdsArr = explode(',', $categoryIdsSecond);
                        if (count($categoryIdsArr) > 0){
                            $categoryNames = '';
                            //print_r($categoryIdsArr);
                            foreach ($categoryIdsArr as $categoryId){
                                if ($categoryId != ''){
                                    $all_third_categorys = AliexpressCategory::model()->getSubCategory($categoryId);
                                    //print_r($all_third_categorys);
                                    $allSelectStatus = true;
                                    if ($all_third_categorys){
                                        if (!empty($categoryIdsThird)){
                                            foreach ($all_third_categorys as $subCategoryId){
                                                if (in_array($subCategoryId['category_id'], $all_third_categorys_Arr)) {
                                                    $allSelectStatus = false;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if($allSelectStatus){
                                        foreach ($all_third_categorys as $subThirdCategoryInfo){
                                            $all_third_categorys_explode .= $subThirdCategoryInfo['category_id'].',';
                                        }
                                    }
                                }                                
                            }
                            $all_third_categorys_explode .= $categoryIdsThird;
                            $categoryIdsThirdArr = explode(',', $all_third_categorys_explode);
                            //print_r($categoryIdsThirdArr);
                            if (count($categoryIdsThirdArr) > 0){
                                $categoryNames = '';
                                foreach ($categoryIdsThirdArr as $categoryId){
                                    $categoryInfo = AliexpressCategory::model()->getCategotyInfoByID($categoryId);
                                    if ($categoryInfo) $categoryNames .= "{$categoryInfo['en_name']}({$categoryInfo['cn_name']})\n";
                                }
                                return $categoryNames;
                            } else {
                                return false;
                            }
                        } else {
                            return false;
                        }
                         
                    } else {
                        return false;
                    }
                    break;
                default:
                    return false;
            } 
            
        }
    }
    
    /**
     * @desc 确认某个分类是否是被所给定的用户们全部绑定,同时返回没有绑定的用户列表
     * @param int   $checkCategoryID 需要确认的分类ID 
     * @param Array $checkUsers      需要确认的用户列表
     * @return json  成功返回:{'status'=>'success','msg'=>''}，失败返回:{'status'=>'failure','msg'=>'未绑定的用户数组'}
     */
    public function checkCategoryBindStatusfromUsers($checkCategoryID,$checkUsersID){
        $return = array('status'=>'success','msg'=>'');
        $secondCheckCategoryID = 0;
        $thirdCheckCategoryID = 0;
        $categoryInfo = AliexpressCategory::model()->getCategotyInfoByID($checkCategoryID);
        if ($categoryInfo && !empty($checkUsersID)){
            $level = $categoryInfo['level'];
            $return['msg']['categoryLevel'] = $level;
            $return['msg']['categoryName'] = $categoryInfo['cn_name'] . '(' . $categoryInfo['en_name'] . ')';
            switch($level){
                case 1:                    
                    foreach ($checkUsersID as $userID){
                        $eachUserBindCategoryInfo = $this->getUserBindCategoryByUserID($userID);
                        if ($eachUserBindCategoryInfo){                            
                            $first_category = $eachUserBindCategoryInfo['first_category'];
                            $first_category_arr = explode(',',$first_category);
                            if (!in_array($checkCategoryID, $first_category_arr)){
                                $accontInfo  = AliexpressAccount::model()->getAccountInfoByAccountID($userID);
                                if ($accontInfo){
                                    $return['status'] = 'failure';
                                    $return['msg']['unbind'][$userID] = $accontInfo['short_name'];
                                }
                            }
                        }                      
                    }
                    break;
                case 2:
                    foreach ($checkUsersID as $userID){
                        $eachUserBindCategoryInfo = $this->getUserBindCategoryByUserID($userID);
                        if ($eachUserBindCategoryInfo){
                            $second_category = $eachUserBindCategoryInfo['second_category'];
                            if ($second_category != ''){
                                $second_category_arr = explode(',', $second_category);
                                if (!in_array($checkCategoryID, $second_category_arr)){
                                    $accontInfo  = AliexpressAccount::model()->getAccountInfoByAccountID($userID);
                                    if ($accontInfo){
                                        $return['status'] = 'failure';
                                        $return['msg']['unbind'][$userID] = $accontInfo['short_name'];
                                    }
                                }
                            }
                        }
                    }                    
                    break;
                case 3:
                    $secondCheckCategoryID = $categoryInfo['parent_category_id'];
                    $thirdCheckCategoryID = $checkCategoryID;
                    foreach ($checkUsersID as $userID){
                        $eachUserBindCategoryInfo = $this->getUserBindCategoryByUserID($userID);
                        if ($eachUserBindCategoryInfo){
                            $second_category = $eachUserBindCategoryInfo['second_category'];
                            if ($second_category != ''){
                                $second_category_arr = explode(',', $second_category);
                                if (!in_array($secondCheckCategoryID, $second_category_arr)){
                                    $accontInfo  = AliexpressAccount::model()->getAccountInfoByAccountID($userID);
                                    if ($accontInfo){
                                        $return['status'] = 'failure';
                                        $return['msg']['unbind'][$userID] = $accontInfo['short_name'];
                                    }
                                } else {
                                    $third_category = $eachUserBindCategoryInfo['third_category'];
                                    $all_third_categorys = AliexpressCategory::model()->getSubCategory($secondCheckCategoryID);
                                    if ($third_category != ''){
                                        $third_category_arr = explode(',', $third_category);
                                        $thirdAllSelectCheckStatus = true;
                                        foreach ($all_third_categorys as $third_category_temp){
                                            if (in_array($third_category_temp['category_id'], $third_category_arr)) {
                                                $thirdAllSelectCheckStatus = false;
                                                break;
                                            }
                                        }
                                        if (!$thirdAllSelectCheckStatus){
                                            if (!in_array($checkCategoryID, $third_category_arr)){
                                                $accontInfo  = AliexpressAccount::model()->getAccountInfoByAccountID($userID);
                                                $return['status'] = 'failure';
                                                $return['msg']['unbind'][$userID] = $accontInfo['short_name'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }                    
                    break;
                case 4:  
                    $thirdCheckCategoryID = $categoryInfo['parent_category_id'];
                    $thirdCheckCategoryInfo = AliexpressCategory::model()->getCategotyInfoByID($thirdCheckCategoryID);
                    $secondCheckCategoryID = $thirdCheckCategoryInfo['parent_category_id'];
                    foreach ($checkUsersID as $userID){
                        $eachUserBindCategoryInfo = $this->getUserBindCategoryByUserID($userID);
                        if ($eachUserBindCategoryInfo){
                            $second_category = $eachUserBindCategoryInfo['second_category'];
                            if ($second_category != ''){
                                $second_category_arr = explode(',', $second_category);
                                if (!in_array($secondCheckCategoryID, $second_category_arr)){
                                    $accontInfo  = AliexpressAccount::model()->getAccountInfoByAccountID($userID);
                                    if ($accontInfo){
                                        $return['status'] = 'failure';
                                        $return['msg']['unbind'][$userID] = $accontInfo['short_name'];
                                    }
                                } else {
                                    $third_category = $eachUserBindCategoryInfo['third_category'];
                                    $all_third_categorys = AliexpressCategory::model()->getSubCategory($secondCheckCategoryID);
                                    if ($third_category != ''){
                                        $third_category_arr = explode(',', $third_category);
                                        $thirdAllSelectCheckStatus = true;
                                        foreach ($all_third_categorys as $third_category_temp){
                                            if (in_array($third_category_temp['category_id'], $third_category_arr)) {
                                                $thirdAllSelectCheckStatus = false;
                                                break;
                                            }
                                        }
                                        if (!$thirdAllSelectCheckStatus){
                                            if (!in_array($thirdCheckCategoryID, $third_category_arr)){
                                                $accontInfo  = AliexpressAccount::model()->getAccountInfoByAccountID($userID);
                                                $return['status'] = 'failure';
                                                $return['msg']['unbind'][$userID] = $accontInfo['short_name'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }                    
                    break;
                default:
                    $return['status'] = 'failure';
                    foreach ($checkUsersID as $userID){
                        $accontInfo  = AliexpressAccount::model()->getAccountInfoByAccountID($userID);
                        if ($accontInfo){                                   
                            $return['msg']['unbind'][$userID] = $accontInfo['short_name'];
                        }
                    }
            }
        }
        return $return;
    }
    
    /**
     * @desc 更新用户绑定的分类数据
     * @param Array
     * @return boolean  成功返回true，失败返回false
     */
    public function updateUserBindCategory($updateUserID,$updateArray){
        $result = $this->dbConnection->createCommand()
                            ->update($this->tableName(), $updateArray,"account_id = $updateUserID");
    
        if ($result > 0){
            return true;
        } else {
            return false;
        }
    }    
    
    /**
     * @desc 插入用户绑定的分类数据
     * @param Array 
     * @return boolean  成功返回true，失败返回false
     */
    public function insertUserBindCategory($insertArray){
        $result = $this->dbConnection->createCommand()
                            ->insert($this->tableName(),$insertArray);
        
       if ($result > 0){
            return true;
        } else {
            return false;
        }
    }    

    /**
     * @desc 获取用户绑定的分类数据
     * @param integer $userID
     * @return array  成功返回array，失败返回false
     */
    public function getUserBindCategoryByUserID($userID) {
        $result = $this->dbConnection->createCommand()
                            ->select('*')
                            ->from($this->tableName())
                            ->where("account_id = $userID")
                            ->queryRow();
        
        if (!$result){
            return false;
        } else {
            return $result;
        }
    }    
    
    /**
     * @desc 查询是否已有数据
     * @param integer $userID
     * @return boolean  有数据返回true，没有数据返回false
     */
    public function checkHasInsertStatus($userID) {
        $result = $this->dbConnection->createCommand()
                            ->select('account_id')
                            ->from($this->tableName())
                            ->where("account_id = $userID")
                            ->queryRow();
        if (!$result){
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * @desc 组合网址url到navTab
     * @param array  $mergeParams url,title,params(array:key,value;),style(h:height,w:width) 
     * @return 不返回，输出内容
     */
    public function mergeUrl($mergeParams) {
        $divStyleStart = '<div style="width:100%; height:100%; text-align:center;">';
        $listStyle = array('h'=>'<br />','w'=>'&nbsp;&nbsp;');
        $urlTemp = '';
        foreach ($mergeParams as $mergeKey => $mergeEach){
            if (isset($mergeEach['target'])) $target = $mergeEach['target']; else $target = 'target="navTab"';
            $urlTemp .= '<a href="'.$mergeEach['url'];
            if (count($mergeEach['params']) > 0){
                foreach ($mergeEach['params'] as $key => $val){
                    $urlTemp .= "/{$key}/{$val}";
                }
            }
            $urlTemp .= '" '.$target.' id="ajun_666_'.$mergeKey.'" rel="Ajun666'.$mergeKey.'">' . $mergeEach['title'] . '</a>' . $listStyle[$mergeEach['style']];
        }        
        $divStyleEnd = '</div>';
        echo $divStyleStart . $urlTemp . $divStyleEnd;
    }

    /**
     * @desc 获取分表账号ID列表
     * @param integer $groupID
     * @return array
     */
    public function getMapAccountList($groupID=null) {
        $cmd = $this->dbConnection->createCommand()
                    ->select("a.id,m.group_id")
                    ->from($this->tableName().' as a')
                    ->leftJoin($this->mapTableName().' as m',"a.id=m.account_id")
                    ->where('a.status = '.self::STATUS_OPEN)
                    ->andWhere("a.is_lock <> " . self::STATUS_ISLOCK);
        if (!empty($groupID)) {
            $cmd->andWhere("m.group_id='{$groupID}'");
        }
        $res = $cmd->queryAll();
        $rtn = array();
        if (!empty($res)) {
            foreach ($res as $v) {
                $group_id = empty($v['group_id']) ? 0 : $v['group_id'];
                $rtn[$group_id][] = $v['id'];
            }
        }
        return isset($rtn[$groupID]) ? $rtn[$groupID] : $rtn;
    }

}