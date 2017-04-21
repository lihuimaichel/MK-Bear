<?php
/**
 * @desc joom产品
 * @author lihy
 *
 */
class JoomProductVariantsAdd extends JoomModel{

	private $_errorMsg;
	public function tableName(){
		return 'ueb_joom_product_variants_add';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	
	
	/**
	 * @desc 设置错误消息
	 * @param unknown $msg
	 */
	public function setErrorMsg($msg){
		$this->_errorMsg = $msg;
	}
	/**
	 * @desc 获取错误信息
	 * @return unknown
	 */
	public function getErrorMsg(){
		return $this->_errorMsg;
	}
	/**
	 * @desc 保存子sku刊登数据（单独的子sku刊登时调用）
	 * @param unknown $datas
	 * @throws Exception
	 * @return boolean
	 */
	public function saveJoomAddVariantsData($datas){
		if(!is_array($datas) || empty($datas)){
			$this->setErrorMsg(Yii::t('joom_listing', 'No datas to be added'));
			return false;
		}
		/**@ 获取产品信息*/
		$config = ConfigFactory::getConfig('serverKeys');
		$skuEncrypt = new encryptSku();
		$userId = Yii::app()->user->id;
		$time = date('Y-m-d H:i:s');
		$trans = $this->getDbConnection()->beginTransaction();
		try {
			$joomProductAdd = self::model('JoomProductAdd');
			foreach ($datas as $accountId=>$data){
				if(empty($data['add_id'])) continue;
				//获取账户名
				$accountInfo = self::model('JoomAccount')->findByPk($accountId);
				if($accountInfo)
					$accountName = '';
				else $accountName = $accountInfo->account_name;
				//判断是否刊登了主sku
				//$joomMainProductInfo = $joomProductAdd->find('account_id=:account_id and parent_sku=:parent_sku', array(':account_id'=>$accountId, ':parent_sku'=>$data['parent_sku']));
				$joomMainProductInfo = $joomProductAdd->find("id='{$data['add_id']}'");
				if(empty($joomMainProductInfo)){
					throw new Exception($accountName . Yii::t('joom_listing', 'Have no upload main SKU'));
				}
				foreach ($data['variants'] as $variant){
					$variantData = array(
							'add_id'=>$joomMainProductInfo->id,
							'parent_sku'=>$joomMainProductInfo->parent_sku,
							'sku'=>$variant['sku'],
							//'online_sku'=>$skuEncrypt->getEncryptSku($variant['sku']),
							'online_sku'=>$variant['sku'],//不需要加密
							'inventory'=>$variant['inventory'],
							'size'=>empty($variant['size'])?'':$variant['size'],
							'color'=>empty($variant['color'])?'':$variant['color'],
							'price'=>$variant['price'],
							'shipping'=>$variant['shipping'],
							'shipping_time'=>'',
							'msrp'=>$variant['market_price'],
							'main_image'=>empty($variant['main_image'])?'':$variant['main_image'],
							'upload_status'	=>	JoomProductAdd::JOOM_UPLOAD_PENDING,
							'upload_times'	=>	0,
							'create_user_id'=>$userId,
							'update_user_id'=>$userId,
							'create_time'=>$time,
							'update_time'=>$time	
					);
					
					
					//检查是否更新还是修改
					$subProductInfo = self::model()->find("add_id=:add_id and sku=:sku", array(':add_id'=>$joomMainProductInfo->id, ':sku'=>$variant['sku']));
					
					//图片
					if(empty($variant['main_image']) && empty($subProductInfo['main_image'])){
						$images = Product::model()->getImgList($variant['sku'], 'ft');
						if($images){
							$imgname = array_shift($images);
							$basefilename = basename($imgname);
							if(strtolower($basefilename) == $variant['sku'].".jpg" && count($images)>1){
								$imgname = array_shift($images);
							}
							$remoteImgUrl = (string)UebModel::model("JoomProductAdd")->getRemoteImgPathByName($imgname, $accountId, $variant['sku']);
					
							$variantData['main_image'] = $remoteImgUrl;
						}
					}
					
					if($subProductInfo){
						unset($variantData['create_user_id'], $variantData['create_time']);
						$res = $this->getDbConnection()->createCommand()->update(self::tableName(), $variantData, 'id=:id', array(':id'=>$subProductInfo->id));
					}else{
						$res = $this->getDbConnection()->createCommand()->insert(self::tableName(), $variantData);
						$lastId = $this->getDbConnection()->getLastInsertID();
					}
					if(!$res)
						throw new Exception($accountName . ' '. Yii::t('system', 'Save failure'));
				}
			}
			$trans->commit();
			return true;
		}catch (Exception $e){
			$trans->rollback();
			$this->setErrorMsg($e->getMessage());
			return false;
		}
	}
	/**
	 * @desc 根据add_id获取全部符合的variants
	 * @param unknown $addId
	 * @return mixed
	 */
	public function getJoomProductVariantsAddListByAddId($addId,  $param = array(), $limit = null, $fields = "*"){
		/* return $this->getDbConnection()->createCommand()
									->from(self::tableName())
									->where('add_id=:add_id', array(':add_id'=>$addId))
									->queryAll(); */
		return $this->getJoomProductVariantsAddList('add_id=:add_id', array(':add_id'=>$addId), $limit, $fields);
	}
	/**
	 * @desc 根据add_id获取待上传的variants
	 * @param unknown $addId
	 * @return NULL
	 */
	public function getPendingUploadVariantsByAddId($addId, $fields = "*", $uploadMaxTimes = 10){
        //$uploadMaxTimes = 3;
		if(!$addId) return null;
		$findUploadStatus = JoomProductAdd::JOOM_UPLOAD_FAIL . ',' . JoomProductAdd::JOOM_UPLOAD_PENDING . ',' . JoomProductAdd::JOOM_UPLOAD_IMG_FAIL;
		//$findUploadStatus = JoomProductAdd::JOOM_UPLOAD_PENDING;
		$conditions = 'v.add_id=:add_id  AND v.upload_status IN(' . $findUploadStatus . ') ';
		if($uploadMaxTimes)
			$conditions .= 'AND v.upload_times<'.$uploadMaxTimes;
		$param = array(':add_id'=>$addId);
		return $this->getJoomProductVariantsAddList($conditions, $param, null, $fields);
	}
	/**
	 * @desc 获取待上传变种产品（部分）
	 * @param number $limit
	 */
	public function getPendingUploadVariants($limit = 10, $fields = "*", $accountId = 0, $addId = 0){
		$uploadMaxTimes = 3;
		//$findUploadStatus = JoomProductAdd::JOOM_UPLOAD_FAIL . ',' . JoomProductAdd::JOOM_UPLOAD_PENDING;
		$findUploadStatus = JoomProductAdd::JOOM_UPLOAD_PENDING;
		$conditions = 'v.upload_times<'.$uploadMaxTimes . ' AND v.upload_status IN(' . $findUploadStatus . ') ';
		if($accountId){
			$conditions .= " and a.account_id={$accountId}";
		}
		if($addId){
			$conditions .= " and v.add_id={$addId}";
		}
		if (isset($_REQUEST['create_user_id']) && $_REQUEST['create_user_id']) {
			$conditions .= " and a.create_user_id='{$_REQUEST['create_user_id']}'";
		}
		return $this->getJoomProductVariantsAddList($conditions, array(), $limit, $fields);
	}
	/**
	 * @desc 根据条件获取数据
	 * @param unknown $conditions
	 * @param unknown $param
	 * @return mixed
	 */
	public function getJoomProductVariantsAddList($conditions, $param = array(), $limit = null, $fields = "*"){
        //$fields  = 'v.id';
		$command = $this->getDbConnection()->createCommand()
					->from(self::tableName().' v')
					->select($fields)
                    ->leftJoin(JoomProductAdd::model()->tableName().' a', "a.id=v.add_id")
					->where($conditions, $param)
                    //->andWhere("a.upload_status = 1")
					;
		if($limit>0)
			$command->limit($limit);
		return $command->queryAll();
	}
	/**
	 * @desc 
	 * @param unknown $conditions
	 * @param unknown $params
	 * @return mixed
	 */
	public function getJoomProductVariantsAddInfo($conditions, $params = array()){
		$command = $this->getDbConnection()->createCommand()
						->from(self::tableName())
						->where($conditions, $params);
		return $command->queryRow();
	}
	/**
	 * @desc 根据主键id更新variant数据
	 * @param unknown $id
	 * @param unknown $data
	 * @return boolean|Ambigous <boolean, unknown, number>
	 */
	public function updateProductVariantAddInfoByPk($id, $data){
		if(!$id || !$data) return false;
		return self::model()->updateByPk($id, $data);
	}
	
	/**
	 * @desc 根据add_id数组来删除数据
	 * @param array $ids
	 * @return boolean
	 */
	public function deleteProductVariantsAddInfoByAddIds($addIds, $conditions = '', $param = null){
		if(!$addIds) return false;
		if(!is_array($addIds))
			$addIds = array($addIds);
		$condition = "add_id IN('".implode("','", $addIds)."')";
		if($conditions)
			$condition .= " AND ".$conditions;
		return $this->getDbConnection()->createCommand()
		->delete(self::tableName(), $condition, $param);
	}
	/**
	 * @desc 根据条件删除
	 * @param unknown $conditions
	 * @param unknown $param
	 * @return Ambigous <number, boolean>
	 */
	public function deleteProductVariant($conditions, $param = array()){
		return $this->getDbConnection()->createCommand()
					->delete(self::tableName(), $conditions, $param);
	}
}