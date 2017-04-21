<?php
class WishProductAdd extends WishModel{
	const LISTING_TYPE_FIXEDPRICE = 2;//一口价
	const LISTING_TYPE_VARIATION = 3;//多属性
	
	const PRODUCT_IS_NORMAL = 0;//基本
	const PRODUCT_IS_SINGLE = 1;//多属性单品
	const PRODUCT_IS_VARIOUS = 2;//多属性组合产品
	
	const WISH_UPLOAD_PENDING = 0;//待上传
	const WISH_UPLOAD_PENDING_MAPPING = -1;//映射到待上传
	const WISH_UPLOAD_SUCCESS = 1;//上传成功
	const WISH_UPLOAD_FAIL = 2;//上传失败
	const WISH_UPLOAD_IMG_FAIL = 3;//图片上传失败
    const WISH_UPLOAD_RUNNING = 4; //正在跑
	
	const UPLOAD_STATUS_IMGRUNNING  = 3;//等待上传图片
	const UPLOAD_STATUS_SUCCESS     = 4;//上传成功
	const UPLOAD_STATUS_FAILURE     = 5;//上传失败
	
	const EVENT_UPLOAD_PRODUCT = 'upload_product';
	
	const PRODUCT_PUBLISH_CURRENCY = 'USD';		//刊登货币
	const PRODUCT_PUBLISH_INVENTORY = 1000;    //刊登数量
	
	// === 保存类型 ===
	const SAVE_TYPE_ALL  = 1;					//主信息和sku都保存
	const SAVE_TYPE_ONLY_SUBSKU = 2;			//只保存sku信息并且已经有主刊登信息
	const SAVE_TYPE_NO_MAIN_SKU = 4;			//只保存sku信息并且没有主待刊登信息
	
	// === 刊登方式 ===
	const ADD_TYPE_DEFAULT = 0;//默认刊登方式
	const ADD_TYPE_BATCH = 1;//批量刊登
	const ADD_TYPE_PRE = 2;//预刊登
	const ADD_TYPE_COPY = 3;//复制刊登
	
	//wish物流类型数组
	public static $logisticsType = array(
			'ghxb_gyhl'		=>	'国洋荷兰小包挂号',
			'cm_gyhl'		=>	'国洋荷兰小包平邮',
			'cm_gyrd_hk'	=>	'国洋瑞典香港小包平邮',
			'ghxb_gyrd_hk'	=>	'国洋瑞典香港小包挂号',
			'ghxb_wish'		=>	'Wish邮挂号',
			'cm_wish'		=>	'wish邮小包',
			'ghxb_cn_e'		=>	'深圳纽扣电池挂号',
			'cm_cnxb_e'		=>	'深圳纽扣电池小包',
			'cm_jrxb'		=>	'广邮广州小包②',
			'ghxb_jr'		=>	'广邮广州挂号②',
			'kd_sfeu'		=>	'顺丰欧洲专递',
			'ghxb_sy_my'	=>	'顺友马邮挂号',
			'cm_sy_my'		=>	'顺友马邮小包',
			'ghxb_syb'		=>	'顺友顺邮宝挂号',
			'cm_syb'		=>	'顺友顺邮宝小包',
			'ghxb_sfoz'		=>	'顺丰欧洲挂号',
			'cm_sfoz'		=>	'顺丰欧洲小包',
			'eub_jiete'		=>	'长邮长沙E邮宝',
			'ghxb_sf'		=>	'顺丰立陶宛挂号',
			'cm_sf'			=>	'顺丰立陶宛小包',
			'ghxb_yuntudd'	=>	'云途福州挂号',
			'kd_fedexie_hongkong'	=>	'圣航FED IE香港快递',
			'cm_dhl'		=>	'A2B香港DHL小包',
			'ghxb_sg'		=>	'递四方新邮挂号',
			'cm_sgxb'		=>	'递四方新邮小包',
			'ghxb_cn'		=>	'深邮深圳挂号',
			'cm_cnxb'		=>	'深邮深圳小包',
			'ghxb_hk'		=>	'京华达香港挂号',
			'cm_hkxb_jhd'	=>	'京华达香港小包',
			'kd_ems'		=>	'EMS快递'
	);
	
	private $_errorMsg;
	
	public $add_id;
	public $id;
	public $parent_sku;
	public $account_name;
	public $upload_status_text;
	public $detail;
	public $subsku;
	public $visiupload;
	public $prop;
	public static $wishAccountPairs;

	public function tableName(){
		return 'ueb_wish_product_add';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}


	private function updateWhenUploaded($addInfo, $listingId)
    {
        $dateTime = new \DateTime();
        $wishProductVariantsAddModel = new WishProductVariantsAdd;


        $this->updateProductAddInfoByPk($addInfo['id'], array(
            'last_upload_time'=>$dateTime->format('Y-m-d H:i:s'),
            'upload_status'=>WishProductAdd::WISH_UPLOAD_SUCCESS,
            'upload_times'=>1+$addInfo['upload_times'],
            'last_upload_msg'=>'success',
            'wish_product_id'	=>	$listingId
        ));

        $variant = $wishProductVariantsAddModel->findByAttributes(array('add_id'=> $addInfo['id']));

        //更新子表第一个
        if($variant){
            $wishProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
                'last_upload_time'=>$dateTime->format('Y-m-d H:i:s'),
                'upload_status'=>WishProductAdd::WISH_UPLOAD_SUCCESS,
                'upload_times'=>1+$variant['upload_times'],
                'last_upload_msg'=>'success',
                'wish_variant_product_id'	=>	$listingId,
            ));
        }

        $params = array(
            'create_user_id' => $addInfo['create_user_id'],
            'account_id' => $addInfo['account_id'],
            'sku' => $addInfo['parent_sku'],
            'warehouse_id' => $addInfo['warehouse_id'],
        );
        //修改待刊登的状态
        WishWaitListing::model()->updateWaitingListingStatus($params, WishWaitListing::STATUS_SCUCESS);
        WishHistoryListing::model()->updateWaitingListingStatus($params, WishHistoryListing::STATUS_SCUCESS);

        //把仓库数据和销售人员绑定关系数据写入海外仓映射表，只针对海外仓
        if ($addInfo['warehouse_id'] > 0 && $addInfo['warehouse_id'] != 41){
            $warehouseData = array(
                'sku'                   => $addInfo['parent_sku'],
                'product_id'            =>$listingId,
                'overseas_warehouse_id' => $addInfo['warehouse_id'],
                'seller_id'             => $addInfo['create_user_id'],
                'account_id'            => $addInfo['account_id'],
            );
            WishOverseasWarehouse::model()->addWarehouseAdd($warehouseData);
        }
    }

	/**
	 * @desc 上传单个产品及其下面的子产品
	 * @param unknown $addId
	 * @throws Exception
	 */
	public function uploadProduct($addId){
		$data = $this->getProductAddInfo("id=:id", array(':id'=>$addId));
		if(empty($data)){
			$this->setErrorMsg(Yii::t('wish_listing', 'NO main sku'));
			return false;
		}

		//是否海外仓刊登，是则不受最低利润限制
		$overseasWarehouse = ($data['warehouse_id'] == WarehouseSkuMap::WARE_HOUSE_GM || $data['warehouse_id'] == 0) ? false : true;

        if ($data['upload_status'] == self::WISH_UPLOAD_RUNNING) {
            // 正在跑的状态，先去拉取一次看看有没有这样一个产品数据
            try {
                $downloader = WishListingDownload::model();
                $listingInfo = $downloader->pullSingleItem($data['online_sku'], $data['account_id'], false);
                if($listingInfo) {
                    $this->updateWhenUploaded($data, $listingInfo->Product->id);
                    $data['upload_status'] = self::WISH_UPLOAD_SUCCESS;
                }
            }catch (\Exception $e) {
               // 没有上传成功继续跑
            }
        }
		$currency = self::PRODUCT_PUBLISH_CURRENCY; //货币
		$accountId = $data['account_id'];
		$createProductRequest = new CreateProductRequest;
		$productModel = new Product;
		
		//获取子sku列表
		$wishProductVariantsAddModel = new WishProductVariantsAdd;
		//取消次数限制
		$pendingUploadVariants = $wishProductVariantsAddModel->getPendingUploadVariantsByAddId($data['id'], "*", 0);
		if(empty($pendingUploadVariants)){
			//$wishLog->setFailure($logId, Yii::t('wish_listing', 'No sku and sub sku to upload'));
			$this->setErrorMsg(Yii::t('wish_listing', 'No sku and sub sku to upload'));
			return false;
		}

		try{
			$time = date("Y-m-d H:i:s");
			//只有主产品没有上传成功的情况下才会从子sku里面抽离


			if($data['upload_status'] != self::WISH_UPLOAD_SUCCESS){

			    // 设置为正在运行
                $dateTime = new \DateTime();

                $this->updateProductAddInfoByPk($data['id'], array(
                    'last_upload_time'=>$dateTime->format('Y-m-d H:i:s'),
                    'upload_status'=>self::WISH_UPLOAD_RUNNING,
                   // 'upload_times'=> $data['upload_times']+1,
                ));

				if(count($pendingUploadVariants) > 1){
					//虚假变种
					$variant = array(
							'online_sku'	=>	$data['online_sku'],
							'inventory'		=>	 1000,///$pendingUploadVariants[0]['inventory'],
							'price'			=>	 109,//$pendingUploadVariants[0]['price'],
							'shipping'		=>	     2, //$pendingUploadVariants[0]['shipping'],
							'size'			=>	'',
							'color'			=>	'',
							'msrp'			=>	'',
					);
				}else{
					$variant = array_shift($pendingUploadVariants);	

					//单品,判断是否小于最低利润（海外仓不限制）
					if (!$overseasWarehouse){		 							
		 				$checkLowest = Product::model()->checkProfitRate($currency, Platform::CODE_WISH, $variant['sku'], ($variant['price'] + $variant['shipping']), null, $data['warehouse_id']);		//把运费加到售价中计算		   			
						if(!$checkLowest){
							throw new Exception('SKU '.$variant['sku'].': Profit is less than the minimum set profit');
						}						
					}			
				}

				$uploadData = array(
						'name'			=>	$data['name'],
						'description'	=>	$data['description'],
						'tags'			=>	$data['tags'],
						'brand'			=>	$data['brand'],
						'parent_sku'	=>	$data['online_sku'],
						'sku'			=>	$variant['online_sku'],
						'inventory'		=>	$variant['inventory'],
						'price'			=>	$variant['price'],
						'shipping'		=>	$variant['shipping'],
						'msrp'			=>	$variant['msrp'],
						'size'			=>	$variant['size'],
						'color'			=>	$variant['color'],
				);
				//上传图片
				if($data['remote_main_img']){
					$uploadData['main_image'] = $data['remote_main_img'];
				}elseif($data['main_image']){
					//$remoteImgUrl = $this->uploadImageToServer($data['main_image'], $accountId);
					
					$remoteImgUrl = "";
					/* if($variant['sku'] != $data['parent_sku']){
						//获取子SKU一张图
						$images = Product::model()->getImgList($variant['sku'], 'ft');
						if($images){
							$imgname = array_shift($images);
							$basefilename = basename($imgname);
							if(strtolower($basefilename) == $variant['sku'].".jpg" && count($images)>1){
								$imgname = array_shift($images);
							}
							$remoteImgUrl = (string)$this->getRemoteImgPathByName($imgname, $accountId, $variant['sku']);
							if(!$remoteImgUrl){
								throw new Exception($variant['sku'].":".$this->getErrorMsg(), WishProductAdd::WISH_UPLOAD_IMG_FAIL);
							}
							$wishProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
									'remote_main_img'=>$remoteImgUrl
							));
							$uploadData['main_image'] = $remoteImgUrl;
							
							$extra_images = explode('|', $data['extra_images']);
							if(count($extra_images)<20){//填充到附图去
								$extra_images[] = $data['main_image'];
								$data['extra_images'] = implode("|", $extra_images);
							}
						}
					} */
					if(!$remoteImgUrl){
						$remoteImgUrl = $this->getRemoteImgPathByName($data['main_image'], $accountId);
						if(!$remoteImgUrl){
							throw new Exception($data['parent_sku'].":".$this->getErrorMsg(), WishProductAdd::WISH_UPLOAD_IMG_FAIL);
						}
						$this->updateProductAddInfoByPk($data['id'], array(
								'remote_main_img'=>$remoteImgUrl
						));
						$uploadData['main_image'] = $remoteImgUrl;
					}
				}else{
					throw new Exception('没有主图不能上传');
				}
				if($data['extra_images']){
					if(empty($data['remote_extra_img'])){
						$extra_images = explode('|', $data['extra_images']);
						$newextra_images = array();
						foreach ($extra_images as $img){
							//$remoteImgUrl = $this->uploadImageToServer($img, $accountId);
							$remoteImgUrl = $this->getRemoteImgPathByName($img, $accountId);
							if(!$remoteImgUrl){
								throw new Exception($this->getErrorMsg(), WishProductAdd::WISH_UPLOAD_IMG_FAIL);
							}
							$newextra_images[] = $remoteImgUrl;
						}
						if($newextra_images){
							$extra_image = implode('|', $newextra_images);
							$this->updateProductAddInfoByPk($data['id'], array(
									'remote_extra_img'=>$extra_image
							));
							$uploadData['extra_images'] = $extra_image;
						}
					}else{
						$uploadData['extra_images'] = $data['remote_extra_img'];
					}
				}
				//if(isset($uploadData['color']) || isset($uploadData['size']))
				//	$uploadData['parent_sku'] = $data['online_sku'];
				$createProductRequest->setAccount($accountId);
				$createProductRequest->setUploadData($uploadData);
				$response = $createProductRequest->setRequest()->sendRequest()->getResponse();
				
				//MHelper::writefilelog("wish/wish-upload-log-".date("Y-m-dHis"), json_encode($response));
				$responseCode = -1;
				if($createProductRequest->getIfSuccess() || $responseCode == 0){
					$res = $this->updateProductAddInfoByPk($data['id'], array(
							'last_upload_time'=>$time,
							'upload_status'=>WishProductAdd::WISH_UPLOAD_SUCCESS,
							'upload_times'=>1+$data['upload_times'],
							'last_upload_msg'=>'success',
							'wish_product_id'	=>	isset($response->data->Product->id) ? (string)$response->data->Product->id : ''
					));
					
					//更新子表第一个
					if(isset($variant['id']) && $variant['id'] > 0){
						$wishProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
								'last_upload_time'=>$time,
								'upload_status'=>WishProductAdd::WISH_UPLOAD_SUCCESS,
								'upload_times'=>1+$variant['upload_times'],
								'last_upload_msg'=>'success',
								'wish_variant_product_id'	=>	isset($response->data->Product->variants[0]->Variant->id) ? (string)$response->data->Product->variants[0]->Variant->id : ''
						));
					}

                    $params = array(
                        'create_user_id' => $data['create_user_id'],
                        'account_id' => $data['account_id'],
                        'sku' => $data['parent_sku'],
                        'warehouse_id' => $data['warehouse_id'],
                    );
					//修改待刊登的状态
                    WishWaitListing::model()->updateWaitingListingStatus($params, WishWaitListing::STATUS_SCUCESS);
                    WishHistoryListing::model()->updateWaitingListingStatus($params, WishHistoryListing::STATUS_SCUCESS);

					//把仓库数据和销售人员绑定关系数据写入海外仓映射表，只针对海外仓
					if ($data['warehouse_id'] > 0 && $data['warehouse_id'] != 41){						
						$warehouseData = array(
							'sku'                   => $data['parent_sku'],
							'product_id'            => isset($response->data->Product->id) ? (string)$response->data->Product->id : '',
							'overseas_warehouse_id' => $data['warehouse_id'],
							'seller_id'             => $data['create_user_id'],
							'account_id'            => $data['account_id'],
							);
						WishOverseasWarehouse::model()->addWarehouseAdd($warehouseData);
					}
				}else{
					throw new Exception($createProductRequest->getErrorMsg());
				}
			}

			// === Start:上传子sku ====
			if($pendingUploadVariants){
								
				foreach ($pendingUploadVariants as $variant){
					$createProductVariantRequest = new CreateProductVariantRequest;
					$variantData = array(
							'parent_sku'	=>	$data['online_sku'],
							'sku'			=>	$variant['online_sku'],
							'inventory'		=>	$variant['inventory'],
							'price'			=>	$variant['price'],
							'shipping'		=>	$variant['shipping'],
							'msrp'			=>	$variant['msrp'],
							'size'			=>	$variant['size'],
							'color'			=>	$variant['color'],
					);
					try{
						//判断是否小于最低利润（海外仓不限制）
						if (!$overseasWarehouse){	
							$checkLowest = $productModel->checkProfitRate($currency, Platform::CODE_WISH, $variant['sku'], ($variant['price'] + $variant['shipping']), null, $data['warehouse_id']);
							if (!$checkLowest){
								throw new Exception('SKU '.$variant['sku'].': Sub sku profit is less than the minimum set profit');
							}
						}

						if($variant['main_image'] && $variant['remote_main_img']){
							$variantData['main_image'] = $variant['remote_main_img'];
						}elseif($variant['main_image']){
							//$remoteImgUrl = $this->uploadImageToServer($variant['main_image'], $accountId);
							$remoteImgUrl = $this->getRemoteImgPathByName($variant['main_image'], $accountId);
							if(!$remoteImgUrl){
								throw new Exception($this->getErrorMsg(), WishProductAdd::WISH_UPLOAD_IMG_FAIL);
							}
							$wishProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
									'remote_main_img'=>$remoteImgUrl
							));
							$variantData['main_image'] = $remoteImgUrl;
						}else{
							//获取子SKU一张图
							$images = Product::model()->getImgList($variant['sku'], 'ft');
							if($images){
								$imgname = array_shift($images);
								$basefilename = basename($imgname);
								if(strtolower($basefilename) == $variant['sku'].".jpg" && count($images)>1){
									$imgname = array_shift($images);
								}
								$remoteImgUrl = (string)$this->getRemoteImgPathByName($imgname, $accountId, $variant['sku']);
								if($remoteImgUrl){
									$wishProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
											'remote_main_img'=>$remoteImgUrl
									));
								}
								if(empty($remoteImgUrl)){
									throw new Exception("Variation SKU IMG FAILURE", WishProductAdd::WISH_UPLOAD_IMG_FAIL);
								}
								$variantData['main_image'] = $remoteImgUrl;
							}
						}
						$createProductVariantRequest->setAccount($accountId);
						$createProductVariantRequest->setUploadData($variantData);
						$response = $createProductVariantRequest->setRequest()->sendRequest()->getResponse();
						//MHelper::writefilelog("wish/wish-upload-log-".date("Y-m-dHis"), json_encode($response));
						if($createProductVariantRequest->getIfSuccess()){
							$wishProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
									'last_upload_time'=>$time,
									'upload_status'=>WishProductAdd::WISH_UPLOAD_SUCCESS,
									'upload_times'=>1+$variant['upload_times'],
									'last_upload_msg'=>'success',
									'main_image'	=>	isset($variantData['main_image']) ? $variantData['main_image'] : '',
									'wish_variant_product_id'	=>	isset($response->data->Variant->id) ? (string)$response->data->Variant->id : ''
							));
						}else{
							throw new Exception($createProductVariantRequest->getErrorMsg());
						}
					}catch (Exception $e){
						$uploadStatus = ($e->getCode() == WishProductAdd::WISH_UPLOAD_IMG_FAIL ? WishProductAdd::WISH_UPLOAD_IMG_FAIL : WishProductAdd::WISH_UPLOAD_FAIL);
						$uploadTimes = 1+$variant['upload_times'];
						/* if($uploadStatus == WishProductAdd::WISH_UPLOAD_IMG_FAIL){
							$uploadTimes = $variant['upload_times'];
						} */
						
						$wishProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
								'last_upload_time'=>$time,
								'upload_status'=>$uploadStatus,
								'upload_times'=>$uploadTimes,
								'last_upload_msg'=>$e->getMessage()
						));
					}
				}
			}
			// === End:上传子sku =====
	
			return true;
		}catch (Exception $e){
			$uploadStatus = ($e->getCode() == WishProductAdd::WISH_UPLOAD_IMG_FAIL ? WishProductAdd::WISH_UPLOAD_IMG_FAIL : WishProductAdd::WISH_UPLOAD_FAIL);
			$uploadTimes = 1+$data['upload_times'];
		
			$time = date("Y-m-d H:i:s");
			$this->updateProductAddInfoByPk($data['id'], array(
					'last_upload_time'=>$time,
					'upload_status'=>$uploadStatus,
					'upload_times'=>$uploadTimes,
					'last_upload_msg'=>$e->getMessage()
			));
		
			$this->setErrorMsg($e->getMessage());
			return false;
		}
	}
	/**
	 * @desc 获取刊登类型
	 * @param int $type
	 */
	public static function getListingType($type = ''){
		if($type != ''){
			switch ($type){
				case self::LISTING_TYPE_FIXEDPRICE :
					return Yii::t('wish_listing', 'FixedFrice');
					break;
				case self::LISTING_TYPE_VARIATION:
					return Yii::t('wish_listing', 'Variation');
					break;
			}
		}
		return array(
				self::LISTING_TYPE_FIXEDPRICE   => Yii::t('wish_listing', 'FixedFrice'),
				self::LISTING_TYPE_VARIATION    => Yii::t('wish_listing', 'Variation'),
		);
	}
	
	
	/**
	 * @desc 根据sku获取可刊登账号
	 * @param string $sku
	 */
	public function getAbleAccountsBySku($sku){
		//排除掉已刊登过当前SKU的账户列表
		$filterAccountIds = array();
		//去掉已发布或者已刊登限制 - 2015-12-11
		/* //获取已经发布过该SKU的账号id
		$listing = self::model('WishListing')->getProductListingBySkuGroupByAccountId($sku);
		if($listing){
			foreach ($listing as $list){
				$filterAccountIds[] = $list['account_id'];
			}
		}
		//获取已经刊登过该sku的账号id
		$listing = $this->getAddListingBySkuGroupByAccountId($sku);
		if($listing){
			foreach ($listing as $list){
				$filterAccountIds[] = $list['account_id'];
			}
		} */
		//获取账号列表，同时排除掉部分
		$accountList = self::model('WishAccount')->getAbleAccountListByFilterId($filterAccountIds);
		$accounAbles = array();
		foreach ($accountList as $account){
			$accounAbles[] = array(
									'id' => $account['id'],
									'short_name' => $account['account_name']
								);
		}
		return $accounAbles;
	}
	/**
	 * @desc 获取刊登过的某个sku列表（按账号聚合）
	 * @param unknown $sku
	 */
	public function getAddListingBySkuGroupByAccountId($sku){
		//return null;
		return $this->getDbConnection()->createCommand()
								->from(self::tableName())
								->group('account_id')
								->where('sku=:sku OR parent_sku=:parent_sku', array(':sku'=>$sku, ':parent_sku'=>$sku))
								->queryAll();
	}
	/**
	 * @desc 获取待传产品信息（部分）
	 * @param number $limit
	 */
	public function getPendingUploadProduct($limit = 10, $fields = "*"){
		$maxUploadTimes = 5;
		$findUploadStatus = self::WISH_UPLOAD_PENDING. ',' . self::WISH_UPLOAD_FAIL.','.self::WISH_UPLOAD_IMG_FAIL.','.self::WISH_UPLOAD_RUNNING;
		return $this->getDbConnection()->createCommand()
									->from(self::tableName())
									->select($fields)
									->where('upload_status in('. $findUploadStatus .') AND upload_times<'.$maxUploadTimes)
									->order('id desc')
									->limit($limit)
									->queryAll();
		
	}
	/**
	 * @desc 根据条件获取单条数据
	 * @param unknown $conditions
	 * @param string $param
	 * @return mixed
	 */
	public function getProductAddInfo($conditions, $param = null){
		return $this->getDbConnection()->createCommand()
								->from(self::tableName())
								->where($conditions, $param)
								->queryRow();
	}
	/**
	 * @desc 根据parent_sku获取相关信息
	 * @param unknown $parentSku
	 */
	public function getProductAddInfoByParentSku($parentSku){
		$conditions = 'parent_sku=:parent_sku';
		$param = array(':parent_sku'=>$parentSku);
		return $this->getProductAddInfo($conditions, $param);
	}
	
	/**
	 * @desc 根据主键id更新
	 * @param unknown $id
	 * @param unknown $data
	 * @return boolean
	 */
	public function updateProductAddInfoByPk($id, $data){
		if(empty($id) || empty($data)){
			return false;
		}
		return self::model()->updateByPk($id, $data);
	}
	/**
	 * @desc 根据主键id数组来删除数据
	 * @param array $ids
	 * @return boolean
	 */
	public function deleteProductAddInfoByIds($ids, $conditions = '', $param = null){
		if(!$ids) return false;
		if(!is_array($ids))
			$ids = array($ids);
		$condition = "id IN('".implode("','", $ids)."')";
		if($conditions)
			$condition .= " AND " . $conditions;
		
		return $this->getDbConnection()->createCommand()
								->delete(self::tableName(), $condition, $param);	
	}
	/**
	 * @desc 根据主产品id获取下面所有子产品信息，只能当前产品为多属性时
	 * @param unknown $mainProductId
	 */
	public function getSubProductByMainProductId($mainProductId, $is_multi = Product::PRODUCT_MULTIPLE_MAIN){
		$hasAttributes = self::model('AttributeMarketOmsMap')->getOmsAttrIdsByPlatAttrName(Platform::CODE_WISH, 0);
		if(!$hasAttributes) return null;
		$platformOwnAttributes = '';
		$platformOwnAttribute = array();
		$sizeAttributeId = 0;
		$colorAttributeId = 0;
		foreach ($hasAttributes as $val){
			$platformOwnAttribute[] = (int)$val['oms_attr_id'];
			if(strtoupper($val['platform_attr_name']) == 'SIZE'){
				$sizeAttributeId = (int)$val['oms_attr_id'];
			}elseif(strtoupper($val['platform_attr_name']) == 'COLOR'){
				$colorAttributeId = (int)$val['oms_attr_id'];
			}
		}
		$platformOwnAttributes = implode(',', $platformOwnAttribute);
		$fplatformOwnAttributes = '22,23,24';
		//获取到当前主产品对应下面所有的子产品sku和对应属性,并且与平台属性一一对应的
		$productSelectAttribute = new ProductSelectAttribute();
		//modified 2016-01-03 去除属性限制
		if($is_multi == Product::PRODUCT_MULTIPLE_MAIN)
			$attributeSkuList = $productSelectAttribute->getSelectedAttributeSKUListByMainProductId($mainProductId, 'attribute_id in('.$fplatformOwnAttributes.')');
		else 
			$attributeSkuList = $productSelectAttribute->getSelectedAttributeSKUListByProductId($mainProductId, 'attribute_id in('.$fplatformOwnAttributes.')');
		$subSku = array();
		$skuAttributes = array();
		$attributeValIds = array();
		$skuProductIds = array();
		$attributeList = array();
		//获取对应的属性名称
		$attributeList = self::model('ProductAttribute')->getAttributeListByIds($platformOwnAttribute);
		//判断是否有size
		$hasSizeAttribute = false;
		if($attributeSkuList){
			foreach ($attributeSkuList as $attribute){
				//抽离出子SKU
				$subSku[$attribute['sku']]['attribute'][$attribute['attribute_id']] = array('attribute_id'=>$attribute['attribute_id'], 'attribute_value_id'=>$attribute['attribute_value_id']);
				$subSku[$attribute['sku']]['product_id'] = $attribute['product_id'];
				$subSku[$attribute['sku']]['sku'] = $attribute['sku'];
				$skuProductIds[] = $attribute['product_id'];
				$skuAttributes[$attribute['attribute_id']] = $attribute['attribute_id'];
				//获取对应的属性名称
				//获取对应属性值名称
				$attributeValIds[] = $attribute['attribute_value_id'];
				if(!$hasSizeAttribute && $attribute['attribute_id'] == $sizeAttributeId){
					$hasSizeAttribute = true;
				}
			}
			//在无size属性下抽取一个非颜色的属性,替换掉size属性
			$replaceSizeAttributeId = 0;
			if(!$hasSizeAttribute){
				foreach ($skuAttributes as $val){
					if($val != $colorAttributeId){
						$replaceSizeAttributeId = $val;
						break;
					}
				}
			}
			//获取对应的属性名称
			//$attributeList = self::model('ProductAttribute')->getAttributeListByIds($skuAttributes);
			//获取对应属性值名称
			//获取对应的英文名称
			$attributeValList = self::model('ProductAttributeValue')->getAttributeValueListByIds($attributeValIds);
			$attributeValNamesList = array();
			if($attributeValList){
				foreach ($attributeValList as $val){
					$attributeValNamesList[] = $val['attribute_value_name'];
				}
			}
			//获取对应语言的属性值名称
			$attributeValLangList = self::model('ProductAttributeValueLang')->getAttributeValueLangs($attributeValNamesList);
			//获取子SKU信息
			$productInfoList = self::model('Product')->getProductInfoListByIds($skuProductIds);
			foreach ($productInfoList as $product){
				foreach ($subSku[$product['sku']]['attribute'] as $k=>$attribute){
					foreach ($attributeList as $attr){
						if($attr['id'] == $attribute['attribute_id']){
							$subSku[$product['sku']]['attribute'][$k]['attribute_name'] = $attr['attribute_name'];
							break;
						}
					}
					foreach ($attributeValList as $attrVal){
						if($attrVal['id'] == $attribute['attribute_value_id']){
							$attrivalname = isset($attributeValLangList[$attrVal['attribute_value_name']]) ? $attributeValLangList[$attrVal['attribute_value_name']]:$attrVal['attribute_value_name'];
							if(!$hasSizeAttribute && $attribute['attribute_id'] == $replaceSizeAttributeId){//填补size的空缺
								$subSku[$product['sku']]['attribute'][$sizeAttributeId]['attribute_value_name'] = $attrivalname;
							}else{
                                                                if( $attribute['attribute_id'] == 22  ){
                                                                    if($attrivalname == 'Silvery'){
                                                                        $attrivalname = 'Silver';
                                                                    } elseif ($attrivalname == 'Sliver') {
                                                                        $attrivalname = 'Silver';
                                                                    }
                                                                }
								$subSku[$product['sku']]['attribute'][$k]['attribute_value_name'] = $attrivalname;
							}
							break;
						}
					}
				}
				$subSku[$product['sku']]['skuInfo'] = $product;
				unset($product);
			}
		}
		return array('skuList'=>$subSku, 'attributeList'=>$attributeList);
	}
	/**
	 * @desc 获取销售价格相关
	 * @param unknown $productInfo
	 */
	public function getSalePrice($sku, $accountId = null, $shipCode = "", $shipWarehouseID = null) {
		//获取最优价格模板
		$params = array(
				'sku' => $sku,
				'platform_code' => Platform::CODE_WISH,
				'account_id' => $accountId,
		);
		$currency = self::PRODUCT_PUBLISH_CURRENCY;
		// $ruleModel = new ConditionsRulesMatch();
		// $ruleModel->setRuleClass(TemplateRulesBase::MATCH_PRICE_TEMPLATE);
		// $salePriceSchemeID = $ruleModel->runMatch($params);
		// if (empty($salePriceSchemeID) || !($salePriceScheme = SalePriceScheme::model()->getSalePriceSchemeByID($salePriceSchemeID))) {
		if (!($salePriceScheme = SalePriceScheme::model()->getSalePriceSchemeByPlatformCode(Platform::CODE_WISH))) {	
			$tplParam = array(
					'standard_profit_rate'  => 0.22,//2016-06-27 25%-->22%
					'lowest_profit_rate'    => 0.1,
					'floating_profit_rate'  => 0.05,
			);
		} else {
			$tplParam = array(
					'standard_profit_rate'  => $salePriceScheme['standard_profit_rate'],
					'lowest_profit_rate'    => $salePriceScheme['lowest_profit_rate'],
					'floating_profit_rate'  => $salePriceScheme['floating_profit_rate'],
			);
		}

		//计算卖价，获取描述
		$priceCal = new CurrencyCalculate();

		// if ($accountId == 15){
		// 	$priceCal->setDebug(1);	//wish新运费方式
		// }

		//设置参数值
		$priceCal->setProfitRate($tplParam['standard_profit_rate']);//设置利润率
		$priceCal->setCurrency($currency);//币种
		$priceCal->setPlatform(Platform::CODE_WISH);//设置销售平台
		$priceCal->setPayPlatform('wishpay');	//wish专用支付
		$priceCal->setSku($sku);//设置sku
		if ($shipWarehouseID){
			$priceCal->setWarehouseID($shipWarehouseID);	//设置仓库，涉及运费
		}		
		//$priceCal->setShipCode($shipCode);//设置运费code
		// $priceCal->setSalePrice($salePrice);

		$productCost = $priceCal->getProductCost();	//获取成本

		/* $data['shipPrice']  = $priceCal->getShippingCost();//获取运费
		$rateToCNY = $priceCal->getCurrencyRate();
		$data['shipPrice'] = ceil(($data['shipPrice']/$rateToCNY)*100)/100; */
		
		//(产品价格+运费)*美元对人民币汇率-成本-运费-(产品价格+运费)*美元对人民币汇率*0.15/(产品价格+运费)*美元对人民币汇率=25%						
		//(p+s)*r-c-s-(p+s)*r*0.15 = 0.25*(p+s)*r;
		//0.75(p+s)*r = c+s+(p+s)*r*0.15;
		//0.6(p+s)*r = c+s
		//p = (c+s)/(0.6*r)-s;
		//新规则计算运费 2016-04-08
		/*
		 * 成本在0-15元 运费是1美金
		* 成本在15-45元 运费是2美金
		* 成本在45元以上 运费是3美金
		* */
		if($productCost < 15){
			$shipFee = 1;
		}elseif($productCost < 45){
			$shipFee = 2;
		}else{
			$shipFee = 3;
		}

		// $priceCal->setShipingPrice($shipFee);

		$data = array();
		$data['salePrice']  = $priceCal->getSalePrice(true);//获取卖价
		$data['orisaleprice'] = $data['salePrice'];
		$data['errormsg'] 	= 	$priceCal->getErrorMessage();
		$data['other'] = $priceCal->profitCalculateFunc;		

		$data['shipPrice'] = $shipFee;
		if($data['salePrice'] > $data['shipPrice'])
			$data['salePrice'] -= $data['shipPrice'];
		//$data['salePrice'] = round($data['salePrice'], 0);
		
		$data['oriprofit']     = $priceCal->getProfit(true);
		$data['oriprofitRate'] = $priceCal->getProfitRate(true)*100 . '%';
		$data['oridesc']  		= $priceCal->getCalculateDescription();
		$data['salePrice'] = ceil($data['salePrice']);		

		$profitInfo = self::getProfitInfo($data['salePrice'], $sku, $currency, $shipFee, $shipWarehouseID, $accountId);
		if($profitInfo){
			$data['profit']     = $profitInfo['profit'];
			$data['profitRate'] = $profitInfo['profitRate'];
			$data['desc']  		= $profitInfo['desc'];
		}else{
			$data['profit']     = 0;
			$data['profitRate'] = '-';
			$data['desc']  		= 'fet profit error';
		}
		
		// MHelper::printvar($data);
		return $data;
	}


    /**
     * @desc 获取利润
     * 销量利润率 = (销售价-固定成本-销售价*(销售平台手续费比例+支付平台手续费比例))/销售价
     * ----> 销售价 = 固定成本/((1-(销售平台手续费比例+支付平台手续费比例))-利润率))
     * 固定成本 = 产品成本 + 运费成本 + 包装成本 + 包材成本
     * 利润率 = 销售价 *（1- 支付手续费率 - 订单损耗率 - 成交率）-（成本+运费+包材包装费）* 汇率  / 销售价 -
     */
    public function getProfitInfo($salePrice, $sku, $currency, $shipingPrice = 0, $shipWarehouseID = null, $accountID = 0){
    	$priceCal = new CurrencyCalculate();
    	$priceCal->setCurrency($currency);//币种
    	$priceCal->setPlatform(Platform::CODE_WISH);//设置销售平台
    	$priceCal->setSku($sku);//设置sku
    	$priceCal->setPayPlatform('wishpay');	//wish专用支付

		// if ($accountID == 15){
		// 	$priceCal->setDebug(1);	//调试wish运费入口
		// }

		if ($shipWarehouseID){
			$priceCal->setWarehouseID($shipWarehouseID);	//设置仓库，涉及运费
		}	    	
    	//把运费加到售价中，运费为0
    	$priceCal->setSalePrice($salePrice+$shipingPrice);
    	// $priceCal->setShipingPrice($shipingPrice);

/*    	//测试账号MT
    	if(isset($_REQUEST['bug']) || $accountID == 15){
    		$time1 = time();
	    	$profit = $priceCal->getProfit(true);
	    	$time2 = time();
	    	$profitRate = $priceCal->getProfitRate(true)*100 . '%';
    	}else{
    		$time1 = time();
	    	$profit = $priceCal->getProfit();
	    	$time2 = time();
	    	$profitRate = $priceCal->getProfitRate()*100 . '%';	
    	}*/

		$time1 = time();
    	$profit = $priceCal->getProfit(true);
    	$time2 = time();
    	$profitRate = $priceCal->getProfitRate(true)*100 . '%';    	



    	$time3 = time();
        $desc = '';
        if (isset($_REQUEST['bug'])) {
            $desc = $priceCal->getCalculateDescription();
        }
    	$ret = array(
    		0=>$profit, 1=>$profitRate,
    		'profit'=>$profit, 'profitRate'=>$profitRate, 'error_msg'=>$priceCal->getErrorMessage(), 'desc'=>$desc, 
    		"xx3"=>$priceCal->getShipParam(),
    		'xx4'=>$priceCal->profitCalculateFunc,
    			
    		'time'	=>	array(
    						$time1, $time2, $time3
    					)
    	);
        if (isset($_REQUEST['bug'])) {
            MHelper::printvar(json_encode($ret));
        }
    	return $ret;
    }	

	/**
	 * @DESC 
	 * @param unknown $imageUrl
	 * @return string|boolean
	 */
	public function uploadImageToServer($imageUrl, $accountID){
		$configs = ConfigFactory::getConfig('serverKeys');
		$config = $configs['image'];
		$domain = $config['domain'];
		
		
		$localpath = parse_url($imageUrl, PHP_URL_PATH);
		//判断OMS本地文件是否存在
		$param = array('path'=>$localpath);
		$api = Yii::app()->erpApi;
		$result = $api->setServer('oms')->setFunction('Products:Productimage:checkImageExist')->setRequest($param)->sendRequest()->getResponse();
		if( $api->getIfSuccess() ){
			if( !$result ){
				$this->setErrorMsg($localpath.' Not Exists.');
				return false;
			}
		}else{
			$this->setErrorMsg($api->getErrorMsg());
			return false;
		}
	
		$productImageAddModel = new ProductImageAdd();
		//上传图片到指定文件夹,返回路径
		$absolutePath = $productImageAddModel->saveTempImage($localpath);
		// ==== start ====
		//@todo 缩略图片800*800
		$filename = basename($absolutePath);
		$extension = strstr($filename, '.');
		$basename = str_replace($extension, '', $filename);
		$resizeDir = UPLOAD_DIR . Platform::CODE_WISH . '/';
		if(!is_dir($resizeDir)){
			@mkdir($resizeDir, 0777, true);
		}
		$resizeFilePath = $resizeDir . $basename . '_800x800' . $extension;
		$flag = Productimage::model()->img2thumb($absolutePath, $resizeFilePath, 800, 800);
		if (!$flag) {	
			$this->setErrorMsg('Thumb Image Error');
			return false;
		}
		// === end ===
		
		list($remoteName, $remotePath) = $productImageAddModel->getImageRemoteInfo($localpath, $accountID, Platform::CODE_WISH);
		$uploadResult = $productImageAddModel->uploadImageServer($resizeFilePath, $remoteName, $remotePath);
		unlink($absolutePath);
		if( $uploadResult != 1 ){
			$this->setErrorMsg(Yii::t('common', 'Upload Connect Error'));
		}else{
			return $remote_path = $domain.$remotePath.$remoteName;
		}
		return false;
	}
	
	/**
	 * @desc 获取远程图片
	 * @param unknown $imgName
	 * @param unknown $accountID
	 * @param unknown $sku
	 * @return boolean
	 */
	public function getRemoteImgPathByName($imgName, $accountID, $sku = null){
		if(empty($imgName)) return false;
		$imageName = basename($imgName);
		$pos = strrpos($imageName, "?");
		if($pos)
			$imageName = substr($imageName, 0, $pos);
		if(empty($sku)){
			$pos = strrpos($imageName, "-");
			if($pos === false){
				$pos = strrpos($imageName, ".");
			}
			$sku = substr($imageName, 0, $pos);
		}
		$productImageAddModel = new ProductImageAdd;		
		$imageNameList = array(
							$imageName
					);
		$platformCode = Platform::CODE_WISH;
		$siteId = null;
		$assistantImage = false;
		$moreParams = array(
							'width'=>'800', 'height'=>'800'
						);
		$response = $productImageAddModel->getSkuImageUpload($accountID, $sku, array_values($imageNameList), $platformCode, $siteId, $assistantImage, $moreParams);
		if(isset($_REQUEST['bug'])){
			MHelper::printvar($response, false);
		}
		if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs']) || count($imageNameList) != count($response['result']['imageInfoVOs']) ) {
			$this->setErrorMsg('Remote get Sku images failure');
			//$productImageAddModel->addSkuImageUpload($accountID, $sku, 0, $platformCode, $siteId);//发送图片上传请求
			return false;
		}
		return $response['result']['imageInfoVOs'][0]['remotePath'];
	}
	
	/**
	 * @desc 通过sku获取刊登记录(按上传时间排序)
	 * @param string $sku
	 */
	public function getRecordBySku($sku){
		return $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where('sku = "'.$sku.'"')
			->order('last_upload_time DESC')
			->queryAll();
	}
	/**
	 * @desc 添加待刊登任务
	 */
	public function productAdd($sku, $accountID){
		/**@ 1.获取需要的参数*/
		//产品信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);

		//刊登类型
		$listingType = isset($listingType) ? $listingType : self::LISTING_TYPE_FIXEDPRICE;

		/**@ 2.检测是否能添加*/
		//检测sku信息是否存在
		if( empty($skuInfo) ){
			return array(
				'status'    => 0,
				'message'   => Yii::t('common','SKU Does Not Exists.'),
			);
		}

		//检测待刊登列表里是否已存在
		$checkExists = $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where('sku = "'.$sku.'"')->andWhere('account_id = '.$accountID)
			->andWhere('status != '.self::WISH_UPLOAD_SUCCESS)
			->queryRow();
		if( !empty($checkExists) ){
			return array(
				'status'    => 0,
				'message'   => Yii::t('common','Record Exists.'),
			);
		}
		//检测是否存在在线广告
		$checkOnline = WishProduct::model()->getProductByParam(array(
			'sku'           => $sku,
			'account_id'    => $accountID,
			'status'        => 1
		));
		if( !empty($checkOnline) ){
			return array(
				'status'    => 0,
				'message'   => Yii::t('common','Listing Exists.'),
			);
		}

		/**@ 3.匹配分类*/
		//检测之前有无已刊登过的分类
		$listings = $this->getRecordBySku($sku);
		$categoryID = 0;
		foreach($listings as $listing){
			$categoryID = $listing['category_id'];
			$historyAddID = $listing['id'];
			break;
		}
		//
//		if( !$categoryID ){//之前没刊登过
//			//获取ebay的分类名
//			$keyWordsEbay = Product::model()->getPlatformCategoryBySku($sku, Platform::CODE_EBAY);
//			$categoryID = WishCategory::model()->getCategoryIDByKeyWords($keyWordsEbay);
//		}
//		if( !$categoryID ){
//			return array(
//				'status'    => 0,
//				'message'   => Yii::t('common','Can Not Match Category.'),
//			);
//		}
//		/**@ 4.计算价格*/
//		$finalPrice = 0;
//		//获取已刊登广告的卖价
//		$onlineProduct = LazadaProduct::model()->getProductByParam(array(
//			'sku'           => $sku,
//			'site_id'       => $site,
//			'status'        => 1,
//		));
//		if( !empty($onlineProduct) ){
//			$finalPrice = $onlineProduct[0]['price'];
//			if($onlineProduct[0]['sale_price'] > 0){
//				$salePrice = $onlineProduct[0]['sale_price'];
//			}
//		}
//
//		if( $finalPrice <= 0 ){
//			//获取待刊登列表的卖价
//			$addProduct = $this->dbConnection->createCommand()
//				->select('*')
//				->from(self::tableName())
//				->where('sku = "'.$sku.'"')
//				->andWhere('listing_type = '.$listingType)
//				->andWhere('site_id = '.$site)
//				->andWhere('status != '.self::UPLOAD_STATUS_SUCCESS)
//				->order('upload_time DESC')
//				->queryRow();
//			if( !empty($addProduct) ){
//				$finalPrice = $addProduct['price'];
//				if($addProduct['sale_price'] > 0){
//					$salePrice = $addProduct['sale_price'];
//				}
//			}
//		}
//		//TODO
//		$tplParam = array(
//			'scheme_name'           => '通用方案',
////             'standard_profit_rate'  => 0.25,
//			'lowest_profit_rate'    => 0.25,
//		);
//
//		$specDone = true;
//		if( $specDone ){//特殊处理
//			if( $finalPrice <= 0 ){
//				//获取建议卖价
//				$priceCal = new CurrencyCalculate();
//				$priceCal->setProfitRate($tplParam['lowest_profit_rate']);//设置利润率
//				$priceCal->setCurrency($currency);//币种
//				$priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
//				$priceCal->setSku($sku);//设置sku
//				$salePrice = $priceCal->getSalePrice();//获取卖价
//				if($accountID==1){
//					$rate = 0.5;
//				}else{
//					$rate = 0.5;
//				}
//				$price = $salePrice / $rate;
//			} else {
//				$price = $finalPrice;
//			}
//		}else{
//			if( $finalPrice <= 0 ){
//				//获取建议卖价
//				$priceCal = new CurrencyCalculate();
//				$priceCal->setProfitRate($tplParam['standard_profit_rate']);//设置利润率
//				$priceCal->setCurrency($currency);//币种
//				$priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
//				$priceCal->setSku($sku);//设置sku
//				$finalPrice = $priceCal->getSalePrice();//获取卖价
//			}
//
//			//获取最低卖价
//			$priceCal = new CurrencyCalculate();
//			$priceCal->setProfitRate($tplParam['lowest_profit_rate']);//设置利润率
//			$priceCal->setCurrency($currency);//币种
//			$priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
//			$priceCal->setSku($sku);//设置sku
//			$lowestPrice = $priceCal->getSalePrice();//获取卖价
//			if( $lowestPrice > $finalPrice ){
//				return array(
//					'status'    => 0,
//					'message'   => Yii::t('common','Can Not Get Sale Price Or Sale Price Is Lower Than Config'),
//				);
//			}
//		}
//
//		/**@ 5.获取属性值*/
//		if( isset($historyAddID) ){//有之前的刊登记录
//			$attributes = LazadaProductAddAttribute::model()->getAttributesByAddID($historyAddID);
//		}
//		/**@ 6.获取Title*/
//		$descriptionTemplate;//TODO
//		$name = $skuInfo['title'][LazadaSite::getLanguageBySite($site)];
//		$name = trim($descriptionTemplate['title_prefix'].' '.$name.' '.$descriptionTemplate['title_suffix']);
//
//		/**@ 7.插入刊登任务*/
//		//图片信息
//		$saveImage = LazadaProductImageAdd::model()->addProductImageBySku($sku,$accountID);
//		if( !$saveImage ){
//			return array(
//				'status'    => 0,
//				'message'   => Yii::t('common','No Image Found'),
//			);
//		}
//		//主信息
//		$addID = $this->saveRecord(array(
//			'account_id'        => $accountID,
//			'sku'               => $sku,
//			'site_id'           => $site,
//			'currency'          => $currency,
//			'listing_type'      => $listingType,
//			'title'             => $name,
//			'price'             => isset($salePrice) ? $price : $finalPrice,
//			'sale_price'        => isset($salePrice) ? $salePrice : 0,
//			'sale_price_start'  => isset($salePrice) ? date('Y-m-d H:i:s') : '',
//			'sale_price_end'    => isset($salePrice) ? date('Y-m-d H:i:s',strtotime('+10 year')) : '',
//			'brand'             => 'OEM',
//			'category_id'       => $categoryID,
//			'create_user_id'    => Yii::app()->user->id,
//			'create_time'       => date('Y-m-d H:i:s'),
//		));
//		if( $addID > 0 ){
//			//属性信息
//			if( $attributes ){
//				foreach($attributes as $attribute){
//					LazadaProductAddAttribute::model()->saveRecord($addID, $attribute['name'], $attribute['value']);
//				}
//			}
//		}
//		return array(
//			'status'    => 1,
//			'addID'     => $addID,
//		);
	}
	/**
	 * @desc  根据add_id 获取variants中数据
	 * @param unknown $productAddId
	 * @return NULL|multitype:unknown Ambigous <unknown, multitype:Ambigous <multitype:, multitype:unknown multitype:unknown  Ambigous <multitype:multitype: , multitype:multitype:Ambigous <> Ambigous <string, unknown>  > > multitype:unknown multitype:unknown  multitype:multitype:Ambigous <string, unknown> Ambigous <unknown>   mixed  >
	 */
	public function getProductVariantsByProductAddId($productAddId){
		$hasAttributes = self::model('AttributeMarketOmsMap')->getOmsAttrIdsByPlatAttrName(Platform::CODE_WISH, 0);
		if(!$hasAttributes) return null;
		$platformOwnAttributes = '';
		$platformOwnAttributeName = array();
		$attributeMap = array();
		$skuAttributes = array();
		foreach ($hasAttributes as $val){
			$platformOwnAttributeName[] = $val['platform_attr_name'];
			$attributeMap[$val['platform_attr_name']] = $val['oms_attr_id'];
			$skuAttributes[] = $val['oms_attr_id'];
		}
		//获取到当前主产品对应下面所有的子产品sku
		$productAddInfo = self::model()->getDbConnection()->createCommand()
								->from(self::tableName())
								->where('id=:id', array(':id'=>$productAddId))
								->queryRow();
		if(empty($productAddInfo)) return null;
		$subSku = array();
		//抽离子sku
		$variantList = self::model('WishProductVariantsAdd')->getWishProductVariantsAddListByAddId($productAddId);
		$hasUploadFailNum = 0;
		if($variantList){
			foreach ($variantList as $variant){
				$attribute = array();
				foreach ($platformOwnAttributeName as $name){
					$attribute[$attributeMap[$name]] = array('attribute_id'=>$attributeMap[$name],
							'attribute_value_name'=>isset($variant[$name])?$variant[$name]:''	);
				}
				$skuinfo = array(
						'sku'=>$variant['sku'],
						'attribute'=>$attribute,
						'product_id'=>$productAddId, //此id不同于product表中 的id
						'skuInfo'=>array(
								'inventory'	=>	$variant['inventory'],
								'size'	=>	$variant['size'],
								'color'	=>	$variant['color'],
								'price'	=>	$variant['price'],
								'product_cost'	=>	$variant['price'],
								'shipping'	=>	$variant['shipping'],
								'shipping_time'	=>	$variant['shipping_time'],
								'market_price'	=>	$variant['msrp'],
								'msrp'	=>	$variant['msrp'],
								'upload_status' => $variant['upload_status'],
								'upload_status_text'	=>	$this->getProductAddInfoUploadStatus($variant['upload_status'])
						)
				);
				if($variant['upload_status'] != self::WISH_UPLOAD_SUCCESS){
					++$hasUploadFailNum;
				}
				$subSku[$variant['sku']] = $skuinfo;
			}
		}
		
		
		$attributeList = array();
		$productAttributeModel = new ProductAttribute;
		//获取对应的属性名称
		$attributeList = $productAttributeModel->getAttributeListByIds($skuAttributes);
		foreach ($subSku as $product){
			foreach ($subSku[$product['sku']]['attribute'] as $k=>$attribute){
				foreach ($attributeList as $attr){
					if($attr['id'] == $attribute['attribute_id']){
						$subSku[$product['sku']]['attribute'][$k]['attribute_name'] = $attr['attribute_name'];
						break;
					}
				}
				
			}
			$subSku[$product['sku']] = $product;
			unset($product);
		}
		return array('skuList'=>$subSku, 'attributeList'=>$attributeList, 'hasUploadFailNum'=>$hasUploadFailNum);
	}
	
	public function rules() {
		return array(
				array('account_id,parent_sku,sku,name,description,tags,
						brand,main_image,extra_iamgs,product_is_multi,
						create_user_id,update_user_id,create_time,update_time', 'safe')
		);
	}
	/**
	 * @desc 保存数据
	 * @param unknown $datas
	 * @return boolean
	 */
	public function saveWishAddData($datas, $saveType = null, $addType = null){
		if(!is_array($datas)){
			return false;
		}
		if($saveType == null)
			$saveType = WishProductAdd::SAVE_TYPE_ALL;
		if($addType == null)
			$addType = WishProductAdd::ADD_TYPE_DEFAULT;
		//有待开启事物处理
		$time = date("Y-m-d H:i:s");
		$userId = Yii::app()->user->id;;
		$skuEncrypt = new encryptSku();
		foreach ($datas as $accountId => $data){
			$transaction = $this->getDbConnection()->beginTransaction();
			try{
				$parentSku = $data['parent_sku'];
				$addData = array(
								'account_id'       => $accountId,
								'parent_sku'       => $data['parent_sku'],
								'name'             => $data['subject'],
								'description'      => $data['detail'],
								'tags'             => $data['tags'],
								'brand'            => $data['brand'],
								'warehouse_id'     => $data['warehouse_id'],
								'main_image'       => $data['main_image'],
								'extra_images'     => $data['extra_images'],
								'product_is_multi' => $data['product_is_multi'],
								'create_user_id'   => $userId,
								'update_user_id'   => $userId,
								'create_time'      => $time,
								'update_time'      => $time,
								'remote_main_img'  => '',
								'remote_extra_img' => '',
								'add_type'         => $addType
							);
				if(isset($data['remote_main_img'])){
					$addData['remote_main_img'] = $data['remote_main_img'];
				}
				if(isset($data['remote_extra_img'])){
					$addData['remote_extra_img'] = $data['remote_extra_img'];
				}
				
                if(isset($data['upload_times'])){
					$addData['upload_times'] = $data['upload_times'];
				}
				if($saveType == WishProductAdd::SAVE_TYPE_ALL){
					$addData['upload_status'] = self::WISH_UPLOAD_PENDING;//重新置为待审核
					$addData['online_sku'] = $skuEncrypt->getEncryptSku($data['parent_sku']);
					//$addData['online_sku'] = $data['parent_sku'];//不需要加密
					$addData['upload_times'] = 0;
				}elseif($saveType == WishProductAdd::SAVE_TYPE_NO_MAIN_SKU){
					$addData['upload_status'] = self::WISH_UPLOAD_SUCCESS;//手动置为成功的，不需要再次上传到wish平台
					$addData['last_upload_msg']	=	Yii::t('wish_listing', 'Had upload wish platform');
				}
				if(isset($data['online_sku'])){
					$addData['online_sku'] = $data['online_sku'];
				}
				//检测是否已经存在该条数据
				$parentSkuInfo = array();
				//$parentSkuInfo = self::model()->find('account_id=:account_id AND parent_sku=:parent_sku', array(':account_id'=>$accountId, ':parent_sku'=>$parentSku));
				if(isset($data['add_id']) && $data['add_id'])
					$parentSkuInfo = $this->findByPk($data['add_id']);
				if($parentSkuInfo){
					if($parentSkuInfo->upload_status != self::WISH_UPLOAD_SUCCESS){
						unset($addData['create_time'], $addData['create_user_id'], $addData['add_type']);
						$res = $parentSkuInfo->updateByPk($parentSkuInfo->id, $addData);
						if(!$res) continue;
					}
					$addId = $parentSkuInfo->id;
				}else{
					$res = self::model()->getDbConnection()->createCommand()->insert(self::tableName(), $addData);
					if(!$res) continue;
					$addId = self::model()->getDbConnection()->getLastInsertID();
				}
				if($addId && $data['variants']){
					foreach ($data['variants'] as $variant){
						$sku = $variant['sku'];
						$variantData = array(
								'add_id'=>$addId,
								'parent_sku'=>$parentSku,
								'sku'	=>	$variant['sku'],
								'inventory'	=>	$variant['inventory'],
								'price'		=>	$variant['price'],
								'shipping'	=>	$variant['shipping'],
								'shipping_time'	=>	'7-35',
								'msrp'	=>	$variant['market_price'],
								'color'	=>	empty($variant['color'])?'':$variant['color'],
								'size'	=>	empty($variant['size'])?'':$variant['size'],
								'main_image'	=>	isset($variant['main_image'])?$variant['main_image']:'',
								'upload_status'	=>	self::WISH_UPLOAD_PENDING,
								'upload_times'	=> 0,
								'create_user_id'	=>	$userId,
								'update_user_id'	=>	$userId,
								'create_time'		=>	$time,
								'update_time'		=>	$time
						);
						if(count($data['variants']) == 1 && $variant['sku'] == $parentSku){
							$variantData['online_sku'] = $addData['online_sku'];
						}else{
							$variantData['online_sku'] = $skuEncrypt->getEncryptSku($variant['sku']);
							//$variantData['online_sku'] = $variant['sku'];//不需要加密
						}
						
						//检测是否存在该条数据
						$skuInfo = self::model('WishProductVariantsAdd')->find('add_id=:add_id AND sku=:sku', array(':add_id'=>$addId, ':sku'=>$sku));
						if($skuInfo){
							unset($variantData['create_time'], $variantData['create_user_id']);
							$res = $skuInfo->updateByPk($skuInfo->id, $variantData);
						}else{
							$res = self::model('WishProductVariantsAdd')->getDbConnection()->createCommand()->insert(self::model('WishProductVariantsAdd')->tableName(), $variantData);												
						}

						if (!$res){
							throw new Exception(Yii::t('wish_listing', 'The Sub SKU save failure'));
						}
					}
				}
				$transaction->commit();
			}catch (Exception $e){
				echo 'ocuur error1: '.$e->getMessage();
				$transaction->rollback();
				throw new Exception($e->getMessage());			
			}
		}
		return true;
	}
	
	/**
	 * @desc 批量添加产品分解操作
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param string $addType
	 * @return boolean
	 */
	public function productAddByBatch($sku, $accountID, $addType = null){
		if(is_null($addType)) $addType = WishProductAdd::ADD_TYPE_DEFAULT;
		try{
			//首先确认产品是否有权限刊登
			if(! Product::model()->checkCurrentUserAccessToSaleSKUNew($sku,$accountID,Platform::CODE_WISH)){
				$this->throwE("{$sku}:" . Yii::t('system', 'Not Access to Add the SKU') );
			}
			//产品表查找sku信息
			$skuInfo = Product::model()->getProductBySku($sku);
			if(empty($skuInfo)){
				$this->throwE("sku:{$sku}不存在！");
			}
			//确定下是否已经在当前账号刊登过
			//1、已刊登成功在线不再刊登
			$checkExists = WishListing::model()->find("account_id=:account_id AND sku=:sku AND enabled=:enabled", array(':account_id'=>$accountID, ':sku'=>$sku, ':enabled'=>1));
			if($checkExists){
				$this->throwE("已经上传过该SKU");
			}
			
			//2、已刊登下线的但在刊登记录里面未上传成功的等待上传的不再刊登
			$uploadStatus = array(
								self::WISH_UPLOAD_PENDING, self::WISH_UPLOAD_SUCCESS
							);
			$checkExists = WishProductAdd::model()->find("account_id=:account_id AND parent_sku=:sku AND upload_status in (". MHelper::simplode($uploadStatus) .")", array(":account_id"=>$accountID, ":sku"=>$sku));
			if($checkExists){
				$this->throwE("已经上传过该SKU2");
			}
			$wishVariationAddModel = new WishProductVariantsAdd();
			//查找下这个sku是否已经在其他账号刊登过，没有刊登过的不刊登
			//优先查找刊登记录表
			$addInfo = $this->getDbConnection()->createCommand()
								->from($this->tableName())
								->select("id, parent_sku as sku,name,description,tags,brand,product_is_multi")
								->where("account_id<>:account_id AND parent_sku=:sku", array(':account_id'=>$accountID, ':sku'=>$sku))
								->order("id DESC")
								->queryRow();
			
			$variationAddList = array();
			if(empty($addInfo)){
				//获取
				$wishListingModel = new WishListing;
				$wishListingExtendModel = new WishListingExtend();
				$addInfo = $wishListingModel->getDbConnection()->createCommand()
											->from($wishListingModel->tableName()." p")
											->join($wishListingExtendModel->tableName()." e", "e.listing_id=p.id")
											->select("p.id, p.sku, p.name, p.brand, p.tags, p.is_varation, e.description")
											->where("p.account_id<>:account_id AND p.sku=:sku", array(':account_id'=>$accountID, ':sku'=>$sku))
											->order("p.id DESC")
											->queryRow();
				if($addInfo && $addInfo['is_varation']){
					//获取子sku
					$wishListingVariationModel = new WishVariants();
					$variationAddList = $wishListingVariationModel->getDbConnection()->createCommand()
										->from($wishListingVariationModel->tableName())
										->select("sku, inventory, price, shipping, shipping_time, msrp, color, size")
										->where("listing_id='{$addInfo['id']}'")
										->queryAll();
				}
			}else{
				//if($addInfo['product_is_multi'] == self::PRODUCT_IS_VARIOUS)
				//获取子sku
				
				$variationAddList = $wishVariationAddModel->getDbConnection()->createCommand()
										->from($wishVariationAddModel->tableName())
										->select("sku, inventory, price, shipping, shipping_time, msrp, color, size")
										->where("add_id='{$addInfo['id']}'")
										->queryAll();
				
			}
			if(empty($addInfo)){
				$this->throwE("SKU:{$sku}还没有刊登过");
			}
			if(empty($variationAddList)){
				$this->throwE("SKU:{$sku}刊登数据异常");
			}
			//开始组装数据
			$config = ConfigFactory::getConfig('serverKeys');
			//sku图片加载
			$skuImg = array();
			$images = Product::model()->getImgList($sku, 'ft');
			$mainImg = "";
			$extraImg = "";
			foreach($images as $k=>$img){
				$filename = basename($img);
				if($filename == $sku.".jpg") continue;
				if(empty($mainImg)) {
					$mainImg = $config['oms']['host'].$img;
				}else{
					$skuImg[$k] = $config['oms']['host'].$img;
				}
			}
			$extraImg = implode("|", $skuImg);
			//主表数据
			$encryptSku = new encryptSku();
			$productIsMulti = $skuInfo['product_is_multi'];
			$addData = array(
							'account_id'	=>	$accountID,
							'online_sku'	=>	$encryptSku->getEncryptSku($sku),
							'parent_sku'	=>	$sku,
							'name'			=>	$addInfo['name'],
							'description'	=>	$addInfo['description'],
							'tags'			=>	$addInfo['tags'],
							'brand'			=>	$addInfo['brand'],
							'main_image'	=>	$mainImg,
							'extra_images'	=>	$extraImg,
							'product_is_multi'	=>	$productIsMulti,
							'upload_status'		=>	self::WISH_UPLOAD_PENDING,
							'create_user_id'	=>	intval(Yii::app()->user->id),
							'update_user_id'	=>	intval(Yii::app()->user->id),
							'create_time'		=>	date("Y-m-d H:i:s"),
							'update_time'		=>	date("Y-m-d H:i:s"),
							'add_type'			=>	$addType
						);
			try{
				$dbTransaction = $this->getDbConnection()->beginTransaction();
				$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $addData);
				if(!$res){
					$this->throwE("添加主表数据失败！");
				}
				$addID = $this->getDbConnection()->getLastInsertID();
				foreach ($variationAddList as $variation){
					$onlineSKU = "";
					if($variation['sku'] == $sku){
						$onlineSKU = $addData['online_sku'];
					}else{
						$onlineSKU = $encryptSku->getEncryptSku($variation['sku']);
					}
						
					$variationData = array(
						'add_id'		=> 	$addID,
						'parent_sku'	=> 	$sku,
						'sku'			=> 	$variation['sku'],
						'online_sku'	=>	$onlineSKU,
						'inventory'		=>	1000,
						'price'			=>	$variation['price'],
						'shipping'		=>	$variation['shipping'],
						'shipping_time'	=> 	$variation['shipping_time'],
						'msrp'			=>	$variation['msrp'],
						'color'			=>	$variation['color'],
						'size'			=>	$variation['size'],
						'main_image'	=>	'',
						'remote_main_img'	=>	'',
						'upload_status'		=>	self::WISH_UPLOAD_PENDING,
						'create_user_id'	=>	intval(Yii::app()->user->id),
						'update_user_id'	=>	intval(Yii::app()->user->id),
						'create_time'		=>	date("Y-m-d H:i:s"),
						'update_time'		=>	date("Y-m-d H:i:s"),
					);
					$wishVariationAddModel->getDbConnection()->createCommand()->insert($wishVariationAddModel->tableName(), $variationData);
				}
				$dbTransaction->commit();
			}catch (Exception $e){
				$dbTransaction->rollback();
				$this->throwE($e->getMessage());
			}
			return true;
		}catch(Exception $e){
			$this->setErrorMsg($e->getMessage());
			return false;
		}
	}
	
	private function throwE($message,$code=null){
		throw new Exception($message,$code);
	}
	
	/**
	 * @desc 获取上传状态文本
	 * @param unknown $uploadStatus
	 * @return string
	 */
	public function getProductAddInfoUploadStatus($uploadStatus){
		$str = '';
		$color = 'red';
		switch ($uploadStatus){
			case self::WISH_UPLOAD_PENDING:
				$color = "blue";
				$str = Yii::t('wish_listing', 'Wish pending upload');
				break;
			case self::WISH_UPLOAD_SUCCESS:
				$color = "green";
				$str = Yii::t('wish_listing', 'Wish upload success');
				break;
			case self::WISH_UPLOAD_FAIL:
				$color = "red";
				$str = Yii::t('wish_listing', 'Wish upload failure');
				break;
			case self::WISH_UPLOAD_IMG_FAIL:
				$color = "red";
				$str = Yii::t('wish_listing', 'Wish upload images failure');
				break;
		}
		return "<font color=".$color.">".$str."</font>";
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
	
	// ==================== START:待刊登列表相关 ==================

	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'=>'id',
		);
		
		$cdbCriteria = new CDbCriteria();
		$cdbCriteria->select = 't.*';
		if(isset($_REQUEST['upload_status']) && $_REQUEST['upload_status']){
			$uploadStatus = $_REQUEST['upload_status'];
			if($uploadStatus == self::WISH_UPLOAD_PENDING_MAPPING)
				$uploadStatus = self::WISH_UPLOAD_PENDING;
			$cdbCriteria->addCondition('upload_status='.$uploadStatus);
		}
		
		//查找子sku的状态时
		$variantCon = $variantParam = array();
		if(isset($_REQUEST['sub_upload_status']) && $_REQUEST['sub_upload_status']){
			$subuploadStatus = $_REQUEST['sub_upload_status'];
			if($subuploadStatus == self::WISH_UPLOAD_PENDING_MAPPING)
				$subuploadStatus = self::WISH_UPLOAD_PENDING;
			$variantCon[] = 'upload_status=:upload_status';
			$variantParam[':upload_status'] = $subuploadStatus;
		}
		if(isset($_REQUEST['parent_sku']) && $_REQUEST['parent_sku']){
			$variantCon[] = 'parent_sku=:parent_sku';
			$variantParam[':parent_sku'] = $_REQUEST['parent_sku'];
		}
		if(isset($_REQUEST['sub_sku']) && $_REQUEST['sub_sku']){
			$variantCon[] = 'sku=:sku';
			$variantParam[':sku'] = $_REQUEST['sub_sku'];
		}
		if(isset($_REQUEST['create_user_id']) && $_REQUEST['create_user_id']){
			$create_user_id =  $_REQUEST['create_user_id'];
			$cdbCriteria->addCondition('create_user_id='.$create_user_id);
		}
		if(isset($variantParam[':sku']) || isset($variantParam[':upload_status'])){
			$wishVariantsModel = new WishProductVariantsAdd;
			$variantCons = implode(" AND ", $variantCon);
			$variantList = $wishVariantsModel->getDbConnection()->createCommand()->from($wishVariantsModel->tableName())->where($variantCons, $variantParam)->group('add_id')->select('add_id')->queryColumn();
			if($variantList){
				$cdbCriteria->addInCondition('id', $variantList);
			}else{
				$cdbCriteria->addCondition('1=0 AND id=-1');
			}
		}

        //销售人员只能看指定分配的账号数据
        $accountIdArr = array();
        if(isset(Yii::app()->user->id)){
            $accountIdArr = WishAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.Yii::app()->user->id);
            if($accountIdArr){
                $ids = implode(',', $accountIdArr);
                $cdbCriteria->addCondition('account_id in('.$ids.')');
            }
        }

		$dataProvider = parent::search($this, $sort, '', $cdbCriteria);
		$data = $this->addtion($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	public function addtion($datas){
		if(empty($datas)) return $datas;
		$accounts = $this->getWishAccountPairs();
		$wishProductAddVariants = new WishProductVariantsAdd;
		foreach ($datas as $key=>$data){
			$datas[$key]->upload_times = $data['upload_times']>4?"<font color='red'><b>{$data['upload_times']}</b></font>":$data['upload_times'];
			$datas[$key]->account_name = isset($accounts[$data->account_id]) ? $accounts[$data->account_id] : '';
			$datas[$key]->upload_status_text = $this->getProductAddInfoUploadStatus($data['upload_status']);
			$datas[$key]->add_type = $this->getProductAddTypeOptions($data['add_type']);
			if($data['wish_product_id']){
				$datas[$key]->last_upload_msg = "<a href='https://www.wish.com/c/{$data['wish_product_id']}' target='__blank'>{$data['wish_product_id']}</a>";
			}
			 
			$datas[$key]->detail = array();
			//获取子sku
			$variantList = $this->getWishVariantAddList($data['id']);
			$varhasPendinguploadStatus = false;
			if($variantList){
				foreach ($variantList as $variant){
					$variant['upload_status_text'] =  $this->getProductAddInfoUploadStatus($variant['upload_status']);
					$variant['subsku'] = $variant['sku'];
					$variant['upload_times'] = $variant['upload_times']>9?"<font color='red'>{$variant['upload_times']}</font>":$variant['upload_times'];
					$variant['prop'] = (empty($variant['size']) ? "" : Yii::t('wish_listing', 'Size') . ":" . $variant['size'] . "<br/>") . (empty($variant['color'])?"" : Yii::t('wish_listing', 'Color') . ":" . $variant['color']);
					$datas[$key]->detail[] = $variant;
					if($variant['upload_status'] != self::WISH_UPLOAD_SUCCESS)
						$varhasPendinguploadStatus = true;
				}
			}
			$datas[$key]->visiupload = ($data['upload_status'] != self::WISH_UPLOAD_SUCCESS || $varhasPendinguploadStatus);
		}
		return $datas;
	}
	
	public function getWishVariantAddList($addId){
		$variantCon = $variantParam = array();
		$variantCon[] = 'add_id=:add_id';
		$variantParam[':add_id'] = $addId; 
		if(isset($_REQUEST['sub_upload_status']) && $_REQUEST['sub_upload_status']){
			$subuploadStatus = $_REQUEST['sub_upload_status'];
			if($subuploadStatus == self::WISH_UPLOAD_PENDING_MAPPING)
				$subuploadStatus = self::WISH_UPLOAD_PENDING;
			$variantCon[] = 'upload_status=:upload_status';
			$variantParam[':upload_status'] = $subuploadStatus;
		}
		if(isset($_REQUEST['parent_sku']) && $_REQUEST['parent_sku']){
			$variantCon[] = 'parent_sku=:parent_sku';
			$variantParam[':parent_sku'] = $_REQUEST['parent_sku'];
		}
		if(isset($_REQUEST['sub_sku']) && $_REQUEST['sub_sku']){
			$variantCon[] = 'sku=:sku';
			$variantParam[':sku'] = $_REQUEST['sub_sku'];
		}
		$wishVariantsModel = new WishProductVariantsAdd;
		$variantCons = implode(" AND ", $variantCon);
		$variantList = $wishVariantsModel->getDbConnection()
									->createCommand()
									->from($wishVariantsModel->tableName())
									->where($variantCons, $variantParam)
									->queryAll();
		return $variantList;
	}
	/**
	 * @desc 获取账号
	 */
	public function getWishAccountPairs(){
    	if(self::$wishAccountPairs == null){
            $list = array();
        	$ret = self::model('WishAccount')->getIdNamePairs();
            if(isset(Yii::app()->user->id)){
                $accountIdArr = WishAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.Yii::app()->user->id);
            }     
            
            if ($ret){
                //如果有账号限制
                if ($accountIdArr){
                    foreach($ret as $key => $val){
                        if (in_array($key,$accountIdArr)){
                            $list[$key] = $val;
                        }
                    }
                }else{
                    $list = $ret;
                }
            }
            self::$wishAccountPairs = $list;
        }
    	return self::$wishAccountPairs;
	}
	/**
	 * @desc 获取刊登方式
	 * @param string $addType
	 * @return multitype:string |string
	 */
	public function getProductAddTypeOptions($addType = null){
		$addTypeOptions = array(
								self::ADD_TYPE_DEFAULT	=>	'默认',
								self::ADD_TYPE_BATCH	=>	'批量',
								self::ADD_TYPE_PRE		=>	'预刊登',
								self::ADD_TYPE_COPY		=>	'复制刊登',
						);
		if(is_null($addType)) return $addTypeOptions;
		return isset($addTypeOptions[$addType]) ? $addTypeOptions[$addType] : '';
	}
	/**
	 * @desc 获取上传状态
	 * @return multitype:NULL Ambigous <string, string, unknown>
	 */
	public function  getUploadStatusOptions(){
		return array(
					//self::WISH_UPLOAD_PENDING=> Yii::t('wish_listing', 'Wish pending upload'),
					self::WISH_UPLOAD_PENDING_MAPPING=> Yii::t('wish_listing', 'Wish pending upload'),
					self::WISH_UPLOAD_IMG_FAIL=>Yii::t('wish_listing', 'Wish upload images failure'),
					self::WISH_UPLOAD_SUCCESS=> Yii::t('wish_listing', 'Wish upload success'),
					self::WISH_UPLOAD_FAIL=>Yii::t('wish_listing', 'Wish upload failure')
				);
	}
	/**
	 * @desc 获取操作文本
	 * @param unknown $addId
	 * @param unknown $hasUpload
	 */
	public function getOperationText($addId, $hasUpload){
		$url = Yii::app()->createUrl("/wish/wishproductadd/update", array("add_id" => $addId));
		$options = array(
				'target'    => 'navTab',
				'class'     =>'btnEdit',
				'rel' => 'page366'
		);
		$_options = '';
		foreach ($options as $key=>$val){
			$_options .= $key . '="'.$val.'"';
		}
		$html = '<a href="'.$url.'" ' . $_options . '>'. Yii::t('wish_listing', 'Edit Publish Info') .'</a>';
		if($hasUpload){
			$html .= "&nbsp;&nbsp;&nbsp;&nbsp;";
			$html .= "";
		}
	}
	/**
	 * @desc 获取创建人列表
	 * @return multitype:
	 */
	public function getCreateUserOptions(){
		return User::model()->getWishUserList(false, true);
		//return UebModel::model('user')  
        //                ->queryPairs('id,user_full_name', "department_id in(15, 37) and user_status=1");   //wish部门
	}
	/**
	 * @desc 搜索筛选栏定义
	 * @return multitype:multitype:string  multitype:string multitype:NULL Ambigous <string, string, unknown>   multitype:string NULL
	 */
	public function filterOptions(){
		return array(
				array(
						'name'		=>	'parent_sku',
						'search'	=>	'=',
						'type'		=>	'text',
						
				),
				array(
						'name'		=>	'account_id',
						'search'	=>	'=',
						'type'		=>	'dropDownList',
						'data'		=>	$this->getWishAccountPairs()
				),
				array(
						'name'		=>	'upload_status',
						'label'		=>	Yii::t('wish_listing', 'Main SKU Upload Status'),
						'search'	=>	'=',
						'type'		=>	'dropDownList',
						'data'		=>	$this->getUploadStatusOptions(),
						//'value'     => self::WISH_UPLOAD_PENDING_MAPPING,
						'rel'		=>	true,
				),
				array(
						'name'		=>	'create_user_id',
						'search'	=>	'=',
						'type'		=>	'dropDownList',
						'data'		=>	$this->getCreateUserOptions(),
						'rel'		=>	true,
				),
				array(
						'name'		=>	'sub_upload_status',
						'label'		=>	Yii::t('wish_listing', 'Upload status'),
						'search'	=>	'=',
						'type'		=>	'dropDownList',
						'data'		=>	$this->getUploadStatusOptions(),
						'rel'		=>	true
				),
				array(
						'name'		=>	'sub_sku',
						'label'		=>	Yii::t('wish_listing', 'Sub sku'),
						'search'	=>	'=',
						'type'		=>	'text',
						'rel'		=>	true
				),
				
				array(
						'name'		=>	'last_upload_time',
						//'label'		=>	Yii::t('wish_listing', 'Sub sku'),
						'search'	=>	'RANGE',
						'type'		=>	'text',
						'htmlOptions' => array(
											'size' => 4,
											'class'=>'date',
											'datefmt'=>'yyyy-MM-dd HH:mm:ss',
											'style'	=>	'width:80px;'
										),
						//'rel'		=>	true
				),
				//create_time
				array(
						'name'		=>	'create_time',
						//'label'		=>	Yii::t('wish_listing', 'Sub sku'),
						'search'	=>	'RANGE',
						'type'		=>	'text',
						'htmlOptions' => array(
								'size' => 4,
								'class'=>'date',
								'datefmt'=>'yyyy-MM-dd HH:mm:ss',
								'style'	=>	'width:80px;'
						),
						//'rel'		=>	true
				),
				array(
						'name'		=>	'add_type',
						'label'		=>	Yii::t('wish_listing', 'Add Type'),
						'search'	=>	'=',
						'type'		=>	'dropDownList',
						'data'		=>	$this->getProductAddTypeOptions(),
						//'rel'		=>	true
				),
				
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels(){
		return array(
				'account_id'=>Yii::t('wish_listing', 'Account Name'),
				'parent_sku'=>Yii::t('wish_listing', 'Parent Sku'),
				'id'	=>	Yii::t('system', 'NO.'),
				'account_name'	=>	Yii::t('wish_listing', 'Account Name'),
				'name'		=>	Yii::t('system', 'Title'),
				'upload_times'	=>	Yii::t('wish_listing', 'Upload times'),
				'last_upload_time'	=>	Yii::t('wish_listing', 'Last upload time'),
				'upload_status'		=>	Yii::t('wish_listing', 'Upload status'),//Upload Status Text
				'upload_status_text'		=>	Yii::t('wish_listing', 'Upload status'),
				'main_sku_upload_status'	=>	Yii::t('wish_listing', 'Main SKU Upload Status'),
				'last_upload_msg'	=>	Yii::t('wish_listing', 'Last upload message'),
				'price'				=>	Yii::t('wish_listing', 'Price'),
				'create_user_id'	=>	Yii::t('wish_listing', 'Create user id'),
				'subsku'			=>	Yii::t('wish_listing', 'Sub sku'),
				'online_sku'		=>	Yii::t('wish_listing', 'Seller Sku'),
				'size'				=>	Yii::t('wish_listing', 'Size'),
				'color'				=>	Yii::t('wish_listing', 'Color'),
				'update_time'		=>	Yii::t('wish_listing', 'Last modify time'),
				'create_time'		=>	Yii::t('wish_listing', 'Create time'),
				'prop'				=>	Yii::t('wish_listing', 'Sale Property'),
				'add_type'			=>	Yii::t('wish_listing', 'Add Type'),
			);
	}
	
	
	// ==================== END: 待刊登列表相关 ===================
	

	/**
	 * @desc 根据条件获取多条数据
	 * @param unknown $fields
	 * @param unknown $conditions
	 * @param string $param
	 * @return mixed
	 */
	public function getProductAddInfoAll($fields, $conditions, $param = null){
		return $this->getDbConnection()->createCommand()
								->select($fields)
								->from(self::tableName())
								->where($conditions, $param)
								->queryAll();
	}


    /**
     * @param string $fields
     * @param string $where
     * @param string $order
     * @return mixed
     */
    public function getAllByCondition($fields='*', $where='1',$order='')
    {
        $sql = "SELECT {$fields} FROM ".$this->tableName()." WHERE {$where} ";
        $cmd = $this->dbConnection->createCommand($sql);
        return $cmd->queryAll();
    }



    /**
	 * @desc 保存刊登数据
	 * @param array $param
	 */
	public function saveRecord($param){
		$flag = $this->dbConnection->createCommand()->insert(self::tableName(), $param);
		if( $flag ){
			return $this->dbConnection->getLastInsertID();
		}else{
			return false;
		}
	}
}