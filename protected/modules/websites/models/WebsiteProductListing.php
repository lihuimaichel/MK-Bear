<?php
class WebsiteProductListing extends WebsiteModelAbstract {
	
	public function sysListing(){
	
		$from_time = Yii::app()->getRequest()->getParam('from_time',NULL);
		$to_time = Yii::app()->getRequest()->getParam('to_time',NULL);
		if ($from_time&&$to_time){//指定日期
		}else{//最近三天的数据
			$from_time=	date('Y-m-d', strtotime('-2 days'));
			$to_time=	date('Y-m-d', strtotime('+1 days'));
		}
		//updated_at filter
		$filters  = array('updated_at'=> array('and'=> 	array(  array('date' => true, 'from' => $from_time,'to'=>$to_time))));
		$pListing = $this->__api()->catalog_product_list($filters);
		if (isset($_GET['debug'])){var_dump($pListing);die;}
		if (is_array($pListing)){
			foreach ($pListing as $item){
				if(empty($item['sku'])){continue;}//防止sku为空的
				$product = $this->find('sku=:_sku',array(':_sku'=>$item['sku']));
				if($product){
					if($product->online_status == $item['status']){//状态没发生改变的产品
						continue;//退出不保存
					}
				}else {
					$product = new ProductListing();
					$product->sku = $item['sku'];
					$product->created_at = $item['created_at'];
					$product->offline_time = 0;//
				}
				//save
				if($item['status'] == self::PRODUCT_STORE_STATUS_ENABLED){
					$product->online_status = 1;
					$product->online_time = $item['updated_at'];//更新上线时间
				}elseif ($item['status'] == self::PRODUCT_STORE_STATUS_DISABLED){
					$product->online_status = 0;//标示下线
					if($product->online_time == null){ $product->online_time = $item['updated_at'];}//新品已经刊登还未上线的
					$product->offline_time = $item['updated_at'];
						
				}
				$product->check_time	= date('Y:m:d H:i:s');
				$product->save();//save
			}
		}
	}
}