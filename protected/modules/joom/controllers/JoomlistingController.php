<?php

/**
 * @desc Joom listing
 * @author Gordon
 * @since 2015-06-02
 */
class JoomlistingController extends UebController
{
    private $_model = null;

    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules()
    {
        return array(
/*            array(
                'allow',
                'users' => array('*'),
                'actions' => array('getlisting', 'list')
            ),*/
        );
    }

    /**
     * @desc 初始化工作
     * @see CController::init()
     */
    public function init()
    {
        parent::init();
        $this->_model = new JoomListing();
    }


    public function actionList()
    {
        $this->render("list", array(
            'model' => new JoomListing()
        ));
    }

    /**
     * @desc 单个下线功能
     * @throws Exception
     */
    public function actionOffline()
    {
        set_time_limit(3600);
        $variantId = Yii::app()->request->getParam('id');
        if (empty($variantId)) {
            echo $this->failureJson(
                array(
                    'message' => Yii::t("joom_listing", 'Invalide Product Variants')
                )
            );
            Yii::app()->end();
        }
        try {
            //获取
            $variants = UebModel::model('JoomVariants')->findByPk($variantId);
            if ($variants) {
                $sku = $variants->online_sku;
                $accountID = $variants->account_id;
                $joomLog = new JoomLog;
                $logID = $joomLog->prepareLog($accountID, JoomListing::EVENT_DISABLED_VARIANTS);
                if ($logID) {
                    //1.检查账号是可以提交请求报告
                    $checkRunning = $joomLog->checkRunning($accountID, JoomListing::EVENT_DISABLED_VARIANTS);
                    if (!$checkRunning) {
                        $joomLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                        throw new Exception(Yii::t('systems', 'There Exists An Active Event'));
                    } else {
                        //插入本次log参数日志(用来记录请求的参数)
                        $eventLog = $joomLog->saveEventLog(JoomListing::EVENT_DISABLED_VARIANTS, array(
                            'log_id' => $logID,
                            'account_id' => $accountID,
                            'start_time' => date('Y-m-d H:i:s'),
                            'end_time' => date('Y-m-d H:i:s'),
                        ));
                        //设置日志为正在运行
                        $joomLog->setRunning($logID);
                        $result = $this->_model->disabledVariants($accountID, $sku);
                        if ($result['success']) {
                            //UebModel::model('JoomVariants')->updateAll(array('enabled'=>0), "account_id={$accountID} AND online_sku in('".implode("','", $result['success'])."')");
                            UebModel::model('JoomVariants')->disabledJoomVariantsByOnlineSku($result['success'], $accountID);
                            $joomLog->setSuccess($logID);
                            $joomLog->saveEventStatus(JoomListing::EVENT_DISABLED_VARIANTS, $eventLog, JoomLog::STATUS_SUCCESS);
                            echo $this->successJson(array('message' => Yii::t('system', 'Update successful')));
                            Yii::app()->end();
                        } else {
                            $joomLog->setFailure($logID, $this->_model->getExceptionMessage());
                            $joomLog->saveEventStatus(JoomListing::EVENT_DISABLED_VARIANTS, $eventLog, JoomLog::STATUS_FAILURE);
                            throw new Exception($this->_model->getExceptionMessage());
                        }
                    }
                }
            }
            throw new Exception('No Invalide Request');
        } catch (Exception $e) {
            echo $this->failureJson(
                array(
                    'message' => $e->getMessage(),
                    //'message'=>Yii::t("system", 'Update failure')
                )
            );
            Yii::app()->end();
        }

    }

    /**
     * @desc 批量下架joom产品
     */
    public function actionBatchoffline()
    {
        set_time_limit(3600);
        $variantsIds = Yii::app()->request->getParam('joom_varants_ids');
        if (empty($variantsIds)) {
            echo $this->failureJson(array(
                'message' => Yii::t('joom_listing', 'Not Specify Sku Which Need To Inactive')
            ));
            Yii::app()->end();
        }
        $variants = UebModel::model('JoomVariants')->findAllByPk($variantsIds);
        if ($variants) {
            $newVariants = $successRes = array();
            foreach ($variants as $variant) {
                $newVariants[$variant['account_id']][] = $variant['online_sku'];
            }
            unset($variants);
            foreach ($newVariants as $accountID => $variant) {
                $joomLog = new JoomLog;
                $logID = $joomLog->prepareLog($accountID, JoomListing::EVENT_DISABLED_VARIANTS);
                if ($logID) {
                    //1.检查账号是可以提交请求报告
                    $checkRunning = $joomLog->checkRunning($accountID, JoomListing::EVENT_DISABLED_VARIANTS);
                    if (!$checkRunning) {
                        $joomLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                    } else {
                        //插入本次log参数日志(用来记录请求的参数)
                        $eventLog = $joomLog->saveEventLog(JoomListing::EVENT_DISABLED_VARIANTS, array(
                            'log_id' => $logID,
                            'account_id' => $accountID,
                            'start_time' => date('Y-m-d H:i:s'),
                            'end_time' => date('Y-m-d H:i:s'),
                        ));
                        //设置日志为正在运行
                        $joomLog->setRunning($logID);
                        $result = $this->_model->disabledVariants($accountID, $variant);

                        if ($result['success']) {
                            $successRes = array_merge($successRes, $result['success']);
                            //更新本地
                            //UebModel::model('JoomVariants')->updateAll(array('enabled'=>0), "account_id={$accountID} AND online_sku in('".implode("','", $successRes)."')");
                            UebModel::model('JoomVariants')->disabledJoomVariantsByOnlineSku($result['success'], $accountID);
                            $joomLog->setSuccess($logID);
                            $joomLog->saveEventStatus(JoomListing::EVENT_DISABLED_VARIANTS, $eventLog, JoomLog::STATUS_SUCCESS);
                        } else {
                            $joomLog->setFailure($logID, $this->_model->getExceptionMessage());
                            $joomLog->saveEventStatus(JoomListing::EVENT_DISABLED_VARIANTS, $eventLog, JoomLog::STATUS_FAILURE);
                        }
                    }
                }
            }
            if ($successRes) {
                echo $this->successJson(array('message' => Yii::t('system', 'Update successful')));
                Yii::app()->end();
            }
        }
        echo $this->failureJson(
            array(
                'message' => Yii::t("system", 'Update failure')
            )
        );
        Yii::app()->end();
    }

    /**
     * @desc 系统自动导入下线sku, 条件：待清仓且可用库存小于等于0
     * @link /joom/joomlisting/autoimportofflinetask
     */
    public function actionAutoimportofflinetask()
    {
        set_time_limit(3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $nowTime = date("Y-m-d H:i:s");
        $productTemp = ProductTemp::model();
        $res = $productTemp->getDbConnection()->createCommand()
            ->select("count(*) as total")
            ->from($productTemp->tableName())
            ->where("product_status IN(6,7) and available_qty<=0")
            ->andWhere("product_is_multi!=2")
            ->queryRow();
        $total = $res['total'];
        $pageSize = 2000;
        $pageCount = ceil($total / $pageSize);
        for ($page = 1; $page <= $pageCount; $page++) {
            $offset = ($page - 1) * $pageSize;
            $res = $productTemp->getDbConnection()->createCommand()
                ->select("sku")
                ->from($productTemp->tableName())
                ->where("product_status IN(6,7) and available_qty<=0")
                ->andWhere("product_is_multi!=2")
                ->order("sku asc")
                ->limit($pageSize, $offset)
                ->queryAll();
            if (empty($res)) {
                continue;
            }
            foreach ($res as $v) {
                $rows = array();
                $variationInfos = JoomVariants::model()->filterByCondition('p.account_id', " v.enabled=1 and v.sku='{$v['sku']}' ");
                if (!empty($variationInfos)) {
                    foreach ($variationInfos as $vs) {
                        $rows[] = array(
                            'sku' => $v['sku'],
                            'account_id' => $vs['account_id'],
                            'status' => 0,
                            'create_user_id' => (int)Yii::app()->user->id,
                            'create_time' => $nowTime,
                            'type' => 2,//系统导入
                        );
                    }
                }
                if ($rows) {
                    $res = JoomOfflineTask::model()->insertBatch($rows);
                }
            }
        }
        Yii::app()->end('finish');
    }

    /**
     * @desc 导入csv文件批量下架
     */
    public function actionImportcsvoffline()
    {
        set_time_limit(3600);
        ini_set('memory_limit', '2048M');
        if (isset($_POST) && $_POST) {
            try {
                if (empty($_FILES['csvfilename']['name'])) {
                    throw new Exception(Yii::t('amazon_product', 'No csv file upload'));
                }
                $accounts = isset($_POST['accounts']) ? $_POST['accounts'] : null;
                if (empty($accounts))
                    throw new Exception(Yii::t('amazon_product', 'No chose account'));
                $fp = fopen($_FILES['csvfilename']['tmp_name'], 'rb');
                if ($fp) {//导入SKU
                    $data = array();
                    $i = 0;
                    $fieldName = 'SKU';
                    $fieldIndex = 0;
                    $hasSkuField = false;
                    $joomOfflineTaskModel = UebModel::model('JoomOfflineTask');
                    $row = 0;
                    while ($value = fgetcsv($fp, 65535)) {
                        if (!isset($value[0])) continue;
                        $fields = explode(" ", $value[0]);
                        if ($fields) {
                            $row++;
                            if ($i == 0) {
                                foreach ($fields as $key => $_field) {
                                    if (strtoupper(trim($_field)) == $fieldName) {
                                        $fieldIndex = $key;
                                        $hasSkuField = true;
                                    }
                                }
                                if (!$hasSkuField)
                                    throw new Exception(Yii::t('amazon_product', 'No sku field'));
                                $i++;
                                continue;
                            }

                            foreach ($accounts as $account) {
                                $data[] = array(
                                    'sku' => trim($fields[$fieldIndex]),
                                    'account_id' => $account,
                                    'status' => 0,
                                    'create_user_id' => Yii::app()->user->id,
                                    'create_time' => date('Y-m-d H:i:s')
                                );
                            }
                            if ($row % 50 == 0) {
                                $res = JoomOfflineTask::model()->insertBatch($data);
                                $data = array();
                            }
                        }
                    }
                    if (!empty($data)) {
                        $res = JoomOfflineTask::model()->insertBatch($data);
                    }
                }
                echo $this->successJson(array(
                    'message' => Yii::t('amazon_product', 'Upload success'),
                    'callbackType' => 'closeCurrent'
                ));
                Yii::app()->end();
            } catch (Exception $e) {
                echo $this->failureJson(array('message' => $e->getMessage()));
                Yii::app()->end();
            }
        } else {
            //获取全部可用账号
            $accounts = UebModel::model('JoomAccount')->getAvailableIdNamePairs();

            $this->render('importcsvoffline', array(
                'accounts' => $accounts, 'model' => $this->_model
            ));
        }
    }

    /**
     * @desc 下线任务
     * @link /joom/joomlisting/offlinetask
     */
    public function actionOfflinetask()
    {
        set_time_limit(3600);
        ini_set('memory_limit', '2048M');
        ini_set('display_errors', true);

        $time = time();
        $type = Yii::app()->request->getParam("type");
        if ($type == 'query') {
            //白天执行查询
            $flag_while = true;
            $flag_online = false;
        } else {
            //晚上执行下架
            $flag_while = true;
            $flag_online = true;
        }

        while ($flag_while) {
            $exe_time = time();
            if (($exe_time - $time) >= 36000) {
                exit('执行超过10小时');
            }
            $joomOfflineTaskModel = new JoomOfflineTask();
            $taskListing = $joomOfflineTaskModel->getJoomTaskListByStatus(JoomOfflineTask::UPLOAD_STATUS_PENDING);
            if ($taskListing) {
                foreach ($taskListing as $listing) {
                    $data = array(
                        'process_time' => date('Y-m-d H:i:s'),
                        'status' => 1,
                    );
                    $sku = $listing['sku'];
                    //$parentInfo = $this->_model->getProductJoinVariantsBySkus($sku, $listing['account_id']);
                    $parentInfo = $this->_model->getListingSkusForOffline($sku, $listing['account_id']);
                    if ($parentInfo) {
                        JoomOfflineTask::model()->getDbConnection()->createCommand()->update("ueb_joom_offline_task", $data, "id = " . $listing['id']);
                    } else {
                        JoomOfflineTask::model()->getDbConnection()->createCommand()->delete("ueb_joom_offline_task", "id = " . $listing['id']);
                    }
                }
            } else {
                $flag_while = false;
            }
        }

        while ($flag_online) {
            $exe_time = time();
            if (($exe_time - $time) >= 36000) {
                exit('执行超过10小时');
            }
            $joomOfflineTaskModel = new JoomOfflineTask();
            $taskListing = $joomOfflineTaskModel->getJoomTaskListByStatus(JoomOfflineTask::UPLOAD_STATUS_PROCESSING);
            if ($taskListing) {
                $newTaskListing = array();
                foreach ($taskListing as $listing) {
                    $sku = $listing['sku'];
                    $newTaskListing[$listing['account_id']]['orig'][] = $sku;
                    //$parentInfo = $this->_model->getProductJoinVariantsBySkus($sku, $listing['account_id']);
                    $parentInfo = $this->_model->getListingSkusForOffline($sku, $listing['account_id']);
                    if ($parentInfo) {
                        //判断主子sku
                        if ($parentInfo['psku'] == $sku) {
                            $newTaskListing[$listing['account_id']]['parent'][$sku] = $parentInfo['parent_sku'];
                        } else {
                            $newTaskListing[$listing['account_id']]['child'][$sku] = $parentInfo['online_sku'];
                        }
                    } else {
                        JoomOfflineTask::model()->getDbConnection()->createCommand()->delete("ueb_joom_offline_task", "id = " . $listing['id']);
                    }
                }
                foreach ($newTaskListing as $accountId => $itemData) {
                    $parentSku = isset($itemData['parent']) ? $itemData['parent'] : null;
                    $childSku = isset($itemData['child']) ? $itemData['child'] : null;

                    $this->_doOffline($parentSku, $accountId, true);
                    $this->_doOffline($childSku, $accountId, false);
                }
            } else {
                $flag_online = false;
            }
        }

        echo "finished";
    }

    /**
     * @desc 执行下线动作
     * @param unknown $itemData
     * @param unknown $accountId
     * @param string $isParent
     * @return boolean
     */
    private function _doOffline($itemData, $accountId, $isParent = true)
    {
        if (!$itemData) {
            return false;
        }

        $joomOfflineTaskModel = new JoomOfflineTask;
        $parentSku = $itemData;

        if ($isParent) {
            $result = $this->_model->disabledProduct($accountId, $itemData);
        } else {
            $result = $this->_model->disabledVariants($accountId, $itemData);
        }
        if ($result['success']) {
            $successSkus = array();
            foreach ($result['success'] as $sucsku) {
                $keys = array_keys($itemData, $sucsku);
                if ($keys)
                    $successSkus = array_merge($successSkus, $keys);
            }
            $data = array('status' => JoomOfflineTask::UPLOAD_STATUS_SUCCESS,
                'process_time' => date("Y-m-d H:i:s"),
                'response_msg' => 'success');
            $skus = '';
            foreach ($successSkus as $item) {
                $skus .= "'{$item}',";
            }
            $skus = trim($skus, ',');
            $conditions = 'account_id=' . $accountId . ' AND sku in(' . $skus . ')';
            $joomOfflineTaskModel->updateJoomTask($data, $conditions);

            // 更新本地产品
            if ($isParent) {
                UebModel::model('JoomListing')->disabledJoomProductByOnlineSku($result['success'], $accountId);
            } else {
                UebModel::model('JoomVariants')->disabledJoomVariantsByOnlineSku($result['success'], $accountId);
            }
        }
        if ($result['failure']) {
            $failureSkus = array();
            $data = array('status' => JoomOfflineTask::UPLOAD_STATUS_FAILURE,
                'process_time' => date("Y-m-d H:i:s"));
            foreach ($result['failure'] as $failsku) {
                $keys = array_keys($itemData, $failsku);
                if ($keys) {
                    $data['response_msg'] = isset($result['errorMsg'][$failsku]) ? $result['errorMsg'][$failsku] : 'unkown';
                    $skus = "'" . implode("','", $keys) . "'";
                    $conditions = 'account_id=' . $accountId . ' AND sku in(' . $skus . ')';
                    $joomOfflineTaskModel->updateJoomTask($data, $conditions);
                }
            }
        }

//    	if(!$result['success']){
//    		echo $this->_model->getExceptionMessage();
//    	}
    }

    /**
     * @desc 创建加密sku
     */
    public function actionCreatesku()
    {
        $this->render('createsku');
    }

    public function actionCreateencrysku()
    {
        $sku = Yii::app()->request->getParam('sku');
        if ($sku) {
            $encrySku = new encryptSku();
            $ensku = $encrySku->getEncryptSku($sku);
            echo $this->successJson(array('message' => $ensku));
        } else {
            echo $this->failureJson(array('message' => Yii::t('joom_listing', 'Please Input Main SKU')));
        }
    }

    /**
     * @desc /joom/joomlisting/getlisting/account_id/xx
     */
    public function actionGetlisting()
    {
        set_time_limit(4 * 3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);
        $accountID = Yii::app()->request->getParam('account_id');
        if ($accountID) {
            //创建日志
            $joomLog = new JoomLog();
            $logID = $joomLog->prepareLog($accountID, JoomListing::EVENT_NAME);
            if ($logID) {
                //检查账号是否可以拉取
                $checkRunning = $joomLog->checkRunning($accountID, JoomListing::EVENT_NAME);
                if (!$checkRunning) {
                    $joomLog->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
                    $msg = Yii::t('systems', 'There Exists An Active Event');
                    Yii::app()->end($msg);
                } else {
                    //插入本次log参数日志(用来记录请求的参数)
                    $time = date('Y-m-d H:i:s');
                    $eventLogID = $joomLog->saveEventLog(JoomListing::EVENT_NAME, array(
                        'log_id' => $logID,
                        'account_id' => $accountID,
                        'start_time' => $time,
                        'end_time' => $time,
                    ));
                    //设置日志正在运行
                    $joomLog->setRunning($logID);
                    //拉取产品
                    $joomListing = new JoomListing();
                    $joomListing->setAccountID($accountID);
                    $flag = $joomListing->getAccountListings();
                    //更新日志信息
                    if ($flag) {
                        $joomLog->setSuccess($logID);
                        $joomLog->saveEventStatus(JoomListing::EVENT_NAME, $eventLogID, JoomLog::STATUS_SUCCESS);
                    } else {
                        $joomLog->setFailure($logID, $joomListing->getExceptionMessage());
                        $joomLog->saveEventStatus(JoomListing::EVENT_NAME, $eventLogID, JoomLog::STATUS_FAILURE);
                    }
                }
            }
        } else {
            $accountList = JoomAccount::model()->getCronGroupAccounts();
            //循环每个账号发送一个拉listing的请求
            foreach ($accountList as $accountID) {
                MHelper::runThreadSOCKET('/' . $this->route . '/account_id/' . $accountID);
                sleep(1);
            }
        }
    }

    /**
     * 导入下架任务表删除没有线上sku的数据
     */
    public function actionDeleteNoOnlineSku()
    {
        JoomProduct::model()->getDbConnection()->createCommand()->delete("ueb_joom_offline_task", "response_msg = 'no online sku'");
    }


    /**
     * @desc 更新主listing表的listing是否多属性标识
     * @link /joom/joomlisting/updateisvariationbyalllisting
     */
    public function actionUpdateisvariationbyalllisting()
    {
        set_time_limit(3600);
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $list = JoomListing::model()->getListByCondition("id", "1 = 1");
        if ($list) {
            foreach ($list as $item) {
                $isVaration = 0;
                $varationList = array();
                $id = $item['id'];

                $varationList = JoomVariants::model()->getJoomProductVarantNumsList("listing_id = {$id}");
                if ($varationList) {
                    $isVaration = count($varationList) > 1 ? 1 : 0;//是否多属性
                }

                //更新多属性标识
                if ($isVaration > 0) {
                    JoomListing::model()->updateInfoByID($id, array('is_varation' => 1));
                }
            }
        }
        Yii::app()->end('Finish');
    }

    public function actionExportListing()
    {
        try {
            set_time_limit(3600);
            Yii::import('application.vendors.PHPExcel');
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);

            /* 账号  sku 产品名称 销售数 收藏数  产品审核状态  价格 */
            $xlsHeader = array(
                '账号',
                'SKU',
                '产品状态',
                '产品名称',
                '产品属性',
                '销售数',
                '收藏数',
                '产品审核状态',
                'Tags'
            );
            foreach ($xlsHeader as $k => $v) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($k, 1, $v);
            }


            $accountList = JoomAccount::getIdNamePairs();
            $limit = Yii::app()->request->getParam('limit', 1000);
            $offset = Yii::app()->request->getParam('offset', 0);
            $indexer = 2;

            $listings = JoomListing::model()->getListingWithVariants($limit, $offset, 'p.id');

            $statusConverter = function ($reviewStatus) {

                switch ($reviewStatus) {
                    case JoomListing::REVIEW_STATUS_REJECTED:
                        $str = Yii::t('joom_listing', 'Review Rejected Status');
                        break;
                    case JoomListing::REVIEW_STATUS_PENDING:
                        $str = Yii::t('joom_listing', 'Review Pending Status');
                        break;
                    case JoomListing::REVIEW_STATUS_APPROVED:
                        $str = Yii::t('joom_listing', 'Review Approved Status');
                        break;
                }

                return $str;

            };

            $exportData = array();
            foreach ($listings as $key => $listing) {
                $attributeList = array();
                if ($listing['color']) {
                    $attributeList[] = $listing['color'];
                }
                if ($listing['size']) {
                    $attributeList[] = $listing['size'];
                }
                $exportData[$indexer] = array(
                    $accountList[$listing['account_id']],
                    $listing['sku'],
                    $listing['enabled'] ? '产品在线' : '产品下线',
                    //$listing['sub_sku'],
                    $listing['name'],
                    join(",", $attributeList),
                    $listing['num_sold'],
                    $listing['num_saves'],
                    $statusConverter($listing['review_status']),
                    $listing['tags']
                );
                $indexer++;
            }


            foreach ($exportData as $key => $value) {
                foreach ($value as $k => $v) {

                    $coordinate = PHPExcel_Cell::stringFromColumnIndex($k) . $key;
                    //$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($k, $key, $v);
                    $objPHPExcel->getActiveSheet()->setCellValueExplicit($coordinate, $v);
                }
            }


            $outputFilename = 'joom_listing.xls';

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $outputFilename . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
            Yii::app()->end();

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


    public function actionModifyProductData()
    {
        try {
            $id = Yii::app()->request->getParam('id');
            $value = Yii::app()->request->getParam('value');
            $target = Yii::app()->request->getParam('target');


            $allowedAction = array(
                'price',
                'stock'
            );

            if (!$id || !$value || !$target) {
                throw new \Exception('Please input all required filed');
            }
            if (!in_array($target, $allowedAction)) {
                throw new \Exception('Action not allowed');
            }

            $variant = JoomVariants::model()->findByPk($id);

            if (!$variant) {
                throw new \Exception('Variant not found');
            }
            $updateData = array();
            $logEventName = '';
            switch ($target) {
                case 'price':
                    $updateData['price'] = $value;
                    $logEventName = JoomLog::EVENT_UPDATE_PRODUCT_PRICE;
                    break;

                case 'stock':
                    $updateData['inventory'] = $value;
                    $logEventName = JoomLog::EVENT_UPDATE_PRODUCT_STOCK;
                    break;
            }
            $logId = JoomLog::model()->prepareLog($variant['account_id'], $logEventName);

            try {
                JoomLog::model()->setRunning($logId);
                JoomVariants::model()->updateVariantDataOnline($variant['online_sku'], $variant['account_id'], $updateData);
                JoomLog::model()->setSuccess($logId);
                echo $this->successJson(
                    array(
                        'message' => 'Successful'
                    )
                );
            } catch (\Exception $e) {
                JoomLog::model()->setFailure($logId, $e->getMessage());
                throw $e;
            }

        } catch (\Exception $e) {
            echo $this->failureJson(
                array(
                    'message' => $e->getMessage()
                )
            );
        }
    }

    public function actionUpdateProductTitleForm($id)
    {
        try {
            $listing = JoomListing::model()->findByPk($id);
            if (!$listing) {
                throw new \Exception('listing not found');
            }

            $this->render('update-product-title-form', array(
                'listing' => $listing
            ));

        } catch (\Exception $e) {
            echo $this->failureJson(
                array(
                    'message' => $e->getMessage()
                )
            );
        }
    }

    public function actionUpdateProductTitle()
    {
        #error_reporting(2048);
        #ini_set('display_errors', true);
        try {
            $id = Yii::app()->request->getParam('id');
            $title = Yii::app()->request->getParam('name');
            if (!$id || !$title) {
                throw new \Exception(Yii::t('joom', 'Please input required filed'));
            }

            $listing = JoomListing::model()->findByPk($id);
            if (!$listing) {
                throw new \Exception(Yii::t('joom', 'Listing not found'));
            }
            $logModel = new JoomLog();
            $logId = $logModel->prepareLog($listing['account_id'], JoomLog::EVENT_UPDATE_PRODUCT);
            if (!$logId) {
                throw new \Exception(Yii::t('joom', 'System error'));
            }
            try {
                $logModel->setRunning($logId);
                JoomListing::model()->updateOnlineListingData($listing['product_id'], $listing['account_id'], array(
                    'name' => $title
                ));
                $logModel->setSuccess($logId);
                echo $this->successJson(
                    array(
                        'message' => 'Successful'
                    )
                );
            } catch (\Exception $e) {
                $logModel->setFailure($logId, $e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            echo $this->failureJson(
                array(
                    'message' => $e->getMessage()
                )
            );
        }
    }


    public function actionBatchUpdate()
    {
        try {
            $data = Yii::app()->request->getParam("data", "");
            $action = Yii::app()->request->getParam('action');
            if (!$data) {
                throw new \Exception(Yii::t('joom', 'Please input data'));
            }


            $listings = JoomVariants::model()->findVariantListByIds(
                array_keys($data)
            );

            $groupListingsByAccount = array();
            foreach ($listings as $listing) {
                $groupListingsByAccount[$listing['account_id']][] = $listing;
            }
            $partialErrors = array();

            foreach ($groupListingsByAccount as $accountId => $listings) {

                foreach ($listings as $listing) {
                    if (!isset($data[$listing['variant_id']])) {
                        continue;
                    }
                    $logModel = new JoomLog();
                    $logId = $logModel->prepareLog($accountId, $this->getEventNameByAction($action));
                    if (!$logId) {
                        continue;
                    }
                    $logModel->setRunning($logId);

                    $variantData = array();
                    if ($action == 'updatePrice') {
                        $variantData['price'] = $data[$listing['variant_id']]['value'];
                    } elseif ($action == 'updateStock') {
                        $variantData['inventory'] = $data[$listing['variant_id']]['value'];
                    }
                    if (!$variantData) {
                        continue;
                    }
                    try{
                        JoomVariants::model()->updateVariantDataOnline($listing['online_sku'],
                            $listing['account_id'], $variantData);
                        $logModel->setSuccess($logId);
                    }catch (\Exception $e) {
                        $logModel->setFailure($logId, $e->getMessage());
                    }
                }
            }

            echo $this->successJson(array(
                'message'=> Yii::t('joom', 'Successful'),
                //'callbackType'=> 'closeCurrent',
                //'navTabId'=> 'page' . UebModel::model('Menu')->getIdByUrl('/joom/joomlisting/')
            ));

        }catch (\Exception $e) {
            echo $this->failureJson(array(
                'message'=> $e->getMessage()
            ));
        }
    }

    /**
     * 获取日志event name
     * @param $action
     * @return 产品更新
     */
    private function getEventNameByAction($action)
    {
        $eventName = JoomLog::EVENT_UPDATE_PRODUCT;
        switch ($action) {
            case 'updatePrice':
                $eventName = JoomLog::EVENT_UPDATE_PRODUCT_PRICE;
                break;

            case 'updateStock':
                $eventName = JoomLog::EVENT_UPDATE_PRODUCT_STOCK;
                break;
        }
        return $eventName;
    }


    public function actionBatchUpdateForm()
    {
        try {

            $ids = Yii::app()->request->getParam('id');
            $action = Yii::app()->request->getParam('action');
            if (!$ids || !$action) {
                throw new \Exception(Yii::t('joom', 'System error'));
            }
            $ids = explode(',', $ids);
            $listings = JoomVariants::model()->findVariantListByIds(
                $ids
            );


            $this->render('batch-update-form-popup', array(
                'listings' => $listings,
                'action' => $action
            ));
        } catch (\Exception $e) {
            echo $this->failureJson(
                array(
                    'message' => $e->getMessage()
                )
            );
        }

    }

    public function actionCalculateSalePrice()
    {
        try {
            $sku = Yii::app()->request->getParam('sku');
            $profitRate = Yii::app()->request->getParam('profit');

            if (!$profitRate || !$sku) {
                throw new \Exception('System error');
            }

            $joomProductAddModel = new JoomProductAdd();
            $priceObject = $joomProductAddModel->getSalePriceWithProfitRate($sku, $profitRate / 100, '');
            $salePrice = $priceObject->getSalePrice();
            if (!$salePrice) {
                throw new \Exception(Yii::t('joom', 'can not calculate sale price for sku :sku', array(':sku' =>
                    $sku)));
            }

            $profitRate = $priceObject->getProfitRate();
            $profit = $priceObject->getProfit();
            $shippingPrice = $priceObject->getShippingPrice();

            echo $this->successJson(
                array(
                    'salePrice' => $salePrice,
                    'profitRate' => $profitRate,
                    'profit' => $profit,
                    'shippingPrice' => $shippingPrice
                )
            );


        } catch (\Exception $e) {
            echo $this->failureJson(
                array(
                    'message' => $e->getMessage(),
                )
            );
        }
    }
}