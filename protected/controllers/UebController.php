<?php
/** 
 * Base generic controller class
 * 
 * @package UEB.controllers
 * 
 * @author Bob <zunfengke@gmail.com>
 */

class UebController extends CController {
    /**
     * @var string the default layout for the controller view	
     */
    public $layout='main';

    /**
     * @var array context menu items. This property will be assigned to {@link CMenu::items}.
     */

    public $menu=array();
    /**
     * @var array the breadcrumbs of the current page. The value of this property will
     * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
     * for more details on how to specify this property.
     */
    public $breadcrumbs=array();
    
    public $orderField = null;
    
    public $orderDirection = 'Desc';
    
    
    public function successJson($data) {        
        $data['statusCode'] = 200;
        header("Content-Type:text/html; charset=utf-8");
        return json_encode($data);
    }
    
    public function failureJson($data) {
        $data['statusCode'] = 300;
        header("Content-Type:text/html; charset=utf-8");
        return json_encode($data);
    }     
    
    /**
     * rewirte render 
     * 
     * @param type $view
     * @param type $data
     * @param type $return
     */
    public function render($view, $data=null, $return = false){
        if (! empty($_REQUEST['orderField']) ) {
            $data['orderField'] = $_REQUEST['orderField'];
            $orderDirection = isset($_REQUEST['orderDirection']) ? $_REQUEST['orderDirection'] : 'DESC';
            $data['orderDirection'] = $orderDirection;
        }       
        parent::render($view, $data, $return);
    }
   
    /**
     * before action check access
     * 
     * @param string $action
     * @return boolean
     * @throws CHttpException
     */
    protected function beforeAction($action = null) {
        return true;
        if ( isset($this->module->id) ) {
        	//过滤非业务请求
            if ( $this->filterAccessAction()) { 
                return true;          
            };
            $auth = Yii::app()->authManager;
            $action = $this->module->id . '_' . strtolower($this->getId()) .'_' .  strtolower($this->getAction()->getId()); 
            //避免重复提交相同请求                 
            if ( stripos(Yii::app()->request->getRequestUri(), "?_=") !== false ) {
                $uri = explode("?_=", Yii::app()->request->getUrl()); 
                $uri[1] = substr($uri[1], 0, 13);
                $uniqueKey = 'submit_'.session_id().$uri[1];  
                       
                if (  $beginTime = Yii::app()->session->get($uniqueKey) ) {                 
                    $seconds = CHelper::diffSeconds($beginTime);
                    if ( $seconds > 5 ) {                   
                        Yii::app()->session->remove($uniqueKey);
                    } else {
                        throw new CHttpException(403, "Can't repeat submit.".$seconds);
                    }               
                }
                
                Yii::app()->session->add($uniqueKey, microtime());              
            } 
            
            $actionResource = 'resource_'.$action;
            //检查请求是否存在于rbac权限控制中
            $actionUrl = '/'. str_replace("_", "/", $action);
            $menuFlag = Menu::model()->exists("menu_url = '$actionUrl'");
            if (! AuthItem::model()->exists("name = '{$actionResource}'") && ! $menuFlag) {
            	$auth->createOperation($actionResource, $action);
            }
            $filterModules = Yii::app()->params['filter_modules'];
            
            if ( isset($this->module->id)) {               
                if ( $parentModule = $this->module->getParentModule() ) {
                    $filterModuleId = $parentModule->id;
                } else {
                    $filterModuleId = $this->module->id;
                }
            } else { 
                $filterModuleId = '';                
            }
           
            if ( (isset($this->module->id) && in_array( $filterModuleId, preg_split('/\s*,\s*/',trim($filterModules),-1,PREG_SPLIT_NO_EMPTY)))) {             
            	return true;
            } elseif (Yii::app()->user->isGuest) {
               	Yii::app()->user->returnUrl = Yii::app()->request->requestUri;
               
            	if( $parms = str_replace(strtolower($actionUrl),'',strtolower(Yii::app()->user->returnUrl)) ){
	               	if ( stripos($parms,'autorun/true')!==false || stripos($parms,'autorun/1')!==false ){
	               		return true;
	               	}
               	}
               	$this->redirect($this->createUrl('/site/login'));
            } else if ( UebModel::checkAccess($actionResource) || $menuFlag ) {
                return true;
            } else {               
                throw new CHttpException(403, Yii::t('system', 'You are not authorized to perform this action.'));
            }
        }     
        
        return true;
    }
    
    /**
     * after action operation
     * 
     * @param type $action
     */
    protected function afterAction($action) {
        parent::afterAction($action);
        if ( stripos(Yii::app()->request->getRequestUri(), "?_=") !== false ) {
            $uri = explode("?_=", Yii::app()->request->getUrl()); 
            $uri[1] = substr($uri[1], 0, 13);
            $uniqueKey = 'submit_'.session_id().$uri[1];
            
            if ( Yii::app()->session->get($uniqueKey) ) {         
                Yii::app()->session->remove($uniqueKey);
            }
            unset($_REQUEST['orderField']);
            CHelper::profilingTimeLog();
        }               
    }

    /**
     * filter check access action 
     */
    protected function filterAccessAction() {
        //filter modules
        //filter controllers
        if (in_array($this->id, array('msg','auto'))) {
            return true;
        }
        // filter actions    
        if (in_array($this->getAction()->getId(), array('sider'))) {
            return true;
        }
        return false;
    }
    
    /**
     * excel scheme column list
     */
    public function actionReportlist() {
    	$schemeId = Yii::app()->request->getParam('scheme_id');
    	$className = Yii::app()->request->getParam('className');
    	$ids = Yii::app()->request->getParam('id');
    	$field = Yii::app()->request->getParam('field', 'id');
    	$subTitle = Yii::app()->request->getParam('subTitle');
    	$params = array();
    	$model = UebModel::model($className);
    
    	//get the column type
    	$columnNameArr = UebModel::model('ExcelSchemeColumn')->getSchemeColumnDataBySchemeId($schemeId);
    	$list = $model->reportColumnGroup($columnNameArr);

    	if($list){
    		if($list['is_condition']){
		    	foreach ($list['is_condition'] as $key=>$val){
		    		//$condArr[] = $val['column_field'];
		    		if ($val['default_value'] != '') {
		    			$defaultValue = explode(',', $val['default_value']);
		    			if (count($defaultValue) > 1) {
		    				$conditionArr[] = "{$val['table_name']}.{$val['column_field']} IN (".implode(',', $defaultValue).")";
		    			}else {
		    				$conditionArr[] = "{$val['table_name']}.{$val['column_field']}='{$defaultValue[0]}'";
		    			}
		    		}
		    	}
    		}
    		//Pleae not delete	9.19
//     		if($list['is_group']){
// 		    	foreach ($list['is_group'] as $key=>$val){
// 		    		$groupArr[] = $val['column_field'];
// 		    	}
//     		}
			
    	}
    	count($conditionArr) && $conditions = implode(' AND ', $conditionArr);
    	
    	$timeFormat = ExcelSchemeColumn::_MONTH;
    	$group_num = 0;
    	if ($_REQUEST['is_group']) {
	    	foreach ($_REQUEST['is_group'] as $key=>$val){
	    		foreach ($val as $k=>$v){
	    			if (is_array($v)){
		    			if (isset($v[0]) && isset($v['day_type']) && !empty($v['day_type'])) {
		    				$timeFormat = $v['day_type'];
		    			}
	    			}
	    			$group_num++;
	    		}
	    	}	
    	}
    	
    	$queryColumnIds = array_keys($_REQUEST['is_value']);
    	$queryColumn = UebModel::model('ExcelSchemeColumn')->getShowColumnGroup($queryColumnIds,$schemeId,$timeFormat);
    	$total= 0;
    	$data = array();
    	if (isset($_REQUEST['ac'])){
	    	//$total	= UebModel::model('ExcelSchemeColumn')->getJoinDataBySchemeId($schemeId, $conditions,$className,true);
	    	$datas	= UebModel::model('ExcelSchemeColumn')->getJoinDataBySchemeId($schemeId, $conditions,$className,$queryColumn, false);
	    	$total = count($datas);
	    	
			foreach ($datas as $key=>$val){
				$val = array_values($val);
				if($group_num>1){
					$isMultiArr = true;
					$data['total'][$val[1]] += $val[2];
					$data[$val[0]][$val[1]] += $val[2];
				}else{
					$isMultiArr = false;
					$xAxis[] = $val[0];
// 					$m =0;
// 					foreach ($val as $k=>$v){
// 						if ($m > 0){
// 							$data[0][$k] += $val[$k];
// 						}else{
// 							$data[0][$k] = 'total';
// 						}
// 						$m++;
// 					}
				}
			}
// 			if($group_num<2){
// 				$datas = array_merge($data,$datas);
// 			}
		}

    	$params['model'] = $model;
    	$params['modelName'] = $this->getId();
		$params['subTitle'] = $subTitle;
    	$params['scheme_id'] = $schemeId;
    	$params['className'] = $className;
    	$params['list'] = $list;
    	$params['total'] = $total;
    	
    	if($group_num>1){
    	//if($isMultiArr){	
	  		$params['data'] = $data;
	    	$this->render('application.components.views.reportlist',$params);
    	}else{
    		//双y轴
    		$params['xAxis'] = $xAxis;
    		$params['data'] = $datas;
    		$this->render('application.components.views.reportlist_1',$params);
    	}
    }
    
    /**
     * export data
     */
    public function actionExport() {
        $schemeName = Yii::app()->request->getParam('schemeName');
        $className = Yii::app()->request->getParam('className');
        $ids = Yii::app()->request->getParam('id');
        $field = Yii::app()->request->getParam('field', 'id');
        
        try {
            //$schemeId = UebModel::model('ExcelSchemeColumn')->getIdBySchemeName($schemeName);
            $schemeId = $schemeName;//直接调方案名称时用，否则用上面的根据名称取
            $titles = UebModel::model('ExcelSchemeColumn')->getColumnTitlePairsBySchemeId($schemeId,'is_value='.ExcelSchemeColumn::IS_VALUE);
            $schemeInfo = UebModel::model('ExcelCustomScheme')->getAttributesById($schemeId);
            
            $columnNameArr = UebModel::model('ExcelSchemeColumn')->getSchemeColumnDataBySchemeId($schemeId);
            $list = UebModel::model('ExcelSchemeColumn')->reportColumnGroup($columnNameArr);
            if($list){
            	if($list['is_condition']){
            		foreach ($list['is_condition'] as $key=>$val){
            			if ($val['default_value'] != '') {
            				$defaultValue = explode(',', $val['default_value']);
            				if (count($defaultValue) > 1) {
            					$conditionArr[] = "{$val['table_name']}.{$val['column_field']} IN (".implode(',', $defaultValue).")";
            				}else {
            					$conditionArr[] = "{$val['table_name']}.{$val['column_field']}='{$defaultValue[0]}'";
            				}
            			}
            		}
            	}
            }

            if (! empty($ids) ) {
                $conditionArr[] = " $field IN($ids)";
            } else {
                //$conditionArr[] = Yii::app()->session->get($className .'_condition');	//有session条件得不到结果，这种情况后续确定后处理
            }
            count($conditionArr) && $conditions = implode(' AND ', $conditionArr);
            
            $data = UebModel::model($className)->getDataBySchemeId($schemeId, $conditions, '', true);
            foreach ($data as $key=>$val){
            	$data[$key] = array_values($val);
            	//if ($key>30) unset($data[$key]);
            }

            $excelObj = ObjectFactory::getObject('MyExcel');
            $fileName = time() .'-'. rand(1, 1000).'.xls';
            $filePath = ObjectFactory::getObject('HashFilePath')
                    ->setBaseFilePath()
                    ->setDirectoryLevel(3)
                    ->getFilePath($fileName);
            $excelObj->export_excel($titles, $data, $filePath, 100, 0);
            if( file_exists($filePath) ) {
                $host = Yii::app()->request->getHostInfo();
                $filePath = str_replace(Yii::getPathOfAlias('webroot'), $host, $filePath);
                $downloadFileModel = UebModel::model('DownloadFile');
                $downloadFileModel->add($schemeName.'_'.$schemeInfo['scheme_name'], $filePath);
                $navTabId = 'page'.$downloadFileModel->getIndexNavTabId();
                $link = CHtml::link(Yii::t('system', 'Download File'), 'javascript:void(0);', array(                
                    'rel' => $navTabId,
                    'forward' => '/systems/downloadfile/list',                                     
                ));
                $jsonData = array(
                    'message' => Yii::t('system', 'Create Excel Success'),     
                    'link'    => $link,
                );
                echo $this->successJson($jsonData);
            } else {
               throw new Exception('create excel failure');
            } 
        } catch (Exception $e) {echo $e->getMessage();
             $jsonData = array(
                'message' => Yii::t('system', 'Create Excel Failure'),
             );
            echo $this->failureJson($jsonData);
        }      
              
        Yii::app()->end();
    }

    /**
     * unique check
     */
    public function actionUnique() {        
      $val = Yii::app()->request->getParam('value');
      $className = Yii::app()->request->getParam('className');
      $attributeName = Yii::app()->request->getParam('attributeName');
      $exist = UebModel::model($className)->exists(" $attributeName = :attributeName ", array( ':attributeName' => $val));
      echo $exist;
      Yii::app()->end();
    }
    
    /**
     * exist check
     */
    public function actionExist() {       
      $val = Yii::app()->request->getParam('value');
      $className = Yii::app()->request->getParam('className');
      $attributeName = Yii::app()->request->getParam('attributeName');
      $exist = UebModel::model($className)->exists(" $attributeName = :attributeName ", array( ':attributeName' => $val));
      echo !$exist;
      Yii::app()->end();
    }
    
    /**
     * custom validate
     */
    public function actionValidate() {
       $model = Yii::app()->request->getParam('model');
       $fieldName = Yii::app()->request->getParam('fieldName');             
       $msg = UebModel::model($model)->customValidate($fieldName);   
       die($msg);
    }
    
    /**
     * $data : view data
     * $dataColumn:params
     * return table html
     * 
    */
    public function renderGridcell($data,$row,$dataColumn){
    	if (! isset($dataColumn->htmlOptions['type']) ) {
    		$dataColumn->htmlOptions['type'] = 'text';
    	}
    	$type = $dataColumn->htmlOptions['type'];
    	if (! isset($dataColumn->htmlOptions['name']) ) {
    		$dataColumn->htmlOptions['name'] = $dataColumn->name;
    	}
    	//=== START绑定点击事件 ======
    	$onclick = null;
    	if (isset($dataColumn->htmlOptions['click_event']) ) {
    		$onclick = $dataColumn->htmlOptions['click_event'];
    	}
    	//=== END绑定点击事件 =======
    	
    	$column = $dataColumn->name ;
    	$name = $dataColumn->htmlOptions['name'];
    	$str = '';
        $tdHeight = '';
    	if(isset($data->detail) && !empty($data->detail)){    		
    		$num = count($data->detail)-1;
    		$str .= '<table cellpadding="0" cellspacing="0" width="100%" border=0 class="innerTable">';
    		foreach ($data->detail as $k=>$v){
    			//=== START: 2015.11.05 增加disabled属性控制 ======
    			$disabled = "";
    			if (isset($dataColumn->htmlOptions['disabled']) ) {
    				$disabled = eval($dataColumn->htmlOptions['disabled']) ? '':'disabled';
    			}
    			//=== END增加disabled属性控制 ====
    			$style = '';
    			if($num > $k) $style = 'border-bottom:1px dashed #70b3fa;overflow:hidden;white-space:nowrap;';
                if(isset($v['td_height'])){
                    $tdHeight = $v['td_height'];
                }

    			$str .= '<tr>';
    			$str .= '<td style="border:0;height:24px;'.$style.$tdHeight.'">';
    			
    			switch($type){
    				case 'checkbox':
    					
    					$str .= "<input type='checkbox' id='".$name."_".$v[$column]."' name='".$name."[]' value=$v[$column] ".($onclick?"onclick='{$onclick}'":'')." {$disabled}>";
    					break;
    				case 'text':
    					$str .= $v[$column];
    					break;
    				default:
    					break;
    			}
    			$str .= '</td>';
    			$str .= '</tr>';
    		}
    		$str .= '</table>';
    	}
    	return $str;
		/**取消用render()获取
    	//Yii::app()->clientscript->scriptMap['jquery.js'] = false;
    	$this->render('application.components.views._gridcell',array(
    		'data' => $data->detail,
    		'column' => $dataColumn->name,
    		'type'=> $dataColumn->htmlOptions['type'],
    		'name'=> $dataColumn->htmlOptions['name']
    		)
    	);
    	**/
    }
    /**
     * generate barcode
     * @param
     * <img src="<?php echo Yii::app()->request->hostInfo;?>
     * /modules/modelName/barcode/barcode/code128/text/aaaaa/o/1/t/30/r/1/f1/-1/f2/8/a1//a2/C/a3/" align="absmiddle">
     */
    public function actionBarcode(){
    	$code = Yii::app()->request->getParam('code');
    	$code  = $code=='' ? 'code128' : $code;
    	$text = Yii::app()->request->getParam('text');
    	$o = Yii::app()->request->getParam('o');
    	$t = Yii::app()->request->getParam('t');
    	$r = Yii::app()->request->getParam('r');
    	$f1 = Yii::app()->request->getParam('f1');
    	$f2 = Yii::app()->request->getParam('f2');
    	$a1 = Yii::app()->request->getParam('a1');
    	$a2 = Yii::app()->request->getParam('a2');
    	$a3 = Yii::app()->request->getParam('a3');
    	$barCode = ObjectFactory::getObject('BarCode');
    	$barCode->createBarCode($text,$code,$o,$t,$r,$f1,$f2,$a1,$a2,$a3);
    }
    /**
     * Get the Model class
     * @return string
     */
    public function getModelClass(){
    	return str_replace('Controller', "", get_class($this));
    }

    public function print_r($data){
    	echo "<pre>";
		print_r($data);
    	echo "</pre>";
    }


    /**
     * @desc 返回赋予特定状态值的json
     * @param  array $data
     * @return json
     */
    public function getStatusCodeJson($data) {
        header("Content-Type:text/html; charset=utf-8");
        return json_encode($data);
    }


    /**
     * @desc 输出csv格式的表格
     * @param  string $filename  导出生成的文件名
     * @param  string $data
     * @return json
     */
    protected function export_csv($filename,$data)   
    {   
        header("Content-type:text/csv");   
        header("Content-Disposition:attachment;filename=".$filename);   
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');   
        header('Expires:0');   
        header('Pragma:public');   
        echo mb_convert_encoding($data,'gb2312','utf-8');   
    }
    
}