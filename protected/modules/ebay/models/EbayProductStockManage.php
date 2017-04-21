<?php
/**
 * @desc ebay库存管理
 * @author yangsh
 * @since 2016-08-31
 */
class EbayProductStockManage extends EbayModel {

	const EVENT_NAME      		= 'revise_inventory';//售出补库存
	const EVENT_FIXED_NAME      = 'fixed_inventory';//来货补库存
    const EVENT_NAME_ADDSTOCK   = 'addstock';//补库存

	/** @var string 异常信息*/
	protected $_Exception   	= null;

    /** @var 统计销量的时间间隔 */
    const INTERVAL_CALCTIME	= 10800;//检查近3小时有没有销量(单位：秒)

    /**@var 状态 */
    const STATUS_DEFAULT		= 0;//默认
    const STATUS_SUCCESS      	= 1;//成功
    const STATUS_FAILURE   		= 2;//失败
    const STATUS_PENDING 		= 3;//暂不补
    const STATUS_INVALID 		= 4;//无效

	public static function model($className = __CLASS__) {
		return parent::model ( $className );
	}

    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_stock_manage';
    }	
		
	/**
	 * 设置异常信息
	 * @param string $message        	
	 */
	public function setExceptionMessage($message) {
		$this->_Exception = $message;
		return $this;
	}

	/**
	 * 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage() {
		return $this->_Exception;
	}		

	/**
	 * @desc 获取最近一次成功的计算时间
	 * @param  string $itemID    
	 * @param  string $onlineSku 
	 * @return string
	 */
	public function getLastSuccessCalcSaleTime($itemID, $onlineSku) {
        $res = $this->dbConnection->createCommand()
	            ->select('calc_sale_count_end')
	            ->from(self::tableName())
	            ->where("item_id='{$itemID}' and online_sku='{$onlineSku}'")
	            ->andWhere("calc_sale_count_end>'0000-00-00'")
	        	->order("calc_sale_count_end desc")
	        	->limit(1)
	            ->queryRow();          
	    return !empty($res) ? $res['calc_sale_count_end'] : '';
	}

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        //echo $cmd->Text;
        return $cmd->queryAll();
    }

    /**
     * @desc 每次准备数据把状态从0改成4
     * @param int $accountID 
     * @return boolean
     */
    public function setDefaultForPrepare($accountID) {
        return $this->dbConnection->createCommand()->update($this->tableName(),array('status'=> self::STATUS_INVALID ), "account_id='{$accountID}' and status=0 ");
    }	

    /**
     * @desc 设置不补
     * @param int $accountID 
     * @return boolean
     */
    public function setInvalid($accountID) {
    	return $this->dbConnection->createCommand()->update($this->tableName(),array('status'=> self::STATUS_INVALID ), "account_id='{$accountID}' and status in(0,2,3) ");
    }

    /**
     * @desc 状态由暂不补恢复默认
     * @param int $accountID 
     * @return boolean
     */
    public function setDefaultForNeedFixstock($accountID) {
    	return $this->dbConnection->createCommand()->update($this->tableName(),array('status'=> self::STATUS_DEFAULT ), "account_id='{$accountID}' and status in(2,3) ");
    }

	/**
	 * [getFormatKey description]
     * @param  string $itemID    
     * @param  string $onlineSku
     * @return string
	 */
	public static function getFormatKey($itemID, $onlineSku) {
		return $itemID.'_'.$onlineSku;
	}    

    /**
     * @desc 获取销售数据
     * @param  integer $accountID 
     * @param  string $startTime 
     * @param  string $endTime  
     * @param  string $itemID    
     * @param  string $onlineSku
     * @return boolean
     */
	public function getSkuSaleList($accountID,$startTime,$endTime,$itemID=null,$onlineSku=null) {
		$platformCode    = Platform::CODE_EBAY;
        $ebayOrderMain   = new EbayOrderMain();
		$ebayOrderDetail = new EbayOrderDetail();
        $cmd = $ebayOrderDetail->dbConnection->createCommand()
        		->select("d.item_id,d.sku as online_sku,SUM(d.quantity) as total")
            	->from($ebayOrderDetail->tableName().' AS d')
                ->join($ebayOrderMain->tableName().' AS m',"m.platform_order_id=d.platform_order_id")
            	->where("m.seller_account_id='{$accountID}' ")
            	->andWhere("d.transaction_id!='0'")//非拍卖下
            	->andWhere("m.created_at >= '{$startTime}' AND m.created_at <= '{$endTime}' ")
            	->group("d.item_id,d.sku");
        if($itemID){
            $cmd->andWhere("d.item_id = '{$itemID}'");
        }
        if ($onlineSku) {
            $cmd->andWhere("d.sku = '{$onlineSku}'");
        }
        $cmd->andWhere("m.seller_account_id not in(".implode(',',EbayAccount::$OVERSEAS_ACCOUNT_ID).")");
        //MHelper::writefilelog('test.txt',$cmd->Text."\r\n");
        $res = $cmd->queryAll();
        if ( empty($res) ) {
			return array();
        }        
        $salelist = array();
		foreach ($res as $v) {
			$skey = self::getFormatKey($v['item_id'],$v['online_sku']);
            $salelist[$skey] = $v;
		}
        return $salelist;		
	}

	/**
	 * @desc 准备数据
     * @param  integer $accountID 
     * @param  string $itemID    
     * @param  string $onlineSku
     * @return boolean   
	 */
	public function prepareData($accountID,$itemID=null,$onlineSku=null) {
        //检查设置零库存状态的记录是否可以自动补库存
        $this->checkZeroStockSet($accountID);
        //每次执行，查看近3个小时内是否有销量, 每2小时间运行一次
		$startTime = date("Y-m-d H:i", time() - self::INTERVAL_CALCTIME );
		$endTime   = date("Y-m-d H:i");      
		//echo 'startTime:'.$startTime,'-->','endTime:'.$endTime."<br>";
    	$dbTransaction = $this->dbConnection->getCurrentTransaction();
        if( !$dbTransaction ){
            $dbTransaction = $this->dbConnection->beginTransaction();
        }
        try {
			//获取销量数据
			$list = array();
	    	$res = $this->getSkuSaleList($accountID,$startTime,$endTime,$itemID,$onlineSku);
            //MHelper::writefilelog('test.txt',print_r($res,true)."\r\n");
	    	if (!empty($res)) {
				foreach ($res as $v) {
	    			$key = self::getFormatKey($v['item_id'],$v['online_sku']);
	    			//从最近一次成功的listing库存记录中calc_sale_count_end做为startTime
	    			$lastSucTime = $this->getLastSuccessCalcSaleTime($v['item_id'], $v['online_sku']);
					//echo $v['item_id'],'----', $v['online_sku'], '---', $lastSucTime,"<br>";
	    			if ($lastSucTime != '' ) {
                        //如果超过1天，则只统计最近一天的销量，避免补过多 add in 2017/01/17
                        if ( (time() - strtotime($lastSucTime)) > 86400 ) {
                            $lastSucTime = date("Y-m-d H:i:s", strtotime("-1 day"));
                        }
	    				$saleResult = $this->getSkuSaleList($accountID,$lastSucTime,$endTime,$v['item_id'], $v['online_sku']);
	    				if ( empty($saleResult[$key]) ) {
	    					continue;
	    				}
	    				$calcSaleStartTime = $lastSucTime;
	    				$count = $saleResult[$key]['total'];
	    			} else {
	    				$calcSaleStartTime = $startTime;
	    				$count = $v['total'];
	    			}
					$list[$key] = array(
						'account_id'            => $accountID,
						'item_id'               => $v['item_id'],
						'online_sku'            => $v['online_sku'],
						'calc_sale_count_start' => $calcSaleStartTime,
						'calc_sale_count_end'   => $endTime,
						'created_at'            => date("Y-m-d H:i:s"),
						'sale_count'            => $count,
						'status'                => 0
					);    			
	    		}
	    	}
	    	if ($list) {
		    	$groupDataList = MHelper::getGroupData($list,500);
		    	foreach ($groupDataList as $groupData) {
		    		$this->batchInsert($this->tableName(),array_keys($groupData[0]),$groupData);
		    	}
	    	}
	    	$dbTransaction->commit();
	    	return true;
        } catch (Exception $e) {
        	$dbTransaction->rollback();
        	echo $e->getMessage()."<br>";
        	$this->setExceptionMessage('准备数据时异常退出');
        	return false;
        }
	}

    /**
     * @desc 检查恢复状态
     */
    public function checkZeroStockSet($accountID) {
        $res = $this->getListByCondition('id,item_id,online_sku',"status=5 and account_id='{$accountID}'");
        if (!empty($res)) {
            foreach ($res as $v) {
                if (!$this->isExistZeroStockSet($v['item_id'], $v['online_sku'])) {
                    $this->dbConnection->createCommand()->update($this->tableName(),array('status'=> self::STATUS_DEFAULT ), "id={$v['id']}");
                }
            }
        }
        return true;
    }

    /**
     * @desc 判断是否有0库存设置
     * 调零status(0待处理,1提交,2成功,3失败) 恢复is_restore(0待恢复,1恢复成功,2恢复失败)
     * @return boolean 
     */
    public function isExistZeroStockSet($itemID,$onlineSku='') {
        $condition = $onlineSku == '' ? '' : " and seller_sku='{$onlineSku}'";
        $model = new EbayZeroStockSku();
        $res = $model->getDbConnection()->createCommand()
                    ->select('id')
                    ->from($model->tableName())
                    ->where("product_id='{$itemID}' and status=2 and is_restore!=1 ".$condition)//有调零且未恢复
                    ->queryRow();
        return empty($res) ? false : true;
    }

	/**
	 * @desc 批量更新listing
     * @param  integer $accountID 
     * @param  string $itemID    
     * @param  string $onlineSku
     * @return boolean  
	 */
    public function updateItemSimpleInfo($accountID,$itemID=null,$onlineSku=null) {
        $ebayProductModel = EbayProduct::model();
        $ebayProductVariantModel = EbayProductVariation::model();
        $cmd = $ebayProductModel->getDbConnection()->createCommand()
                                ->select("t.account_id,t.item_id,t.sku_online,m.id")
                                ->from($ebayProductModel->tableName() . " p")
                                ->join($ebayProductVariantModel->tableName() . " as t", "p.id=t.listing_id")
                                ->join($this->tableName() . " as m", "m.item_id=t.item_id and m.online_sku=t.sku_online")
                                ->where("t.account_id='{$accountID}'")
                                ->andWhere("m.status=0")
                                ->andWhere("p.listing_type in('FixedPriceItem', 'StoresFixedPrice')");
        if($itemID){
            $cmd->andWhere("t.item_id = '{$itemID}'");
        }
        if ($onlineSku) {
            $cmd->andWhere("t.sku_online = '{$onlineSku}'");
        }       
        $cmd->limit(100);
		//echo $cmd->Text."<br>";
        $res = $cmd->queryAll();
        if (empty($res)) {
			$this->setExceptionMessage('没有可更新的listing');
        	return false;
        }
        try {
	        foreach ($res as $v) {
	        	$ebayProductNew = new EbayProduct();
	        	$isOk = $ebayProductNew->updateItemSimpleInfo($v['item_id'],$v['account_id']);
                if ($isOk) {
                    //更新listing更新时间
                    $this->dbConnection->createCommand()->update($this->tableName(),array('calc_listing_count_at'=> date("Y-m-d H:i:s") ), " id={$v['id']} ");
                }else{
                    echo $ebayProductNew->getExceptionMessage()."<br>";
                }
	        }
	        return true;
        } catch (Exception $e) {
        	$this->setExceptionMessage('更新listing时异常退出');
        	return false;
        }
    }

    /**
     * @desc 更新sku修改数量
     * 		限制条件：产品状态为已停售不补 、设置断货的不补， 待清仓直接用可用库存替换，其他状态下定时批量补库存；
		补货数量：
			a、如在设置时间范围内，则根据OMS系统可用库存数量补库存，可用库存为光明本地仓的库存
			如果当天售出数量<=可用库存，则补货数量=当天售出数量
			否则补货数量=可用库存
			当可用库存为0时，不再补货 
            b、设置零库存的，待恢复不补, 恢复后自动补
            c、如果统计最近售出时间超过1天，则只统计最近一天到现在的销量
            d、每2小时检测最近3小时售出的数量，按最近一次成功开始计算售出量
     * @param  integer $accountID 
     * @param  string $itemID    
     * @param  string $onlineSku
     * @return boolean            
     */
    public function updateSkuCount($accountID,$itemID=null,$onlineSku=null) {
        $ebayProductModel = EbayProduct::model();
        $ebayProductVariantModel = EbayProductVariation::model();
        $cmd = $ebayProductVariantModel->getDbConnection()->createCommand()
                                ->select("m.id,t.item_id,t.sku_online,t.sku,t.quantity_available,m.sale_count,p.item_status,p.timestamp")
                                ->from($ebayProductModel->tableName() . " p")
                                ->join($ebayProductVariantModel->tableName() . " as t", "p.id=t.listing_id")
                                ->join($this->tableName() . " as m", "m.item_id=t.item_id and m.online_sku=t.sku_online")
                                ->where("t.account_id='{$accountID}'")
                                ->andWhere("m.status=0")
                                ->andWhere("p.listing_type in('FixedPriceItem', 'StoresFixedPrice')");
        if($itemID){
            $cmd->andWhere("t.item_id = '{$itemID}'");
        }

        if ($onlineSku) {
            $cmd->andWhere("t.sku_online = '{$onlineSku}'");
        }

        $res = $cmd->queryAll();
    	if (empty($res)) {
    		$this->setExceptionMessage('没有可更新的count记录');
    		return false;
    	}

    	try {
	     	foreach ($res as $v) {
				$row                = array();
				$sku                = $v['sku'];
				$availableQty       = $v['quantity_available'];
				$itemStatus 		= $v['item_status'];

				if ($itemStatus != 1) {//listing已下架，则不补
					$row['count'] = 0;
					$row['status'] = 4;
					$row['note'] = 'listing已下架';
				} else {
                    $productInfo        = Product::model()->getProductBySku($sku);
                    $ebayOutofstockInfo = EbayOutofstock::model()->getOneByCondition('id',"sku='{$sku}' and is_outofstock=1");
                    $stockInfo          = WarehouseSkuMap::model()->getListByCondition("available_qty","warehouse_id=41 and sku='{$sku}'");
                    $row['listing_count'] = $availableQty;//在线listing数量
                    if (!empty($ebayOutofstockInfo)) {//设置断货的不补
                        $row['count']  = 0;
                        $row['status'] = 4;//不补
                        $row['note']   = '已设置断货处理';
                    } else if ($productInfo['product_status'] == Product::STATUS_STOP_SELLING ) {//已停售不补
                        $row['count']  = 0;
                        $row['status'] = 4;//不补
                        $row['note']   = '已停售';
                    } else if ($productInfo['product_status'] == Product::STATUS_WAIT_CLEARANCE) { //待清仓直接用可用库存替换
                        $row['status'] = 0;
                        $row['note']   = '可用库存:'.$stockInfo[0]['available_qty'];

                        $available_qty = !empty($stockInfo) && $stockInfo[0]['available_qty'] > 0 ? $stockInfo[0]['available_qty'] : 0;
                        $replenish_qty = $availableQty + $v['sale_count'];
                        
                        $row['count'] = $replenish_qty < $available_qty ? $replenish_qty : $available_qty ;

                        if ($row['count'] <= 0) {
                            $row['status'] = 4;//不补
                            $row['count']  = 0;
                            $row['note']   = '待清仓无可用库存';
                        }
                    } else if ( $this->isExistZeroStockSet($v['item_id'],$v['sku_online']) ) {
                        $row['count']  = 0;
                        $row['status'] = 5;
                        $row['note']   = '有调零库存且未恢复,暂不补';
                    } else {
                        if (!empty($stockInfo) && $stockInfo[0]['available_qty'] > 0) {
                            $row['status'] = 0;
                            $row['note']   = '可用库存:'.$stockInfo[0]['available_qty'];
                            $row['count'] = $v['sale_count'] <= $stockInfo[0]['available_qty'] ? $v['sale_count'] : $stockInfo[0]['available_qty'];
                            if ($row['count'] > 0 ) {
                                $row['count'] += $availableQty;//要修改的数量 = 补货数量 + 在线listing数量
                            } else {
                                $row['count'] = 0;
                            }
                        } else {
                            $row['count']  = 0;
                            $row['status'] = 3;
                            $row['note']   = '可用库存为'. $stockInfo[0]['available_qty'] .',小于1暂不补.';
                        }                    
                    }
				}
                
                //判断是否更新时间
                if ($row) {
                    $this->getDbConnection()->createCommand()->update($this->tableName(),$row,'id='.$v['id']);
                }
	    	}
	    	return true;
    	} catch (Exception $e) {
    		$this->setExceptionMessage('更新的count记录时异常退出');
    		return false;
    	}
    }

    /**
     * @desc 售出后补库存
     * @param  int $accountID
     * @param  string $itemID    
     * @param  string $onlineSku
     * @return boolean   
     */
    public function replenishStock($accountID,$itemID=null,$onlineSku=null) {
    	try {
    		$calcTime = date("Y-m-d H:i:s", time() - 5*60);//北京时间，5分钟内有更新listing的进行操作 
    		//1.准备数据
    		$flag = $this->prepareData($accountID,$itemID,$onlineSku);
    		if (!$flag) {
	    		echo $this->getExceptionMessage()."<br>";
	    		return false;
    		}
    		//2.检查是否需要更新并更新status
    		$flag = $this->updateSkuCount($accountID,$itemID,$onlineSku);
    		if (!$flag) {
	    		echo $this->getExceptionMessage()."<br>";
	    		return false;
    		}
    		//3.更新listing数据
    		$flag = $this->updateItemSimpleInfo($accountID,$itemID,$onlineSku);
    		if (!$flag ) {
    			echo $this->getExceptionMessage()."<br>";
	    		return false;
    		}
    		//4.更新count
    		$flag = $this->updateSkuCount($accountID,$itemID,$onlineSku);
    		if (!$flag) {
	    		echo $this->getExceptionMessage()."<br>";
	    		return false;
    		}
    		//5.补库存
	        $cmd = $this->getDbConnection()->createCommand()
		                            ->select("id,item_id,online_sku,count,note")
		                            ->from($this->tableName())
		                            ->where("account_id='{$accountID}'")
		                            ->andWhere("status=0")
		                            ->andWhere("sale_count>0 ")
		                            ->andWhere("count>0")
		                            ->andWhere("calc_listing_count_at>'{$calcTime}'");
	        if($itemID){
	            $cmd->andWhere("item_id = '{$itemID}'");
	        }
	        if ($onlineSku) {
	            $cmd->andWhere("online_sku = '{$onlineSku}'");
	        }
            //排除海外仓账号
            $cmd->andWhere("account_id not in(".implode(',',EbayAccount::$OVERSEAS_ACCOUNT_ID).")");
            $cmd->order("calc_listing_count_at asc");
            $cmd->limit(2000);
	        $res = $cmd->queryAll();
	    	if (empty($res)) {
	    		return false;
	    	}
	    	foreach ($res as $v) {
	    		$data = array('item_id'=>$v['item_id'],'sku_online'=>$v['online_sku'],'count'=>$v['count']);
	    		$res2 = EbayProduct::model()->reviseEbayListing($accountID, $data);
	    		$status = $res2['errorCode'] == 200 ? 1 : 2;
	    		$note = $res2['errorCode'] != 200 ? $res2['errorMsg'] : 'ok';
				$this->getDbConnection()->createCommand()->update($this->tableName(),array('replenished_at'=>date("Y-m-d H:i:s"), 'status'=>$status, 'note'=>$note.'@'.$v['note']),'id='.$v['id'] );
	    	}
	    	return true;
    	} catch (Exception $e) {
    		echo $e->getMessage()."<br>";
    		$this->setExceptionMessage('售出补库存时出现异常');
    		return false;
    	}
    }

    /**
     * 补库存
     * @param [type] $accountID
     * @param [type] $itemID   
     * @param [type] $onlineSku
     */
    public function addOnlineStock($accountID,$itemID=null,$onlineSku=null) {
        try {
            $calcTime = date("Y-m-d H:i:s", time() - 5*60);//北京时间，5分钟内有更新listing的进行操作
            //补库存
            $cmd = $this->getDbConnection()->createCommand()
                                    ->select("id,item_id,online_sku,count,note")
                                    ->from($this->tableName())
                                    ->where("account_id='{$accountID}'")
                                    ->andWhere("status=0")
                                    ->andWhere("sale_count>0 ")
                                    ->andWhere("count>0")
                                    ->andWhere("calc_listing_count_at>'{$calcTime}'");
            if($itemID){
                $cmd->andWhere("item_id = '{$itemID}'");
            }
            if ($onlineSku) {
                $cmd->andWhere("online_sku = '{$onlineSku}'");
            }
            //排除海外仓账号
            $cmd->andWhere("account_id not in(".implode(',',EbayAccount::$OVERSEAS_ACCOUNT_ID).")");
            $cmd->order("calc_listing_count_at asc");
            $cmd->limit(500);
            //echo $cmd->Text;exit;
            $res = $cmd->queryAll();
            if (empty($res)) {
                return false;
            }
            foreach ($res as $v) {
                $data = array('item_id'=>$v['item_id'],'sku_online'=>$v['online_sku'],'count'=>$v['count']);
                $res2 = EbayProduct::model()->reviseEbayListing($accountID, $data);
                $status = $res2['errorCode'] == 200 ? 1 : 2;
                $note = $res2['errorCode'] != 200 ? $res2['errorMsg'] : 'ok';
                $this->getDbConnection()->createCommand()->update($this->tableName(),array('replenished_at'=>date("Y-m-d H:i:s"), 'status'=>$status, 'note'=>$note.'@'.$v['note']),'id='.$v['id'] );
            }
            return true;
        } catch (Exception $e) {
            echo $e->getMessage()."<br>";
            $this->setExceptionMessage('售出补库存时出现异常');
            return false;
        }
    }

    /**
     * @desc 来货后补库存
     * @param  int $accountID  
     * @param  string $itemID    
     * @param  string $onlineSku
     * @return boolean   
     */
    public function fixedStock($accountID,$itemID=null,$onlineSku=null) {
    	try {
    		$calcTime = date("Y-m-d H:i:s", time() - 5*60);//北京时间，5分钟内有更新listing的进行操作
    		//1.失败、暂不补记录恢复默认状态
    		$this->setDefaultForNeedFixstock($accountID);
    		//2.更新status
    		$flag = $this->updateSkuCount($accountID,$itemID,$onlineSku);
    		if (!$flag) {
	    		return false;
    		}
    		//3.更新listing数据
    		$flag = $this->updateItemSimpleInfo($accountID,$itemID,$onlineSku);
    		if (!$flag ) {
	    		return false;
    		}
    		//3.更新count
    		$flag = $this->updateSkuCount($accountID,$itemID,$onlineSku);
    		if (!$flag) {
	    		return false;
    		}
    		//5.补库存
	        $cmd = $this->getDbConnection()->createCommand()
	                                ->select("id,item_id,online_sku,count,note")
	                                ->from($this->tableName())
	                                ->where("account_id='{$accountID}' ")
	                                ->andWhere("status=0")
		                            ->andWhere("sale_count>0 ")
		                            ->andWhere("listing_count>0")
		                            ->andWhere("count>0")
		                            ->andWhere("calc_listing_count_at>'{$calcTime}'");
	        if($itemID){
	            $cmd->andWhere("item_id = '{$itemID}'");
	        }
	        if ($onlineSku) {
	            $cmd->andWhere("online_sku = '{$onlineSku}'");
	        }
            $cmd->order("calc_listing_count_at asc");
            $cmd->limit(500);
	        $res = $cmd->queryAll();
	    	if (empty($res)) {
	    		//echo '33333 -- '.' data is empty<br>';
	    		return true;
	    	}
	    	foreach ($res as $v) {
	    		$data = array('item_id'=>$v['item_id'],'sku_online'=>$v['online_sku'],'count'=>$v['count']);
	    		$res2 = EbayProduct::model()->reviseEbayListing($accountID, $data);
	    		$status = $res2['errorCode'] == 200 ? 1 : 2;
	    		$note = $res2['errorCode'] != 200 ? $res2['errorMsg'] : 'ok';
				$this->getDbConnection()->createCommand()->update($this->tableName(),array('replenished_at'=>date("Y-m-d H:i:s"), 'status'=>$status, 'note'=>$note.'@'.$v['note']),'id='.$v['id'] );
	    	}
	    	return true;
    	} catch (Exception $e) {
    		//echo $e->getMessage()."<br>";
    		$this->setExceptionMessage('到货补库存时出现异常');
    		return false;
    	}
    }


    /**
     * @desc 准备数据 -- test
     * @param  integer $accountID 
     * @param  string $itemID    
     * @param  string $onlineSku
     * @return boolean   
     */
    public function prepareDataTest($accountID,$itemID=null,$onlineSku=null) {
        //每次执行，查看近12个小时内是否有销量
        $startTime = date("Y-m-d H:i", time() - self::INTERVAL_CALCTIME );
        $endTime   = date("Y-m-d H:i");      
        echo 'startTime:'.$startTime,'-->','endTime:'.$endTime."<br>";
        try {
            //获取销量数据
            $list = array();
            $res = $this->getSkuSaleList($accountID,$startTime,$endTime,$itemID,$onlineSku);
            //MHelper::writefilelog('test.txt',print_r($res,true)."\r\n");
            if (!empty($res)) {
                foreach ($res as $v) {
                    $key = self::getFormatKey($v['item_id'],$v['online_sku']);
                    //从最近一次成功的listing库存记录中calc_sale_count_end做为startTime
                    $lastSucTime = $this->getLastSuccessCalcSaleTime($v['item_id'], $v['online_sku']);
                    //echo $v['item_id'],'----', $v['online_sku'], '---', $lastSucTime,"<br>";
                    if ($lastSucTime != '' ) {
                        $saleResult = $this->getSkuSaleList($accountID,$lastSucTime,$endTime,$v['item_id'], $v['online_sku']);
                        if ( empty($saleResult[$key]) ) {
                            continue;
                        }
                        $calcSaleStartTime = $lastSucTime;
                        $count = $saleResult[$key]['total'];
                    } else {
                        $calcSaleStartTime = $startTime;
                        $count = $v['total'];
                    }
                    $list[$key] = array(
                        'account_id'            => $accountID,
                        'item_id'               => $v['item_id'],
                        'online_sku'            => $v['online_sku'],
                        'calc_sale_count_start' => $calcSaleStartTime,
                        'calc_sale_count_end'   => $endTime,
                        'created_at'            => date("Y-m-d H:i:s"),
                        'sale_count'            => $count,
                        'status'                => 0
                    );              
                }
            }
            MHelper::printvar($list,false);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage()."<br>";
            //$this->setExceptionMessage('准备数据时异常退出');
            return false;
        }
    }    

}