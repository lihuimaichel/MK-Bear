<?php 

class WishBuyerSkuSchedule extends WishModel{

	private $_exceptionMsg = '';
	
    public static function model($className = __CLASS__) {
    	return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return 'ueb_wish_buyer_sku_schedule';
    }
    
    /**
     * @desc 保存
     * @param string $transactionID
     * @param string $orderID
     * @param array $param
     */
    public function saveRecord($data){
        return $this->dbConnection->createCommand()->replace($this->tableName(), $data);
    }
    /**
     * @desc 获取排期sku列表
     * @return Ambigous <multitype:, mixed>
     */
    public function getScheduleSkuList($limit = 5000){
    	$scheduleList = $this->getDbConnection()->createCommand()->from($this->tableName())->order("id ASC")->limit($limit)->queryAll();
    	return $scheduleList;
    }
    
    public function createSkuSchedule($buyerID, $day = 5, $buyerScheduleOffset = 0, $buyerSchduleOrder = 0){
    	$bug = isset($_REQUEST['bug']) && $_REQUEST['bug']; 
    	$wishBuyerAccountScheduleModel = new WishBuyerAccountSchedule();
    	$needCount = 50;//每次每个buyer需要sku数量
    	$loopNum = $day;//每10个排期重置为0
    	//1 取出所有数据，目前少
    	//$scheduleList = $this->getScheduleSkuList();
    	$orderStr = "id ASC";
    	switch ($buyerSchduleOrder){
    		case 1:
    		case 2:
    		case 3:
    		case 4:
    		case 5:
    		case 6:
  			case 7:
    		case 8:
    		case 9:
   			case 10:
   				$orderStr = "order_num".$buyerSchduleOrder." ASC";
   			default:
   				$orderStr = $orderStr;
    	}
    	$scheduleList = $this->getDbConnection()->createCommand()->from($this->tableName())->order($orderStr)->limit(5000)->queryAll();
    	//2 对账号统计分析和分组
    	$scheduleGroup = array();
    	$scheduleTotal = count($scheduleList);
    	foreach ($scheduleList as $schedule){
    		$key = trim(strtolower($schedule['account_name']));
    		if(!isset($scheduleGroup[$key]['account_name']))
    			$scheduleGroup[$key]['account_name'] = $key;
    		if(!isset($scheduleGroup[$key]['count']))
    			$scheduleGroup[$key]['count'] = 0;
    		$scheduleGroup[$key]['count']++;
    		$scheduleGroup[$key]['skuList'][] = $schedule;
    	}
    	if($bug){
    		echo "<pre>";
    		print_r($scheduleGroup);
    	}
    	//exit;
    	//计算出每个账号的占比数
    	$accountRateCountArr = array();
    	//统计占比数之外还超过一个循环周期的
    	$overRateCountArr = array();
    	if($bug){
    		echo "scheduleTotal:",$scheduleTotal;
    		echo "<br/>";
    	}
    	$realAccountCount = 0;
    	foreach ($scheduleGroup as $accountName=>$val){
    		$rateCount = ceil(($val['count']/$scheduleTotal)*$needCount);
    		$halfCount = floor($val['count']/2);
    		$halfCount = $halfCount ? $halfCount : 1;
    		//比例数大于一半数则取最小的
    		$realCount = min($rateCount, $halfCount);
			$accountRateCountArr[$accountName] = $realCount;
			$realAccountCount += $realCount;
			if($bug){
				echo $accountName.":",$rateCount," ", $halfCount."<br/>";
			}
			//如果总数超过占比总数2x循环周期的
			if($val['count']>$realCount*2*$loopNum){
				$overRateCountArr[$accountName] = $val['count'];
			}
    	}
    	if($bug){
    		echo $realAccountCount,"<br>";
    	}
    	//按照从低到高数量获取
    	arsort($accountRateCountArr);
    	if($bug){
    		echo "===============accountRateCountArr=============<br/>";
    		print_r($accountRateCountArr);
    		echo "===============overRateCountArr=============<br/>";
    		print_r($overRateCountArr);
    	}
    	//超过的总数
    	$overRateCount = count($overRateCountArr);
    	$avgReduceCount = $overRateCount ? ceil(($realAccountCount-$needCount)/$overRateCount) : ($realAccountCount-$needCount);
    	foreach ($accountRateCountArr as $accountName=>$count){
    		if($realAccountCount > $needCount && isset($overRateCountArr[$accountName])){
    			if(($realAccountCount - $avgReduceCount) > $needCount){
    				$accountRateCountArr[$accountName] = $count-$avgReduceCount;
    				$realAccountCount -= $avgReduceCount;
    			}else{
    				$accountRateCountArr[$accountName] = $count-($realAccountCount-$needCount);
    				$realAccountCount = $needCount;
    			}
    			
    		}
    	}
    	if($bug){
    		echo "===============accountRateCountArr 2=============<br/>";
    		print_r($accountRateCountArr);
    	}
    	asort($accountRateCountArr);
    	if($bug){
    		echo "===============accountRateCountArr 3=============<br/>";
    		print_r($accountRateCountArr);
    	}
    	//exit;
    	$fetchDaySkuLists = array();
    	$i = 1;
    	for ($i = 1; $i <= $day; $i++){
    		if($bug){
    			echo "===================DAY $i ===================<br/>";
    		}
    		//取出每个买家和账号
    		//可以做成统一变量
    		$buyerAccountList = $wishBuyerAccountScheduleModel->getListPairsByBuyerID($buyerID);
    		//均摊数量
    		$shareCount = 0;
    		$shareLeftCount = -1;
    		//获取到的SKU列表
    		$fetchSkuLists = array();
    		//判断当前排期是否在排次数取模下为0，同时偏移量超过总数，则重置索引
    		//如果不是对排期数取模为0的， 则判断偏移量是否在区间内
    		//如果偏移量为0则取占比数量，如果偏移量不为0，不大于总数则，则取占比数量
    		//如果偏移量未超过总数，但是数量不足占比数，则循环对应账号的sku数量，模拟队列
    		//如果偏移量大于总数，则过滤掉不取，直到下次排期取模为0
    		//在一个排期内未满的，移到下个多的账号里面去补
    		foreach ($accountRateCountArr as $accountName=>$count){
    			if($bug){
    				echo "========accountName:{$accountName}==============<br/>";
    			}
    			$scheduleNumber = 0;
    			$scheduleOffset = $buyerScheduleOffset;
    			if(isset($buyerAccountList[$accountName])){
    				$scheduleNumber = $buyerAccountList[$accountName]['schedule_number'];
    				$scheduleOffset = $buyerAccountList[$accountName]['schedule_offset'];
    			}
    			$accountTotal = $scheduleGroup[$accountName]['count'];
    			if($bug){
    				echo "=========accountTotal:{$accountTotal}========<br/>";
    			
    				echo "========scheduleNumber:{$scheduleNumber}, scheduleOffset:{$scheduleOffset} ========<br/>";
    			}
    			$mod = $scheduleNumber%$loopNum;
    			if($bug){
    				echo "==============MOD:{$mod}=============<br/>";
    			}
    			if($mod == 0 && $scheduleOffset > ($accountTotal-1)){
    				$scheduleOffset = 0;
    			}
    			
    			$fetchCount = $count;
    			if($scheduleOffset > $accountTotal-1){
    				if($bug){
    					echo "========= IF 1===========<BR/>";
    				}
    				$shareCount += $fetchCount;
    				$scheduleOffset += $fetchCount;
    				//@todo 记录
    				$scheduleNumber++;
    				$wishBuyerAccountScheduleModel->addOrUpadateRecord(array(
    						'account_name'		=>	$accountName,
    						'buyer_id'			=>	$buyerID,
    						'schedule_number'	=>	$scheduleNumber,
    						'schedule_offset'	=>	$scheduleOffset
    				));
    				
    				continue;
    			}
    			if($bug){
    				echo "========= ELSE 1===========<BR/>";
    				echo "============均摊：{$accountName} =============<br/>";
    			}
    				if($shareCount>0 && isset($overRateCountArr[$accountName])){
    					//均摊开始
    					if($shareLeftCount == -1) {
    						if($bug){
    							echo "============shareLeftCount init：{$shareLeftCount} =============<br/>";
    						}
    						$shareLeftCount = $shareCount;
    					}
    					if($bug){
    						echo "============shareLeftCount 1：{$shareLeftCount} =============<br/>";
    					}
    					$hadShare = ceil($shareCount/$overRateCount);
    					if($bug){
    						echo "===============hadShare 1:{$hadShare}=============<br/>";
    					}
    					if($shareLeftCount > $hadShare){
    						$shareLeftCount -= $hadShare;
    					}else{
    						$hadShare = $shareLeftCount;
    						$shareLeftCount = 0;
    					}
    					if($bug){
    						echo "============shareLeftCount 2：{$shareLeftCount} =============<br/>";
    						echo "===============hadShare 2:{$hadShare}=============<br/>";
    					}
    					$fetchCount += $hadShare;
    				}
    				if($bug){
    					echo "============fetchCount：{$fetchCount} =============<br/>";
    				}
    				//获取到的SKU列表
    				$fetchSkuList = array();
    				//偏移量在总数范围内，并且占比数能够取足
    				if($bug){
    					echo "=======$scheduleOffset < ($accountTotal-1)  && $accountTotal-$scheduleOffset-1 >= $fetchCount=====<br/>";
    				}
    				if($scheduleOffset < ($accountTotal-1) && ($accountTotal-$scheduleOffset-1) >= $fetchCount){
    					if($bug){
    						echo "=========scheduleOffset 222222222222 ==========<br/>";
    					}
    					$fetchSkuList = array_slice($scheduleGroup[$accountName]['skuList'], $scheduleOffset, $fetchCount);
    					//if(($accountTotal-$scheduleOffset-1) > $fetchCount){
    						$scheduleOffset += $fetchCount;
    					//}else{
    					//	$scheduleOffset = 0;//重置为0
    					//}
    				}elseif($scheduleOffset <= ($accountTotal-1)){
    					if($bug){
    						echo "=========scheduleOffset 333333333 ==========<br/>";
    					}
    					
    					//偏移量在总数范围内，但是占比数不能够取足,不足部分从头获取，同时偏移量变为补差额数
    					//先取现在的
    					$fetchSkuList = array_slice($scheduleGroup[$accountName]['skuList'], $scheduleOffset);
    					//再取重复的
    					$fetchSkuList2 = array_slice($scheduleGroup[$accountName]['skuList'], 0, $fetchCount-count($fetchSkuList));
    					$scheduleOffset = ($accountTotal-1)-$scheduleOffset;
    					$fetchSkuList = array_merge($fetchSkuList, $fetchSkuList2);
    				}else{
    					if($bug){
    						echo "=========scheduleOffset 444444444444 ==========<br/>";
    					}
    					
    					$fetchSkuList = array_slice($scheduleGroup[$accountName]['skuList'], $scheduleOffset, $fetchCount);
    					$scheduleOffset += $fetchCount;
    				}
    				$fetchSkuLists[$accountName] = $fetchSkuList;
    				//@todo 记录
    				$scheduleNumber++;
    				$wishBuyerAccountScheduleModel->addOrUpadateRecord(array(
    						'account_name'		=>	$accountName,
    						'buyer_id'			=>	$buyerID,
    						'schedule_number'	=>	$scheduleNumber,
    						'schedule_offset'	=>	$scheduleOffset
    				));
    			if($bug){
    				echo "===============scheduleOffset : {$scheduleOffset} 8888 ===============<br/>";
    				 
    				echo "===================DAY $i END ===================<br/>";
    			}
    			
    		}
    		if($bug){
    			echo "<br>============fetchSkuListsCount===========<br/>";
    			echo count($fetchSkuLists);
    		}
    		
    		$sumTotal = 0;
    		foreach ($fetchSkuLists as $skuList){
    			$sumTotal += count($skuList);
    		}
    		if($sumTotal<50){
    			//@todo 补充
    			$additionalCount = 50-$sumTotal;
    			$buyerAccountList = $wishBuyerAccountScheduleModel->getListPairsByBuyerID($buyerID);
    			$additionalAvgCount = ceil($additionalCount/$overRateCount);
    			$additionalLeftCount = 0;
    			foreach ($overRateCountArr as $accountName=>$val){
    				$scheduleNumber = 0;
    				$scheduleOffset = 0;
    				if(isset($buyerAccountList[$accountName])){
    					$scheduleNumber = $buyerAccountList[$accountName]['schedule_number'];
    					$scheduleOffset = $buyerAccountList[$accountName]['schedule_offset'];
    				}
    				if($additionalLeftCount == $additionalCount) break;
    				if($additionalCount - $additionalLeftCount > $additionalAvgCount){
    					$needFetchCount = $additionalAvgCount;
    				}else{
    					$needFetchCount = $additionalCount - $additionalLeftCount;
    				}
    				
    				// @todo 
    				$fetchSkuList = array_slice($scheduleGroup[$accountName]['skuList'], $scheduleOffset, $needFetchCount);
    				$fetchCount = count($fetchSkuList);
    				$newScheduleOffset = 0;
    				if($fetchCount < $needFetchCount){
    					//如果不够，再重新从头取出，并且更新offset
    					$newScheduleOffset = $needFetchCount-$fetchCount;
    					$additionFetchSkuList = array_slice($scheduleGroup[$accountName]['skuList'], 0, $needFetchCount-$fetchCount);
    					$fetchSkuList = array_merge($fetchSkuList, $additionFetchSkuList);
    				}else{
    					//
    					$newScheduleOffset = $scheduleOffset+$needFetchCount;
    				}
    				$additionalLeftCount += $needFetchCount;
    				$fetchSkuLists[$accountName] = array_merge($fetchSkuLists[$accountName], $fetchSkuList);
    				//更新
    				$wishBuyerAccountScheduleModel->addOrUpadateRecord(array(
    						'account_name'		=>	$accountName,
    						'buyer_id'			=>	$buyerID,
    						'schedule_offset'	=>	$newScheduleOffset
    				));
    			}
    		}
    		echo "==<br/>";
    		$fetchDaySkuLists[$i] = $fetchSkuLists;
    	}
    	//echo "=============fetchDaySkuLists=============<br/>";
    	//print_r($fetchDaySkuLists);
    	//shuffle($fetchDaySkuLists);
    	//echo "=============fetchDaySkuLists2=============<br/>";
    	//print_r($fetchDaySkuLists);
    	//EXIT;
    	return $fetchDaySkuLists;
    	$this->getExportData($fetchDaySkuLists, $buyerID);
    	//echo "done";
    }
    
    
    public function createSkuSchedule2($buyerID, $day = 20, $needCount = 50, $buyerScheduleOffset = 0, $buyerSchduleOrder = 0){
    	$bug = isset($_REQUEST['bug']) && $_REQUEST['bug'];
    	$wishBuyerAccountScheduleModel = new WishBuyerAccountSchedule();
    	//$needCount = 50;//每次每个buyer需要sku数量
    	$loopNum = $day;
    	//1 取出所有数据，目前少
    	//$scheduleList = $this->getScheduleSkuList();
    	$orderStr = "id ASC";
    	$isShuffix = false;
    	switch ($buyerSchduleOrder){
    		case 1:
    		case 2:
    		case 3:
    		case 4:
    		case 5:
    		case 6:
    		case 7:
    		case 8:
    		case 9:
    		case 10:
    			$orderStr = "order_num".$buyerSchduleOrder." ASC";
    		default:
    			$orderStr = $orderStr;
    			$isShuffix = true;
    	}
    	$scheduleList = $this->getDbConnection()->createCommand()->from($this->tableName())->order($orderStr)->limit(5000)->queryAll();
    	if($isShuffix){
    		shuffle($scheduleList);
    	}
    	
    	//2 对账号统计分析和分组
    	$scheduleGroup = array();
    	$scheduleTotal = count($scheduleList);
    	foreach ($scheduleList as $schedule){
    		$key = trim(strtolower($schedule['account_name']));
    		if(!isset($scheduleGroup[$key]['account_name']))
    			$scheduleGroup[$key]['account_name'] = $key;
    		if(!isset($scheduleGroup[$key]['count']))
    			$scheduleGroup[$key]['count'] = 0;
    		$scheduleGroup[$key]['count']++;
    		$scheduleGroup[$key]['skuList'][] = $schedule;
    		$scheduleGroup[$key]['offset'] = 0;
    	}
    	if($bug){
    		echo "<pre>";
    		//print_r($scheduleGroup);
    	}
    	//exit;
    	//计算出每个账号的占比数
    	$accountRateCountArr = array();
    	//统计占比数之外还超过一个循环周期的
    	$overRateCountArr = array();
    	if($bug){
    		echo "scheduleTotal:",$scheduleTotal;
    		echo "<br/>";
    	}
    	$realAccountCount = 0;
    	foreach ($scheduleGroup as $accountName=>$val){
    		$rateCount = ceil(($val['count']/$scheduleTotal)*$needCount);
    		$rateCount2 = floor(($val['count']/$scheduleTotal)*$needCount);
    		$halfCount = floor($val['count']/2);
    		$halfCount = $halfCount ? $halfCount : 1;
    		//比例数大于一半数则取最小的
    		$realCount = min($rateCount, $halfCount);
    		$accountRateCountArr[$accountName] = $realCount < 1 ? 1 : $realCount;
	    	$realAccountCount += $realCount;
	    	if($bug){
	    		echo $accountName.":",$rateCount," ", $halfCount."<br/>";
	    	}
	    	//如果总数超过占比总数2x循环周期的
	    	if($val['count']>=$rateCount*$loopNum){
	    		$overRateCountArr[$accountName] = $val['count'];
    		}
    	}
    	if($bug){
    		echo $realAccountCount,"<br>";
    	}
    	//按照从低到高数量获取
    	arsort($accountRateCountArr);
    	if($bug){
    		echo "===============accountRateCountArr=============<br/>";
    		print_r($accountRateCountArr);
    		echo "===============overRateCountArr=============<br/>";
    		//print_r($overRateCountArr);
    	}
    	//超过的总数
    	$overRateCount = count($overRateCountArr);
    	$avgReduceCount = $overRateCount ? ceil(($realAccountCount-$needCount)/$overRateCount) : ($realAccountCount-$needCount);
    	foreach ($accountRateCountArr as $accountName=>$count){
    		if($realAccountCount > $needCount && isset($overRateCountArr[$accountName])){
    			if(($realAccountCount - $avgReduceCount) > $needCount){
    				$accountRateCountArr[$accountName] = $count-$avgReduceCount;
    				$realAccountCount -= $avgReduceCount;
    			}else{
    				$accountRateCountArr[$accountName] = $count-($realAccountCount-$needCount);
    				$realAccountCount = $needCount;
    			}
    			if($accountRateCountArr[$accountName] < 1){
    				$accountRateCountArr[$accountName] = 1;
    			}
    		}
    	}
    	if($bug){
    		echo "=============== re accountRateCountArr=============<br/>";
    		print_r($accountRateCountArr);
    	}
    	$fetchDaySkuLists = array();
    	$i = 1;
    	for ($i = 1; $i <= $day; $i++){
    		//获取到的SKU列表
    		$fetchSkuLists = array();
    		$sum = 0;
    		foreach ($accountRateCountArr as $accountName=>$count){
    			$skuLists = $scheduleGroup[$accountName]['skuList'];
    			$offset = $scheduleGroup[$accountName]['offset'];
    			if($i == $day){//最后一天剩下的全部取出来
    				
    				$fetchSkuList = array_slice($skuLists, $offset, $count);
    				$scheduleGroup[$accountName]['offset'] += count($fetchSkuList);
    				
    			}else{
    				$fetchSkuList = array_slice($skuLists, $offset, $count);
    				$scheduleGroup[$accountName]['offset'] += $count;
    			}
    			$sum += count($fetchSkuList);
    			$fetchSkuLists[$accountName] = $fetchSkuList;
    			
    		}
    		
    		if($bug){
    			echo "<br>============day $i : {$sum}===========<br/>";
    		}
    		
    		
    		$newAccountRateCountArr = array();
    		foreach ($accountRateCountArr as $accountName=>$count){
    			if($scheduleGroup[$accountName]['offset']>=$scheduleGroup[$accountName]['count']-1){
    				continue;
    			}
    			$newAccountRateCountArr[$accountName] = $count;
    		}
    		//开始补和退
    		if($sum>$needCount){
    			$maxIt = $sum-$needCount;
    			$begin = 0;
    			$ccc = count($fetchSkuLists);
    			$avg = ceil($maxIt/$ccc);
    			$vvv = 0;
    			foreach ($fetchSkuLists as $accountName=>$tempSkuList){
    				$ccccc = count($tempSkuList);
    				$reduceCount = $avg;
    				if($begin == $ccc-1){
    					$ofs = $maxIt-$begin;
    					$scheduleGroup[$accountName]['offset'] -= $ofs;
    					//$tempSkuList = $fetchSkuLists[$accountName];
    					$tempSkuList = array_alice($tempSkuList, 0, $ccccc - $ofs - 1);
    					$fetchSkuLists[$accountName] = $tempSkuList;
    					$begin += $ofs;
    				}else{
    					if($ccccc<$avg){
    						$reduceCount = $ccccc;
    						$vvv = $avg-$ccccc;
    					}elseif ($ccccc > ($avg+$vvv)){
    						$vvv = 0;
    						$reduceCount = ($avg+$vvv);
    					}elseif ($ccccc > ($avg + floor($vvv/2))){
    						$vvv = $vvv-floor($vvv/2);
    						$reduceCount = ($avg + floor($vvv/2));
    					}elseif ($ccccc > ($avg + floor($vvv/3))){
    						$vvv = $vvv-floor($vvv/3);
    						$reduceCount = ($avg + floor($vvv/3));
    					}elseif ($ccccc > ($avg + floor($vvv/4))){
    						$vvv = $vvv-floor($vvv/4);
    						$reduceCount = ($avg + floor($vvv/4));
    					}elseif ($ccccc > ($avg + floor($vvv/5))){
    						$vvv = $vvv-floor($vvv/5);
    						$reduceCount = ($avg + floor($vvv/5));
    					}
    					$scheduleGroup[$accountName]['offset'] -= $reduceCount;
    					//$tempSkuList = $fetchSkuLists[$accountName];
    					$tempSkuList = array_slice($tempSkuList, 0, $ccccc - $reduceCount - 1);
    					//array_pop($tempSkuList);
    					$fetchSkuLists[$accountName] = $tempSkuList;
    					$begin += $reduceCount;
    				}
    				if($begin>=$maxIt) break;
    			}
    			
    			/* foreach ($accountRateCountArr as $accountName=>$count){
    				if($begin == count($accountRateCountArr)-1){
    					$ofs = $maxIt-$begin;
    					$scheduleGroup[$accountName]['offset'] -= $ofs;
    					$tempSkuList = $fetchSkuLists[$accountName];
    					$tempSkuList = array_alice($tempSkuList, 0, $ofs);
    					$fetchSkuLists[$accountName] = $tempSkuList;
    					$begin += $ofs;
    				}else{
    					$scheduleGroup[$accountName]['offset'] -= 1;
    					$tempSkuList = $fetchSkuLists[$accountName];
    					$tempSkuList = array_alice($tempSkuList, 0, 1);
    					//array_pop($tempSkuList);
    					$fetchSkuLists[$accountName] = $tempSkuList;
    					$begin++;
    				}
    				
    				if($begin>=$maxIt) break;
    			} */
    			
    		}elseif ($sum<$needCount){
    			$maxIt = $needCount-$sum;
    			$begin = 0;
    			
    			$minave = round($maxIt/count($newAccountRateCountArr));
    	
    			
    			foreach ($newAccountRateCountArr as $accountName=>$count){
    				$skuLists = $scheduleGroup[$accountName]['skuList'];
    				if($begin == count($accountRateCountArr)-1){
    					$ofs = ($maxIt-$begin);
    				}else{
    					$ofs = $minave;
    				}
    				$temsku = array_slice($skuLists, $scheduleGroup[$accountName]['offset'], $ofs);
    				//var_dump($temsku);
    				if($temsku){
    					$fetchSkuLists[$accountName] = array_merge($fetchSkuLists[$accountName], $temsku);
    					$scheduleGroup[$accountName]['offset'] += $ofs;
    					$begin	+=	$ofs;
    				}
    				if($begin>=$maxIt) break;
    				
    			}
    		}
    		
    		$fetchDaySkuLists[$i] = $fetchSkuLists;
    	}

    	return $fetchDaySkuLists;
    }
    
    public function getNextWorkday($beginDayDate){
    	//获取给出开始日期的后续工作日
    	//遇到非工作日顺延
    	$time = strtotime($beginDayDate);
    	$dateInfo = getdate($time);
    	if(!$dateInfo) return 0;
    	$w = $dateInfo['wday'];
    	$day = 1;
    	if($w>4){
    		$day = 8-$w;
    	}
    	$nextWorkDate = date("Y-m-d", strtotime("+{$day} day", $time));
    	return $nextWorkDate;
    }
    
    public function getExportData($fd, $exportData, $buyerID){
    	if(!empty($exportData)){
    		try{
    			//$filename = 'everydayskuschedule_' . "_" . $buyerID . "_" . date("Y-m-d")."_".rand(1000, 9999).'.csv'; //设置文件名
    			$headArr = array(0=>'buyerID', 1=>'日期', 2=>'账号', 3=>'SKU', 4=>'售价', 5=>'运费', 6=>'收藏数', 7=>'链接地址');
    			/* $uploadDir = "./uploads/downloads/";
    			if(!is_dir($uploadDir)){
    				mkdir($uploadDir, 0755, true);
    			} */
    			foreach( $headArr as $key => $val ){
    				$headArr[$key] = iconv('utf-8','gbk',$val);
    			}
    			/* $filename2 = $uploadDir.$filename;
    			$fd = fopen($filename2, "w+"); */
    			fputcsv($fd, $headArr);
    			$currentDay = $date = date("Y-m-d"/* , strtotime("+1 day") */);
	    		foreach($exportData as $day => $skuLists){
	    			//空行
	    			$row = array(
	    							'','','','','',''
	    					);
	    			//fputcsv($fd, $row);
	    			//日期
	    			//周五运行，日期算为下周一，需要再加两天 
	    			//$day += 3;
	    			//$date = date("Y-m-d", strtotime("+{$day} day"));
	    			
	    			$date = $currentDay = $this->getNextWorkday($currentDay);
	    			
	    			foreach ($skuLists as $skuList){
	    				foreach ($skuList as $sku){
		    				$row = array();
		    				$row[0] = $buyerID;
		    				$row[1] = $date;
		    				$row[2] = $sku['account_name'];
		    				$row[3] = $sku['sku'];
		    				$row[4] = $sku['sale_price'];
		    				$row[5] = $sku['shipping'];
		    				$row[6] = $sku['wishes'];
		    				$row[7] = $sku['link'];
		    				
		    				foreach ($row as $k =>$v){
		    					$row[$k] = iconv('utf-8','gbk',$v);
		    				}
		    				fputcsv($fd, $row);
		    			}
	    			}
	    			
	    			
	    			
	    		}
	    		/* fclose($fd);
	    		//写入日志表
	    		FileDownloadList::model()->addData(array(	'filename'		=>	$filename,
	    		'local_path'	=>	$filename2,
	    		'create_time'	=>	date("Y-m-d H:i:s"),
	    		'create_user_id'=>	intval(Yii::app()->user->id),
	    		'platform_code'	=>	Platform::CODE_WISH
	    		)); */
    		}catch (Exception $e){
    			$this->setExceptionMsg($e->getMessage());
    			return false;
    		}
    	}
    	return true;
    }
    
    public function setExceptionMsg($msg){
    	$this->_exceptionMsg = $msg;
    }
    
    public function getExceptionMsg(){
    	return $this->_exceptionMsg;
    	
    }
}

?>