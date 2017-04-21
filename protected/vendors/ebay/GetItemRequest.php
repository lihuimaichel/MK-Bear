<?php
/**
 * @desc 根据ItemID获取一条广告的信息
 * @author lihy
 * @since 2015-06-02
 */
class GetItemRequest extends EbayApiAbstract{
    private $_itemID =null;
    private $_IncludeItemSpecifics = false;
    private $_IncludeVariations = false;
    private $_IncludeWatchCount = false;
    private $_OutputSelector = null;
	private $_Version = null;
	public $_verb = 'GetItem';
    public function setRequest(){
    	$request = array(
    			'RequesterCredentials'=>array(
    									'eBayAuthToken'=>$this->getToken()
    								),
    			
    			'ItemID'	=>	$this->_itemID
    	);
    	
    	if(!is_null($this->_Version)) $request['Version'] = $this->_Version;
    	if($this->_IncludeItemSpecifics) $request['IncludeItemSpecifics'] = $this->_IncludeItemSpecifics;
    	if($this->_IncludeVariations) $request['IncludeVariations'] = $this->_IncludeVariations;
    	if($this->_IncludeWatchCount) $request['IncludeWatchCount'] = $this->_IncludeWatchCount;
    	if(!is_null($this->_OutputSelector)){
    		$request['OutputSelector'] = $this->_OutputSelector;
    	}
    	$this->request = $request;
    	return $this;
    }

    public function setOutSelector($isSet = false){
    	if($isSet){
    		$this->_OutputSelector = array(
	    			'Item.ShippingDetails.ShippingServiceOptions',
	    			'Item.ShippingDetails.InternationalShippingServiceOption',
	    			'Item.PrimaryCategory',
	    			'Item.PayPalEmailAddress',
	    			'Item.SellingStatus',
	    			'Item.PictureDetails',
	    			'Item.SKU',
	    			'Item.Title',
	    			'Item.Quantity',
	    			'Item.Variations',
	    			'Item.ListingDetails',
	    			'Item.TimeLeft',
	    			'Item.Seller',
	    			'Item.ItemID',
	    			'Item.ListingDuration',
	    			'Item.ListingType',
	    			'Item.Storefront',
	    			'Item.Site',
	    			'Item.WatchCount',
	    			'Item.Location',
	    			'Item.DispatchTimeMax',
	    			'Item.ItemSpecifics',
    		);
    	}
    	return $this;
    }

    public function setOutSelectorSimple($isSet = false){
        if($isSet){
            $this->_OutputSelector = array(
                    'Item.ItemID',
                    'Item.SKU',
                    'Item.Quantity',
                    'Item.SellingStatus.QuantitySold',
                    'Item.SellingStatus.CurrentPrice',
                    'Item.SellingStatus.ListingStatus',
                    'Item.ListingType',
                    'Item.ListingDuration',
                    'Item.Site',                    
                    'Item.Variations.Variation'                    
            );
        }
        return $this;
    } 

    public function setItemID($itemID){
    	$this->_itemID = $itemID;
    	return $this;
    }
    
    public function setIncludeSpecifics($include = false){
    	$this->_IncludeItemSpecifics = $include;
    	return $this;
    }
    
    public function setIncludeWatchCount($include = false){
    	$this->_IncludeWatchCount = $include;
    	return $this;
    }
    
    public function setIncludeVariations($include = false){
    	$this->_IncludeVariations = $include;
    	return $this;
    }
    
    /**
     * @override 重写
     * @desc 将请求参数转化为Xml
     */
    public function getRequestXmlBody(){
    	$xmlGeneration = new XmlGenerator();
    	return $xmlGeneration->XmlWriter()->push($this->getXmlRequestHeader(), array('xmlns' => 'urn:ebay:apis:eBLBaseComponents'))
    	->buildXMLFilterMulti($this->getRequest())
    	->pop()
    	->getXml();
    }
}