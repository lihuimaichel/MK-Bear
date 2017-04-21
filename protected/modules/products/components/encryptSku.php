<?php
/**
 * @desc sku加密解密
 * @author Gordon
 */
class encryptSku {
	public $_rand_len = 2;
	
	/**
	 * 加密sku
	 * 
	 * @param string $sku        	
	 * @return string
	 */
	public function getEncryptSku($sku) {
		$encryptSku = '';
		$length = strlen ( $sku );
		$i = 0;
		$canAdd = true;
		while ( $i < $length ) {
			$encryptSku .= substr ( $sku, $i, 1 );
			if (substr ( $sku, $i, 1 ) == '.') {
				$canAdd = false;
			}
			$addchr = true;
			if ($i >= $length - 1) { // 最后一个不加
				$addchr = false;
			}
			if ($i == $length - 2) { // 倒数第二个
				$lastAscll = ord ( substr ( $sku, $i + 1, 1 ) );
				if ($lastAscll >= ord ( 'A' ) && $lastAscll <= ord ( 'Z' ) || $lastAscll >= ord ( 'a' ) && $lastAscll <= ord ( 'z' )) {
					// 如果最后一个是字符,那么倒数第二个后面也不添加
					$addchr = false;
				}
			}
			if ($canAdd && $addchr) {
				$encryptSku .= $this->getRandomChars ();
			}
			$i ++;
		}
		return $encryptSku;
	}
	
	/**
	 *
	 * @param int $length        	
	 * @return string
	 */
	public function getRandomChars() {
		$chars = '';
		$length = $this->_rand_len;
		while ( $length -- ) {
			$chars .= $this->getUpperChar ();
		}
		return $chars;
	}
	
	/**
	 * 获取规定长度的随机大写字母
	 * 
	 * @return string
	 */
	public function getUpperChar() {
		$rand = rand ( 65, 90 ); // 随机大写字母的Ascii码
		$chr = chr ( $rand );
		if (in_array ( $chr, array (
				'I',
				'O' 
		) )) {
			return $this->getUpperChar ();
		} else {
			return $chr;
		}
	}
	
	/*
	 * @desc 获取wish
	 */
	public static function getWishRealSku($encryptSku){
		$realSku = '';
		$encryptSku = trim($encryptSku);
		$length = strlen($encryptSku);
		$i = 0;
		//存在D为.的加密SKU
		// ......7D01
		$reg = "/(\d{1,2})D(\d{1,3})$/";
		if(preg_match($reg, $encryptSku)){
			$encryptSku = preg_replace($reg, "$1.$2", $encryptSku);
		}
		while($i<$length){
			$addchr = false;
			$char = substr($encryptSku,$i,1);
			if($i>=$length-1){//最后一个需要
				$addchr = true;
			}else{
				$char_ascll = ord($char);
				if($char_ascll>=ord('A') && $char_ascll<=ord('Z') || $char_ascll>=ord('a') && $char_ascll<=ord('z')){
					$addchr = false;
				}else{
					$addchr = true;
				}
			}
			if($addchr){
				$realSku .= $char;
			}
			$i++;
		}
		return $realSku;
	}
	/**
	 * 根据加密的sku获取真实的SKU
	 * 
	 * @param string $encryptSku        	
	 */
	public static function getRealSku($encryptSku) {
		$realSku = '';
		$encryptSku = trim ( $encryptSku );
		$length = strlen ( $encryptSku );
		$i = 0;
		while ( $i < $length ) {
			$addchr = false;
			$char = substr ( $encryptSku, $i, 1 );
			if ($i >= $length - 1) { // 最后一个需要
				$addchr = true;
			} else {
				$charAscll = ord ( $char );
				if ($charAscll >= ord ( 'A' ) && $charAscll <= ord ( 'Z' ) || $charAscll >= ord ( 'a' ) && $charAscll <= ord ( 'z' )) {
					$addchr = false;
				} else {
					$addchr = true;
				}
			}
			if ($addchr) {
				$realSku .= $char;
			}
			$i ++;
		}
		return $realSku;
	}
	
        /**
         * @desc 亚马逊sku加密   
         * 规则： 原sku：2422.02 加密： 2fd4hg2ee2D02
             *      '.'前面每个数字中间插两位随机小写字母， 如果.前面一位是字母则前面不加随机
             *      '.'换成大写D
             *      '.'后面不变
         * @param  string $sku
         * @return string $encryptSku
         * @since 2015-03-31
         * @author Super
         * 
         */
        public function getAmazonEncryptSku($sku)
        {
            $encryptSku = '';//加密后的sku
            $sku = trim($sku);
            $rand = 2;//插入两位
            if(strpos($sku,'.'))//判断sku是否有'.';
            {
                    $front_sku = strstr($sku,'.',true);	//截取sku'.'	前的;
                    //	$position_num=strpos($sku,'.');
                    $back_sku = ltrim($sku,$front_sku);//截取sku'.'后的;
                    $back_sku = ltrim($back_sku,'.');
                    $len =strlen($front_sku);
                    $sku = $front_sku;
            }
            else
            {
                    $len =strlen($sku);
            }
            $sku_lastOne = substr($sku,$len-1,1);//截取sku最后一位数
            for($i = 0;$i<$len-1;$i++)
            {
                    $encryptSku.= substr($sku,$i,1);
                    $this->_rand_len = $rand;
                    
                    $encryptSku.= strtolower($this->getRandomChars());
            }
            $encryptSku.= $sku_lastOne;
            if(isset($back_sku) && $back_sku)
            {
                    $encryptSku.= 'D'.$back_sku;//小数点用D代替
            }	
                    return $encryptSku;
        }
        
	/*
	 * 2fd4hg2ee2D02_C1 sku只取下滑线前面部分解密，规格同加密
	 */
	function getAmazonRealSku2($encryptSku) {
		$encryptSku = trim($encryptSku);
		if(strtoupper($encryptSku)=='UT-EU22-ZOAT'){
			$encryptSku = '67048-ES1';
		}elseif(strtoupper($encryptSku)=='BM-L1P7-RTE2'||strtoupper($encryptSku)=='LD-GCV0-P541'
				||strtoupper($encryptSku)=='LD-GCV0-P541'
		){
			return '70799.03';
		}elseif(strtoupper($encryptSku) == 'VI-NSVF-SWKV'){
			return '70799.01';
		}
		
		if(strtoupper($encryptSku) == 'P4-EG90-EPVI'){
			return '89400';
		}
		
		if(strtoupper($encryptSku) =="DB-U8CS-1XTF"){
			return "102390";
		}
		
		if(strtoupper($encryptSku) == '7325.02-US12'){
			return '57325.02';
		}
		if(strtoupper($encryptSku) == '18607-FR1-S'){
			return '18607';
		}
		if(strtoupper($encryptSku) == '23154.01-UK1'){
			return '23154.01';
		}
		if(strtoupper($encryptSku) == '61819.05-UK1'){
			return '61819.05';
		}
		if(strtoupper($encryptSku) == '22131.07－JP02'){
			return '22131.07';
		}
		if(strtoupper($encryptSku) == '32944.01－JP02'){
			return '32944.01';
		}
		if(strtoupper($encryptSku) == '52556.01-jp01'){
			return '52556.01';
		}
		if(strtoupper($encryptSku) == '75715.04－JP2'){
			return '75715.04';
		}
		if(strtoupper($encryptSku) == '7MG1EP5BF7YF6D02ZZ_AUSZ'){
			return '71576.02';
		}
		if(strtoupper($encryptSku) == '6RG5GF4SK2HJ7DE01'){
			return '65427';
		}
		if(strtoupper($encryptSku) == '73062.02UK03'){
			return '73062.02';
		}
		if(strtoupper($encryptSku) == '1MN5XP6TL6'){
			return '1566';
		}
		
		if(strtoupper($encryptSku) == '1093.02-US1'){
			return '1093.02';
		}
		if(strtoupper($encryptSku) == '61629.01-US11'){
			return '61629.01';
		}
		if(strtoupper($encryptSku) == '81397.02-US5'){
			return '81397.02';
		}
		if(strtoupper($encryptSku) == '76975.09-US6'){
			return '76975.09';
		}
		if(strtoupper($encryptSku) == '76975.05-US6'){
			return '76975.05';
		}
		if(strtoupper($encryptSku) == '75807.03-US11-Y'){
			return '75807.03';
		}
		if(strtoupper($encryptSku) == '6538F-US1-A' || strtoupper($encryptSku) == '6538F-UK1'){
			return '6538F';
		}
		if(strtoupper($encryptSku) == '5WN0YX4ZG3VAE-US02'){
			return '5043E';
		}
		if(strtoupper($encryptSku) == '6KM0TN6SU7UVA-US02'){
			return '6067A';
		}
		if(strtoupper($encryptSku) == '4AB1XH2GP2TTA-US5'){
			return '4122A';
		}
		if(strtoupper($encryptSku) == '4026A-DE1'){
			return '4026A';
		}

		if(strtoupper($encryptSku) == '4BJ0AQ2DV6NHA-DE02'){
			return '4026A';
		}
		
		if(strtoupper($encryptSku) == 'P4-EG90-EPVI'){
			return '89400';
		}
		if(strtoupper($encryptSku) == '6329A-US5'){
			return '6329A';
		}
		if(strtoupper($encryptSku) == '4026A-DE1'){
			return '4026A';
		}
		if(strtoupper($encryptSku) == '4AB1XH2GP2TTA-US5'){
			return '4122A';
		}
		if(strtoupper($encryptSku) == '4122A-UK1'){
			return '4122A';
		}
		
		if(strtoupper($encryptSku) == '32508.03-US5'){
			return '62508.03';
		}
		
		if(strtoupper($encryptSku) == '3NX0AL1XL7YKB-US01'){
			return '3017B';
		}
		if(strtoupper($encryptSku) == '6UM4UY4EL3ZQC-JP04'){
			return '6443C';
		}
		if(strtoupper($encryptSku) == '6316D-DE1'){
			return '6316D';
		}
		if(strtoupper($encryptSku) == '5Q-2IXJ-3972'){
			return '70799.02';
		}
		if(strtoupper($encryptSku) == 'MU-O5NZ-VA5S'){
			return '69763';
		}
		if(strtoupper($encryptSku) == 'CI-TAB2-OATI'){
			return '70799.04';
		}
		if(strtoupper($encryptSku) == 'EA-9S6V-H9OF'){
			return '70799.06';
		}
		if(strtoupper($encryptSku) == '70084.02-UK4'){
			return '70084.02';
		}
		if(strtoupper($encryptSku) == '87558.01-UK1'){
			return '87558.01';
		}
		if(strtoupper($encryptSku) == '2012-UK1-A'){
			return '2012';
		}
		if(strtoupper($encryptSku) == '84615.08-DE1'){
			return '84615.08';
		}
		if(strtoupper($encryptSku) == '73438.02-US12'){
			return '73438.02';
		}
		if(strtoupper($encryptSku) == '8ZU4GF6AU1PZ5D02-US05'){
			return '84615.02';
		}
		if(strtoupper($encryptSku) == '8HT7ST7ET8KL0D01-US05'){
			return '87780.01';
		}
		if(strtoupper($encryptSku) == '8PL7FW7WX8FR0D04-US05'){
			return '87780.04';
		}
		
		if(strtoupper($encryptSku) == '87648.04-DE1'){
			return '87648.04';
		}
		
		if(strtoupper($encryptSku) == '29299-JP06'){
			return '26299';
		}
		if(strtoupper($encryptSku) == '7UQ8GZ9HY6ES9D04-UK05'){
			return '78969.04';
		}
		
		if(strtoupper($encryptSku) == 'B00VQ3F2Z8-US02'){
			return '78750';
		}
		
		if(strtoupper($encryptSku) == '6538E-US5'){
			return '6538E';
		}
		if(strtoupper($encryptSku) == '6538K-US1'){
			return '6538K';
		}
		if(strtoupper($encryptSku) == '6427A-US12'){
			return '6427A';
		}
		if(strtoupper($encryptSku) == '23516-DE1'){
			return '23516';
		}
		
		if(strtoupper($encryptSku) == 'O8-NAP3-I8PX'){
			return '86968';
		}
		
		if(strtoupper($encryptSku) == '92-25BI-MDUS'){
			return '86270';
		}
		
		if(strtoupper($encryptSku) == 'B00SHFOXSK'){
			return '72766.05';
		}
		
		if(strtoupper($encryptSku) == 'KF4EG4SZ9ZJ8D18-FR3'){
			return '94498.18';
		}
		if(strtoupper($encryptSku) == '4U-XB0G-7BHX'){
			return '89593';
		}
		if(strtoupper($encryptSku) == '6NU3FE1RV6QQD-DE02'){
			return '6316D';
		}
		if(strtoupper($encryptSku) == 'B00SHFOXSK'){
			return '72766.05';
		}
		
		if(strtoupper($encryptSku) == '09TU7YB6VP1FK6-FR02'){
			return '97616';
		}
		
		if(strtoupper($encryptSku) == '8YZ1DP6KH8GW5491-US04'){
			return '81685';
		}

		//如果以FBA结尾，排除sku带-_, 先去掉FBA, add by yangsh 2017/1/13
		if (preg_match("/[^-_]FBA$/i",$encryptSku)) {
		    $encryptSku = substr($encryptSku,0,-3);
		}		

		if (strpos ( $encryptSku, "-" ) !== false) {
			// 16395-DE1-A
			$sku = explode ( "-", $encryptSku );
			
			$exskus = ",0235A,0237A,0238A,0252A,0270A,0272A,0273A,0372A,1005B,10067A,10069A,10075A,10084A,1008A,10144A,10166A,10181A,10181B,10181C,10181D,10182A,10183A,10189A,10195A,10205A,10247A,10264A,10277A,10279A,10287A,1041B,1045A,1047A,10490A,10490B,1053A,1076A,1076B,1084A,1093A,1093B,1120A,1124A,1150A,1153B,1156A,1173A,11890 ,1195A,1253A,1259A,1263A,1302C,1302D,2015B,2032B,2033B,2034A,2043A,2046B,2053B,2097A,2177A,2182A,2182B,2221A,2277A,3017A,3017B,3031A,3052A,3075A,3096F,3111C,3151A,3159A,3159B,3204A,4008A,4020B,4026A,4026C,4026D,4058A,4066A,4072A,4122A,4141A,4168A,4212A,4212B,4212C,4212D,4223A,4290A,4330A,5043A,5043B,5043C,5043D,5043E,5122A,5132A,5132B,5136A,5136B,5151A,5160A,5160B,5185A,5301A,5441A,6008A,6041A,6056A,6056B,6056C,6067A,6072A,6072B,6072C,6072D,6072E,6072F,6072G,6090A,6090B,6090C,6090D,6091A,6091B,6109A,6125A,6127A,6128A,6155D,6172C,6172F,6172H,6174A,6174B,6174C,6174D,6179A,6183A,6183B,6183C,6183D,6183E,6188A,6194A,6203H,6205C,6219D,6249A,6249B,6249C,6267A,6268A,6268B,6281B,6281D,6281E,6294A,6305A,6315B,6316A,6316B,6316C,6316D,6317A,6319A,6319B,6320A,6323A,6327A,6329A,6332A,6341A,6341B,6341C,6341E,6341G,6341H,6344A,6344B,6361A,6361B,6366A,6368A,6368B,6375B,6375C,6375D,6377A,6385A,6386A,6387A,6387B,6424A,6427A,6443A,6443C,6443E,6490A,6505A,6516A,6517A,6517C,6519A,6526A,6526B,6538A,6538B,6538C,6538D,6538E,6538F,6538G,6538H,6538I,6538J,6538K,6538L,6558A,6592A,7447A,7448A,7507A,";
			
			$sku = $sku [0];
			
			$fixsku = "," . $sku . ",";
			
			if (strpos ( $exskus, $fixsku ) !== false) {
				return $sku;
			}
			
			$frontsku = "";
			$backsku = "";
			if (strpos ( $sku, "." ) !== false) {
				$ss = explode ( ".", $sku );
				$backsku = $ss [1];
				$frontsku = $ss [0];
			} else {
				$frontsku = $sku;
			}
			$len = strlen ( $frontsku );
			$temp = "";
			for($i = 0; $i < $len; $i ++) {
				$str = substr ( $frontsku, $i, 1 );
				if (preg_match ( '/\d/', $str )) {
					$temp .= $str;
				}
			}
			$temp2 = $temp;
			if ($backsku != "") {
				$temp2 = $temp . '.' . $backsku;
			}
			if ($sku == $temp2) {
				//解密后如果不符合SKU规则（字符不能小于4位），直接返回空
				if(strlen($temp2) < 4) return false;
				return $temp2;
			}
		}

		//--- 普通加密
		//--- 判断加密格式是否符合要求，不符合要求直接返回空
		$encryptSku = trim ( $encryptSku );
		if (strpos ( $encryptSku, "--" ) !== false) {
			$sku = explode ( "--", $encryptSku );
			$sku = $sku [0];
			if (strlen ( $sku ) < 4) {
				$sku = sprintf ( "%04d", $sku ); // 格式化产品号.不足位的在前加0
			}
		} elseif (strpos ( $encryptSku, "-" ) !== false) {
			$sku = explode ( "-", $encryptSku );
			$sku = $sku [0];
			// if(strpos($encryptSku,'-2')||strpos($encryptSku,'-Busw')||strpos($encryptSku,'- Busw')){
			$encryptSku = $sku;
			$back_sku = '';
			$sku = '';
			if (strpos ( $encryptSku, 'D' )) {
				$back_sku = strstr ( $encryptSku, 'D' );
				$back_sku = str_replace ( 'D', '.', $back_sku ); // 后面的D替换成'.';
				$encryptSku = strstr ( $encryptSku, 'D', true );
			}
			$len = strlen ( $encryptSku );
			for($i = 0; $i < $len; $i ++) {
				$str = substr ( $encryptSku, $i, 1 );
				if (preg_match ( '/\d/', $str )) {
					$sku .= $str;
				}
			}
			if ($back_sku != '') {
				$sku .= $back_sku;
			}
			// }
			// else{
			// if(strlen($sku) < 4){
			// $sku = sprintf("%04d",$sku);//格式化产品号.不足位的在前加0
			// }
			// }
			// $sku = $this->getRealSku($sku);
		} elseif (strpos ( $encryptSku, "." ) !== false) {
			return $encryptSku;
		} else {
			$sku = '';
			if (strpos ( $encryptSku, '_' )) 			// 判断是否有'_';
			{
				$encryptSku = strstr ( $encryptSku, '_', true ); // 去掉'-'后面的内容
			}
			$back_sku = '';
			if (strpos ( $encryptSku, 'D' )) {
				$back_sku = strstr ( $encryptSku, 'D' );
				$back_sku = str_replace ( 'D', '.', $back_sku ); // 后面的D替换成'.';
				$encryptSku = strstr ( $encryptSku, 'D', true );
			}
			$len = strlen ( $encryptSku );
			for($i = 0; $i < $len; $i ++) {
				$str = substr ( $encryptSku, $i, 1 );
				if (preg_match ( '/\d/', $str )) {
					$sku .= $str;
				}
			}
			$last_ascll = ord ( substr ( $encryptSku, $len - ($len + 1), 1 ) ); // 如果加密后的 最后一个字符是 英文字母 说明这个sku 本身最后一个就是字母
			if (($last_ascll >= ord ( 'A' ) && $last_ascll <= ord ( 'Z' )) || ($last_ascll >= ord ( 'a' ) && $last_ascll <= ord ( 'z' ))) {
				$sku .= substr ( $encryptSku, $len - ($len + 1), 1 );
			}
			if ($back_sku != '') {
				$sku .= $back_sku;
			}
		}
		// add by Tom 去掉业务人员因上架产品误操作而导致seller_sku产生多余空格的问题
		if (mb_substr_count ( $sku, ' ' )) {
			$sku = str_replace ( ' ', '', $sku );
		}

		//解密后如果不符合SKU规则（字符不能小于4位），直接返回空
		if(strlen($sku) < 4) return false;

		return $sku;
	}
	
	/*
	 * 2fd4hg2ee2D02_C1
	 * 
	 * sku只取下滑线前面部分解密，规格同加密
	 */
	public static function getAliRealSku($encryptSku)
	{
		$encryptSku =trim($encryptSku);
		if(strpos($encryptSku,"--") !== false){
			$sku = explode("--",$encryptSku);
			$sku = $sku[0];
			if(strlen($sku) < 4){
				$sku = sprintf("%04d",$sku);//格式化产品号.不足位的在前加0
			}
		}elseif(strpos($encryptSku,"-") !== false){
			$sku = explode("-",$encryptSku);
			$sku = $sku[0];
			if(strpos($encryptSku,'-2')||strpos($encryptSku,'-Busw')||strpos($encryptSku,'- Busw')){
				$encryptSku = $sku;
				$sku = '';
				$back_sku='';
				if(strpos($encryptSku,'D'))
				{
					$back_sku = strstr($encryptSku,'D');
					$back_sku = str_replace('D','.',$back_sku);//后面的D替换成'.';
					$encryptSku = strstr($encryptSku,'D',true);
				}
				$len = strlen($encryptSku);	
				for($i = 0; $i<$len;$i++)
				{
					$str = substr($encryptSku,$i,1);
					if(preg_match('/\d/',$str))
					{	
						$sku.=$str;
					}
				}
				if($back_sku!='')
				{
					$sku.= $back_sku;
				}
			}else{
				if(strlen($sku) < 4){
					$sku = sprintf("%04d",$sku);//格式化产品号.不足位的在前加0
				}
			}
			
		}elseif(strpos($encryptSku,".") !== false){
			//目前是没有加密的线上sku，备注lihy 2016-04-20
			$encryptSku = self::getRealSku($encryptSku);
			return $encryptSku;
		}
		else{
			$sku = '';
			$back_sku='';
			if(strpos($encryptSku,'_'))//判断是否有'_';
			{
				$encryptSku = strstr($encryptSku,'_',true);//去掉'-'后面的内容
			}
			if(strpos($encryptSku,'D') && substr($encryptSku, -1, 1) != 'D')
			{
				//需要保证最后一位不是D的才可以 lihy 2016-04-20
				$back_sku = strstr($encryptSku,'D');
				$back_sku = str_replace('D','.',$back_sku);//后面的D替换成'.';
				$encryptSku = strstr($encryptSku,'D',true);
			}
			$len = strlen($encryptSku);	
			for($i = 0; $i<$len;$i++)
			{
				$str = substr($encryptSku,$i,1);
				if($i == $len-1){//SKU最后一个元素不匹配字母(6538K) add by Super 2015-05-22
					$sku.=$str;
				}else{
					if(preg_match('/\d/',$str))
					{
						$sku.=$str;
					}
				}
			}
			if($back_sku!='')
			{
				$sku.= $back_sku;
			}
		}
		//add by Tom 去掉业务人员因上架产品误操作而导致seller_sku产生多余空格的问题
		if(mb_substr_count($sku,' ')){
			$sku = str_replace(' ','', $sku);
		}
		return $sku;
	}
        
        /*
	 * @desc 获取joom
	 */
	public static function getJoomRealSku($encryptSku){
		$realSku = '';
		$encryptSku = trim($encryptSku);
		$length = strlen($encryptSku);
		$i = 0;
		while($i<$length){
			$addchr = false;
			$char = substr($encryptSku,$i,1);
			if($i>=$length-1){//最后一个需要
				$addchr = true;
			}else{
				$char_ascll = ord($char);
				if($char_ascll>=ord('A') && $char_ascll<=ord('Z') || $char_ascll>=ord('a') && $char_ascll<=ord('z')){
					$addchr = false;
				}else{
					$addchr = true;
				}
			}
			if($addchr){
				$realSku .= $char;
			}
			$i++;
		}
		return $realSku;
	}


	/**
	 * @desc sku转换格式
	 * @param $sku
	 */
	public static function skuToFloat($sku){
		//检测不够四位，不够的话前缀补零
		if(is_numeric($sku)){
			//如果检测到位浮点类型,四舍五入，主要解决会出现小数点后面带99999999...的情况
			// $sku = floatval($sku);
			if(is_float($sku)){
				$sku = round($sku, 2);
			}
			$skuPre = substr($sku, 0, strpos($sku, "."));
			if(strlen($skuPre)<4){
				// if(is_float($sku)){
				// 	$sku = sprintf("%07.2f", $sku);
				// }else{
					$sku = sprintf("%04d", $sku);
				// }
			}

			if(strpos($sku, ".") > 0){
				return sprintf("%07.2f", $sku);
			}else{
				return $sku;
			}
		}else{
			return $sku;
		}
	}
}