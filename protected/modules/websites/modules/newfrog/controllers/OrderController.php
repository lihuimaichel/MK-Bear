<?php
/**
 * 
 */
class OrderController extends WebsiteOrderController{
	public $platformcode = 'NF';
	public function getPlatformcode(){
		return $this->platformcode;
	}
}