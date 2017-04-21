<?php
class EbayEncryptSKU{
	function getEncryptSku($sku, $n = 0){
		$encryptSku = '';
		$sku = trim($sku);
		$length = strlen($sku);
		$i = 0;
		$global_addchar = true;
		while($i<$length){
			$encryptSku .= substr($sku,$i,1);
			if(substr($sku,$i,1)=='.'){
				$global_addchar = false;
			}
			$addchr = true;
			if($i>=$length-1){//最后一个不加
				$addchr = false;
			}
			if($i==$length-2){//倒数第二个
				$last_ascll = ord(substr($sku,$i+1,1));
				if($last_ascll>=ord('A') && $last_ascll<=ord('Z') || $last_ascll>=ord('a') && $last_ascll<=ord('z')){
					//如果最后一个是字符,那么倒数第二个后面也不添加
					$addchr = false;
				}
			}
			if($global_addchar && $addchr){
				//				$rand = rand(1,2);
				$rand = 2;
				$encryptSku .= $this->getRandomChars($rand);
			}
			$i++;
		}
		return $encryptSku;
	}
	
	function getRandomChars($length){
		$chars = '';
		while($length--){
			$chars .= $this->getUpperChar();
		}
		return $chars;
	}
	
	function getUpperChar(){
		$rand = rand(65,90);
		$chr = chr($rand);
		if(in_array($chr,array('I','O'))){
			return $this->getUpperChar();
		}else{
			return $chr;
		}
	}
}