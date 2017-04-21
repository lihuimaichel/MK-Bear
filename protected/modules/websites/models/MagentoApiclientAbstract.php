<?php
/**
 * @author xiej
 * @Magento api
 * 
 */
class MagentoApiclientAbstract {

	public $soap_url;
	public $api_user;
	public $api_key;
	public $sessionid;
	public $proxy;
	public $error = array();
	public static $instance;
	
	public function __construct($soap_url,$api_user,$api_key)
	{
		$this->soap_url=$soap_url;
		$this->api_user=$api_user;
		$this->api_key=$api_key;
		$this->generalApi();
	}

	public static function singleton($soap_url,$api_user,$api_key)
	{	
		if (!isset(self::$instance)) {
			//$className = __CLASS__;
			self::$instance = new self ($soap_url,$api_user,$api_key);
		}
		return self::$instance;
	}

	public function generalApi(){
		//增加超时时间
		$params = array ("connection_timeout" => 600 );
		try{
			$this->proxy=new SoapClient($this->soap_url,$params);
			$this->sessionid=$this->proxy->login( $this->api_user, $this->api_key );
		}catch(SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	
	/**
	 * 获取网站  任一	分类下的所有分类
	 * @param int $id
	 */
	public function catalog_category_tree(){
		try{
			$category = $this->proxy->call($this->sessionid, 'catalog_category.tree');
			return $category;
		}catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	
	/**
	 * new 获取分类下产品
	 *
	 * @param int $category_id
	 * @return array
	 */
	public function new_catalog_category_assignedproducts($category_ids){
		try{
			return $this->proxy->call($this->sessionid, 'vakindapi_category.newAssignedProducts',array($category_ids));
		}catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	/**
	 * 获取分类下产品
	 * 
	 * @param int $category_id
	 * @return array
	 */
	public function catalog_category_assignedproducts($category_id){
		try{
			return $this->proxy->call($this->sessionid, 'catalog_category.assignedProducts',$category_id);
		}catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	/**
	 * 
	 * @param string $sku
	 * @param string $attributes
	 */
	public function website_product_info($sku,$attributes=''){
		try{
			return $this->proxy->call($this->sessionid, 'vakindapi_product.info', array($sku,'',$attributes,$identifierType='sku'));
		}catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}
	public function website_product_create($sku,$data,$type = 'simple',$set_id=4)
	{
		try{
			return $this->proxy->call($this->sessionid, 'vakindapi_product.create', array($type, $set_id, $sku, $data));
		}catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}
	/********************************** 订单相关   start  **********************************/
	/*********************************************************************************/
	/**
	 * createCreditmemo  - 支持订单系统中已付款   已完成订单  未发货的产品创建Creditmemo
	 * @param string $orderIncrementId
	 * @param boolean $email
	 */
	public function website_order_createCreditmemo($orderIncrementId, $creditmemoData = null, $comment = null, $notifyCustomer = false){
		try{
			//return $this->proxy->call($this->sessionid, 'vakindapi_order_creditmemo.create', array($orderIncrementId, $creditmemoData, $comment,$notifyCustomer));
			return $this->proxy->call($this->sessionid, 'vakindapi_order_creditmomo.create', array($orderIncrementId, $creditmemoData, $comment,$notifyCustomer));
		}catch (SoapFault $e)
		{
			$this->error[$orderIncrementId]=$orderIncrementId.":".$e->getMessage();
		}
	}
	/**
	 * create order shipment
	 * @param array $shipmentInfo
	 */
	public function create_website_order_shipment($orderIncrementId,$shipmentInfo,$email = true)
	{
		try{
			return $this->proxy->call($this->sessionid, 'vakindapi_order_shipment.create', array($orderIncrementId,$shipmentInfo,$email = true,$includeComment = true));
		}catch (SoapFault $e)
		{
			$this->error[$orderIncrementId]=$orderIncrementId.":".$e->getMessage();
		}
	}
	/**
	 * update processing order invoices state
	 * @param String $orderIncrementId
	 */
	public function create_website_order_paidInvoice($orderIncrementId)
	{
		try{
			return $this->proxy->call($this->sessionid, 'vakindapi_order_shipment.paidInvoice', array($orderIncrementId));
		}catch (SoapFault $e)
		{
			$this->error[$orderIncrementId]=$orderIncrementId.":".$e->getMessage();
		}
	}
	/********************************** 订单相关  end **********************************/
	
	public function catalog_product_create($sku,$data,$type = 'simple',$set_id=4)
	{
		try{
			return $this->proxy->call($this->sessionid, 'product.create', array($type, $set_id, $sku, $data));
		}catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}
	public function catalog_product_info($sku,$attributes=''){
		try{
			return $this->proxy->call($this->sessionid, 'product.info', array($sku,'',$attributes,$identifierType='sku'));
		}catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}
	/**
	 * 新改写方法 批量获取不存在的记录
	 * @param 
	 */
	public function catalog_product_specialList($isNew=FALSE,$storeView=null,$attributes=null,$identifierType='sku'){
		try{
			return $this->proxy->call($this->sessionid, 'vakindapi_product.speciallist',array($isNew,$storeView,$attributes,$identifierType));
	
		}catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	/**
	 * 返回记录有已经更改的sku
	 * @param
	 */
	public function catalog_product_outSpecialList($arr){
		try{
			return $this->proxy->call($this->sessionid, 'vakindapi_product.outofspecialList',array($arr));
	
		}catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	public function catalog_product_list($filters){
		try{
			$products = $this->proxy->call($this->sessionid, 'vakindapi_product.list',array($filters));
// 			$products = $this->proxy->call($this->sessionid, 'catalog_product.list',array($filters));
			return $products;
		}
		catch (SoapFault $e)
		{
			$this->error['catalog_product_list']=$e->getMessage();
		}
	}
	/**
	 * XJ 改写的方法 返回数据请参考 网站api 例如 array($sku = array())
	 * @param Array $productlistarray
	 * @param string $isnew
	 * @param string $storeView
	 */
	public function catalog_product_updatelist($productlistarray,$isnew=FALSE,$storeView=null){
		try{
			return $this->proxy->call($this->sessionid, 'vakindapi_product.updatelist',array($productlistarray,$storeView,$identifierType='sku',$isnew));
		}
		catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}
	public function catalog_product_update($sku,$array,$storeView=null){
		try{
			return $this->proxy->call($this->sessionid, 'vakindapi_product.update',array($sku,$array,$storeView,$identifierType='sku'));
		}
		catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}
	public function catalog_product_attribute_update($sku,$array,$storeView=null){
		try{
			return $this->proxy->call($this->sessionid, 'product_attribute.update',array($sku,$array,$storeView,$identifierType='sku'));
		}
		catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}

	public function catalog_product_getSpecialPrice($sku,$storeView=null)
	{
		try{
			return $this->proxy->call($this->sessionid, 'product.getSpecialPrice',array($sku,$storeView));
		}
		catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}
	
	//这个方法服务器上已经做了修改
	public function catalog_product_setSpecialPrice($sku,$array,$storeView=null)
	{
		try{
			return $this->proxy->call($this->sessionid, 'product.setSpecialPrice',array($sku,$array['special_price'],$array['special_from_date'],$array['special_to_date'],'sku'));
		}
		catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}

	public function product_stock_update($data,$sku){
		try{
			return $this->proxy->call($this->sessionid, 'product_stock.update', array($sku, $data,$identifierType='sku'));

		}
		catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}
	public function product_stock_list($sku)
	{
		try
		{
			return $this->proxy->call($this->sessionid, 'product_stock.list',array($sku,$identifierType='sku'));

		}
		catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}

	public function product_tier_price_update($sku,$price=0,$specialData=null,$storeView=null){



		$tierPrices[] = array(
				'website'           => 'all',
				'customer_group_id' => 'all',
				'qty'               => 3,
				'price'             => $price*0.96
		);
		$tierPrices[] = array(
				'website'           => 'all',
				'customer_group_id' => 'all',
				'qty'               => 10,
				'price'             => $price*0.93
		);
		$tierPrices[] = array(
				'website'           => 'all',
				'customer_group_id' => 'all',
				'qty'               => 30,
				'price'             => $price*0.91
		);
		$tierPrices[] = array(
				'website'           => 'all',
				'customer_group_id' => 'all',
				'qty'               => 100,
				'price'             => $price*0.90
		);
		try{
			$this->proxy->call($this->sessionid, 'product_tier_price.update',array($sku,$tierPrices,$identifierType='sku'));
			if (!is_null($specialData))
			{
				$this->catalog_product_setSpecialPrice($sku,$specialData,$storeView);
			}
			//add update special price
		}
		catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}

	public function product_tier_price_info($sku){
		try{
			return $this->proxy->call($this->sessionid, 'product_tier_price.info',array($sku,$identifierType='sku'));

		}
		catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}


	public function product_media_list($sku,$storeView=null)
	{
		try{

			return $this->proxy->call($this->sessionid, 'product_media.list', array($sku,$storeView,$identifierType='sku'));

		}
		catch (SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();

		}
	}
	public function product_media_info($sku,$filename,$storeView=null)
	{
		try{
			return $this->proxy->call($this->sessionid, 'product_media.info', array($sku,$filename,$storeView,$identifierType='sku'));
		}
		catch (SoapFault $e)
		{
			//$this->error[$sku]=$sku.":".$e->getMessage();

		}
	}
	 
	public function product_media_create($sku,$imageArray)

	{
		try{

			return $imageFilename = $this->proxy->call($this->sessionid, 'product_media.create', array($sku, $imageArray,'',$identifierType='sku'));

		}
		catch(SoapFault $e){
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}
	public function catalog_product_delete($sku)
	{
		try
		{
			$this->proxy->call($this->sessionid, 'product.delete', array($sku,$identifierType='sku'));
		}
		catch(SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}

	public function product_media_remove($sku,$filename)
	{
		try{
			$this->proxy->call($this->sessionid, 'product_media.remove', array($sku,$filename,$identifierType='sku'));
		}
		catch(SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}
	public function product_media_removeAll($sku)
	{
		try{
			return $this->proxy->call($this->sessionid, 'product_media.removeall', array($sku, $identifierType='sku'));
		}
		catch(SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}

	public function product_media_update($sku, $oldfilename, $imageArray)
	{
		try{
			return $this->proxy->call($this->sessionid, 'product_media.update', array($sku, $oldfilename, $imageArray, '', $identifierType='sku'));
		}
		catch(SoapFault $e)
		{
			$this->error[$sku]=$sku.":".$e->getMessage();
		}
	}
	


	public function catalog_category_info($id,$storeView=null)
	{

		try{
			return $this->proxy->call($this->sessionid, 'catalog_category.info', array($id,$storeView));
		}
		catch(SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}

	public function catalog_category_update($categoryId,$categoryData,$storeView=null)
	{
		try{
			return $this->proxy->call($this->sessionid, 'catalog_category.update', array($categoryId,$categoryData,$storeView));
		}
		catch(SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	public function catalog_category_create($parentId,$categoryData,$storeView=null)
	{
		try{
			return $this->proxy->call($this->sessionid, 'catalog_category.create', array($parentId,$categoryData,$storeView));
		}
		catch(SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}

	public function catalog_category_delete($categoryId)
	{
		try{
			return $this->proxy->call($this->sessionid, 'catalog_category.delete',$categoryId);
		}
		catch(SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	 
	public function catalog_category_removeProduct($categoryId,$sku){
		try{
			return $this->proxy->call($this->sessionid, 'category.removeProduct',array($categoryId, $sku));
		}
		catch(SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}

	public function isError($key=null){
		if($key==null)
		{
			if (empty($this->error)) return false;
			return true;
		}
		else
		{
			if (empty($this->error[$key])) return false;
			return true;
		}
	}

	public function setError($error,$key=null){
		if ($key==null){
			$this->error[]=$error;
		}else
		{
			$this->error[$key]=$error;
		}
	}

	public function getError($key=null){
		if ($key!=null)
		{
			return $this->error[$key];
		}
		else
		{
			if (!empty($this->error))
			{
				return implode("\n",$this->error);
			}
			return false;
		}
	}

	public function product_attribute_set_list()
	{
		try{
		 return $this->proxy->call($this->sessionid, 'product_attribute_set.list');

		}catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	public function product_attribute_list($set_id)
	{

		try{
			return $this->proxy->call($this->sessionid, 'product_attribute.list', $set_id);

		}catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}

	}

	public function catalog_product_attribute_options($attribute_id)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'product_attribute.options',array('attribute_id'=>$attribute_id));

		}
		catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	/**
	 * 根据id获取多个订单的详细信息
	 * @param unknown $filter
	 */
	public function sales_order_info_list($orderIds = array())
	{
		try
		{
			return $this->proxy->call($this->sessionid,'vakindapi_order.info_list',array($orderIds));
		}
		catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	public function sales_order_olist($filter)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'vakindapi_order.olist',array($filter));
		}
		catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	public function sales_order_list($filter)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'sales_order.list',array($filter));
		}

		catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}

	public function sales_order_info($orderIncrementId)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'sales_order.info',$orderIncrementId);
		}

		catch (SoapFault $e)
		{
			$this->error[$orderIncrementId]=$orderIncrementId.":".$e->getMessage();
		}
	}

	public function sales_order_shipment_info($shippingId)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'sales_order_shipment.info',$shippingId);
		}
		catch (SoapFault $e)
		{
			$this->error[$shippingId]=$shippingId.":".$e->getMessage();
		}
	}

	public function sales_order_shipment_list($filter)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'sales_order_shipment.list',$filter);
		}
		catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}

	}

	public function sales_order_invoice_list($filter)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'sales_order_invoice.list',$filter);
		}

		catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}


	public function contacts_list($filter)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'contacts.list',$filter);
		}
		catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}

	public function contacts_info($id)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'contacts.info',$id);
		}
		catch (SoapFault $e)
		{
			$this->error[$id]=$id.":".$e->getMessage();
		}

	}

	public function contacts_create($data)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'contacts.create',array($data));
		}
		catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}

	}

	public function sales_order_shipment_create($incrementid,$order_items,$packageid="",$comment = "",$email = true,$includeComment = true)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'sales_order_shipment.create',array($incrementid,$order_items,$packageid,$comment,$email,$includeComment));
		}
		catch (SoapFault $e)
		{
			$this->error[$incrementid]=$incrementid.":".$e->getMessage();
		}
	}

	public function sales_order_invoice_create($incrementid,$order_items,$comment = "",$email = true,$includeComment = true)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'sales_order_invoice.create',array($incrementid,$order_items,$comment,$email,$includeComment));
		}
		catch (SoapFault $e)
		{
			$this->error[$incrementid]=$incrementid.":".$e->getMessage();
		}
	}

	public function sales_order_shipment_addTrack($shipmentIncrementId,$carrier,$title,$trackNumber)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'sales_order_shipment.addTrack',array($shipmentIncrementId,$carrier,$title,$trackNumber));
		}
		catch (SoapFault $e)
		{
			$this->error[$shipmentIncrementId]=$shipmentIncrementId.":".$e->getMessage();
		}

	}
	public function sales_order_shipment_addComment($shipmentIncrementId,$comment,$send=true,$includeInEmail = true)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'sales_order_shipment.addComment',array($shipmentIncrementId,$comment,$send,$includeInEmail));
		}
		catch (SoapFault $e)
		{
			$this->error[$shipmentIncrementId]=$shipmentIncrementId.":".$e->getMessage();
		}
	}

	public function rma_create($data)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'rma.create',array($data));
		}
		catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}

	public function product_attribute_options($attribute_id)
	{
		try
		{
			return $this->proxy->call($this->sessionid,'product_attribute.options',array('attribute_id'=>$attribute_id));
		}
		catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
	/**
	 * 专业刷库存的 index
	 * @xj add 
	 * @param unknown $attribute_id
	 */
	public function website_index_process()
	{
		try{
			return $this->proxy->call($this->sessionid,'vakindapi_index.process',array('indexID'=>8));
		}catch (SoapFault $e)
		{
			$this->error[]=$e->getMessage();
		}
	}
}
?>