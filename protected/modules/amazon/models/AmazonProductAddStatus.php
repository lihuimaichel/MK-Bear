<?php
/**
 * @desc 产品刊登多属性产品
 * @author Liz
 *
 */
class AmazonProductAddStatus extends AmazonModel {
	/**
	 * @desc 获取model
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @设置表名
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_amazon_product_add_status';
	}

	/**
	 * @desc 通过刊登ID和刊登类型获取刊登产品对应上传类型记录（单品）
	 * @param int $addID 产品刊登主表ID
	 * @param int $type 上传类型
	 */
	public function getStatusByAddIDAndType($addID = 0,$type = 0) {

		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("add_id = " .$addID. " AND upload_type = ".$type)
			->queryRow();
	}	
	
	/**
	 * @desc 通过刊登主ID获取所有上传状态记录
	 * @param int $addID 产品刊登主表ID
	 */
	public function getAllStatusByAddID($addID) {
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("add_id = :add_id", array(':add_id' => $addID))
			->order("upload_type ASC")
			->queryAll();
	}

	/**
	 * @desc 根据刊登多属性ID获取所有上传状态记录
	 * @param int $variationID 产品刊登多属性ID
	 */
	public function getAllStatusByVariationID($variationID) {
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("variation_id = :variation_id", array(':variation_id' => $variationID))
			->order("upload_type ASC")
			->queryAll();
	}	

	/**
	 * @desc 通过FeedID获取状态为上传中的列表
	 * @param string $feedID 平台上传唯一编码
	 */
	public function getStatusListByFeedID($feedID) {
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("feed_id = '{$feedID}'")
			->andWhere("upload_status =" .AmazonProductAdd::UPLOAD_STATUS_RUNNING)
			->order("id ASC")
			->queryAll();
	}	

	/**
	 * @desc 单品类型：通过刊登ID刊登类型，获取上传状态记录
	 * @param int $addID 产品刊登主表ID
	 * @param int $variationID 多属性ID
	 * @param int $upload_type 刊登类型
	 */
	public function getUploadInfoByType($addID,$variationID,$upload_type) {
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("add_id = '{$addID}' AND upload_type = '{$upload_type}'")					
			->order("id desc")
			->limit(1)
			->queryRow();
	}	

	/**
	 * @desc 通过刊登ID和多属性ID、刊登类型（确保唯一），获取上传状态记录
	 * @param int $addID 产品刊登主表ID
	 * @param int $variationID 多属性ID
	 * @param int $upload_type 刊登类型
	 */
	public function getUploadStatusInfoByType($addID,$variationID,$upload_type) {
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("add_id = '{$addID}' AND variation_id = '{$variationID}' AND upload_type = '{$upload_type}'")					
			->order("id desc")
			->limit(1)
			->queryRow();
	}

	/**
	 * @desc 单品刊登：通过刊登ID和刊登类型及状态，获取最近的一条刊登上传状态记录（主要用于基本产品刊登的前置条件判断）
	 * @param int $addID 产品刊登主表ID
	 * @param int $upload_type 刊登类型
	 * @param int $uploadStatus ,$uploadStatus2 上传类型
	 */
	public function getUploadInfoByTypeStatus($addID = 0,$upload_type = 0,$uploadStatus = 0,$uploadStatus2 = '') {		
		$statusSql = "upload_status = '{$uploadStatus}'";
		if(!empty($uploadStatus2)) $statusSql = "(upload_status = '{$uploadStatus}' OR upload_status = '{$uploadStatus2}')";
		return $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("add_id = '{$addID}' AND upload_type = '{$upload_type}' AND '{$statusSql}'")
			->order("id desc")
			->limit(1)
			->queryRow();
	}		

	/**
	 * @desc 获取基本产品刊登是否成功（只要有一条成功即可）
	 * @param int $addID 产品刊登主表ID
	 * @param int $upload_type 刊登类型
	 */
	public function getCheckProductIsSeccess($addID) {
		$ret = $this->dbConnection->createCommand()
			->from(self::tableName())
			->where("add_id = :add_id AND upload_type = :upload_type AND upload_status = :upload_status", array(':add_id' => $addID, ':upload_type' => 1, ':upload_status' => AmazonProductAdd::UPLOAD_STATUS_SUCCESS))					
			->order("id desc")
			->limit(1)
			->queryRow();
		if ($ret){
			return true;
		}else{
			return false;
		}	
	}

	/**
	 * @desc 判断所有接口刊登是否成功（价格、库存、图片三接口都有上传成功的记录即可）
	 * @param int $addID
	 * @return boolean
	 */
	public function getAllUploadIsFinish($variationID){
		if(empty($variationID)) return false;
		$sql = "SELECT distinct upload_type FROM " .$this->tableName(). " WHERE variation_id = " .$variationID. " AND upload_status = " .AmazonProductAdd::UPLOAD_STATUS_SUCCESS. " AND upload_type IN (" .AmazonProductAdd::UPLOAD_TYPE_INVENTORY. "," .AmazonProductAdd::UPLOAD_TYPE_PRICE. "," .AmazonProductAdd::UPLOAD_TYPE_IMAGE. ")";
		$result = $this->getDbConnection()->createCommand($sql)->queryAll();
		if ($result){
			if(count($result) == 3) return true;
		}
		return false;
	}	

	/**
	 * @desc 通过主表ID判断四个接口（基本产品、价格、库存、图片)都有上传成功的记录
	 * @param int $addID
	 * @return boolean
	 */
	public function getAllUploadIsFinishList($addID){
		if(empty($addID)) return false;
		$str = '';
		$ret = array();
		$sql = "SELECT distinct upload_type FROM " .$this->tableName(). " WHERE add_id = " .$addID. " AND upload_status = " .AmazonProductAdd::UPLOAD_STATUS_SUCCESS. " AND upload_type IN (" .AmazonProductAdd::UPLOAD_TYPE_PRODUCT. "," .AmazonProductAdd::UPLOAD_TYPE_INVENTORY. "," .AmazonProductAdd::UPLOAD_TYPE_PRICE. "," .AmazonProductAdd::UPLOAD_TYPE_IMAGE. ")";
		$result = $this->getDbConnection()->createCommand($sql)->queryAll();
		if ($result){
			foreach($result as $key => $val){
				$ret[$key]= $val['upload_type'];
			}
		}
		return $ret;
	}		

	/**
	 * @desc 通过多属性ID判断四个接口（基本产品、价格、库存、图片)都有上传成功的记录
	 * @param int $variationID
	 * @return boolean
	 */
	public function getAllUploadIsFinishListByVariationID($variationID){
		if(empty($variationID)) return false;
		$str = '';
		$ret = array();
		$sql = "SELECT distinct upload_type FROM " .$this->tableName(). " WHERE variation_id = " .$variationID. " AND upload_status = " .AmazonProductAdd::UPLOAD_STATUS_SUCCESS. " AND upload_type IN (" .AmazonProductAdd::UPLOAD_TYPE_PRODUCT. "," .AmazonProductAdd::UPLOAD_TYPE_INVENTORY. "," .AmazonProductAdd::UPLOAD_TYPE_PRICE. "," .AmazonProductAdd::UPLOAD_TYPE_IMAGE. ")";
		$result = $this->getDbConnection()->createCommand($sql)->queryAll();
		if ($result){
			foreach($result as $key => $val){
				$ret[$key]= $val['upload_type'];
			}
		}
		return $ret;
	}

	/**
	 * @desc 通过多属性ID判断四个接口只读状态：（基本产品、价格、库存、图片)都有上传成功或是上传中的记录
	 * @param int $variationID
	 * @return boolean
	 */
	public function getAllUploadIsReadonlyListByVariationID($variationID){
		if(empty($variationID)) return false;
		$str = '';
		$ret = array();
		$sql = "SELECT distinct upload_type FROM " .$this->tableName(). " WHERE variation_id = " .$variationID. " AND upload_status in (" .AmazonProductAdd::UPLOAD_STATUS_RUNNING. "," .AmazonProductAdd::UPLOAD_STATUS_SUCCESS. ") AND upload_type IN (" .AmazonProductAdd::UPLOAD_TYPE_PRODUCT. "," .AmazonProductAdd::UPLOAD_TYPE_INVENTORY. "," .AmazonProductAdd::UPLOAD_TYPE_PRICE. "," .AmazonProductAdd::UPLOAD_TYPE_IMAGE. ")";
		$result = $this->getDbConnection()->createCommand($sql)->queryAll();
		if ($result){
			foreach($result as $key => $val){
				$ret[$key]= $val['upload_type'];
			}
		}
		return $ret;
	}		

	/**
	 * @desc 新增上传状态数据
	 * @param array $data
	 * @return int
	 */
	public function addUploadStatus($data){
		if(empty($data)) return false;
		$id = 0;
		$ret = $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
		if($ret) $id = $this->getDbConnection()->getLastInsertID();
		return $id;
	}
	
	/**
	 * @desc 根据自增ID更新数据
	 * @param int $id
	 * @param array $updata
	 * @return boolean
	 */
	public function updateUploadStatusByID($id, $updata){
		if(empty($id) || empty($updata)) return false;
		$conditions = "id = ".$id;
		return $this->getDbConnection()->createCommand()->update(self::tableName(), $updata, $conditions);
	}

	/**
	 * 根据条件获取单个上传状态数据
	 */
	public function getUploadStatusByCondition($where) {
		if (empty($where)) return false;
        return $this->getDbConnection()->createCommand()
					->select('*')
					->from(self::tableName())
					->where($where)
					->limit(1)
                    ->queryRow();
	}

	/**
	 * 根据条件获取上传状态列表数据
	 */
	public function getUploadStatusListByCondition($where) {
		if (empty($where)) return false;
        return $this->getDbConnection()->createCommand()
					->select('*')
					->from($this->tableName())
					->where($where)
                    ->queryAll();
	}	

	/**
	 * @desc 根据自增IDs批量更新上传状态
	 * @param array $feedIDsArr
	 * @param int $uploadStatus 上传状态
	 * @return boolean
	 */
	public function updateStatusByFeedIDs($feedIDsArr = array(), $uploadStatus = 0){
		if(!$feedIDsArr || !is_array($feedIDsArr)) return false;
		$errmessage = '';
		//如果是失败，则写入错误信息
		if($uploadStatus == AmazonProductAdd::UPLOAD_STATUS_FAILURE) $errmessage = '平台接口返回结果：'.CommonSubmitFeedRequest::FEED_STATUS_CANCELLED;
		foreach ($feedIDsArr as $feedID){
			if (!empty($feedID)){
				$updata = array(
					'upload_status' => $uploadStatus, 
					'receive_time' => date('Y-m-d H:i:s'), 
					'upload_message' => $errmessage
				);
				$conditions = "feed_id = '{$feedID}'";
				$this->updateProductAddStatus($conditions,$updata);
			}
		}
		return true;
	}	

	/**
	 * @desc 根据条件更新亚马逊上传状态表
	 * @param string $condition
	 * @param array $updata
	 * @return boolean
	 */
	public function updateProductAddStatus($conditions, $updata){
		if(empty($conditions) || empty($updata)) return false;
		return $this->getDbConnection()->createCommand()
				    ->update($this->tableName(), $updata, $conditions);
	}	

	/**
	 * 根据条件判断是否此类型已上传成功记录
	 */
	public function getHadUploadSucessfulByConditions($AddID = 0, $uploadType = 0, $variationID = 0) {
		if($AddID == 0 || $uploadType == 0) return false;
		$uploadStatus = AmazonProductAdd::UPLOAD_STATUS_SUCCESS;	//已上传成功
		$where = "add_id = {$AddID} AND upload_type = {$uploadType} AND upload_status = {$uploadStatus} ";
		if($variationID > 0) $where .= " AND variation_id = {$variationID}";
        return $this->getDbConnection()->createCommand()
					->select('id')
					->from($this->tableName())
					->where($where)
					->limit(1)
                    ->queryRow();
	}	

	/**
	 * @desc 根据条件删除
	 * @param unknown $conditions
	 * @param unknown $param
	 * @return Ambigous <number, boolean>
	 */
	public function deleteListByConditions($conditions, $param = array()){
		return $this->getDbConnection()->createCommand()->delete($this->tableName(), $conditions, $param);
	}			

}