<?php

/**
 * sku 在各平台刊登上线 链接主库
 * @author chenxy
 *
 */
class ProductPlatformPublishReportMain extends ProductsModel
{

	const ONLINE_STATUS = 1;
	const UMLINE_STATUS = 2;
	public $product_status;
	public $category_id;
	public $product_title;
	public $product_id;
	public $sku_count;
	public $count;
	public $product_category_name;
	public $EB;
	public $NF;
	public $ALI;
	public $KF;
	public $AMAZON;
	public $YF;
	public $NE;
	public $LAZADA;
	public $ECB;
	public $JDGJ;

	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ueb_product_seller_platform_publish_temporary';
	}

	public function rules()
	{
		return array();

	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array();

	}


	/**
	 * @param $users
	 * @param int $type
	 * @return array|CDbDataReader
	 */
	public function getSumData($users, $type = 2)
	{
		$table = $this->tableName();
		$ids = is_array($users) ? join(",", $users) : $users;
		$sql = "SELECT SUM(sku_count) AS sku_amount, seller_user_id FROM {$table} WHERE	1 AND type = '{$type}'
				AND seller_user_id IN ({$ids}) GROUP BY seller_user_id";

		$rows = $this->getDbConnection()->createCommand("{$sql}")->queryAll();
		$data = array();
		if (!empty($rows)) {
			foreach ($rows as $k => $v) {
				$data[$v['seller_user_id']] = $v['sku_amount'];
			}
		}
		return $data;
	}

	public function getSumDataByParams($types, $excludeSite, $platform, $user_arr = array())
	{
		$types = is_array($types) ? join(",", $types) : $types;
		$condition = !empty($user_arr) ? " AND seller_user_id IN('".join("','", $user_arr)."')" : "";
		$table = $this->tableName();
		$sql = "SELECT SUM(sku_count) AS sku_count, site, account_id, seller_user_id, type, product_status 
				FROM {$table} 
				WHERE 1 AND site <> '{$excludeSite}' AND platform_code = '{$platform}' {$condition} AND type IN({$types})
				GROUP BY seller_user_id, account_id, site, type, product_status";
		$rows = $this->getDbConnection()->createCommand("{$sql}")->queryAll();
		return $rows;
	}


	/**
	 * Declares attribute labels.
	 * @return array
	 */
	public function attributeLabels()
	{
		$attr = array(
			'id' => Yii::t('system', 'No.'),
			'product_status' => Yii::t('system', 'Status'),
			'product_category_name' => Yii::t('products', 'company category'),
			'category_id' => Yii::t('products', 'company category'),
			'platform_code' => Yii::t('purchases', 'promotion_platform_code'),
			'sku' => Yii::t('products', 'Sku'),
			'title' => Yii::t('system', 'Title'),
			'product_title' => Yii::t('system', 'Title'),
			'category_id' => Yii::t('products', 'company category'),
			'online_status' => Yii::t('products', '刊登状态'),
			'product_status' => Yii::t('products', 'Product Status'),
			'account_id' => Yii::t('order', 'Account Id'),
			'site' => Yii::t('purchases', 'platform_site'),
			'sc_name_id' => Yii::t('users', '负责人姓名'),
		);
		$platformList = UebModel::model('Platform')->getPlatformList();
		foreach ($platformList as $code => $name) {
			$attr[$code] = "$name";
		}
		return $attr;
	}

	/**
	 * get search info
	 */
	public function search()
	{
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 'id',
		);
		$dataProvider = parent::search(get_class($this), $sort, array(), $this->_setCDbCriteria());
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}

	private function _setCDbCriteria()
	{
		$criteria = new CDbCriteria();
		$criteria->select = '*';
		//$criteria->join   = 'LEFT JOIN ueb_product p ON p.sku = t.sku ';
		//$criteria->join   .= ' LEFT JOIN ueb_product_class_to_online_class d ON d.online_id = p.online_category_id ';
		//	$criteria->condition = '1>1';
		if (!$_REQUEST['sc_name_id']) {
			$criteria->condition .= '1>1';
		}
		//$criteria->group = 'd.category_id';

		return $criteria;
	}

	/**
	 * addition information
	 *
	 * @param type $dataProvider
	 */
	public function addition($data)
	{

		return $data;
	}

	/**
	 * filter search options
	 * @return type
	 */
	public function filterOptions()
	{
// 	    if($_REQUEST['platform_code']){
//     		$arr = UebModel::model('Order')->getPlatformAccount(trim($_REQUEST['platform_code']));
//     	}else{
//     		$arr = array();
//     	}
		$result = array(
			array(
				'name' => 'sc_name_id',
				'type' => 'dropDownList',
				'search' => '=',
				'data' => UebModel::model("ProductMarketersManager")->getSellerUserIdAndName(),
				'htmlOptions' => array(),
				'alias' => 't',
			),
			array(
				'name' => 'platform_code',
				'type' => 'dropDownList',
				'search' => '=',
				'value' => $_REQUEST['platformCode'] ? $_REQUEST['platformCode'] : '',
				'data' => UebModel::model('Platform')->getPlatformList(),
				'htmlOptions' => array('onchange' => 'getAccount(this)'),
				'alias' => 't',
			),
			array(
				'name' => 'site',
				'type' => 'dropDownList',
				'search' => '=',
				//'data'          => $arr,
				'htmlOptions' => array(),
				'alias' => 't',
			),

			array(
				'name' => 'product_status',
				'type' => 'checkBoxList',
				'rel' => true,
				'data' => array(Product::STATUS_ON_SALE => '在售中', Product::STATUS_WAIT_CLEARANCE => '待清仓'),
				//'data'          =>Product::getProductStatusConfig(),
				'clear' => true,
				'hide' => '',
				'htmlOptions' => array('container' => '', 'separator' => ''),
				'alias' => 't',
			),
		);


		return $result;
	}


	/**
	 * 查询刊登数据并保存到临时表
	 * @param array $typeArr 统计类型 如：[0,1,2,3,4,5]
	 */
	public function timeCreateRecord($typeArr = null)
	{
		try {
			$noStaticPlatform = array('NE', 'YF', 'ECB', 'BELLABUY', 'PAYTM');
			$platformList = UebModel::model('Platform')->getPlatformList();
			$productStatus = array(Product::STATUS_ON_SALE, Product::STATUS_WAIT_CLEARANCE, Product::STATUS_PRE_ONLINE);
			$userAll = UebModel::model("ProductMarketersManager")->findAll("is_del = 0");
			$categoryAll = UebModel::model('ProductClass')->getCat();
			$keyArray = array('sku_count', 'product_status', 'platform_code', 'site', 'account_id', 'seller_user_id', 'category_id');
			if (empty($userAll)) {
				return false;
			}

			//每次执行脚本时，先清空数据
			if (is_null($typeArr)) {
				$flag = UebModel::model('ProductPlatformPublishReportMain')->getDbConnection()->createCommand()->delete(self::tableName(), "1=1");
			} else {
				$flag = UebModel::model('ProductPlatformPublishReportMain')->getDbConnection()->createCommand()->delete(self::tableName(), "type in(" . implode(',', $typeArr) . ")");
			}

			foreach ($productStatus as $status) {
				foreach ($categoryAll as $key => $values) {
					foreach ($userAll as $list) {
						if (in_array($list->platform_code, $noStaticPlatform)) {
							continue;
						}

						if (is_null($typeArr) || in_array(0, $typeArr)) {
							//type=0 根据账号，平台，站点，人员，和分类得到数量 -- 已刊登
							$condition = '';
							if (in_array($list->platform_code, array('EB', 'AMAZON', 'LAZADA', 'SHOPEE'))) {
								if ($list->site != '') {
									$condition .= " and t.site = '{$list->site}'";
								}
							}
							$cmd = $this->getDbConnection()->createCommand()
								->select('count(distinct(t.id)) as sku_count')
								->from(UebModel::model('Product')->tableName() . ' p')
								->leftJoin(UebModel::model('ProductClassToOnlineClass')->tableName() . ' c', 'c.online_id = p.online_category_id')
								->leftJoin(UebModel::model('ProductPlatformListing')->tableName() . ' t', "p.sku=t.sku ")
								->leftJoin(ProductToAccountSellerPlatform::getRelationTableName($list->platform_code, $list->account_id) . ' r', 'r.sku = t.sku')
								->where("c.category_id = $key and t.online_status = 1")
								->andWhere("t.platform_code = '{$list->platform_code}' and t.account_id = '{$list->account_id}' {$condition} ")
								->andwhere("p.product_status = $status  and p.product_is_multi != 2 ")
								->andWhere("r.seller_user_id = '{$list->seller_user_id}' ");
							//echo $cmd->getText()."<br>";exit;	
							$result = $cmd->queryRow();

							$data = array(
								'sku_count' => $result['sku_count'],
								'product_status' => $status,
								'platform_code' => $list->platform_code,
								'account_id' => $list->account_id,
								'site' => $list->site,
								'seller_user_id' => $list->seller_user_id,
								'category_id' => $key,
								'updated_at' => date('Y-m-d H:i:s'),
								'type' => 0
							);
							$this->dbConnection->createCommand()->insert(self::tableName(), $data);
						}

						if (is_null($typeArr) || in_array(5, $typeArr)) {
							//type=5 是分类平台账号站点已分配数	
							$condition = '';
							if (in_array($list->platform_code, array('EB', 'AMAZON', 'LAZADA', 'SHOPEE'))) {
								if ($list->site != '') {
									$condition .= " and r.site = '{$list->site}'";
								}
							}

							$cmd = $this->getDbConnection()->createCommand()
								->select('count(distinct(p.id)) as sku_count')
								->from(UebModel::model('Product')->tableName() . ' p')
								->leftJoin(UebModel::model('ProductClassToOnlineClass')->tableName() . ' c', 'c.online_id = p.online_category_id')
								->leftJoin(ProductToAccountSellerPlatform::getRelationTableName($list->platform_code, $list->account_id) . ' r', "p.sku=r.sku ")
								->where("c.category_id = $key")
								->andWhere("r.platform_code = '{$list->platform_code}' and r.account_id = '{$list->account_id}' {$condition} ")
								->andWhere("r.seller_user_id = '{$list->seller_user_id}' ")
								->andwhere("p.product_status = $status  and p.product_is_multi != 2 ");
							//echo $cmd->getText()."<br>";exit;
							$result = $cmd->queryRow();

							$data = array(
								'sku_count' => $result['sku_count'],
								'product_status' => $status,
								'platform_code' => $list->platform_code,
								'account_id' => $list->account_id,
								'site' => $list->site,
								'seller_user_id' => $list->seller_user_id,
								'category_id' => $key,
								'updated_at' => date('Y-m-d H:i:s'),
								'type' => 5
							);
							$this->dbConnection->createCommand()->insert(self::tableName(), $data);
						}

						if (is_null($typeArr) || in_array(2, $typeArr)) {
							//type=2 根据销售员，分类得到该销售员在某个分类下绑定的SKU数量 -- 公司分类统计
							$tmp = UebModel::model('ProductPlatformPublishReportMain')->find("category_id =$key and seller_user_id ='{$list->seller_user_id}' and product_status = $status and type = 2");
							if (empty($tmp)) {
								$results = $this->getDbConnection()->createCommand()
									->select('count(distinct(p.id)) as sku_count')
									->from(UebModel::model('Product')->tableName() . ' p')
									->leftJoin(UebModel::model('ProductClassToOnlineClass')->tableName() . ' c', 'c.online_id = p.online_category_id')
									->leftJoin(ProductToAccountSellerPlatform::getRelationTableName($list->platform_code, $list->account_id) . ' r', 'r.sku = p.sku')
									->where("c.category_id = $key ")
									->andwhere("p.product_status = $status  and p.product_is_multi != 2  and r.seller_user_id = '{$list->seller_user_id}'")
									->queryRow();

								$datas = array(
									'sku_count' => $results['sku_count'],
									'product_status' => $status,
									'seller_user_id' => $list->seller_user_id,
									'category_id' => $key,
									'updated_at' => date('Y-m-d H:i:s'),
									'type' => 2
								);

								$this->dbConnection->createCommand()->insert(self::tableName(), $datas);
							}
						}

						if (is_null($typeArr) || in_array(4, $typeArr)) {
							//type=4 销售员下某个平台账号站点下的待刊登SKU数量
							$readyPublish = UebModel::model('ProductToAccountRelation')->getUserReadyPublishCount($list->platform_code, $list->seller_user_id, $key, $list->site, $list->account_id, $status);
							$readyPublishArr = array(
								'sku_count' => $readyPublish,
								'product_status' => $status,
								'platform_code' => $list->platform_code,
								'account_id' => $list->account_id,
								'site' => $list->site,
								'seller_user_id' => $list->seller_user_id,
								'category_id' => $key,
								'updated_at' => date('Y-m-d H:i:s'),
								'type' => 4
							);
							$this->dbConnection->createCommand()->insert(self::tableName(), $readyPublishArr);
						}

					}//end userAll

					if (is_null($typeArr) || in_array(1, $typeArr)) {
						//type=1 得到各平台下的每个分类的数量(分类刊登统计报表里用到)
						foreach ($platformList as $platformCode => $vs) {
							if (!in_array($platformCode, $noStaticPlatform)) {
								$platformSkuCount = UebModel::model('ProductPlatformListing')->getSkuPlatformByPlatformCodeAndClassId($key, $platformCode, $status);// sku 在各平台上线的 数量
								$datas = array(
									'sku_count' => $platformSkuCount,
									'product_status' => $status,
									'platform_code' => $platformCode,
									'category_id' => $key,
									'updated_at' => date('Y-m-d H:i:s'),
									'type' => 1
								);

								$this->dbConnection->createCommand()->insert(self::tableName(), $datas);
							}
						}
					}

					if (is_null($typeArr) || in_array(3, $typeArr)) {
						//type=3 统计公司各分类的sku数量
						$productCountAll = UebModel::model('ProductClass')->getClassToSkuConut($status, $key);
						//echo '<pre>';print_r($productCountAll);
						$this->dbConnection->createCommand()->insert(self::tableName(), array(
								'category_id' => $key,
								'product_status' => $status,
								'sku_count' => $productCountAll[$key],
								'updated_at' => date('Y-m-d H:i:s'),
								'type' => 3
							)
						);
						//echo $list->site.'--'.$list->platform_code.'--'.$list->account_id.'--'.$list->seller_user_id;
					}

				}//end category

				if (is_null($typeArr) || in_array(1, $typeArr)) {
					//type=1 统计未分类的刊登数量
					foreach ($platformList as $platformCode => $vs) {
						if (!in_array($platformCode, $noStaticPlatform)) {
							$platformSkuCount = UebModel::model('ProductPlatformListing')->getSkuPlatformByPlatformCodeAndNotClassId($platformCode, $status);// sku 在各平台上线的 数量
							$datas = array(
								'sku_count' => $platformSkuCount,
								'product_status' => $status,
								'platform_code' => $platformCode,
								'category_id' => '',
								'type' => 1
							);

							$this->dbConnection->createCommand()->insert(self::tableName(), $datas);
						}
					}
				}
			}
			return true;
		} catch (Exception $e) {
			echo $e->getMessage() . "<br>";
			return false;
		}
	}

	public function createStatistics($platform, $tmp_model, $user_arr)
	{
		try {
			$noStaticPlatform = array('NE', 'YF', 'ECB', 'BELLABUY', 'PAYTM');
			$productStatus = array(Product::STATUS_ON_SALE, Product::STATUS_WAIT_CLEARANCE, Product::STATUS_PRE_ONLINE);
			//6为已刊登（子），7为已分配（子）， 8为预刊登（子）, 9为已分配（主）
			$typeArr = array(6, 7, 8, 9);
			if (!empty($user_arr)) {
				$user_string = join("','", $user_arr);
				$userAll = UebModel::model("ProductMarketersManager")->findAll("is_del = 0 AND platform_code = '{$platform}' AND seller_user_id IN('{$user_string}')");
			} else {
				$userAll = UebModel::model("ProductMarketersManager")->findAll("is_del = 0 AND platform_code = '{$platform}'");
			}

			if (empty($userAll)) {
				return false;
			}

			$platform_class = new Platform();
			$platform_code = $platform_class->getPlatformCodesAndNames();
			$platform_name = strtolower($platform_code[$platform]);

			$task_sync_model = new TaskSyncModel();
			//每次执行脚本时，先清空数据
			UebModel::model('ProductPlatformPublishReportMain')->getDbConnection()->createCommand()->delete(self::tableName(), "platform_code = '{$platform}' AND type in(" . implode(',', $typeArr) . ")");
			foreach ($productStatus as $status) {
				foreach ($userAll as $list) {
					if (in_array($list->platform_code, $noStaticPlatform)) {
						continue;
					}

					//循环类型
					foreach ($typeArr as $tk => $type) {
						if (in_array($type, array(6))) {
							//type=6 根据账号，平台，站点，人员获取已刊登的数量
							$prefix = $list->seller_user_id % 10;
							$single_listing = $task_sync_model->calcutorSiteStatusListing($list->seller_user_id, $list->platform_code, $platform_name, $prefix, 'single', $list->account_id, $status, $list->site);
							$sub_listing = $task_sync_model->calcutorSiteStatusListing($list->seller_user_id, $list->platform_code, $platform_name, $prefix, 'sub', $list->account_id, $status, $list->site);
							$data = array(
								'sku_count' => $single_listing + $sub_listing,
								'product_status' => $status,
								'platform_code' => $list->platform_code,
								'account_id' => $list->account_id,
								'site' => $list->site,
								'seller_user_id' => $list->seller_user_id,
								'updated_at' => date('Y-m-d H:i:s'),
								'type' => $type
							);
							$this->dbConnection->createCommand()->insert(self::tableName(), $data);
						}


						if (in_array($type, array(7, 9))) {
							//type=7 是平台人员账号站点已分配数（子)，9是平台人员账号站点已分配数（主）
							//计算单品分配的数量
							$prefix = ($list->seller_user_id % 10);
							$single_count = $tmp_model->calculatorProductSiteStatus($list->seller_user_id, $platform_name, $prefix, 'single', $list->account_id, $status, $list->site);
							if (7 == $type) {
								//计算分配的子sku数量
								$sku_count = $tmp_model->calculatorProductSiteStatus($list->seller_user_id, $platform_name, $prefix, 'sub', $list->account_id, $status, $list->site);
							} else {
								//计算分配的主sku数量
								$sku_count = $tmp_model->calculatorProductSiteStatus($list->seller_user_id, $platform_name, $prefix, 'main', $list->account_id, $status, $list->site);
							}

							$data = array(
								'sku_count' => $sku_count + $single_count,
								'product_status' => $status,
								'platform_code' => $list->platform_code,
								'account_id' => $list->account_id,
								'site' => $list->site,
								'seller_user_id' => $list->seller_user_id,
								'updated_at' => date('Y-m-d H:i:s'),
								'type' => $type
							);
							$this->dbConnection->createCommand()->insert(self::tableName(), $data);
						}


						if (in_array($type, array(8))) {
							//type=8 是预刊登数（子)
							$condition = '';
							if (in_array($list->platform_code, array('EB', 'AMAZON', 'LAZADA', 'SHOPEE'))) {
								if ($list->site != '') {
									$condition .= " AND r.site = '{$list->site}'";
								}
							}

							$cmd = $this->getDbConnection()->createCommand()
								->select('COUNT(DISTINCT(r.sku)) AS total, p.product_status')
								->from("ueb_product_to_account_relation_".strtoupper($list->platform_code)." r")
								->leftJoin(UebModel::model('Product')->tableName() . ' p', "r.sku = p.sku")
								->where("r.online_status = 0")
								->andWhere("r.seller_user_id = '{$list->seller_user_id}' {$condition}")
								->andWhere("r.platform_code ='{$list->platform_code}' AND r.account_id = '{$list->account_id}'")
								->andwhere("p.product_status = '{$status}' AND p.product_is_multi <> 2 ");
							$result = $cmd->queryRow();
							$data = array(
								'sku_count' => $result['total'],
								'product_status' => $status,
								'platform_code' => $list->platform_code,
								'account_id' => $list->account_id,
								'site' => $list->site,
								'seller_user_id' => $list->seller_user_id,
								'updated_at' => date('Y-m-d H:i:s'),
								'type' => $type
							);
							$this->dbConnection->createCommand()->insert(self::tableName(), $data);
						}

					}// end typeArr
				}//end userAll
			} // product status
			return true;
		} catch (Exception $e) {
			echo $e->getMessage() . "<br>";
			return false;
		}
	}


}