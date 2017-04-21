<?php
/**
 * 
 */
class OrderController extends WebsiteOrderController{
	public $platformcode = 'ECB';
	public function getPlatformcode(){
		return $this->platformcode;
	}
}