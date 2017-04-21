<?php
/**
 * @desc joom 在线产品统计
 * @author liht
 * @since 20151117
 *
 */
class JoomproductstatisticController extends UebController {

	/** @var object 模型实例 **/
	protected $_model = NULL;

	/**
	 * (non-PHPdoc)
	 * @see CController::init()
	 */
	public function init() {
		$this->_model = new JoomProductStatistic();
	}

	/**
	 * @desc 列表页
	 */
	public function actionList() {
		$accountID = Yii::app()->request->getParam('account_id', '0');
		$this->_model->account_id = $accountID;
		$this->render('list', array(
			'model' => $this->_model,'accountID'=>$accountID
		));
	}

	/**
	 * @desc 批量添加刊登任务（根据子sku）
	 * @throws Exception
	 */
	public function actionBatchPublish() {
		$accountID = Yii::app()->request->getParam('account_id');
		$skus = Yii::app()->request->getParam('ids');
                
		if (empty($accountID)) {
			echo $this->failureJson(array(
				'message' => Yii::t('lazada_product_statistic', 'Invalid Account'),
			));
			Yii::app()->end();
		}

		$skuArr = explode(',', $skus);
		$skuArr = array_filter($skuArr);

		if (empty($skuArr)) {
			echo $this->failureJson(array(
				'message' => Yii::t('lazada_product_statistic', 'Not Chosen Products'),
			));
			Yii::app()->end();
		}
		$message = '';
		//批量添加到待上传列表
		//$this->_model->batchAddProduct($skuArr, $accountID);
		$joomProductAddModel = new JoomProductAdd();
		foreach ($skuArr as $sku){
			//$this->_model->batchAddProductFromListing($skuArr, $accountID);
			$res = $joomProductAddModel->productAddByBatch($sku, $accountID, JoomProductAdd::ADD_TYPE_BATCH);
			if(!$res){
				$message .= $joomProductAddModel->getErrorMsg()."<br/>";
			}
		}
		
        //$message = $this->_model->getErrMsg();
		if( $message=='' ){
			echo $this->successJson(array(
				'message' => Yii::t('lazada_product_statistic', 'Publish Task Create Successful'),
				'callbackType' => 'navTabAjaxDone',
			));
		}else{
			echo $this->failureJson(array(
				'message' => $message,
			));
		}

	}
        
        /**
	 * @desc 批量添加其他账号已刊登任务（根据子sku）
	 * @throws Exception
	 */
	public function actionBatchPublishAll() {
            set_time_limit(2*3600);
            ini_set('memory_limit','2048M');
            $accountID = Yii::app()->request->getParam('account_id');
            $old_account_id = Yii::app()->request->getParam('old_account_id');

            if (empty($accountID) || empty($old_account_id)) {
                    echo $this->failureJson(array(
                            'message' => Yii::t('lazada_product_statistic', 'Invalid Account'),
                    ));
                    Yii::app()->end();
            }

            $flag_while = true;
            $limit = 20;
            $offset = 0;
            while($flag_while){
                //从ueb_listing_variants表按账号查找
                $skus = JoomVariants::model()->getDbConnection()->createCommand()
                        ->from(JoomVariants::tableName())
                        ->select('sku')
                        //->where('enabled =1')
                        ->andWhere("account_id={$old_account_id}")
                        ->order('id asc')
                        ->limit($limit, $offset)
                        ->queryColumn();
                $offset +=20;
                if (empty($skus)) {
                    $flag_while = false;
                    break;
                }
                
                //批量添加到待上传列表
                JoomProductStatistic::model()->batchAddProductFromListing($skus, $accountID);
            }
            echo 'end';
            exit;

	}
        
        /**
	 * @desc 从wish复制sku到待刊登列表（根据主sku来复制）
	 * @throws Exception
	 */
	public function actionBatchPublishFromWish() {
            $time = time();
            set_time_limit(5*3600);
            ini_set('memory_limit','2048M');
            $accountID = Yii::app()->request->getParam('account_id');
            $wish_account_id = Yii::app()->request->getParam('wish_account_id');
            $wish_listing_id_begin = Yii::app()->request->getParam('begin');
            $wish_listing_id_end = Yii::app()->request->getParam('end');
            if (empty($accountID) || empty($wish_account_id) ) {
                    echo $this->failureJson(array(
                            'message' => Yii::t('lazada_product_statistic', 'Invalid Account'),
                    ));
                    Yii::app()->end();
            }

            $flag_while = true;
            $limit = 50;
            $offset = 0;
            while($flag_while){
                $exe_time = time();
                if($exe_time - $time > 18000){
                    echo '执行超过5小时';
                    exit;
                }
                //从ueb_listing_variants表按账号查找
                $skus = JoomListing::model()->getDbConnection()->createCommand()
                        ->from('market_wish.ueb_wish_listing_bak')
                        ->select('sku')
                        ->where("account_id='{$wish_account_id}'")
                        ->andWhere("id>{$wish_listing_id_begin}")
                        ->andWhere("id<{$wish_listing_id_end}")
                        ->andWhere("extra_images !=''")
                        ->order('id asc')
                        ->limit($limit, $offset)
                        ->queryColumn();
                $offset += 50;
                if (empty($skus)) {
                    $flag_while = false;
                    break;
                }
                //批量添加到待上传列表
                JoomProductStatistic::model()->batchAddProductFromWishListing($skus, $accountID, $wish_account_id);
            }
            echo 'end';
            exit;

	}

        /**
	 * @desc 修改待清仓和已停售sku的库存
	 * @throws Exception
	 */
        public function actionUpdateStockNum(){
            set_time_limit(2*3600);
            ini_set('memory_limit','2048M');
            $id_begin = Yii::app()->request->getParam('id_begin');
            $id_end = Yii::app()->request->getParam('id_end');
            if($id_begin){
                
                $flag_while = true;
                $limit = 50;
                $offset = 0;
                while($flag_while){
                    
                    //1.JoomProductVariantsAdd表查出sku
                    $list = JoomProductVariantsAdd::model()->getDbConnection()->createCommand()
                            ->from(JoomProductVariantsAdd::model()->tableName())
                            ->select('id,sku,inventory')
                            ->andWhere("id>={$id_begin}")
                            ->andWhere("id<={$id_end}")
                            ->order('id asc')
                            ->limit($limit, $offset)
                            ->queryAll();
                    $offset += $limit;
                    if (empty($list)) {
                        $flag_while = false;
                        break;
                    }
                    
                    foreach ($list as $detail){
                        //2.根据sku查出产品状态和库存
                        $productInfo = JoomProductStatistic::model()->getProduct($detail['sku']);
                        if(!$productInfo){
                                //$this->setErrMsg("{$sku}:" . Yii::t('joom_listing', "Not found the sku"));
                                continue;
                        }

                        
                        if( $productInfo['product_status'] == Product::STATUS_WAIT_CLEARANCE || $productInfo['product_status'] == Product::STATUS_STOP_SELLING){
                            //待清仓、已停售修改为实际库存
                            $inventory = WarehouseSkuMap::model()->getAvailableBySkuAndWarehouse($detail['sku'], WarehouseSkuMap::WARE_HOUSE_GM);
                        } elseif ($productInfo['product_status'] != Product::STATUS_ON_SALE) {
                            //既不是待清仓、已停售，也不是在售中
                            $inventory = 0;
                        } else {
                            //在售中
                            continue;
                        }
                        //3.根据状态和库存修改刊登表的库存
                        $result = JoomProductVariantsAdd::model()->getDbConnection()->createCommand()->update(JoomProductVariantsAdd::model()->tableName(), array('inventory' => $inventory), "id={$detail['id']}");
                    }
                    
                }
                echo 'end';
                exit;
                
            }
            
            
        }
        
        
        /**
         * vakind.com/joom/Joomproductstatistic/BatchPublishFromAdd/old_account_id/1/new_account_id/2/id_begin/1/id_end/12997
         * 7248
	 * @desc 从待刊登列表其他账号复制数据
	 * @throws Exception
	 */
        public function actionBatchPublishFromAdd(){
            set_time_limit(2*3600);
            ini_set('memory_limit','2048M');
            $old_account_id = Yii::app()->request->getParam('old_account_id');
            $new_account_id = Yii::app()->request->getParam('new_account_id');
            //variants 表开始id
            $id_begin = Yii::app()->request->getParam('id_begin');
            //variants 表结束id
            $id_end = Yii::app()->request->getParam('id_end');
            
            //1查出add表id以外的所有字段生成字段字符串用于 insert into select * from
            $sql_add_column_name = "select COLUMN_NAME from information_schema.COLUMNS where table_name = 'ueb_joom_product_add' and table_schema = 'market_joom' and COLUMN_NAME !='id' and COLUMN_NAME !='last_upload_msg'";
            $add_column_name_array = JoomProductAdd::model()->getDbConnection()->createCommand($sql_add_column_name)->queryColumn();
            $add_insert_column_str = implode(',', $add_column_name_array);
            
            //2查出add表最大的id，作为偏移量加上当前id生成新的id)
            $add_number = JoomProductAdd::model()->getDbConnection()->createCommand()
                    ->from(JoomProductAdd::model()->tableName())
                    ->select('id')
                    ->order('id desc')
                    ->queryScalar();
            
            $add_number +=100;
            echo $add_number;
            $last_upload_msg = "copy_add_{$new_account_id}";
            
            //3复制add表数据
            $sql_copy_add = "insert into market_joom.ueb_joom_product_add (id,{$add_insert_column_str},last_upload_msg) select id+{$add_number},{$add_insert_column_str},'{$last_upload_msg}' from market_joom.ueb_joom_product_add where account_id ='{$old_account_id}' and upload_status=1";
            $flag_add = JoomProductAdd::model()->getDbConnection()->createCommand($sql_copy_add)->query();
            
            if (!$flag_add){
                //$this->setExceptionMessage('copy add Failure');
                return false;
                exit;
            }
            
            //4查出variants表id以外的所有字段生成字段字符串用于 insert into select * from
            $sql_variants_column_name = "select COLUMN_NAME from information_schema.COLUMNS where table_name = 'ueb_joom_product_variants_add' and table_schema = 'market_joom' and COLUMN_NAME !='id' and COLUMN_NAME !='last_upload_msg'";
            $variants_column_name_array = JoomProductVariantsAdd::model()->getDbConnection()->createCommand($sql_variants_column_name)->queryColumn();
            $variants_insert_column_str = implode(',', $variants_column_name_array);
            
            //5查出variants表最大的id，作为偏移量加上当前id生成新的id)
            $variants_number = JoomProductVariantsAdd::model()->getDbConnection()->createCommand()
                    ->from(JoomProductVariantsAdd::model()->tableName())
                    ->select('id')
                    ->order('id desc')
                    ->queryScalar();
            
            $variants_number +=100;
            $variants_last_upload_msg = "copy_variants_{$new_account_id}";
            
            //6复制variants表数据
            $sql_copy_variants = "insert into market_joom.ueb_joom_product_variants_add (id,{$variants_insert_column_str},last_upload_msg) select id+{$variants_number},{$variants_insert_column_str},'{$variants_last_upload_msg}' from market_joom.ueb_joom_product_variants_add where id>='{$id_begin}' and id<='{$id_end}' and upload_status=1 and inventory >0";
            $flag_variants = JoomProductAdd::model()->getDbConnection()->createCommand($sql_copy_variants)->query();
            
            if (!$flag_variants){
                //$this->setExceptionMessage('copy variants Failure');
                return false;
                exit;
            }
            
            
            //7修改add表的online_sku和其他数据
            $flag_while = true;
            $limit = 100;
            $offset = 0;
            while($flag_while){
                $list = JoomProductAdd::model()->getDbConnection()->createCommand()
                        ->from(JoomProductAdd::model()->tableName())
                        ->select('id,parent_sku,online_sku')
                        ->where("last_upload_msg = '{$last_upload_msg}'")
                        ->order('id asc')
                        ->limit($limit, $offset)
                        ->queryAll();
                //$offset += $limit;
                if (empty($list)) {
                    $flag_while = false;
                    break;
                }

                foreach ($list as $detail){
                    $skuEncrypt = new encryptSku();
                    $online_sku = $skuEncrypt->getEncryptSku($detail['parent_sku']);
                    $update_data = array(
                        'account_id'        => $new_account_id,
                        'online_sku'        => $online_sku,
                        'upload_status'     => 0,
                        'upload_times'      => 0,
                        'last_upload_msg'   => '',
                        'last_upload_time'  => null,
                        'create_time'       => date('Y-m-d H:i:s', time()),
                        'update_time'       => date('Y-m-d H:i:s', time()),
                    );
                    $result = JoomProductAdd::model()->getDbConnection()->createCommand()->update(JoomProductAdd::model()->tableName(), $update_data, "id={$detail['id']}");
                }
            }
            
            
            //8修改variants表online_sku和其他数据
            $flag_while = true;
            $limit = 100;
            $offset = 0;
            while($flag_while){

                $list = JoomProductVariantsAdd::model()->getDbConnection()->createCommand()
                        ->from(JoomProductVariantsAdd::model()->tableName())
                        ->select('id,add_id,sku,online_sku')
                        ->where("last_upload_msg = '{$variants_last_upload_msg}'")
                        ->order('id asc')
                        ->limit($limit, $offset)
                        ->queryAll();
                //$offset += $limit;
                if (empty($list)) {
                    $flag_while = false;
                    break;
                }

                foreach ($list as $detail){
                    $skuEncrypt = new encryptSku();
                    $online_sku = $skuEncrypt->getEncryptSku($detail['sku']);
                    $add_id = $detail['add_id'] + $add_number;
                    
                    $update_variants_data = array(
                        'add_id'            => $add_id,
                        'online_sku'        => $online_sku,
                        'upload_status'     => 0,
                        'upload_times'      => 0,
                        'last_upload_msg'   => '',
                        'last_upload_time'  => null,
                        'create_time'       => date('Y-m-d H:i:s', time()),
                        'update_time'       => date('Y-m-d H:i:s', time()),
                    );
                    
                    $result = JoomProductVariantsAdd::model()->getDbConnection()->createCommand()->update(JoomProductVariantsAdd::model()->tableName(), $update_variants_data, "id={$detail['id']}");
                }
            }
        }
        
        /**
         * vakind.com/joom/Joomproductstatistic/DeleteZeroStockVariants
         * 7248
	 * @desc 删除零库存数据
	 * @throws Exception
	 */
        public function actionDeleteZeroStockVariants(){
            $result = JoomProductVariantsAdd::model()->getDbConnection()->createCommand()->delete(JoomProductVariantsAdd::model()->tableName(), "inventory<=0 and upload_status = 0");
        }
        
        /**
	 * @desc 从wish复制已停售有库存sku到待刊登列表（根据子sku来刊登）
	 * @throws Exception
	 */
	public function actionBatchPublishStopFromWish() {
            //1.从系统找出已停售有库存的子sku （product_type=1 或单品）
            $time = time();
            set_time_limit(5*3600);
            ini_set('memory_limit','2048M');
            $accountID = Yii::app()->request->getParam('account_id');
            if (empty($accountID) ) {
                    echo $this->failureJson(array(
                            'message' => Yii::t('lazada_product_statistic', 'Invalid Account'),
                    ));
                    Yii::app()->end();
            }

            //1.从系统找出已停售有库存的子sku （product_type=1 或单品product_type=0）
            $flag_while = true;
            $limit = 50;
            $offset = 0;
            while($flag_while){
                $exe_time = time();
                if($exe_time - $time > 18000){
                    echo '执行超过5小时';
                    exit;
                }
                //从产品表查出已停售有库存的 sku
                $list = Product::model()->getDbConnection()->createCommand()
                        ->from(Product::model()->tableName() .' p')
                        //->leftJoin(WarehouseSkuMap::model()->tableName() . ' w', "p.sku=w.sku")
                        ->leftJoin('ueb_warehouse.ueb_warehouse_sku_map w', "p.sku=w.sku")
                        ->select('p.sku,p.product_type,w.available_qty')
                        ->where('p.product_status=:product_status', array(':product_status'=> Product::STATUS_STOP_SELLING))
                        ->andWhere('p.product_type<>:product_type', array(':product_type'=> Product::PRODUCT_MULTIPLE_MAIN))
                        ->andWhere('w.warehouse_id=:warehouse_id', array(':warehouse_id'=> WarehouseSkuMap::WARE_HOUSE_GM))
                        ->andWhere('w.available_qty > 0')
                        ->order('p.id asc')
                        ->limit($limit, $offset)
                        ->queryAll();
                $offset += $limit;
                if (empty($list)) {
                    $flag_while = false;
                    break;
                }
                //批量添加到待上传列表
                JoomProductStatistic::model()->batchAddStopProductFromWishListing($list, $accountID);
                //var_dump(JoomProductStatistic::model()->getErrMsg());
                //exit;
            }
            echo 'end';
            exit;

	}
        
        /**
	 * @desc 查出未添加到待刊登列表的sku并导出
         * 172.16.1.21/joom/Joomproductstatistic/ExportSkuNotAdd/account_id/1/type/7/export_type/1/begin_id/1/end_id/5000
	 * @throws Exception
	 */
        public function actionExportSkuNotAdd() {
            set_time_limit(2*3600);
            ini_set('memory_limit','2048M');
            $accountID = Yii::app()->request->getParam('account_id');
            //待清仓：6；已停售：7
            $type = Yii::app()->request->getParam('type');
            // 导出类型：1只导出sku;2导出全部信息
            $export_type = Yii::app()->request->getParam('export_type');
            // 开始和结束product表的id，用于多线程查询和分段导出
            $begin_id = Yii::app()->request->getParam('begin_id');
            $end_id = Yii::app()->request->getParam('end_id');
            if(!$export_type){
                $export_type = 1;
            }
            $flag_while = true;
            $limit = 50;
            $offset = 0;
            $result_data = array();
            
            //excel导出数据
            while($flag_while){
                //从产品表查出已停售\待清仓有库存的 sku
                $query = Product::model()->getDbConnection()->createCommand()
                        ->from(Product::model()->tableName() .' p')
                        ->leftJoin('ueb_warehouse.ueb_warehouse_sku_map w', "p.sku=w.sku")
                        ->where('p.product_status=:product_status', array(':product_status'=> $type))
                        ->andWhere('p.product_type<>:product_type', array(':product_type'=> Product::PRODUCT_MULTIPLE_MAIN))
                        ->andWhere('w.warehouse_id=:warehouse_id', array(':warehouse_id'=> WarehouseSkuMap::WARE_HOUSE_GM))
                        ->andWhere('w.available_qty > 0');
                
                if($begin_id && $end_id){
                    $query = $query->andWhere("p.id between '{$begin_id}' and '{$end_id}'");
                }
                
                if($export_type == 1){
                    $query = $query->select('p.sku');
                } else {
                    $query = $query->select('p.sku,p.product_type,w.available_qty,d.title as d_title,z.title as z_title,d.description');
                    $query = $query->leftJoin('ueb_product.ueb_product_description d', "p.id=d.product_id");
                    $query = $query->leftJoin('ueb_product.ueb_product_description z', "p.id=z.product_id");
                    $query = $query->andWhere("d.language_code = 'english'");
                    $query = $query->andWhere("z.language_code = 'Chinese'");
                }
                $list = $query
                ->order('p.id asc')
                ->limit($limit, $offset)
                ->queryAll();
                
                $offset += $limit;
                if (empty($list)) {
                    $flag_while = false;
                    break;
                }
                
                //查询未添加到待刊登列表的sku并导出（账号1）
                foreach ($list as $detail){
                    $product_type = '';
                    $available_qty = '';
                    $title_en = '';
                    $title_cn = '';
                    $description = '';
                    
                    $sku = $detail['sku'];
                    $is_add = JoomProductVariantsAdd::model()->find("sku='{$sku}'");
                    if(!$is_add){
                        //echo $sku . ',';continue;
                        if($export_type != 1){
                            $wish_is_upload = JoomVariants::model()->getDbConnection()->createCommand()
                                    ->select('sku')
                                    ->from('market_wish.ueb_listing_variants')
                                    ->where("sku='{$sku}'")
                                    ->limit(1)
                                    ->queryScalar();
                            if(!$wish_is_upload){
                                $product_type = $detail['product_type'];
                                $available_qty = $detail['available_qty'];
                                $title_en = $detail['d_title'];
                                $title_cn = $detail['z_title'];
                                $description = $detail['description'];
                                $description = strip_tags($description,'<b> <br>');
                            }
                            
                        }
                        
                        $result_data[] = array(
                            'A' => $sku,
                            'B' => $product_type,
                            'C' => $available_qty,
                            'D' => $title_en,
                            'E' => $title_cn,
                            'F' => $description,
                        );
                    }
                }

            }
            
            //var_dump($result_data);exit;
            //excel处理
            $PHPExcel = new MyExcel();
            $column_width = array(15,15,15,70,30,80);
            Yii::import('application.vendors.MyExcel');
            //获取可用库存
            
            $file = 'sku.xlsx';
            $PHPExcel->export_excel(array('sku','产品类型','可用库存','英文标题','中文标题','描述'),$result_data,'sku.xls',20000,$output=1,$column_width,$isCreate=false);
            $PHPExcel->file = $file;
            $PHPExcel->download_excel(1);
            //echo $this->successJson(array('message'=>'执行完成！'));
            exit;
        }
        
        /**
	 * @desc 从excel中上传wish上传过的sku
         * 172.16.1.21/joom/Joomproductstatistic/PublishExcelSkuFromWish
	 * @throws Exception
	 */
        public function actionPublishExcelSkuFromWish() {
            //1.导入excel文件获取sku（tags）
            //2.a.查库存、从wish复制 tags等数据
            //2.b.查库存、从excel中取tags
            //3保存数据
            
            set_time_limit(2*3600);
            ini_set('memory_limit','2048M');
            $accountID = 2;
            $PHPExcel = new MyExcel();
            //上传文件
            if($_FILES){
                if(empty($_FILES['csvfilename']['tmp_name'])){
                        echo $this->failureJson(array('message'=>"文件上传失败"));
                        Yii::app()->end();
                }
                $file = $_FILES['csvfilename']['tmp_name'];

                //excel处理
                Yii::import('application.vendors.MyExcel');
                $data = $PHPExcel->get_excel_con($file);
                foreach ($data as $key => $rows) {
                    if($key == 1){
                        continue;
                    }
                    $sku = trim($rows['A']);
                    if (empty($sku)) {
                        continue;
                    }
                    //var_dump($sku);exit;
                    $sku = MHelper::excelSkuSet($sku);
                    $result_available = WarehouseSkuMap::model()->dbConnection->createCommand()
                        ->select('available_qty')
                        ->from(WarehouseSkuMap::model()->tableName())
                        ->where('sku = "' . $sku . '"')
                        ->andWhere('warehouse_id = "' . WarehouseSkuMap::WARE_HOUSE_GM . '"')
                        ->limit(1)
                        ->queryColumn();
                    $available_qty = empty($result_available) ? 0 : $result_available['0'];
                    if($available_qty <= 0){
                        continue;
                    }
                    
                    //批量添加到待上传列表
                    $list = array(
                        array(
                            'sku' => $sku,
                            'available_qty' => $available_qty,
                            'product_type' => '',
                        )
                    );
                    //JoomProductStatistic::model()->batchAddStopProductFromWishListing($list, $accountID);
                    JoomProductStatistic::model()->batchAddProductFromExcel($list, $accountID);
                }
                
            }
            $this->render('upload');
            exit;
        }
        
        
        /**
	 * @desc 从excel中上传wish上传过的sku
         * 172.16.1.21/joom/Joomproductstatistic/UpdateTagsForAdd
	 * @throws Exception
	 */
        public function actionUpdateTagsForAdd(){
            //根据excel更新tags
            set_time_limit(2*3600);
            ini_set('memory_limit','2048M');
            $accountID = 2;
            $PHPExcel = new MyExcel();
            //上传文件
            if($_FILES){
                if(empty($_FILES['csvfilename']['tmp_name'])){
                        echo $this->failureJson(array('message'=>"文件上传失败"));
                        Yii::app()->end();
                }
                $file = $_FILES['csvfilename']['tmp_name'];

                //excel处理
                Yii::import('application.vendors.MyExcel');
                $data = $PHPExcel->get_excel_con($file);
                foreach ($data as $key => $rows) {
                    if($key == 1){
                        continue;
                    }
                    $sku = trim($rows['A']);
                    if (empty($sku)) {
                        continue;
                    }
                    $tags = trim($rows['B']);
                    if (empty($tags)) {
                        continue;
                    }
                    //var_dump($sku);exit;
                    $sku = MHelper::excelSkuSet($sku);
                    $add_id = JoomProductVariantsAdd::model()->dbConnection->createCommand()
                        ->select('a.id')
                        ->from(JoomProductVariantsAdd::model()->tableName() .' v')
                        ->leftJoin(JoomProductAdd::model()->tableName() .' a', 'a.id=v.add_id')
                        ->where('v.sku = "' . $sku . '" OR a.parent_sku="'.$sku.'"')
                        ->andWhere("a.account_id=2")
                        ->limit(1)
                        ->queryScalar();
                    if($add_id){
                        JoomProductAdd::model()->updateByPk($add_id, array('tags' => $tags));
                    }
                    
                }
                
            }
            $this->render('upload');
            exit;
        }
        
        /**
	 * @desc 删除错误数据（按时间）
         * 172.16.1.21/joom/Joomproductstatistic/DeleteWrongAdd
	 * @throws Exception
	 */
        public function actionDeleteWrongAdd(){
            //删除多属性表数据
            JoomProductVariantsAdd::model()->getDbConnection()->createCommand()->delete(JoomProductVariantsAdd::model()->tableName(), "create_time > '2016-06-24 17:00:17'");
            //删除主表数据
            JoomProductAdd::model()->getDbConnection()->createCommand()->delete(JoomProductAdd::model()->tableName(), "create_time > '2016-06-24 17:00:17'");
        }
        
        /**
	 * @desc 上传之前先上传图片，并更新remote_main_img）
         * 172.16.1.21/joom/Joomproductstatistic/UploadImageOnly/begin_id/11400/end_id/11500
	 * @throws Exception
	 */
        public function actionUploadImageOnly(){
            // 开始和结束add表的id，用于多线程查询和分段上传图片
            $begin_id = Yii::app()->request->getParam('begin_id');
            $end_id = Yii::app()->request->getParam('end_id');
            set_time_limit(2*3600);
            ini_set('memory_limit','2048M');
            $flag_while = true;
            $limit = 50;
            $offset = 0;
            $accountId = 2;
            //excel导出数据
            while($flag_while){
                $list = JoomProductAdd::model()->dbConnection->createCommand()
                            ->select('id, main_image,remote_main_img')
                            ->from(JoomProductAdd::model()->tableName())
                            ->where("update_time > '2016-06-25 08:00:17'")
                            //->where("update_time > '2016-06-24 16:00:26'")
                            //->where("remote_main_img = ''")
                            ->andWhere("account_id=2")
                            ->andWhere("id between '{$begin_id}' and '{$end_id}'")
                            ->order('id asc')
                            ->limit($limit, $offset)
                            ->queryAll();

                $offset += $limit;
                if (empty($list)) {
                    $flag_while = false;
                    break;
                }
                
                foreach ($list as $data){
                
                    //上传图片
                    if($data['remote_main_img']){
                        
                    }elseif($data['main_image']){
                            $joomProductAddModel = new JoomProductAdd();
                            $remoteImgUrl = $joomProductAddModel->uploadImageToServer($data['main_image'], $accountId);
                            if(!$remoteImgUrl){
                                
                                    //throw new Exception($joomProductAddModel->getErrorMsg());
                            }
                            $joomProductAddModel->updateProductAddInfoByPk($data['id'], array(
                                            'remote_main_img'=>$remoteImgUrl
                            ));
                    }
                }
                
            }
        }
        
        /**
	 * @desc 更新库存
         * 172.16.1.21/joom/Joomproductstatistic/UpdateInventory
	 * @throws Exception
	 */
        public function actionUpdateInventory(){
            set_time_limit(4600);
            ini_set('memory_limit','2048M');
            $flag_while = true;
            $limit = 50;
            $offset = 0;
            $accountId = 1;
            
            while($flag_while){
                $list = JoomProductVariantsAdd::model()->dbConnection->createCommand()
                            ->select('sku,online_sku,inventory')
                            ->from(JoomProductVariantsAdd::model()->tableName())
                            ->where("inventory <> '1000'")
                            ->andWhere("upload_status=1")
                            ->order('id asc')
                            ->limit($limit, $offset)
                            ->queryAll();

                $offset += $limit;
                if (empty($list)) {
                    $flag_while = false;
                    break;
                }
                $count = 0;
                foreach ($list as $data){
                    $request = new UpdateInventoryRequest();
                    $request->setSku($data['online_sku']);
                    $request->setInventory($data['inventory']);
                    $request->setAccount($accountId);
                    $response = $request->setRequest()->sendRequest()->getResponse();

                    if($request->getIfSuccess()){
                        $count++;
                    } else {
                        echo $data['sku'] .'_'.$data['online_sku'] ."<br />" ;
                        $message    = $request->getErrorMsg();
                        echo $message;
                    }
                }
                
            }
            echo $count;
        }
        
}