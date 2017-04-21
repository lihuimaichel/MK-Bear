<?php
/**
 * @desc Ebay 产品权重表
 * @author Gordon
 * @since 2015-07-25
 */
class EbayProductRank extends EbayModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_rank';
    }
    
    public function addData($data){
    	$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	if($res){
    		return $this->getDbConnection()->getLastInsertID();
    	}else{
    		return false;
    	}
    }
    
    public function updateData($data, $conditions){
    	return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, $conditions);
    }
    
    /**
     * @desc 更新rank信息
     * @param unknown $accountID
     */
    public function updateEbayProductRankWatchCount($accountID){
    	$ebayProductModel = EbayProduct::model();
    	$itemList = $this->getDbConnection()->createCommand()->from($this->tableName() . " as r")
    											->select("p.item_id,p.watch_count")
    											->join($ebayProductModel->tableName() . " as p", "p.item_id=r.item_id")
    											->where("p.account_id='{$accountID}' and p.item_status=1 and p.watch_count != r.watch_count")
    											->queryAll();
    	foreach($itemList as $item){
    		$this->getDbConnection()->createCommand()->update($this->tableName(), array('watch_count'=>$item['watch_count']), "item_id={$item['item_id']}");
    	}
    }
    
    /**
     * @desc 添加新插入的数据
     * @param unknown $accountID
     */
    public function addEbayProductRankDataForNewAddProduct($accountID){
    	$ebayProductModel = EbayProduct::model();
    	$itemList = $this->getDbConnection()->createCommand()->from($ebayProductModel->tableName())
									    	->select("item_id, sku, watch_count")
									    	->where("item_id not in(SELECT item_id FROM ".$this->tableName().") AND account_id='{$accountID}' AND item_status=1")
									    	->queryAll();
    	foreach ($itemList as $item){
    		$addData = array(
    				'item_id' => $item['item_id'],
    				'sku' => $item['sku'],
    				'watch_count' => $item['watch_count'],
    		);
    		$this->getDbConnection()->createCommand()->insert($this->tableName(), $addData);
    	}
    	
    	//插入新刊登的多样性子sku
    	$ebayVariationProductModel = new EbayProductVariation();
    	$itemList = $this->getDbConnection()->createCommand()->from($ebayVariationProductModel->tableName() . " as v")
    					->join($ebayProductModel->tableName() . " as p", "p.item_id=v.item_id")
				    	->select("v.item_id,v.sku, p.watch_count")
				    	->where("v.sku not in(SELECT sku FROM ".$this->tableName()." r where r.item_id=v.item_id) AND p.account_id='{$accountID}' AND p.item_status=1")
				    	->queryAll();
    	foreach ($itemList as $item){
    		$addData = array(
    				'item_id' => $item['item_id'],
    				'sku' => $item['sku'],
    				'watch_count' => $item['watch_count'],
    		);
    		$this->getDbConnection()->createCommand()->insert($this->tableName(), $addData);
    	}
    }
    
    /**
     * @desc 获取sku在ebay上的浏览量
     * @param unknown $accountid
     * @return unknown
     */
    public function getWatchCount($accountID){
    	$ebayProductModel = EbayProduct::model();
    	$ebayVariationProductModel = EbayProductVariation::model();
    	$itemList = $this->getDbConnection()->createCommand()->from($this->tableName() . " as r")
    											->select("r.watch_count, r.sku, r.item_id, IFNULL(v.quantity, p.quantity) AS quantity")
    											->join($ebayProductModel->tableName() . " as p", "p.item_id=r.item_id")
    											->leftJoin($ebayVariationProductModel->tableName() . " as v", "v.item_id=r.item_id and v.sku=r.sku")
    											->where('p.account_id = "'.$accountID.'" AND p.item_status = 1 AND p.listing_duration = "GTC"')
    											->queryAll();
    	$newResult = array();
    	if($itemList){
	    	foreach ($itemList as $item){
	    		$newResult[$item['item_id']][$item['sku']]['watch_count'] = $item['watch_count'];
	    		$newResult[$item['item_id']][$item['sku']]['quantity'] = $item['quantity'];
	    	}
    	}
    	return $newResult;
    }
    
    
}