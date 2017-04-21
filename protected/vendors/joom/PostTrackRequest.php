<?php
/**
 * Joom邮　运单跟踪信息
 * @author	Rex
 * @since	2015-10-12
 */

class PostTrackRequest extends JoomPostBase {
	
	protected $_url = 'http://www.shpostjoom.com/api_track.asp';
	
	protected $_sign = '18f0c2df5b4187511ff58a16b3a225477257';
	
	public function setRequest() {
		return $this;
	}
	
	
	
}