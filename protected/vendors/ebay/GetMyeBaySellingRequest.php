<?php
/**
 * @desc 同步指定账号的广告信息
 * @author lihy
 * @since 2016-01-22
 */
class GetMyeBaySellingRequest extends EbayApiAbstract{
    private $_perPageNum = 100;
    private $_pageNum = 1;
	private $_Version = null;
	private $_OutputSelector = null;
	private $_SellingSummary = array();
	
	public $_verb = 'GetMyeBaySelling';
    public function setRequest(){
    	
    	$request = array(
    			'RequesterCredentials'=>array(
    									'eBayAuthToken'=>$this->getToken()
    								),
    			'DetailLevel' => 'ReturnAll',
    			'ActiveList' => array(
    					'Sort' => 'ShippingServiceCost',
    					//'Sort' => 'ShippingServiceCostDescending',
    					'Pagination' => array(
    							'EntriesPerPage' => $this->_perPageNum,
    							'PageNumber' => $this->_pageNum
    					)
    			),
    			/* 'SellingSummary'=>array(
    						'Include'=>true
    					) */
    			//'OutputSelector' => 'Summary',
    	);
    	
    	if(!is_null($this->_Version)) $request['Version'] = $this->_Version;
    	if(!is_null($this->_OutputSelector)) $request['OutputSelector'] = $this->_OutputSelector;
    	if(!empty($this->_SellingSummary)) $request['SellingSummary'] = $this->_SellingSummary;
    	
    	$this->request = $request;
    	return $this;
    }
    
    public function setPerPageNum($perPageNum){
    	$this->_perPageNum = $perPageNum;
    	return $this;
    }
    
    
    public function setPageNum($pageNum){
    	$this->_pageNum = $pageNum;
    	return $this;
    }
    
    public function setVersion($version){
    	$this->_Version = $version;
    	return $this;
    }
    
    public function setOutputSelectorSummary(){
    	$this->_OutputSelector = "Summary";
    	return $this;
    }
    
    public function setSellingSummaryInclude($isInclude){
    	$this->_SellingSummary['Include'] = $isInclude;
    	return $this;
    }
}