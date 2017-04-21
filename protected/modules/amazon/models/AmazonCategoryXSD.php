<?php
class AmazonCategoryXSD extends AmazonModel {

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
		return 'ueb_amazon_xsd_category';
	}
	
	/**
	 * @desc 保存分类数据
	 * @param array $data
	 * @return boolean
	 */
	public function saveAmazonCategoryXSD($data = array()) {		
		if (!$data || !isset($data['category_name']) || !isset($data['category_list'])) return false;	

		if (!empty($data['category_name']) && count($data['category_list']) > 0){
			$dbTransaction = $this->getDbConnection()->getCurrentTransaction();
			if (empty($dbTransaction))
				$dbTransaction = $this->getDbConnection()->beginTransaction();
			try {						
				//顶级分类入库
				$topCategoryID = 0;
				$top_data = array(
					'title'     => $data['category_name'],
					'top_id'    => 0,
					'parent_id' => 0,
					'timestamp' => date('Y-m-d H:i:s')
				);
				$this->getDbConnection()->createCommand()->insert(self::model()->tableName(), $top_data);
				$topCategoryID = $this->getDbConnection()->getLastInsertID();	//插入的顶级分类ID值

				if ($topCategoryID > 0){
					foreach ($data['category_list'] as $cateKey => $cateItem) {
						//分类入库
						//排除顶级分类
						if ($cateKey != $data['category_name']){
							$category_data = array(
								'title'     => $cateKey,
								'top_id'    => $topCategoryID,
								'parent_id' => $topCategoryID,
								'timestamp' => date('Y-m-d H:i:s')
							);
							$this->getDbConnection()->createCommand()->insert(self::model()->tableName(), $category_data);
							$categoryID = $this->getDbConnection()->getLastInsertID();	//插入的分类ID值
						}else{
							$categoryID = $topCategoryID;
						}
						//分类属性入库
						foreach ($cateItem as $key => $item){
							//多属性批量入库
							if ($key == 'variation'){	
								if ($item){							
									$sql = "insert into " .AmazonCategoryAttributeXSD::model()->tableName(). "(cid,title,type) values ";
									if ($item){
										foreach ($item as $var){
											$sql .="(" .$categoryID. ",'" .trim(addslashes($var)). "',1),";	
										}
									}
									$sql = substr($sql,0,strlen($sql)-1);
									$this->getDbConnection()->createCommand($sql)->execute();		
								}						
							}else{
								if ($item){
									//通用属性批量入库
									$sql = "insert into " .AmazonCategoryAttributeXSD::model()->tableName(). "(cid,title,type,data_type,limit_type) values ";
									foreach ($item as $com){
										$limit_type = 0;
										if (count($com['limit_value']) > 0){
											if (isset($com['limit_value']['min'])){
												$limit_type = 2;	//2-range，限制数值范围
											}else{
												$limit_type = 1;	//1-select，限制字符选择
											}
										}
										$sql .="(" .$categoryID. ",'" .trim(addslashes($com['name'])). "',0,'" .trim(addslashes($com['data_type'])). "'," .$limit_type. "),";	
									}
									$sql = substr($sql,0,strlen($sql)-1);
									$attr_res = $this->getDbConnection()->createCommand($sql)->execute();

									//通用属性下的限制数据批量入库
									if ($attr_res){
								        $attr_list = $this->getDbConnection()->createCommand()
					                       ->select('*')
					                       ->from(AmazonCategoryAttributeXSD::model()->tableName())
					                       ->where("cid = :cid AND type = :type AND limit_type != :limit_type", array(":cid" => $categoryID, ":type" => 0, ":limit_type" => 0))
					                       ->queryAll();
								        if ($attr_list){
								        	foreach($attr_list as $attr_info){
												foreach ($item as $com){
													if (trim($attr_info['title']) == trim($com['name'])){
														if (count($com['limit_value']) > 0){
															$value_sql = "insert into " .AmazonCategoryAttributeValueXSD::model()->tableName(). "(attr_id,title) values ";
															//限制数值范围
															if (isset($com['limit_value']['min'])){
																$value_sql .="(" .$attr_info['id']. ",'" .trim(addslashes($com['limit_value']['min'])). "'),(" .$attr_info['id']. ",'" .trim(addslashes($com['limit_value']['max'])). "'),";
															}else{
																//限制字符选择
																foreach($com['limit_value'] as $limit_val){
																	$value_sql .="(" .$attr_info['id']. ",'" .trim(addslashes($limit_val)). "'),";	
																}
															}
															$value_sql = substr($value_sql,0,strlen($value_sql)-1);
															$this->getDbConnection()->createCommand($value_sql)->execute();
														}														
													}	
												}
											}
								        }
									}									
								}
							}
						}
					}
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
	 * @desc 通过上传分类名获取分类列表
	 * @param string $product_type 分类类型名称
	 * @return array
	 */
	public function getCategoryProductTypeList($product_type){
		if (empty($product_type)) return false;
        return $this->model()->dbConnection->createCommand()
                       ->select('*')
                       ->from(self::tableName())
                       ->where("LOWER(title) = '".strtolower(trim(addslashes($product_type)))."'")
                       ->queryAll();
	}

	/**
	 * @desc 通过顶级分类ID获取属下第一个分类类型信息
	 * @param $id 分类ID值
	 * @return array
	 */
	public function getFirstCategoryInfoById($id){
		if ((int)$id == 0) return false;
		return $this->model()->dbConnection->createCommand()
					->select("*")
					->from(self::TableName())
					->where("parent_id = ".$id)
					->order("id asc")
					->limit(1)
					->queryRow();
	}

	/**
	 * @desc 通过父级分类ID获取顶级分类信息
	 * @param $parent_id 父级分类ID值
	 * @return array
	 */
	public function getTopCategoryinfoByParentId($parent_id){
		if ((int)$parent_id == 0) return false;
		return $this->model()->dbConnection->createCommand()
					->select("*")
					->from(self::TableName())
					->where("id = ".$parent_id)
					->queryRow();
	}	

	/**
	 * @desc 获取所有顶级分类列表
	 * @return array
	 */
	public function getTopCategoryList(){

		return $this->model()->dbConnection->createCommand()
					->select("*")
					->from(self::TableName())
					->where("parent_id = 0")
					->order("id asc")
					->queryAll();
	}	

	/**
	 * @desc 通过顶级分类ID获取所属分类类型列表
	 * @return array
	 */
	public function getProductTypeListByTopid($topid){

		return $this->model()->dbConnection->createCommand()
					->select("*")
					->from(self::TableName())
					->where("parent_id = " .$topid)
					->order("id asc")
					->queryAll();
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