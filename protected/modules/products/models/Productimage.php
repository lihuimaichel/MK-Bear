<?php

class Productimage extends ProductsModel
{
	public $img_config = array();
	
	function __construct(){
		$this->img_config = UebModel::model('SysConfig')->getPairByType('image');//取ueb_sys_config 表
	}
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'ueb_product';
	}

	public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/products/Productimage/index');
    }
    /*
     * 获取文件配置信息
     * 模块路径：systems/models/imgset.php
     * 返回图片相关设置一维数组
     */
    public function get_img_config(){
//     	return UebModel::model('Imgset')->para_list();//取ueb_para_setting 表
    	return UebModel::model('SysConfig')->getPairByType('image');//取ueb_sys_config 表
    }
    /*
     * 验证图片
     * 条件：图片大小，尺寸，规格，========（是否在sku存在）
     * $file_name 绝对路径
     * 返回验证结果，一维 数组
     */
    public function check_img($file_name,$path){
		$img_config = $this->img_config;//$this->get_img_config();
    	$img_tmp_path = Yii::getPathOfAlias('webroot').$img_config['img_temp_path'].$path;
    	$file_name_path = $img_tmp_path.'/'.$file_name;
    	$str = '';
    	
    	
    	$img_info   = $this->getImagesInfo($file_name_path);
    	    	
    	if(false === strpos($img_config['img_allowed_ext'],$img_info['type'])){
    		$str .= ', '.Yii::t('products','The format is wrong, must be').':'.$img_config['img_allowed_ext'];
    	}
    	
    	if($img_config['img_allowed_size'] < $img_info['size']){
    		$str .= ', '.Yii::t('products','Size is not greater than').' '.$img_config['img_allowed_size'].'KB';
    	}
    	$img_wh = $img_info['width'].'x'.$img_info['height'];
    	if(false === strpos($img_config['img_allowed_width_height'],$img_wh)){
    		$str .= ', '.Yii::t('products','The wrong size, must be for the following specifications').': '.$img_config['img_allowed_width_height'];
    	}
    	if($str==''){//如果以上都没错误，再查库
	    	$sku = $this->deal_sku($img_info['type'],$file_name);
	    	$flag = UebModel::model('Product')->checkSkuIsExisted($sku);
	    	if(!$flag){
	    		$str .= ', <strong>'.Yii::t('products','Images corresponding SKU does not exist').'</strong>';
	    	}
    	}
    	if($str==''){
    		return false;
    	}else{
    		return $file_name_path.$str;
    	}
    }
    /*
     * 处理sku-1.jpg,-2......等形式图片
     */
    public function deal_sku($img_type='jpg',$file_name){
    	$sku = str_replace('.'.$img_type,'',$file_name);
    	$n=strrpos($sku,'-');//去除图片名为-1，-2的文件为主图形式，即图名35897-2.jpg形的图片，取其SKU时只取35897，-2去掉
    	if ($n) $sku=substr($sku,0,$n);//删除后面
    	return $sku;
    }
    
    //参数images为图片的绝对地址
    public function getImagesInfo($images){
    	$img_info = getimagesize($images);
    	
    	//$img_info[2] :
    	// 1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，6 = BMP，7 = TIFF(intel byte order)，
    	// 8 = TIFF(motorola byte order)，9 = JPC，10 = JP2，11 = JPX，12 = JB2，
    	// 13 = SWC，14 = IFF，15 = WBMP，16 = XBM；
    	switch ($img_info[2]){
    		case 1:
    			$imgtype = "gif";
    			break;
    		case 2:
    			$imgtype = "jpg";
    			break;
    		case 3:
    			$imgtype = "png";
    			break;
    		default:
    			$imgtype = "";
    	}
//     	$img_type = $imgtype."图像";
    	//获取文件大小
    	$img_size = ceil(filesize($images)/1000);
    	clearstatcache();
    	$new_img_info = array (
	    	"width"=>$img_info[0], //图像宽
	    	"height"=>$img_info[1], //图像高
	    	"type"=>$imgtype, //图像类型
	    	"size"=>$img_size //图像大小
    	);
    	return $new_img_info;
    }
    

	/**
	 * getDir()取文件夹列表，getFile()取对应文件夹下面的文件列表
	 */
	
	//获取文件目录列表,该方法返回数组
	public function getDir($dir) {
		$dirArray=array();
		if (false != ($handle = opendir ( $dir ))) {
			$i=0;
			while ( false !== ($file = readdir ( $handle )) ) {
				//去掉"“.”、“..”以及带“.xxx”后缀的文件
				if ($file != "." && $file != ".."&&!strpos($file,".")) {
					$dirArray[$i]=$file;
					$i++;
				}
			}
			//关闭句柄
			closedir ( $handle );
		}
		return $dirArray;
	}
	
	//获取文件列表
	public function getFile($dir) {
		$fileArray=array();
		if (false != ($handle = opendir ( $dir ))) {
			$i=0;
			while ( false !== ($file = readdir ( $handle )) ) {
				//去掉"“.”、“..”以及带“.xxx”后缀的文件
				if ($file != "." && $file != ".." && strpos($file,".")) {
					$fileArray[$i]=$file;
					if($i==500){
						break;
					}
					$i++;
				}
			}
			//关闭句柄
			closedir ( $handle );
		}
		return $fileArray;
	}
	/*
	 * 层级生成文件夹
	 * 使用方法 mkdirs('div/css/layout');
	 */
	function mkdirs($dir)
	{
		if(!is_dir($dir))
		{
			if(!mkdirs(dirname($dir))){
				return false;
			}
			if(!mkdir($dir,0777)){
				return false;
			}
		}
		return true;
	}
	/*
	 * 层级删除目录
	 */
	function rmdirs($dir)
	{
		$d = dir($dir);
		while (false !== ($child = $d->read())){
			if($child != '.' && $child != '..'){
				if(is_dir($dir.'/'.$child))
					rmdirs($dir.'/'.$child);
				else unlink($dir.'/'.$child);
			}
		}
		$d->close();
		rmdir($dir);
	}
	
	
	/**
	 * 建立文件夹
	 *
	 * @param string $aimUrl
	 * @return viod
	 */
	public function createDir($aimUrl) {
		$aimUrl = str_replace('', '/', $aimUrl);
		$aimDir = '';
		$arr = explode('/', $aimUrl);
		$result = true;
		foreach ($arr as $str) {
			$aimDir .= $str . '/';
			if (!file_exists($aimDir)) {
				$result = mkdir($aimDir,0777);
			}
		}
		return $result;
	}
	
	/**
	 * 移动文件
	 *
	 * @param string $fileUrl
	 * @param string $aimUrl
	 * @param boolean $overWrite 该参数控制是否覆盖原文件
	 * @return boolean
	 */
	public function moveFile($fileUrl, $aimUrl, $overWrite = false) {
		$img_tmp_path = rtrim(Yii::getPathOfAlias('webroot').$this->img_config['img_temp_path'],'/').$fileUrl;
		$img_web_path = rtrim(Yii::getPathOfAlias('webroot').$this->img_config['img_local_path'],'/').$aimUrl;
		if (!file_exists($img_tmp_path)) {
			return false;
		}
		if (file_exists($img_web_path) && $overWrite == false) {
			return ;
		} elseif (file_exists($img_web_path) && $overWrite == true) {
			$this->unlinkFile($img_web_path);	
		    $aimDir = dirname($img_web_path);
		    $this->createDir($aimDir);
		    rename($img_tmp_path, $img_web_path);
			return true;
		}else{
			$aimDir = dirname($img_web_path);
			$this->createDir($aimDir);
			rename($img_tmp_path, $img_web_path);
			return true;
		}
	}
	
	/**
	 * 删除文件
	 *
	 * @param string $aimUrl
	 * @return boolean
	 */
	function unlinkFile($aimUrl) {
		if (file_exists($aimUrl)) {
			unlink($aimUrl);
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 生成缩略图
	 * @author 
	 * @param string     源图绝对完整地址{带文件名及后缀名}
	 * @param string     目标图绝对完整地址{带文件名及后缀名}
	 * @param int        缩略图宽{0:此时目标高度不能为0，目标宽度为源图宽*(目标高度/源图高)}
	 * @param int        缩略图高{0:此时目标宽度不能为0，目标高度为源图高*(目标宽度/源图宽)}
	 * @param int        是否裁切{宽,高必须非0}
	 * @param int/float  缩放{0:不缩放, 0<this<1:缩放到相应比例(此时宽高限制和裁切均失效)}
	 * @return boolean
	 */
	function img2thumb($src_img, $dst_img, $width = 100, $height = 100, $cut = 0, $proportion = 0)
	{
		if(!is_file($src_img))
		{
			return false;
		}
		
		$ot = $this->fileext($dst_img);
		$otfunc = 'image' . (strtoupper($ot) == 'JPG' ? 'jpeg' : $ot);
		
		$srcinfo = getimagesize($src_img);
		$src_w = $srcinfo[0];
		$src_h = $srcinfo[1];
		
		$type  = strtolower(substr(image_type_to_extension($srcinfo[2]), 1));
		if(empty($type)){
			return false;
		}
		$createfun = 'imagecreatefrom' . ($type == 'jpg' ? 'jpeg' : $type);
		$dst_h = $height;
		$dst_w = $width;
		$x = $y = 0;
	
		/**
		 * 缩略图不超过源图尺寸（前提是宽或高只有一个）
		 */
		if(($width> $src_w && $height> $src_h) || ($height> $src_h && $width == 0) || ($width> $src_w && $height == 0))
		{
			$proportion = 1;
		}
		if($width> $src_w)
		{
			$dst_w = $width = $src_w;
		}
		if($height> $src_h)
		{
			$dst_h = $height = $src_h;
		}
	
		if(!$width && !$height && !$proportion)
		{
			return false;
		}
		if(!$proportion)
		{
			if($cut == 0)
			{
				if($dst_w && $dst_h)
				{
					if($dst_w/$src_w> $dst_h/$src_h)
					{
						$dst_w = $src_w * ($dst_h / $src_h);
						$x = 0 - ($dst_w - $width) / 2;
					}
					else
					{
						$dst_h = $src_h * ($dst_w / $src_w);
						$y = 0 - ($dst_h - $height) / 2;
					}
				}
				else if($dst_w xor $dst_h)
				{
					if($dst_w && !$dst_h)  //有宽无高
					{
						$propor = $dst_w / $src_w;
						$height = $dst_h  = $src_h * $propor;
					}
					else if(!$dst_w && $dst_h)  //有高无宽
					{
						$propor = $dst_h / $src_h;
						$width  = $dst_w = $src_w * $propor;
					}
				}
			}
			else
			{
				if(!$dst_h)  //裁剪时无高
				{
					$height = $dst_h = $dst_w;
				}
				if(!$dst_w)  //裁剪时无宽
				{
					$width = $dst_w = $dst_h;
				}
				$propor = min(max($dst_w / $src_w, $dst_h / $src_h), 1);
				$dst_w = (int)round($src_w * $propor);
				$dst_h = (int)round($src_h * $propor);
				$x = ($width - $dst_w) / 2;
				$y = ($height - $dst_h) / 2;
			}
		}
		else
		{
			$proportion = min($proportion, 1);
			$height = $dst_h = $src_h * $proportion;
			$width  = $dst_w = $src_w * $proportion;
		}
	
		$src = $createfun($src_img);
		$dst = imagecreatetruecolor($width ? $width : $dst_w, $height ? $height : $dst_h);
		$white = imagecolorallocate($dst, 255, 255, 255);
		imagefill($dst, 0, 0, $white);
	
		if(function_exists('imagecopyresampled'))
		{
			imagecopyresampled($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
		}
		else
		{
			imagecopyresized($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
		}
		$otfunc($dst, $dst_img);
		imagedestroy($dst);
		imagedestroy($src);
		return true;
	}
	//获取文件后缀
	public function fileext($file)
	{
		return pathinfo($file, PATHINFO_EXTENSION);
	}
	
	//通过图片名获取sku
	public function getSkuByImage($image){
		$index = strrpos($image,'.');
		$fileinfo_arr = explode('-',substr($image,0,$index));
		$sku = $fileinfo_arr[0];
		return $sku;
	}
	/*
	 * 功能：获取某sku的图片列表
	 * $filepath，sku绝对路径
	 * $sku:SKU
	 * $first:0为取所有，1，获取第一张图
	 * $m:从第几张开始取
	 */
	public function getImageList($filepath,$sku,$first=0,$m=0){
		$imageList = array();
		if(!is_dir($filepath)){
			return $imageList;
		}
		$search_qty = $this->img_config['img_max_qty'];
		$types = explode(',',$this->img_config['img_allowed_ext']);
		$i = $m;
		while($i<$search_qty){
			if($i==0){
				$filename = $sku;
			}else{
				$filename = $sku.'-'.$i;
			}
			foreach($types as $type){
				$fullname = $filename.'.'.$type;
				$local_path = $filepath.'/'.$fullname;
				if(file_exists($local_path)){
					$local_path = str_replace(Yii::getPathOfAlias('webroot'),'',$local_path);
					if($first){
						//return $local_path;
						$imageList[$i] = $local_path;
						return $imageList;
					}else{
						$imageList[$filename] = $local_path;
					}
					break;
				}
			}
			$i++;
		}
		return $imageList;
	}
	
	//获取副图，一次一张
	public function getFtList($sku,$m=0){
		$ass_path = Yii::getPathOfAlias('webroot').$this->img_config['img_local_path'].$this->img_config['img_local_assistant_path'];
		$first = substr($sku,0,1);
		$second = substr($sku,1,1);
		$third = substr($sku,2,1);
		$four = substr($sku,3,1);
		$filepath = $ass_path.'/'.$first.'/'.$second.'/'.$third.'/'.$four;
		return $this->getImageList($filepath,$sku,1,$m);
	}
	//获取副图列表
	public function getFtLists($sku,$m=0){
		$ass_path = Yii::getPathOfAlias('webroot').$this->img_config['img_local_path'].$this->img_config['img_local_assistant_path'];
		$first = substr($sku,0,1);
		$second = substr($sku,1,1);
		$third = substr($sku,2,1);
		$four = substr($sku,3,1);
		$filepath = $ass_path.'/'.$first.'/'.$second.'/'.$third.'/'.$four;
		
		return $this->getImageList($filepath,$sku);//$this->getImageList($filepath,$sku,0,$m);
	}
	
	
	//获取主图列表
	public function getZtList($sku){
		$ass_path = Yii::getPathOfAlias('webroot').$this->img_config['img_local_path'].$this->img_config['img_local_main_path'];
		$first = substr($sku,0,1);
		$second = substr($sku,1,1);
		$third = substr($sku,2,1);
		$four = substr($sku,3,1);
		$filepath = $ass_path.'/'.$first.'/'.$second.'/'.$third.'/'.$four;
		
		return $this->getImageList($filepath,$sku);
	}
	//获取相应图片
	public function getImageByName($sku){
	
	}
	
	/*
	 * 根据产品信息获取图片
	* $data:产品信息,数组
	* * $getType:指定所需获取的数据类型，为1则为数组，否则为对象
	*/
	public function getImageByProductInfo($data,$getType=0){
		foreach($data as $key=>$val){
			$curSku = isset($getType) ? $val['sku'] : $val->sku;
			$data[$key]['img']= MHelper::getProductPicBySku($curSku,"thumb",$key+1);
		}
		return $data;
	}
	
	/**
	 * @desc 检查图片是否存在
	 * @param string $path
	 */
	public function checkImageExist($path){
		if( file_exists(Yii::getPathOfAlias('webroot').$path) ){
			return true;
		}
		return false;
	}
}