<?php
/**
 * @desc 修改在线广告(针对一口价和多属性广告)(单一功能)
 * @author lihy
 * @since 2016-01-28
 */
class ReviseFixedPriceItemRequest extends EbayApiAbstract{
	public $_verb = 'ReviseFixedPriceItem';
	private $_itemInput = array();
	// .... other here
    public function setRequest(){
    	$request = array(
    			'RequesterCredentials' => array(
    					'eBayAuthToken' => $this->getToken(),
    			),
    	);
    	 
    	$request['Item'] = $this->_itemInput;
    	$this->request = $request;
    	return $this;
    }
    
    
    public function setItemID($itemID){
    	$this->_itemInput['ItemID'] = $itemID;
    	return $this;
    }
    public function setTitle($title){
    	$this->_itemInput['Title'] = $title;
    	return $this;
    }
    public function setItemEAN($EAN){
    	$this->_itemInput['ProductListingDetails']['EAN'] = $EAN;
    	return $this;
    }
    public function setItemUPC($UPC){
    	$this->_itemInput['ProductListingDetails']['UPC'] = $UPC;
    	return $this;
    }
    public function setItemISBN($ISBN){
    	$this->_itemInput['ProductListingDetails']['ISBN'] = $ISBN;
    	return $this;
    }
    public function setItemGTIN($GTIN){
    	$this->_itemInput['ProductListingDetails']['GTIN'] = $GTIN;
    	return $this;
    }
    public function setItemBrand($brand){
    	$this->_itemInput['ProductListingDetails']['BrandMPN']['Brand'] = $brand;
    	return $this;
    }
    
    public function setItemMPN($mpn){
    	$this->_itemInput['ProductListingDetails']['BrandMPN']['MPN'] = $mpn;
    	return $this;
    }
    public function setItemSpecifics($nameValueList){
    	foreach ($nameValueList as $name=>$value){
    		$this->_itemInput['ItemSpecifics']['NameValueList'][] = array('name'=>$name, 'value'=>$value);
    	}
    	return $this;
    }
    
    public function setItemQuantity($quantity){
    	$this->_itemInput['Quantity'] = $quantity;
    	return $this;
    }
    
    public function setVariation($variation){
    	$this->_itemInput['Variations']['Variation'][] = $variation;
    	return $this;
    }
    
    public function setDescription($description){
    	$this->_itemInput['Description'] = "<![CDATA[" . $description . "]]>";
    	$this->_itemInput['DescriptionReviseMode'] = "Replace";
    	return $this;
    }
    
    public function setPictureDetails($pictureDetails){
    	$this->_itemInput['PictureDetails'] = $pictureDetails;
    	return $this;
    }
    public function setVariationsModifyNameList($modifyNameValues){
    	$this->_itemInput['Variations']['ModifyNameList']['ModifyName'] = $modifyNameValues;
  
    	return $this;
    }
    public function setVariationsPictures($pictures){
    	$this->_itemInput['Variations']['Pictures'] = $pictures;
    	return $this;
    }
    
    public function setLocation($location){
    	$this->_itemInput['Location'] = $location;
    	return $this;
    }
    
    public function setCountry($countryCode){
    	$this->_itemInput['Country'] = $countryCode;
    	return $this;
    }
    
    public function setDispatchTimeMax($DispatchTimeMax){
    	$this->_itemInput['DispatchTimeMax'] = $DispatchTimeMax;
    	return $this;
    }

	public function setShippingServiceOptions($ShippingServiceOptions){
		$this->_itemInput['ShippingDetails']['ShippingServiceOptions'][] = $ShippingServiceOptions;
		return $this;
	}
    
    public function clean(){
    	$this->_itemInput = array();
    	return $this;
    }
    
    /**
     * @override 重写
     * @desc 将请求参数转化为Xml
     */
    public function getRequestXmlBody(){
    	$xmlGeneration = new XmlGenerator();
    	$xmls = $xmlGeneration->XmlWriter()->push($this->getXmlRequestHeader(), array('xmlns' => 'urn:ebay:apis:eBLBaseComponents'))
					    	->buildXMLFilterMulti($this->getRequest())
					    	->pop()
					    	->getXml();
    	//
    	//MHelper::writefilelog("ebay-xml-".date("Y-m-d H:i:s"), $xmls);
    	return $xmls;
    }
    // ... other method
}