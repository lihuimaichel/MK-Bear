<?php
class JoomProductAdd extends JoomModel{
	const LISTING_TYPE_FIXEDPRICE = 2;//一口价
	const LISTING_TYPE_VARIATION = 3;//多属性
	
	const PRODUCT_IS_NORMAL = 0;//基本
	const PRODUCT_IS_SINGLE = 1;//多属性单品
	const PRODUCT_IS_VARIOUS = 2;//多属性组合产品
	
	const JOOM_UPLOAD_PENDING = 0;//待上传
	const JOOM_UPLOAD_PENDING_MAPPING = -1;//映射到待上传
	const JOOM_UPLOAD_SUCCESS = 1;//上传成功
	const JOOM_UPLOAD_FAIL = 2;//上传失败
	const JOOM_UPLOAD_IMG_FAIL = 3;//图片上传失败
	
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
	
	//joom物流类型数组
	public static $logisticsType = array(
			'ghxb_gyhl'		=>	'国洋荷兰小包挂号',
			'cm_gyhl'		=>	'国洋荷兰小包平邮',
			'cm_gyrd_hk'	=>	'国洋瑞典香港小包平邮',
			'ghxb_gyrd_hk'	=>	'国洋瑞典香港小包挂号',
			'ghxb_joom'		=>	'Joom邮挂号',
			'cm_joom'		=>	'joom邮小包',
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
	public static $joomAccountPairs;

	public function tableName(){
		return 'ueb_joom_product_add';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	
	/**
	 * @desc 上传单个产品及其下面的子产品
	 * @param unknown $addId
	 * @throws Exception
	 */
	public function uploadProduct($addId){
		$data = $this->getProductAddInfo("id=:id", array(':id'=>$addId));
		if(empty($data)){
			$this->setErrorMsg(Yii::t('joom_listing', 'NO main sku'));
			return false;
		}
		$accountId = $data['account_id'];
		$createProductRequest = new CreateProductRequest;
		
		//获取子sku列表
		$joomProductVariantsAddModel = new JoomProductVariantsAdd;
		//取消次数限制
		$pendingUploadVariants = $joomProductVariantsAddModel->getPendingUploadVariantsByAddId($data['id'], "v.*", 0);
		if(empty($pendingUploadVariants)){
			//$joomLog->setFailure($logId, Yii::t('joom_listing', 'No sku and sub sku to upload'));
			$this->setErrorMsg(Yii::t('joom_listing', 'No sku and sub sku to upload'));
			return false;
		}

		//验证最小利润率
		$platformCode = Platform::CODE_JOOM;
		$currency = self::PRODUCT_PUBLISH_CURRENCY;
		foreach ($pendingUploadVariants as $variantVal) {
			$info = Product::model()->checkProfitRate($currency, $platformCode, $variantVal['sku'], $variantVal['price']);
			if(!$info){
				$this->setErrorMsg($variantVal['sku'].' Profit is less than the minimum set profit');
				return false;
				break;
			}
		}


		try{
			$time = date("Y-m-d H:i:s");
			//只有主产品没有上传成功的情况下才会从子sku里面抽离
			if($data['upload_status'] != self::JOOM_UPLOAD_SUCCESS){
				if(count($pendingUploadVariants) > 1){
					//虚假变种
					$variant = array(
							'online_sku'	=>	$data['online_sku'],
							'inventory'		=>	1000,
							'price'			=>	109,
							'shipping'		=>	0,
							'size'			=>	'',
							'color'			=>	'',
							'msrp'			=>	'',
					);
				}else{
					$variant = array_shift($pendingUploadVariants);
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
				/* if($variant['main_image']){
					if($variant['remote_main_img']){
						$uploadData['main_image'] = $variant['remote_main_img'];
					}elseif($variant['main_image']){
						//$remoteImgUrl = $this->uploadImageToServer($variant['main_image'], $accountId);
						$remoteImgUrl = $this->getRemoteImgPathByName($variant['main_image'], $accountId);
						if(!$remoteImgUrl){
							throw new Exception($this->getErrorMsg());
						}
						$joomProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
									'remote_main_img'=>$remoteImgUrl
							));
						$uploadData['main_image'] = $remoteImgUrl;
					}
				} */


				if($data['remote_main_img']){
					//if(empty($uploadData['main_image'])){
						$uploadData['main_image'] = $data['remote_main_img'];
					//}
				}elseif($data['main_image']){
					$remoteImgUrl = "";
					if(!$remoteImgUrl){
						$remoteImgUrl = $this->getRemoteImgPathByName($data['main_image'], $accountId);
						if(!$remoteImgUrl){
							throw new Exception($data['parent_sku'].":".$this->getErrorMsg(), JoomProductAdd::JOOM_UPLOAD_IMG_FAIL);
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
						$indexer = 0;
						foreach ($extra_images as $img){
						    if ($indexer > 19) {
						        break;
                            }
                            $indexer++;
							//$remoteImgUrl = $this->uploadImageToServer($img, $accountId);
							$remoteImgUrl = $this->getRemoteImgPathByName($img, $accountId);
							if(!$remoteImgUrl){
								throw new Exception($this->getErrorMsg());
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
				/* if(isset($uploadData['color']) || isset($uploadData['size']))
					$uploadData['parent_sku'] = $data['online_sku']; */
				$createProductRequest->setAccount($accountId);
				$createProductRequest->setUploadData($uploadData);
				$response = $createProductRequest->setRequest()->sendRequest()->getResponse();
				$responseCode = -1;
				if($createProductRequest->getIfSuccess() || $responseCode == 0){
					$res = $this->updateProductAddInfoByPk($data['id'], array(
							'last_upload_time'=>$time,
							'upload_status'=>JoomProductAdd::JOOM_UPLOAD_SUCCESS,
							'upload_times'=>1+$data['upload_times'],
							'last_upload_msg'=>'success'
					));
					//更新子表第一个
					if(isset($variant['id']) && $variant['id'] > 0){
						$joomProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
								'last_upload_time'=>$time,
								'upload_status'=>JoomProductAdd::JOOM_UPLOAD_SUCCESS,
								'upload_times'=>1+$variant['upload_times'],
								'last_upload_msg'=>'success'
						));
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
						if($variant['main_image'] && $variant['remote_main_img']){
							$variantData['main_image'] = $variant['remote_main_img'];
						}elseif($variant['main_image']){
							//$remoteImgUrl = $this->uploadImageToServer($variant['main_image'], $accountId);
							$remoteImgUrl = $this->getRemoteImgPathByName($variant['main_image'], $accountId);
							if(!$remoteImgUrl){
								throw new Exception($this->getErrorMsg().':'.$variant['main_image'], JoomProductAdd::JOOM_UPLOAD_IMG_FAIL);
							}
							$joomProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
									'remote_main_img'=>$remoteImgUrl
							));
							$variantData['main_image'] = $remoteImgUrl;
						}
						$createProductVariantRequest->setAccount($accountId);
						$createProductVariantRequest->setUploadData($variantData);
						$response = $createProductVariantRequest->setRequest()->sendRequest()->getResponse();
						if($createProductVariantRequest->getIfSuccess()){
							$joomProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
									'last_upload_time'=>$time,
									'upload_status'=>JoomProductAdd::JOOM_UPLOAD_SUCCESS,
									'upload_times'=>1+$variant['upload_times'],
									'last_upload_msg'=>'success'
							));
						}else{
							throw new Exception($createProductVariantRequest->getErrorMsg());
						}
					}catch (Exception $e){
						$uploadStatus = ($e->getCode() == JoomProductAdd::JOOM_UPLOAD_IMG_FAIL ? JoomProductAdd::JOOM_UPLOAD_IMG_FAIL : JoomProductAdd::JOOM_UPLOAD_FAIL);
						$uploadTimes = 1+$variant['upload_times'];
						/* if($uploadStatus == WishProductAdd::WISH_UPLOAD_IMG_FAIL){
						 $uploadTimes = $variant['upload_times'];
						} */
						$joomProductVariantsAddModel->updateProductVariantAddInfoByPk($variant['id'], array(
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
			$uploadStatus = ($e->getCode() == JoomProductAdd::JOOM_UPLOAD_IMG_FAIL ? JoomProductAdd::JOOM_UPLOAD_IMG_FAIL : JoomProductAdd::JOOM_UPLOAD_FAIL);
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
					return Yii::t('joom_listing', 'FixedFrice');
					break;
				case self::LISTING_TYPE_VARIATION:
					return Yii::t('joom_listing', 'Variation');
					break;
			}
		}
		return array(
				self::LISTING_TYPE_FIXEDPRICE   => Yii::t('joom_listing', 'FixedFrice'),
				self::LISTING_TYPE_VARIATION    => Yii::t('joom_listing', 'Variation'),
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
		$listing = self::model('JoomListing')->getProductListingBySkuGroupByAccountId($sku);
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
		$accountList = self::model('JoomAccount')->getAbleAccountListByFilterId($filterAccountIds);
		$accounAbles = array();

		$userID = isset(Yii::app()->user->id)?Yii::app()->user->id:'';
		if(!$userID){
			echo $this->failureJson(array('message' => '登录状态失效，请重新登录'));
			Yii::app()->end();
		}
		//通过userid取出对应的账号
		$userAccount = JoomAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.$userID);

		foreach ($accountList as $account){
			if($userAccount && !in_array($account['id'], $userAccount)){
				continue;
			}
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

	public function getAllPendingUploadProduct($limit = 10,  $fields = "*", $account_id=0, $addId = 0, $addType = null)
    {
        $findUploadStatus = self::JOOM_UPLOAD_PENDING. ',' . self::JOOM_UPLOAD_FAIL . ',' . self::JOOM_UPLOAD_IMG_FAIL;
        $maxUploadTimes = 10;

        if($account_id>0){
            $where_account_id = " and a.account_id ='{$account_id}'";
        } else {
            $where_account_id = '';
        }
        if($addId){
            $where_account_id .= " and a.id='{$addId}'";
        }
        if (isset($_REQUEST['create_user_id']) && $_REQUEST['create_user_id']) {
            $where_account_id .= " and a.create_user_id='{$_REQUEST['create_user_id']}'";
        }

        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName() . ' AS a')
            ->select('a.id, a.account_id')
            ->leftJoin(JoomProductVariantsAdd::model()->tableName() . ' AS v', 'a.id = v.add_id')
            ->where('v.upload_status in(' . $findUploadStatus . ') AND a.upload_times<' . $maxUploadTimes .
                $where_account_id)
            ->andWhere("tags != ''");
        if (null !== $addType) {
            $queryBuilder->andWhere("add_type='" . $addType . "'");
        }
        $queryBuilder->order('id asc')->limit($limit);
        $queryBuilder->group('a.id');

        return $queryBuilder->queryAll();


    }

	/**
	 * @desc 获取待传产品信息（部分）
	 * @param number $limit
	 */
	public function getPendingUploadProduct($limit = 10, $fields = "*", $account_id=0, $addId = 0, $addType = null){
            if($account_id>0){
                $where_account_id = " and account_id ='{$account_id}'";
            } else {
                $where_account_id = '';
            }
            if($addId){
            	$where_account_id .= " and id='{$addId}'";
            }
            if (isset($_REQUEST['create_user_id']) && $_REQUEST['create_user_id']) {
            	$where_account_id .= " and create_user_id='{$_REQUEST['create_user_id']}'";
            }

		$maxUploadTimes = 10;
		$findUploadStatus = self::JOOM_UPLOAD_PENDING. ',' . self::JOOM_UPLOAD_FAIL . ',' . self::JOOM_UPLOAD_IMG_FAIL;
		//$findUploadStatus = self::JOOM_UPLOAD_PENDING;

		return $this->getDbConnection()->createCommand()
									->from(self::tableName())
									->select($fields)
									->where('upload_status in('. $findUploadStatus .') AND upload_times<'.$maxUploadTimes . $where_account_id)
									->andWhere("tags != ''")
									->andWhere(is_null($addType) ? '1' : "add_type='".$addType."'")
									->order('id asc')
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
		$hasAttributes = self::model('AttributeMarketOmsMap')->getOmsAttrIdsByPlatAttrName(Platform::CODE_JOOM, 0);
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
		//获取到当前主产品对应下面所有的子产品sku和对应属性,并且与平台属性一一对应的
		$productSelectAttribute = new ProductSelectAttribute();
		//modified 2016-01-03 去除属性限制
		if($is_multi == Product::PRODUCT_MULTIPLE_MAIN)
			$attributeSkuList = $productSelectAttribute->getSelectedAttributeSKUListByMainProductId($mainProductId/* , 'attribute_id in('.$platformOwnAttributes.')' */);
		else
			$attributeSkuList = $productSelectAttribute->getSelectedAttributeSKUListByProductId($mainProductId/* , 'attribute_id in('.$platformOwnAttributes.')' */);
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
     * @param $productCost
     * @return float|int
     */
	public function productCostToPublishCurrency($productCost) {
        $currency = self::PRODUCT_PUBLISH_CURRENCY;
        //产品成本转换成美金
        $productCost = $productCost / CurrencyRate::model()->getRateToCny($currency);
        $productCost = round($productCost,2);
        return $productCost;
    }

    /**
     * @param $sku
     * @param $profitRate
     * @param $shipCode
     * @param null $salePrice
     * @param int $shippingPrice
     * @return CurrencyCalculate
     */
    public function getSalePriceWithProfitRate($sku, $profitRate, $shipCode ='', $salePrice = null, $shippingPrice = 0)
    {
        $currency = self::PRODUCT_PUBLISH_CURRENCY;

        //计算卖价，获取描述
        $priceCal = new CurrencyCalculate();
        //设置参数值
        $priceCal->setProfitRate($profitRate);//设置利润率
        $priceCal->setCurrency($currency);//币种
        $priceCal->setPlatform(Platform::CODE_JOOM);//设置销售平台
        $priceCal->setSku($sku);//设置sku
        $priceCal->setShipCode($shipCode);//设置运费code
        if($salePrice){
            $priceCal->setSalePrice($salePrice);//设置运费code
        }
        $priceCal->setShipingPrice($shippingPrice);
        return $priceCal;
    }
    public function getListingProfit($sku, $salePrice, $shipCode = '', $shippingPrice= 0)
    {
        $currency = self::PRODUCT_PUBLISH_CURRENCY;

        //计算卖价，获取描述
        $priceCal = new CurrencyCalculate();
        //设置参数值

        $priceCal->setCurrency($currency);//币种
        $priceCal->setPlatform(Platform::CODE_JOOM);//设置销售平台
        $priceCal->setSku($sku);//设置sku
        $priceCal->setShipCode($shipCode);//设置运费code
        $priceCal->setSalePrice($salePrice);//设置运费code

        $priceCal->setShipingPrice($shippingPrice);
        return $priceCal;
    }
	/**
	 * @desc 获取销售价格相关
	 * @param unknown $productInfo
	 */
	public function getSalePrice($sku, $accountId = null, $shipCode = "", $salePrice = null, $shipingPrice = 0){
		$data = array();
		$currency = self::PRODUCT_PUBLISH_CURRENCY;
		$dataParam = array(
			':platform_code' 		 => Platform::CODE_JOOM,
			':profit_calculate_type' => SalePriceScheme::PROFIT_SYNC_TO_SALE_PRICE
		);
		$schemeWhere = 'platform_code = :platform_code AND profit_calculate_type = :profit_calculate_type';
		$salePriceScheme = SalePriceScheme::model()->getSalePriceSchemeByWhere($schemeWhere,$dataParam);
		if (!$salePriceScheme) {
			$tplParam = array(
					'standard_profit_rate'  => 0.20,
					'lowest_profit_rate'    => 0.15,
					'floating_profit_rate'  => 0.05,
			);
		} else {
			$tplParam = array(
					'standard_profit_rate'  => $salePriceScheme['standard_profit_rate'],
					'lowest_profit_rate'    => $salePriceScheme['lowest_profit_rate'],
					'floating_profit_rate'  => $salePriceScheme['floating_profit_rate'],
			);
		}

		//通过计算价格获取默认物流：销售价格<=10美金 采用顺丰小包，销售价格>10美金采用顺丰挂号
		$productCost = 0;

		//获取产品信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);
        if(!$skuInfo){
        	echo json_encode($data);
			Yii::app()->end();
        }

        if($skuInfo['avg_price'] <= 0){
        	$productCost = $skuInfo['product_cost'];   //加权成本
    	}else{
    		$productCost = $skuInfo['avg_price'];      //产品成本
    	}

    	//产品成本转换成美金
/*        $productCost = $productCost / CurrencyRate::model()->getRateToCny($currency);
        $productCost = round($productCost,2);
        if($productCost <= 10){
            $shipCode = Logistics::CODE_CM_SF;
        }else{
            $shipCode = Logistics::CODE_GHXB_SFEU;
        }*/

		//计算卖价，获取描述
		$priceCal = new CurrencyCalculate();
		//设置参数值
		$priceCal->setProfitRate($tplParam['standard_profit_rate']);//设置利润率
		$priceCal->setCurrency($currency);//币种
		$priceCal->setPlatform(Platform::CODE_JOOM);//设置销售平台
		$priceCal->setSku($sku);//设置sku
		$priceCal->setShipCode($shipCode);//设置运费code
		if($salePrice){
			$priceCal->setSalePrice($salePrice);//设置运费code
		}
		$priceCal->setShipingPrice($shipingPrice);

		$data = array();
		$data['salePrice']  = $priceCal->getSalePrice();//获取卖价
		$data['errormsg'] 	= $priceCal->getErrorMessage();
		$data['profit']     = $priceCal->getProfit();
		$data['profitRate'] = $priceCal->getProfitRate();
		//$data['shipPrice']  = $priceCal->getShippingCost();//获取运费
		$data['shipPrice']  = 0;    //运费设为0，运费加在售价上

		return $data;
	}
	
	/**
	 * @DESC 
	 * @param unknown $imageUrl
	 * @return string|boolean
	 */
	public function uploadImageToServer($imageUrl, $accountID){
		$configs = ConfigFactory::getConfig('serverKeys');
		$config = $configs['image'];
		$domain = $configs['domain'];
		$localpath = parse_url($imageUrl, PHP_URL_PATH);
		//判断OMS本地文件是否存在
		$result = UebModel::model("Productimage")->checkImageExist($localpath);
		if( !$result ){
			$this->setErrorMsg($localpath.' Not Exists.');
			return false;
		}
		
		/* $param = array('path'=>$localpath);
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
		} */
	
		$productImageAddModel = new ProductImageAdd();
		//上传图片到指定文件夹,返回路径
		$absolutePath = $productImageAddModel->saveTempImage($localpath);
		list($remoteName, $remotePath) = $productImageAddModel->getImageRemoteInfo($localpath, $accountID, Platform::CODE_JOOM);
		$uploadResult = $productImageAddModel->uploadImageServer($absolutePath, $remoteName, $remotePath);
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
		$platformCode = Platform::CODE_JOOM;
		$siteId = null;
		$assistantImage = false;
		$moreParams = array(
				'width'=>'800', 'height'=>'800'
		);
		$response = $productImageAddModel->getSkuImageUpload($accountID, $sku, array_values($imageNameList), $platformCode, $siteId, $assistantImage, $moreParams);
		//MHelper::printvar($response);
		if (empty($response) || empty($response['result']) || empty($response['result']['imageInfoVOs']) || count($imageNameList) != count($response['result']['imageInfoVOs']) ) {
			$this->setErrorMsg('Remote get Sku images failure');
			$productImageAddModel->addSkuImageUpload($accountID, $sku, 0, $platformCode, $siteId);//发送图片上传请求
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
			->andWhere('status != '.self::UPLOAD_STATUS_SUCCESS)
			->queryRow();
		if( !empty($checkExists) ){
			return array(
				'status'    => 0,
				'message'   => Yii::t('common','Record Exists.'),
			);
		}
		//检测是否存在在线广告
		$checkOnline = JoomProduct::model()->getProductByParam(array(
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
//			$categoryID = JoomCategory::model()->getCategoryIDByKeyWords($keyWordsEbay);
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
		$hasAttributes = self::model('AttributeMarketOmsMap')->getOmsAttrIdsByPlatAttrName(Platform::CODE_JOOM, 0);
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
		$variantList = self::model('JoomProductVariantsAdd')->getJoomProductVariantsAddListByAddId($productAddId,
            null, null, '*, v.upload_status as sub_upload_status');
		$hasUploadFailNum = 0;
		if($variantList){
			foreach ($variantList as $variant){
				$attribute = array();
				foreach ($platformOwnAttributeName as $name){
					$attribute[$attributeMap[$name]] = array('attribute_id'=>$attributeMap[$name],
							'attribute_value_name'=>isset($variant[$name])?$variant[$name]:''	);
				}

				/*//获取运费
				$attributesFeatures = Product::model()->getAttributeBySku($variant['sku'], 'product_features');//属性
                if($attributesFeatures){
                    //特殊属性的按黑龙江俄速通亚欧小包
                    $shipCode = Logistics::CODE_CM_YO_EST;
                }else{
                    //普通的是易时达俄罗斯专线
                    $shipCode = Logistics::CODE_CM_HUNGARY;
                }*/

                $salePrice = $this->getListingProfit($variant['sku'], $variant['price'], '', $variant['shipping']);
                $salePrice->getProfit();
				$skuinfo = array(
						'sku'=>$variant['sku'],
						'attribute'=>$attribute,
						'product_id'=>$productAddId, //此id不同于product表中 的id
						'skuInfo'=>array(
								'inventory'	=>	$variant['inventory'],
								'size'	=>	$variant['size'],
								'color'	=>	$variant['color'],
								'price'	=>	$variant['price'],
								'price_profit'	=>	'利润:<b>'.$salePrice->profit.'</b>,利润率:<b>'.$salePrice->profitRate*
                                    100
                                    .'%</b>',
								'product_cost'	=>	$variant['price'],
								'shipping'	=>	$variant['shipping'],
								'shipping_time'	=>	$variant['shipping_time'],
								'market_price'	=>	$variant['msrp'],
								'msrp'	=>	$variant['msrp'],
								'upload_status' => $variant['sub_upload_status'],
								'upload_status_text'	=>	$this->getProductAddInfoUploadStatus($variant['sub_upload_status'])
						)
				);
				if($variant['sub_upload_status'] != self::JOOM_UPLOAD_SUCCESS){
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
	public function saveJoomAddData($datas, $saveType = null, $addType = 0){
		if(!is_array($datas)){
			return false;
		}
		if($saveType == null)
			$saveType = JoomProductAdd::SAVE_TYPE_ALL;
		//有待开启事物处理
		$time = date("Y-m-d H:i:s");
		$userId = intval(Yii::app()->user->id);
		$skuEncrypt = new encryptSku();
		/**@ 获取产品信息*/
		$config = ConfigFactory::getConfig('serverKeys');
		$message = "";
		foreach ($datas as $accountId => $data){
			$transaction = $this->getDbConnection()->beginTransaction();
			try{
				$parentSku = $data['parent_sku'];
				$addData = array(
								'account_id'=>$accountId,
								'parent_sku'=>$data['parent_sku'],
								'name'=>$data['subject'],
								'description'=>$data['detail'],
								'tags'=>$data['tags'],
								'brand'=>$data['brand'],
								'main_image'=>$data['main_image'],
								'extra_images'=>$data['extra_images'],
								'product_is_multi'=>$data['product_is_multi'],
								'create_user_id'=>$userId,
								'update_user_id'=>$userId,
								'create_time'=>$time,
								'update_time'=>$time,
                                'remote_main_img'	=>	'',
								'remote_extra_img'	=>	'',
								'add_type'	=>	$addType
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
				if($saveType == JoomProductAdd::SAVE_TYPE_ALL){
					$addData['upload_status'] = self::JOOM_UPLOAD_PENDING;//重新置为待审核
					$addData['online_sku'] = $skuEncrypt->getEncryptSku($data['parent_sku']);
					//$addData['online_sku'] = $data['parent_sku'];//不需要加密
					$addData['upload_times'] = 0;
				}elseif($saveType == JoomProductAdd::SAVE_TYPE_NO_MAIN_SKU){
					$addData['upload_status'] = self::JOOM_UPLOAD_SUCCESS;//手动置为成功的，不需要再次上传到joom平台
					$addData['last_upload_msg']	=	Yii::t('joom_listing', 'Had upload joom platform');
				}
				if(isset($data['online_sku'])){
					$addData['online_sku'] = $data['online_sku'];
				}
				//检测是否已经存在该条数据
				$parentSkuInfo = array();
				//$parentSkuInfo = self::model()->find('account_id=:account_id AND parent_sku=:parent_sku', array(':account_id'=>$accountId, ':parent_sku'=>$parentSku));
				if(isset($data['add_id']) && $data['add_id']){
					$parentSkuInfo = $this->find("id='{$data['add_id']}'");
				}
				if($parentSkuInfo){
					if($parentSkuInfo->upload_status != self::JOOM_UPLOAD_SUCCESS){
						unset($addData['create_time'], $addData['create_user_id']);
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
								'inventory'	=>	intval($variant['inventory']),
								'price'		=>	$variant['price'],
								'shipping'	=>	0,
								'shipping_time'	=>	'7-35',
								'msrp'	=>	$variant['market_price'],
								'color'	=>	empty($variant['color'])?'':$variant['color'],
								'size'	=>	empty($variant['size'])?'':$variant['size'],
								'main_image'	=>	isset($variant['main_image'])?$variant['main_image']:'',
								'upload_status'	=>	self::JOOM_UPLOAD_PENDING,
								'upload_times'	=> 0,
								'create_user_id'	=>	$userId,
								'update_user_id'	=>	$userId,
								'create_time'		=>	$time,
								'update_time'		=>	$time
						);
						if($variant['sku'] == $parentSku && isset($addData['online_sku'])){
							$variantData['online_sku'] = $addData['online_sku'];
						}else{
							$variantData['online_sku'] = $skuEncrypt->getEncryptSku($variant['sku']);
						}
						
						//图片
						if(empty($variant['main_image'])){
							$images = Product::model()->getImgList($variant['sku'],'ft');
							foreach($images as $k=>$img){
								$variants ['main_image'] = $config['oms']['host'].$img;
								break;
							}
						}
						//$variantData['online_sku'] = $variant['sku'];//不需要加密
						//检测是否存在该条数据
						$skuInfo = self::model('JoomProductVariantsAdd')->find('add_id=:add_id AND sku=:sku', array(':add_id'=>$addId, ':sku'=>$sku));
						if($skuInfo){
							unset($variantData['create_time'], $variantData['create_user_id']);
							$res = $skuInfo->updateByPk($skuInfo->id, $variantData);
						}else{
							self::model('JoomProductVariantsAdd')->getDbConnection()->createCommand()->insert(self::model('JoomProductVariantsAdd')->tableName(), $variantData);												
						}
					}
				}
				$transaction->commit();
			}catch (Exception $e){
				
				$message .= $e->getMessage();
				$transaction->rollback();
			}
		}
		if($message){
			$this->setErrorMsg($message);
		}
		return true;
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
			case self::JOOM_UPLOAD_PENDING:
				$color = "blue";
				$str = Yii::t('joom_listing', 'Joom pending upload');
				break;
			case self::JOOM_UPLOAD_SUCCESS:
				$color = "green";
				$str = Yii::t('joom_listing', 'Joom upload success');
				break;
			case self::JOOM_UPLOAD_FAIL:
				$color = "red";
				$str = Yii::t('joom_listing', 'Joom upload failure');
				break;
			case self::JOOM_UPLOAD_IMG_FAIL:
				$color = "red";
				$str = Yii::t('joom_listing', 'joom upload images failure');
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
	
	
	/**
	 * @desc 批量添加产品分解操作
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param string $addType
	 * @return boolean
	 */
	public function productAddByBatch($sku, $accountID, $addType = null){
		if(is_null($addType)) $addType = JoomProductAdd::ADD_TYPE_DEFAULT;
		try{
			//首先确认产品是否有权限刊登
			if(! Product::model()->checkCurrentUserAccessToSaleSKU($sku,$accountID,Platform::CODE_JOOM)){
				$this->throwE("{$sku}:" . Yii::t('system', 'Not Access to Add the SKU') );
			}
			//产品表查找sku信息
			$skuInfo = Product::model()->getProductBySku($sku);
			if(empty($skuInfo)){
				$this->throwE("sku:{$sku}不存在！");
			}
			//确定下是否已经在当前账号刊登过
			//1、已刊登成功在线不再刊登
			$checkExists = JoomListing::model()->find("account_id=:account_id AND sku=:sku AND enabled=:enabled", 
												array(':account_id'=>$accountID, ':sku'=>$sku, ':enabled'=>1));
			if($checkExists){
				$this->throwE("已经上传过该SKU");
			}
				
			//2、已刊登下线的但在刊登记录里面未上传成功的等待上传的不再刊登
			$uploadStatus = array(
					self::JOOM_UPLOAD_PENDING, self::JOOM_UPLOAD_SUCCESS
			);
			$checkExists = JoomProductAdd::model()->find("account_id=:account_id AND parent_sku=:sku AND upload_status in (". MHelper::simplode($uploadStatus) .")", array(":account_id"=>$accountID, ":sku"=>$sku));
			if($checkExists){
				$this->throwE("已经上传过该SKU2");
			}
			$joomVariationAddModel = new JoomProductVariantsAdd();
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
				$joomListingModel = new JoomListing;
				$joomListingExtendModel = new JoomListingExtend();
				$addInfo = $joomListingModel->getDbConnection()->createCommand()
				->from($joomListingModel->tableName()." p")
				->join($joomListingExtendModel->tableName()." e", "e.listing_id=p.id")
				->select("p.id, p.sku, p.name, p.brand, p.tags, p.is_varation, e.description")
				->where("p.account_id<>:account_id AND p.sku=:sku", array(':account_id'=>$accountID, ':sku'=>$sku))
				->order("p.id DESC")
				->queryRow();
				if($addInfo && $addInfo['is_varation']){
					//获取子sku
					$joomListingVariationModel = new JoomVariants();
					$variationAddList = $joomListingVariationModel->getDbConnection()->createCommand()
					->from($joomListingVariationModel->tableName())
					->select("sku, inventory, price, shipping, shipping_time, msrp, color, size")
					->where("listing_id='{$addInfo['id']}'")
					->queryAll();
				}
			}else{
				//if($addInfo['product_is_multi'] == self::PRODUCT_IS_VARIOUS)
				//获取子sku
	
				$variationAddList = $joomVariationAddModel->getDbConnection()->createCommand()
				->from($joomVariationAddModel->tableName())
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
					'upload_status'		=>	self::JOOM_UPLOAD_PENDING,
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
							'upload_status'		=>	self::JOOM_UPLOAD_PENDING,
							'create_user_id'	=>	intval(Yii::app()->user->id),
							'update_user_id'	=>	intval(Yii::app()->user->id),
							'create_time'		=>	date("Y-m-d H:i:s"),
							'update_time'		=>	date("Y-m-d H:i:s"),
					);
					$joomVariationAddModel->getDbConnection()->createCommand()->insert($joomVariationAddModel->tableName(), $variationData);
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
	// ==================== START:待刊登列表相关 ==================

	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'=>'id',
		);

		$cdbCriteria = new CDbCriteria();
		$cdbCriteria->select = 't.*';

		$account_id = '';
		$accountIdArr = array();
		if(isset(Yii::app()->user->id)){
			$accountIdArr = JoomAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.Yii::app()->user->id);
		}

		if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']) ){
			$account_id = (int)$_REQUEST['account_id'];
		}

		if($accountIdArr && !in_array($account_id, $accountIdArr)){
			$account_id = implode(',', $accountIdArr);
		}

		if($account_id){
			$cdbCriteria->condition = "t.account_id IN(".$account_id.")";
		}

		if(isset($_REQUEST['upload_status']) && $_REQUEST['upload_status']){
			$uploadStatus = $_REQUEST['upload_status'];
			if($uploadStatus == self::JOOM_UPLOAD_PENDING_MAPPING)
				$uploadStatus = self::JOOM_UPLOAD_PENDING;
			$cdbCriteria->addCondition('upload_status='.$uploadStatus);
		}

		//查找子sku的状态时
		$variantCon = $variantParam = array();
		if(isset($_REQUEST['sub_upload_status']) && $_REQUEST['sub_upload_status']){
			$subuploadStatus = $_REQUEST['sub_upload_status'];
			if($subuploadStatus == self::JOOM_UPLOAD_PENDING_MAPPING)
				$subuploadStatus = self::JOOM_UPLOAD_PENDING;
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
		if(isset($variantParam[':sku']) || isset($variantParam[':upload_status'])){
			$joomVariantsModel = new JoomProductVariantsAdd;
			$variantCons = implode(" AND ", $variantCon);
			$variantList = $joomVariantsModel->getDbConnection()->createCommand()->from($joomVariantsModel->tableName())->where($variantCons, $variantParam)->group('add_id')->select('add_id')->queryColumn();
			if($variantList){
				$cdbCriteria->addInCondition('id', $variantList);
			}else{
				$cdbCriteria->addCondition('1=0 AND id=-1');
			}
		}
		$dataProvider = parent::search($this, $sort, '', $cdbCriteria);
		$data = $this->addtion($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	public function addtion($datas){
		if(empty($datas)) return $datas;
		$accounts = $this->getJoomAccountPairs();
		$joomProductAddVariants = new JoomProductVariantsAdd;
		foreach ($datas as $key=>$data){
			$datas[$key]->upload_times = $data['upload_times']>4?"<font color='red'><b>{$data['upload_times']}</b></font>":$data['upload_times'];
			$datas[$key]->account_name = $accounts[$data->account_id];
			$datas[$key]->upload_status_text = $this->getProductAddInfoUploadStatus($data['upload_status']);
			$datas[$key]->add_type = $this->getProductAddTypeOptions($data['add_type']);
			$datas[$key]->detail = array();
			//获取子sku
			$variantList = $this->getJoomVariantAddList($data['id']);
			$varhasPendinguploadStatus = false;
			if($variantList){
				foreach ($variantList as $variant){
					$variant['upload_status_text'] =  $this->getProductAddInfoUploadStatus($variant['upload_status']);
					$variant['subsku'] = $variant['sku'];
					$variant['upload_times'] = $variant['upload_times']>9?"<font color='red'>{$variant['upload_times']}</font>":$variant['upload_times'];
					$variant['prop'] = (empty($variant['size']) ? "" : Yii::t('joom_listing', 'Size') . ":" . $variant['size'] . "<br/>") . (empty($variant['color'])?"" : Yii::t('joom_listing', 'Color') . ":" . $variant['color']);
					$datas[$key]->detail[] = $variant;
					if($variant['upload_status'] != self::JOOM_UPLOAD_SUCCESS)
						$varhasPendinguploadStatus = true;
				}
			}
			$datas[$key]->visiupload = ($data['upload_status'] != self::JOOM_UPLOAD_SUCCESS || $varhasPendinguploadStatus);
		}
		return $datas;
	}
	
	public function getJoomVariantAddList($addId){
		$variantCon = $variantParam = array();
		$variantCon[] = 'add_id=:add_id';
		$variantParam[':add_id'] = $addId; 
		if(isset($_REQUEST['sub_upload_status']) && $_REQUEST['sub_upload_status']){
			$subuploadStatus = $_REQUEST['sub_upload_status'];
			if($subuploadStatus == self::JOOM_UPLOAD_PENDING_MAPPING)
				$subuploadStatus = self::JOOM_UPLOAD_PENDING;
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
		$joomVariantsModel = new JoomProductVariantsAdd;
		$variantCons = implode(" AND ", $variantCon);
		$variantList = $joomVariantsModel->getDbConnection()
									->createCommand()
									->from($joomVariantsModel->tableName())
									->where($variantCons, $variantParam)
									->queryAll();
		return $variantList;
	}
	/**
	 * @desc 获取账号
	 */
	public function getJoomAccountPairs(){
		if(!self::$joomAccountPairs){
			$user_id = isset(Yii::app()->user->id)?Yii::app()->user->id:0;
			$idArr = JoomAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.$user_id);
			if($idArr){
				self::$joomAccountPairs = self::model('JoomAccount')->getAvailableIdNamePairs($idArr);
			}else{
				self::$joomAccountPairs = self::model('JoomAccount')->getIdNamePairs();
			}
		}
		return self::$joomAccountPairs;
	}
	/**
	 * @desc 获取上传状态
	 * @return multitype:NULL Ambigous <string, string, unknown>
	 */
	public function  getUploadStatusOptions(){
		return array(
					//self::JOOM_UPLOAD_PENDING=> Yii::t('joom_listing', 'Joom pending upload'),
					self::JOOM_UPLOAD_PENDING_MAPPING=> Yii::t('joom_listing', 'Joom pending upload'),
					self::JOOM_UPLOAD_IMG_FAIL=>Yii::t('joom_listing', 'joom upload images failure'),
				    self::JOOM_UPLOAD_SUCCESS=> Yii::t('joom_listing', 'Joom upload success'),
					self::JOOM_UPLOAD_FAIL=>Yii::t('joom_listing', 'Joom upload failure')
				);
	}
	/**
	 * @desc 获取操作文本
	 * @param unknown $addId
	 * @param unknown $hasUpload
	 */
	public function getOperationText($addId, $hasUpload){
		$url = Yii::app()->createUrl("/joom/joomproductadd/update", array("add_id" => $addId));
		$options = array(
				'target'    => 'navTab',
				'class'     =>'btnEdit',
				'rel' => 'page366'
		);
		$_options = '';
		foreach ($options as $key=>$val){
			$_options .= $key . '="'.$val.'"';
		}
		$html = '<a href="'.$url.'" ' . $_options . '>'. Yii::t('joom_listing', 'Edit Publish Info') .'</a>';
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
		 return UebModel::model('user')  
                        ->queryPairs('id,user_full_name', "department_id=15");   //joom部门
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
						'data'		=>	$this->getJoomAccountPairs()
				),
				array(
						'name'		=>	'upload_status',
						'label'		=>	Yii::t('joom_listing', 'Main SKU Upload Status'),
						'search'	=>	'=',
						'type'		=>	'dropDownList',
						'data'		=>	$this->getUploadStatusOptions(),
						//'value'     => self::JOOM_UPLOAD_PENDING_MAPPING,
						'rel'		=>	true,
				),
				array(
						'name'		=>	'create_user_id',
						'search'	=>	'=',
						'type'		=>	'dropDownList',
						'data'		=>	$this->getCreateUserOptions()
				),
				array(
						'name'		=>	'sub_upload_status',
						'label'		=>	Yii::t('joom_listing', 'Upload status'),
						'search'	=>	'=',
						'type'		=>	'dropDownList',
						'data'		=>	$this->getUploadStatusOptions(),
						'rel'		=>	true
				),
				array(
						'name'		=>	'sub_sku',
						'label'		=>	Yii::t('joom_listing', 'Sub sku'),
						'search'	=>	'=',
						'type'		=>	'text',
						'rel'		=>	true
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
				'account_id'=>Yii::t('joom_listing', 'Account Name'),
				'parent_sku'=>Yii::t('joom_listing', 'Parent Sku'),
				'id'	=>	Yii::t('system', 'NO.'),
				'account_name'	=>	Yii::t('joom_listing', 'Account Name'),
				'name'		=>	Yii::t('system', 'Title'),
				'upload_times'	=>	Yii::t('joom_listing', 'Upload times'),
				'last_upload_time'	=>	Yii::t('joom_listing', 'Last upload time'),
				'upload_status'		=>	Yii::t('joom_listing', 'Upload status'),//Upload Status Text
				'upload_status_text'		=>	Yii::t('joom_listing', 'Upload status'),
				'main_sku_upload_status'	=>	Yii::t('joom_listing', 'Main SKU Upload Status'),
				'last_upload_msg'	=>	Yii::t('joom_listing', 'Last upload message'),
				'price'				=>	Yii::t('joom_listing', 'Price'),
				'create_user_id'	=>	Yii::t('joom_listing', 'Create user id'),
				'subsku'			=>	Yii::t('joom_listing', 'Sub sku'),
				'online_sku'		=>	Yii::t('joom_listing', 'Seller Sku'),
				'size'				=>	Yii::t('joom_listing', 'Size'),
				'color'				=>	Yii::t('joom_listing', 'Color'),
				'update_time'		=>	Yii::t('joom_listing', 'Last modify time'),
				'prop'				=>	Yii::t('joom_listing', 'Sale Property'),
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
}