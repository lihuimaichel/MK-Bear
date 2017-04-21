<?php
class AmazonCategory extends AmazonModel {

	const REPROT_FOR_NUM    = 5;	//获取报告循环次数
	const SLEEP_TIME        = 5;	//休眠秒数
	const FOR_CONTINUE_FLAG = 'todo'; //循环标识

	const VARIATION_COLOR   = 'color_map';	//亚马逊多属性标识
	const VARIATION_SIZE    = 'size_map';	//亚马逊多属性标识
	const VARIATION_STYLE   = 'style_name';	//亚马逊多属性标识

	public $_errorMsg;

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * @desc 设置数据库名
	 * @return string
	 */
	public function getDbKey() {
		return 'db_amazon';
	}
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_amazon_category';
	}
	
	/**
	 * @desc 保存分类数据
	 * @param array $data
	 * @param int $type 如果$type=1，则是根分类列表入库
	 * @return boolean
	 */
	public function saveAmazonCategory($data,$type = 0) {
		
		if (!isset($data) || count($data) == 0) return false;

		foreach ($data as $cateKey => $item) {
			$dbTransaction = $this->getDbConnection()->getCurrentTransaction();
			if (empty($dbTransaction))
				$dbTransaction = $this->getDbConnection()->beginTransaction();
			try {
				$cateID = 0;	//分类自增ID
				$params = array();
				$path = array();
				$attribute_arr = array();  //分类关键字
				if (isset($item->browseNodeId)) $params['category_id'] = floor(floatval($item->browseNodeId));

				if (isset($item->browsePathById)) $params['category_path_id'] = trim(addslashes($item->browsePathById));
				if (isset($item->browsePathByName)) $params['category_path_name'] = trim(addslashes($item->browsePathByName));

				//判断是否根分类：browsePathById如果只有两组数据就认为其为根分类，如果大于两组则是子分类
				if ($params['category_path_id']) $path = explode(',',$params['category_path_id']);
				if ($path && (count($path) >= 2)){
					if (count($path) == 2){
						$params['parent_id'] = 0;	//根分类
					}else{
						//数据库查询获取父级信息
						$parentInfo = $this->getParentCategoryInfo($params['category_path_id']);
						if ($parentInfo){
							$params['parent_id'] = $parentInfo['id'];
						}
						unset($parentInfo);
					}
				}
				
				//子分类用browseNodeName，根分类用browsePathByName，因为根分类的browseNodeName是Departments或Categories表示，无意义分类名称
				if ($params['parent_id'] == 0){
					if (isset($item->browsePathByName)) $params['en_name'] = trim(addslashes($item->browsePathByName));
				}else{
					if (isset($item->browseNodeName)) $params['en_name'] = trim(addslashes($item->browseNodeName));
				}

				if ($item->hasChildren){
					$childNodes = (array)$item->childNodes;

					if (isset($childNodes['@attributes']['count'])) $params['child_category_count'] = $childNodes['@attributes']['count'];

					if (isset($childNodes['id'])){
						if (is_array($childNodes['id'])){
							$params['child_category_ids'] = trim(addslashes(implode(",",$childNodes['id'])));
						}else{
							$params['child_category_ids'] = trim(addslashes($childNodes['id']));
						}
					}
				}

				//获取分类关键字
				if (isset($item->browseNodeAttributes->attribute)) {			
					foreach($item->browseNodeAttributes->attribute as $v){
						//另方法不用转数组：(string)$xml->result->attributes()->name
						$v = (array)$v;
						$attribute_arr[] = $v['@attributes']['name']. ':' . $v[0];
					}
					$params['category_fields_required'] = implode("|",$attribute_arr);
				}
				if (isset($item->browseNodeStoreContextName)) $params['store_context_name'] = trim(addslashes($item->browseNodeStoreContextName));
				if (isset($item->productTypeDefinitions)) $params['feed_product_type'] = trim(addslashes($item->productTypeDefinitions));		

				$params['timestamp'] = date('Y-m-d H:i:s');	

				//分类主表
				//检查该分类ID是否已经存在（因为存在不同分类共用同个子分类的情况，所以要分类ID+路径判断是否存在（唯一））
				$amazonCategoryList = $this->find('category_id = :category_id AND category_path_id = :category_path_id',
					array(
					':category_id'      => $params['category_id'],
					':category_path_id' => $params['category_path_id'],
				));	 		

				if (!empty($amazonCategoryList)) {
					$cateID = $amazonCategoryList->id;
					//如果存在更新数据
					$res = $this->getDbConnection()->createCommand()->update(self::tableName(), 
														$params, 
														"id = :id", 
														array(':id' => $cateID)
													);
					if (!$res)
						throw new Exception(Yii::t('amazon_product', 'Upload Product1 Info Failure'));
				} else {
					$res = $this->getDbConnection()->createCommand()->insert(self::model()->tableName(), $params);
					if ($res){
						$cateID = $this->getDbConnection()->getLastInsertID();	//新插入的分类自增ID
					}else{
						throw new Exception(Yii::t('amazon_product', 'Save Product2 Info Failure'));
					}
				}			

				//分类多属性表

				//只在第一次处理多属性表前执行一次性清空数据
				//清空多属性表（ueb_amazon_category_attribute）和多属性值表（ueb_amazon_category_attribute_value）所有数据，直接删除后再新增，分类的多属性不考虑更新。
				// if ($cateKey == 0){
				// 	$attrDelSql = "truncate table " .AmazonCategoryAttribute::model()->tableName();
				// 	$this->getDbConnection()->createCommand($attrDelSql)->execute();

				// 	$attrValueDelSql = "truncate table " .AmazonCategoryAttributeValue::model()->tableName();
				// 	$this->getDbConnection()->createCommand($attrValueDelSql)->execute();	
				// }			
				
				//在根分类列表入库接口时处理多属性
				//在根分类下获取所有数据接口时不用再处理根分类(parent_id=0)的多属性数据
				if ($type == 0 && $params['parent_id'] = 0){
					continue;
				}else{
					//多属性和多属性值处理

					//先删除此分类ID的原有多属性表记录
	            	$this->getDbConnection()->createCommand()->delete(AmazonCategoryAttribute::model()->tableName(), " cid = " .$cateID);

	            	//删除此分类ID的原有多属性值表记录
	            	$this->getDbConnection()->createCommand()->delete(AmazonCategoryAttributeValue::model()->tableName(), " cid = " .$cateID);

					if(isset($item->refinementsInformation)) $mulAttr = (array)$item->refinementsInformation;
					unset($item);

					if ($mulAttr){
						$ultAttrNum = $mulAttr['@attributes']['count'];
						//先格式化入库数据，然后再统一新增入库（多属性表，多属性值表）
						if ($ultAttrNum > 0){
							$refinementName = array();
							$attrItem = array();

							//refinementName只有一条和多条记录时结构和数据类型都有区别，统一结构处理
							if (isset($mulAttr['refinementName'])){
								//当只有一条记录时是对象，多条时是数组
								if (is_object($mulAttr['refinementName'])){
									$refinementName[0] = (array)$mulAttr['refinementName'];
								}else{
									$refinementName = (array)$mulAttr['refinementName'];
								}
							}

							//格式化入库数据
							if ($refinementName){
								foreach ($refinementName as $val){
									$val = (array)$val;
									$refinementField = array();
									if (isset($val['refinementField'])){
										//refinementField只有一条和多条记录时结构和数据类型都有区别，统一结构处理
										//当只有一条记录时是对象，多条时是数组
										if (is_object($val['refinementField'])){
											$refinementField[0] = (array)$val['refinementField'];
										}else{
											$refinementField = (array)$val['refinementField'];
										}

										if ($refinementField){
											foreach ($refinementField as $field){
												$attrVal = array();
												$field = (array)$field;

												//多属性分类自增ID
												$attrVal['cid'] = $cateID;		
												//多属性名称
												if(isset($val['@attributes']['name'])) $attrVal['attribute_name'] = trim(addslashes($val['@attributes']['name']));
												//多属性值
												if(isset($field['acceptedValues'])) $attrVal['acceptedvalues'] = trim(addslashes($field['acceptedValues']));
												//多属性单位
												if (isset($field['hasModifier']) && $field['hasModifier']){
													if(isset($field['modifiers'])) {
														if (is_object($field['modifiers'])){
															$modifiers = array();
															foreach($field['modifiers'] as $v){
																$modifiers[] = $v;
															}
															if ($modifiers){
																$attrVal['attribute_unit'] = trim(addslashes(implode(",",$modifiers)));
															}
														}else{
															$attrVal['attribute_unit'] = trim(addslashes($field['modifiers']));
														}
													}
												}
												//多属性字段名称
												if(isset($field['refinementAttribute'])) $attrVal['attribute_fields_name'] = trim(addslashes($field['refinementAttribute']));

												$attrItem[] = $attrVal;
											}
										}
										unset($refinementField);
									}else{
										$attrVal = array();
										//多属性分类自增ID
										$attrVal['cid'] = $cateID;		
										//多属性名称
										if(isset($val['@attributes']['name'])) $attrVal['attribute_name'] = trim(addslashes($val['@attributes']['name']));
										$attrItem[] = $attrVal;
									}														
								}
							}

							//入库操作,先分类多属性入库，然后获取新插入的多属性ID值，再新增多属性值
							if ($attrItem){
								foreach($attrItem as $attrData){
									$values = '';
									$values = $attrData['acceptedvalues'];
									unset($attrData['acceptedvalues']);

									$attrData['timestamp'] = date('Y-m-d H:i:s');
									$attrData['status']    = 0;		//默认为此多属性启用状态：0

									$attrRes = $this->getDbConnection()->createCommand()->insert(AmazonCategoryAttribute::model()->tableName(), $attrData);
									if (!$attrRes)
										throw new Exception(Yii::t('amazon_product', 'Save Product3 Info Failure'));
									$attrID = $this->getDbConnection()->getLastInsertID();	//新插入的多属性ID值

									//操作多属性值表
									if ($attrID > 0 && $values){
										$value_arr = explode(',',$values);
										if ($value_arr){

											//使用优化SQL语句，然后一次性新增入库
											$sql = "insert into " .AmazonCategoryAttributeValue::model()->tableName(). "(cid,category_attribute_id,en_value,timestamp) values ";

											foreach($value_arr as $i){
												$sql .="('" .$attrData['cid']. "','" .$attrID. "','" .trim(addslashes($i)). "','" .date('Y-m-d H:i:s'). "'),";	
											}

											$sql = substr($sql,0,strlen($sql)-1);
											$valueRes = $this->getDbConnection()->createCommand($sql)->execute();		
											if (!$valueRes)
												throw new Exception(Yii::t('amazon_product', 'Save Product4 Info Failure'));						
										}
										unset($value_arr);
									}
								}
							}
							unset($attrItem);
						}
					}
					unset($mulAttr);
				}
				$dbTransaction->commit();
			} catch (Exception $e) {
				$dbTransaction->rollback();
				$this->setErrorMsg($e->getMessage());
				return false;
			}
		}
		return true;
	}

	/**
	 * @desc 获取该分类节点下子分类列表
	 * @param bigint $categoryID
	 * @return array
	 */
	public function getSubCategoryList($parentID){

        return $this->model()->dbConnection->createCommand()
                       ->select('*')
                       ->from(self::tableName())
                       ->where('parent_id = '.$parentID)
                       ->queryAll();
	}

	/**
	 * @desc 通过自增ID获取该分类信息
	 * @param int $ID（自增ID）
	 * @return array
	 */
	public function getCategoryInfoByID($ID){
		if (empty($ID)) return false;
        return $this->model()->dbConnection->createCommand()
                       ->select('*')
                       ->from(self::tableName())
                       ->where('id = '.$ID)
                       ->queryRow();
	}

	/**
	 * @desc 通过分类ID获取分类列表
	 * @param int $categoryID（分类ID）
	 * @return array
	 */
	public function getCategoryInfoBycategoryID($categoryID){
		if (empty($categoryID)) return false;
        return $this->model()->dbConnection->createCommand()
                       ->select('*')
                       ->from(self::tableName())
                       ->where('category_id = '.$categoryID)
                       ->queryAll();
	}	



	/**
	 * @desc 获取分类信息
	 * @param string $subPathStr 当前子分类ID路径
	 * @return array
	 */
	public function getParentCategoryInfo($subPathStr){
		if (empty($subPathStr)) return false;

		$pathArr = explode(',',$subPathStr);
		if(is_array($pathArr) && count($pathArr) > 2){
			$temp = array_slice($pathArr,-2,1);
			if ($temp) $categoryID = $temp[0];	//分类ID

			array_pop($pathArr);
			$pathStr = implode(",",$pathArr);	//分类路径
		}else{
			return false;
		}

		if ($categoryID && $pathStr){
	        return $this->model()->dbConnection->createCommand()
                       ->select('*')
                       ->from(self::tableName())
                       ->where("category_id=:category_id AND category_path_id=:category_path_id", array(":category_id"=>$categoryID, ":category_path_id"=>$pathStr))
                       ->queryRow();
	    }
	    return false;
	}


	/**
	 * @desc 通过分类ID和分类名称路径获取该分类信息
	 * @param string $categoryID 分类ID
	 * @param string $pathName 分类名称路径
	 * @return array
	 */
	public function getCategoryInfoByCidPath($categoryID,$pathName){	
		if (empty($categoryID) || empty($pathName)) return false;	
        return $this->model()->dbConnection->createCommand()
                       ->select('*')
                       ->from(self::tableName())
                       ->where("category_id=:category_id AND category_path_name=:category_path_name", array(":category_id"=>$categoryID, ":category_path_name"=>$pathName))
                       ->queryRow();
	}	


	
	/**
	 * @desc 获取feed的报告结果
	 * @param unknown $accountID
	 * @param unknown $feedSubmissionId
	 * @return number
	 */
	public function getFeedSubmissionResult($accountID, $feedSubmissionId){
		$feedSubmissionResult = new GetFeedSubmissionResultRequest();
		$feedSubmissionResult->setAccount($accountID);
		$feedSubmissionResult->setFeedSubmissionId($feedSubmissionId);
		$response = $feedSubmissionResult->setRequest()->sendRequest()->getResponse();
		return $response;
	}

	/**
	 * @desc 获取分类属性（属性值）相关列表
	 * @param int $categoryID 分类自增ID
	 * @return array
	 */
	public function getCategoryAttributeList($categoryID){

		if (empty($categoryID)) return false;

		$list = array();
		$item = array();
        $attr_list = $this->model()->dbConnection->createCommand()
                   ->select('*')
                   ->from(AmazonCategoryAttribute::tableName())
                   ->where("cid=:cid", array(":cid"=>$categoryID))
                   ->queryAll();

		if ($attr_list){
			foreach($attr_list as $k => $val){
				//只有当为颜色和尺寸属性时为多属性
				if ($val['attribute_fields_name'] == self::VARIATION_COLOR || $val['attribute_fields_name'] == self::VARIATION_SIZE)
				{
					$item['attribute_id']              = $val['id'];
					$item['category_id']               = $val['cid'];
					$item['attribute_name_english']    = $val['attribute_name'];
					$item['attribute_fields_name']     = $val['attribute_fields_name'];
					$item['attribute_visible']         = ($val['status'] == 0) ? 1: 0;
					$item['attribute_customized_name'] = 1;		//默认为1-支持多属性
					$item['attribute_showtype_value']  = 'check_box';	//默认checkbox的操作样式
					$item['attribute_children']        = 0;	//默认子属性为0

					$item['value_list'] = array();
			        $value_list = $this->model()->dbConnection->createCommand()
			                   ->select('*')
			                   ->from(AmazonCategoryAttributeValue::tableName())
			                   ->where("category_attribute_id=:category_attribute_id", array(":category_attribute_id"=>$val['id']))
			                   ->queryAll();
			        if ($value_list){
			        	foreach ($value_list as $v){
			        		$item['value_list'][$v['id']] = array(
									'id'                      => $v['id'],
									'attribute_value_id'      => $v['id'],
									'category_id'             => $v['cid'],
									'attribute_value_cn_name' => $v['cn_value'],
									'attribute_value_en_name' => $v['en_value'],
									'create_time'             => $v['timestamp']
			        		);
			        	}
			        }
			        $list[] = $item;
			    }
			}
		}
		return $list;
	}	

    /**
     * @desc 获取分类的面包屑导航
     * @param string $category_path 分类路径
     */
    public function getBreadcrumbCategory($category_path, $separate = '->'){
    	if (empty($category_path)) return false;

		// $category_path = 'Clothing, Shoes & Jewelry,Baby,Baby Boys,Clothing,Sweaters';
		$category_path = str_replace(', ', '=====' ,$category_path);	//替换非分隔符", "
		$category_path = str_replace(',', $separate ,$category_path);
		$category_path = str_replace('=====', ', ',$category_path);
        
        return $category_path;
    }	


    /**
     * @desc 获取分类关键字(item_type)
     * 
     */
    public function getCategoryItemType($category_id,$category_path) {
    	$itemType = '';
        if (!empty($category_id) && !empty($category_path)){
            $categoryInfo = $this->getCategoryInfoByCidPath($category_id,$category_path);
            $categoryRequired = $categoryInfo['category_fields_required'];
            if ($categoryRequired){
                $categoryRequiredFieldArr = explode('|',$categoryRequired);
                if ($categoryRequiredFieldArr){
                    foreach($categoryRequiredFieldArr as $val){
                        $temp = explode(':',$val);
                        if ($temp[0] == 'item_type_keyword'){
                            $itemType = $temp[1];
                        }
                    }
                }
            }
        }  
        return $itemType;
    }    


	/**
	 * @desc 通过关键字搜索获取分类列表
	 * @param string $keyword
	 * @return array()
	 */
	public function getCategoryListByKeyword($keyword = '') {
		if (empty($keyword)) return false;
		$tempCategory = array();
		$searchCategory = array();		
		$keyword = addslashes($keyword);

		$publishList = $this->getDbConnection()->createCommand()
			->select("*")
			->from(self::tableName())
			->where("en_name like '%{$keyword}%'")
			->andWhere("child_category_count = 0")
			->order('id ASC')
			->queryAll();
		if ($publishList) {
			foreach ($publishList as $list) {
				if(!empty($list['category_id']) && !empty($list['category_path_name'])) $tempCategory[$list['id']] = $list['category_path_name'];
			}
		}
		//格式化面包导航分类，category_id换成分类自增ID
		if ($tempCategory) {
			foreach ($tempCategory as $key => $val) {
				$searchCategory[$key] = $this->getBreadcrumbCategory($val);		
			}
		}
		return $searchCategory;
	}    


	/**
	 *  
	 * @desc 设置错误消息
	 * @param unknown $msg
	 */
	
	private function setErrorMsg($msg){
		$this->_errorMsg = $msg;
	}
	/**
	 * @desc 获取错误消息
	 * @return unknown
	 */
	public function getErrorMsg(){
		return $this->_errorMsg;
	}
}