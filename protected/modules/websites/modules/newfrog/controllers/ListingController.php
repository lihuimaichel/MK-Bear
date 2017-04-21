<?php
/**
 * 
 */
class ListingController extends WebsiteProductListingController{
	
	public $platformcode = 'NF';
	
	public function getPlatformcode(){
		return $this->platformcode;
	}
}