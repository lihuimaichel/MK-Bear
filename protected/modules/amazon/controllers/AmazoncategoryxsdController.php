<?php
/**
 * @desc Amazon上传分类控制器
 * 通过官方提供下载的28个XSD分类文件：https://sellercentral.amazon.com/gp/help/help.html?ie=UTF8&itemID=1611&ref_=ag_1611_cont_help&=undefined&language=zh_CN&languageSwitched=1
 * 提取有效数据入库，作用于amazon刊登分类类型选择
 * @author liz
 * @since 2016-07-28
 *
 */
class AmazoncategoryxsdController extends UebController{
    /**
     * @desc 访问过滤配置
     * @see CController::accessRules()
     */
    public function accessRules() {
        return array(
			array(
				'allow',
				'users' => array('*'),
				'actions' => array('uploadproductcategorybyxsd','simpleitembyxsd')
			),
		);
    }

	/**
	 * @desc 通过顶级分类ID获取分类下的分类类型列表
	 * @link /amazon/amazoncategoryxsd/getproducttypelist/topid/9
	 */
	public function actionGetProductTypeList(){
		$topid = Yii::app()->request->getParam('topid');
		if (empty($topid) || (int)$topid == 0) exit;

		$list = array();
		$list = AmazonCategoryXSD::model()->getProductTypeListByTopid($topid);
		echo json_encode($list);
		exit;
	}    

	/**
	 * 上传各分类的分类信息
	 * @link /amazon/amazoncategoryxsd/uploadproductcategorybyxsd
	 */
	public function actionUploadProductCategoryByXSD(){
		$file_list = array();
	    $filePath = 'uploads/amazon/xsd/';

	    //获取目录下所有xsd文件名
	    if(is_dir($filePath)){  
	        $dp = dir($filePath);  
	        while ($file = $dp ->read()){
				$extendname = end(explode(".", $file));
				$filename = basename($file,'.'.$extendname);
	            if($file !="." && $file !=".." && $extendname == "xsd"){  
	                $file_list[] = $filename;
	            }
	        }  
	        $dp ->close();  
	    }else{
		    echo '请把官方XSD分类文件下载到"uploads/amazon/xsd/"路径下执行。';
		    Yii::app()->end();	
	    }
	    // MHelper::printvar($file_list);
	    if ($file_list){
	    	foreach($file_list as $key => $val){
	    		// if ($key == 12){
		    		$this->actionSimpleItemByXSD($val);
		    	// }
	    	}
	    }	
	    echo 'Finish';
	    Yii::app()->end();
	}	

	/**
	 * 通过文件名提取单个上传分类的相关数据
	 * @param  string $filename 文件名（不带后缀）
	 * @link /amazon/amazonproductadd/simpleitembyxsd/filename/beauty
	 * @return 
	 */
	public function actionSimpleItemByXSD($filename = ''){
		if(!$filename) $filename = Yii::app()->request->getParam('filename');
		if(!$filename) return;

		$filePath = 'uploads/amazon/xsd/';
	    $f_xsd = $filePath.$filename.'.xsd';
	    $f_xml = $filePath.$filename.'.xml';

		$doc = new DOMDocument(); 
		$doc->preserveWhiteSpace = true;
		$doc->load($f_xsd);
		$doc->save($f_xml);
		$xmlfile = file_get_contents($f_xml);
		$parseObj = str_replace($doc->lastChild->prefix.':',"",$xmlfile);
		$ob= simplexml_load_string($parseObj);
		$json  = json_encode($ob);
		$all_data = json_decode($json, true);
		// MHelper::printvar($all_data);
		$top_list = array();
		$category_name = '';
		$item = $this->xsd_element($all_data);
		// MHelper::printvar($item);
		if ($item){
			//分类名称
			$category_name = $this->xsd_category_attr_name($item);			
			if (!$category_name) {
				$category_name = $this->xsd_category_attr_name($item[0]);
				$item_1 = $this->xsd_com_seq_element($item[0]);
			}else{
				$item_1 = $this->xsd_com_seq_element($item);
			}
			$top_list['category_name'] = $category_name;
			$list = array();
			// MHelper::printvar($item_1);

			//提取分类类型列表和顶级分类类型的变体
			if ($item_1){
				$producttype_list = array();				
				foreach($item_1 as $element){								
					$element_name = $this->xsd_category_attr_name($element);
					// echo $element_name;MHelper::printvar($element);				
					//分类Shoes用的是ClothingType<=>ProductType
					if ($element_name == 'ProductType' || $element_name == 'ClothingType'){		
						$element_1 = isset($element['complexType']['choice']['element']) ? $element['complexType']['choice']['element'] : '';	
						if (!$element_1) $element_1 = $this->xsd_sim_res_enumeration($element);	//XSD分类：Sports的特殊情况
						if ($element_1){
							if (count($element_1) == 1){
								$element_1_foreach[0] = $element_1;
							}else{
								$element_1_foreach = $element_1;
							}
							// MHelper::printvar($element_1_foreach);	
							foreach($element_1_foreach as $val){
								$producttype_name = '';
								$producttype_name = $this->xsd_category_attr_name($val);		
								if($producttype_name) $producttype_list[] = $producttype_name;
							}						
						}
					}
				}					
				//有些分类没有ProductType，例如：ClothingAccessories
				if(!$producttype_list) $producttype_list[] = $category_name;
				if ($producttype_list) $list['producttype'] = $producttype_list;							
			}
			// MHelper::printvar($list);

			//提取每项分类类型里的相关数据
			if (isset($list['producttype']) && count($list['producttype']) > 0){
				$product_type_list = $list['producttype'];
				if ($product_type_list){
					$inner_list = array();
					foreach($item as $info){
						// MHelper::printvar($info);
						$element_list = array();
						$inner_category_name = $this->xsd_category_attr_name($info);
						// if ($inner_category_name == 'SeedsAndPlants'){
						if ($inner_category_name){
							//属于分类类型列表的和顶级分类的才提取，其它暂时不提取
							if (in_array($inner_category_name,$product_type_list) || $inner_category_name == $category_name){									
								$data = $this->xsd_com_seq_element($info);
								// MHelper::printvar($data);
								if ($data){
									$variation_list = array();	//变体列表数据
									$common_list    = array();	//通用数据											
									$i = 0;
									$n = 1000;	//不限制
									if (count($data) > 1){								
										foreach($data as $last_list){
											$var_name = $this->xsd_category_attr_name($last_list);
											//如果有则提取变体数据
											if ($var_name == 'VariationData' || $var_name == 'VariationTheme'){
												//CameraPhoto分类变体结构有异，只有VariationTheme
												$temp_list = array();	
												if ($var_name == 'VariationData'){
													$temp_list = $this->xsd_variation_list($last_list);
												}else{
													$temp_list = $this->xsd_variation_list($last_list,false);
												}			
												$variation_list = $temp_list;											
											}else{
												//排除以下两项
												if($var_name == 'ProductType' || $var_name == 'ProductSubtype') continue;

												//特殊分类：ClothingAccessories，结构名：ClassificationData
												if($var_name == 'ClassificationData'){
													$k = $i;
													$m = $n;
													$classification_data = $this->xsd_com_seq_element($last_list);
													if ($classification_data){
														foreach($classification_data as $c){
															if ($k < $m){
																$common_list_temp = array();
																$common_list_temp = $this->xsd_common_list($c);
																if($common_list_temp) $common_list[] = $common_list_temp;
															}else{
																break;
															}
															$k++;
														}
													}
													unset($classification_data);
													// MHelper::printvar($common_list);
												}else{
													//提取其它通用数据
													if ($i < $n){
														$common_list_temp = array();
														$common_list_temp = $this->xsd_common_list($last_list);
														if($common_list_temp) $common_list[] = $common_list_temp;
													}else{
														continue;	//不能用break，必须遍历所有，以便提取到有可能靠后的变体数据
													}
													$i++;
												}
	
/*												if ($var_name == 'USDAHardinessZone'){
													// MHelper::printvar($last_list);exit;
													$common_list[$var_name] = $this->xsd_common_list($last_list);
													MHelper::printvar($common_list);
													echo 'ttttt';exit;
												}	*/									
											}
										}
									}else{
										$var_name = $this->xsd_category_attr_name($data);
										//如果有则提取变体数据
										if ($var_name == 'VariationData'){
											$temp_list = array();						
											$temp_list = $this->xsd_variation_list($data);			
											$variation_list = $temp_list;											
										}else{
											//提取其它通用数据
											if($var_name) $common_list[] = $var_name;
										}
									}	
									$element_list['variation'] = $variation_list;
									$element_list['common'] = $common_list;
								}
								// MHelper::printvar($element_list);exit;
							}else{
								continue;
							}
						}
						if($element_list) $inner_list[$inner_category_name] = $element_list;
					}	
					$top_list['category_list'] = ($inner_list);

					//XSD分类Sports、SportsMemorabilia、ToysBaby、Shoes的ProductType特殊结构
					if (($top_list['category_name'] == 'Sports') || ($top_list['category_name'] == 'SportsMemorabilia') || ($top_list['category_name'] == 'ToysBaby') || ($top_list['category_name'] == 'Shoes')){						
						foreach($product_type_list as $pt){
							if (($pt != 'Sports') && ($pt != 'SportsMemorabilia') && ($pt != 'ToysBaby') && ($pt != 'Shoes')){ 
								$top_list['category_list'][$pt] = array();
							}
						}						

						// MHelper::printvar($top_list['category_list']);
					}	

					//特殊分类：Miscellaneous-杂项分类，分类类型以MiscType的子项组成
					if ($top_list['category_name'] == 'Miscellaneous'){
						$misc_list = $this->xsd_sim_res_enumeration($all_data);
						foreach($misc_list as $misc_info){
							$misc_name = $this->xsd_category_attr_name($misc_info);
							if($misc_name) $top_list['category_list'][$misc_name] = array();
						}
					}				
				}
			}
			// MHelper::printvar($top_list);
			if ($top_list){
				AmazonCategoryXSD::model()->saveAmazonCategoryXSD($top_list);
				return true;
			}
		}
	}	

	/**
	 * XSD：提取变体列表数据
	 * @param array $element
	 * @param int $have_variation_data：特殊XSD变体结构(XSD分类：CameraPhoto)，没有VariationData父级结构
	 */
	private function xsd_variation_list($element = array(),$have_variation_data = true){
		if (!$element) return;
		if (!is_array($element)) return 'NO Array!';
		$variation_list = array();

		if ($have_variation_data){
			$element_1 = $this->xsd_com_seq_element($element);						
			foreach($element_1 as $val){
				$temp = $this->xsd_category_attr_name($val);
				if ($temp == 'VariationTheme'){
					$element_1_1 = $this->xsd_sim_res_enumeration($val);
					if ($element_1_1){
						foreach($element_1_1 as $i){
							$temp_attr_name = $this->xsd_category_attr_name($i);
							if($temp_attr_name) $variation_list[] = $temp_attr_name;
						}
					}
				}
			}
		}else{
			$element_1_1 = $this->xsd_sim_res_enumeration($element);
			// MHelper::printvar($element_1_1);
			if ($element_1_1){
				foreach($element_1_1 as $i){
					$temp_attr_name = $this->xsd_category_attr_name($i);
					if($temp_attr_name) $variation_list[] = $temp_attr_name;
				}
			}
		}
		return $variation_list;
	}


	/**
	 * XSD：提取通用数据
	 * @param array $val
	 */
	private function xsd_common_list($val){
		if (!$val) return;
		$attr_list = array();
		$limit_list = array();
		$attr_type = '';

		$attr_name = $this->xsd_category_attr_name($val);
		$attr_list['name'] = $attr_name;

		$attr_type = $this->xsd_category_attr_type($val);
		

		//字段类型限制方式，并存在限制的数据范围
		if (!$attr_type){				
			$attr_limit_type = $this->xsd_sim_res_attr_base($val);
			if ($attr_limit_type){
				if ($attr_limit_type == 'positiveInteger'){
					$limit_list['min'] = $this->xsd_sim_res_min($val);
					$limit_list['max'] = $this->xsd_sim_res_max($val);
				}else{
					$temp_list = $this->xsd_sim_res_enumeration($val);
					if ($temp_list){
						foreach($temp_list as $value){
							if ($value){
								$attr_name = '';
								$attr_name = $this->xsd_category_attr_name($value);
								if($attr_name) $limit_list[] = $attr_name;
							}				
						}
					}
				}
				$attr_type = $attr_limit_type;
			}
		}
		
		$attr_list['data_type'] = $attr_type;		
		$attr_list['limit_value'] = $limit_list;

		//针对Miscellaneous分类
		if ($attr_name == 'ProductCategory' || $attr_name == 'ProductSubcategory'){
			return;
		}
		return $attr_list;
	}	

	/**
	 * XSD：上传分类路径
	 * @param array $ret
	 */
	private function xsd_element($ret = array()){
		if (!$ret) return;
		if (!is_array($ret)) return 'No Array!';
		// MHelper::printvar($ret);
		$element = array();
		$temp = array();
		$temp = isset($ret['element']) ? $ret['element'] : '';
		if ($temp){
			if (!isset($temp[0])){
				$element[0] = $temp;
			}else{
				$element = $temp;
			}
		}
		return $element;
	}

	/**
	 * XSD：上传分类路径
	 * @param array $ret
	 */
	private function xsd_com_seq_element($ret = array()){
		if (!$ret) return;
		if (!is_array($ret)) return 'No Array!';
		// MHelper::printvar($ret);
		$element = array();
		$temp = array();
		$temp = isset($ret['complexType']['sequence']['element']) ? $ret['complexType']['sequence']['element'] : '';
		if ($temp){
			if (!isset($temp[0])){
				$element[0] = $temp;
			}else{
				$element = $temp;
			}
		}
		return $element;
	}

	/**
	 * XSD：上传分类路径
	 * @param array $ret
	 */
	private function xsd_sim_res_enumeration($ret = array()){
		if (!$ret) return;
		if (!is_array($ret)) return 'No Array!';

		$element = array();
		$temp = array();
		$temp = isset($ret['simpleType']['restriction']['enumeration']) ? $ret['simpleType']['restriction']['enumeration'] : '';
		if ($temp){
			if (!isset($temp[0])){
				$element[0] = $temp;
			}else{
				$element = $temp;
			}
		}else{
			//针对Miscellaneous分类
			$temp = isset($ret['simpleType'][0]['restriction']['enumeration']) ? $ret['simpleType'][0]['restriction']['enumeration'] : '';
			if($temp) $element = $temp;
		}
		return $element;		
	}	

	/**
	 * XSD：数据类型(限制输入数据)
	 * @param array $ret
	 */
	private function xsd_sim_res_attr_base($ret = array()){
		if (!$ret) return;
		if (!is_array($ret)) return 'No Array!';

		return isset($ret['simpleType']['restriction']['@attributes']['base']) ? $ret['simpleType']['restriction']['@attributes']['base'] : '';
	}	

	/**
	 * XSD: 数据类型
	 */

	/**
	 * XSD：数据类型（通用）
	 * @param array $ret
	 */
	private function xsd_category_attr_type($ret = array()){
		if (!$ret) return;
		if (!is_array($ret)) return 'No Array!';

		return isset($ret['@attributes']['type']) ? $ret['@attributes']['type'] : '';
	}

	/**
	 * XSD：数据限制（最小）
	 * @param array $ret
	 */
	private function xsd_sim_res_min($ret = array()){
		if (!$ret) return;
		if (!is_array($ret)) return 'No Array!';

		return isset($ret['simpleType']['restriction']['minInclusive']['@attributes']['value']) ? $ret['simpleType']['restriction']['minInclusive']['@attributes']['value'] : '';
	}	

	/**
	 * XSD：数据限制（最大）
	 * @param array $ret
	 */
	private function xsd_sim_res_max($ret = array()){
		if (!$ret) return;
		if (!is_array($ret)) return 'No Array!';

		return isset($ret['simpleType']['restriction']['maxInclusive']['@attributes']['value']) ? $ret['simpleType']['restriction']['maxInclusive']['@attributes']['value'] : '';
	}

	/**
	 * XSD：上传分类标题
	 * @param array $val
	 * @param string $type 指定路径名称
	 */
	private function xsd_category_attr_name($val = '',$type = ''){
		if (!$val) return;
		if (!is_array($val)) return 'No Array!';

		if ($type){
			if ($type == 'name'){
				if(isset($val['@attributes']['name'])) return $val['@attributes']['name'];	
			}
			if ($type == 'ref'){
				if(isset($val['@attributes']['ref'])) return $val['@attributes']['ref'];	
			}
			if ($type == 'value'){
				if(isset($val['@attributes']['value'])) return $val['@attributes']['value'];	
			}			
		}else{
			if(isset($val['@attributes']['name'])) return $val['@attributes']['name'];
			if(isset($val['@attributes']['ref'])) return $val['@attributes']['ref'];
			if(isset($val['@attributes']['value'])) return $val['@attributes']['value'];
		}
		return;
	}	




}