<?php
	
/**
 * @package Pda.models
 *
 * @author Gordon
 */
class PdaClientModel extends UebModel {
	/**
	 * PDA Client Url Param
	 * @var string
	 */
	const URL_PARAM = 'client_type';	//type冲突，更换 9.4
	const URL_PARAM_VALUE = 'pda';
	
	const URL_UID_PARAM = 'uid';
	const URL_PASSWORD_PARAM = 'pw';
	
	public $submit = false;
	
	//pda nav title
	const INDEX					= 'index';
	const C_PDA_PICK			= 'pdapick_';
	const A_TYPE_LIST			= 'typelist';
	const A_MISSION_LIST		= 'missionlist';
	const A_MISSION_DETAIL		= 'missiondetail';
	const A_MISSION_LIST_CONTENT = 'missionlistcontent';
	
	public function getPdaUrlParam(){
		return self::URL_PARAM;
	}
	
	public function getPdaUrlParamValue(){
		return self::URL_PARAM_VALUE;
	}
	
	public function getPdaTopNavTitle() {
		return $titleArr = array(
				self::C_PDA_PICK.self::INDEX	=> Yii::t('pda', 'Pick mission'),
				self::C_PDA_PICK.self::A_TYPE_LIST => array('print_num'),
				self::C_PDA_PICK.self::A_MISSION_LIST => array('label_type'),
				self::C_PDA_PICK.self::A_MISSION_DETAIL => array('mission_id'),
		);
	}
	
	/**
	 * Create The Link Of Pda Client 
	 * @param string $url
	 * @return string
	 */
	public function createPdaClientLink($url){
		$uid = Yii::app()->request->getParam(self::URL_UID_PARAM);
		$password = Yii::app()->request->getParam(self::URL_PASSWORD_PARAM);
		return $url.'/'.self::URL_PARAM.'/'.self::URL_PARAM_VALUE.'?'.self::URL_UID_PARAM.'='.$uid.'&'.self::URL_PASSWORD_PARAM.'='.$password;		
	}
	
	/**
	 * Create top navigation bar
	 * @param	string	$url
	 * @return	string
	 * @since	tan 9.5
	 */
	public function createPdaTopNavBar($url='') {
		if ($url == '') {
			$url = $_SERVER['REQUEST_URI'];
			$controller = strtolower($this->getId());
			$action = strtolower($this->getAction()->getId());
			$key = $controller.'_'.$action;
		}else {
			$ret = parse_url($url);//extend
			$key = '';
			return '';
		}
		
		$topNavTitleArr = self::getPdaTopNavTitle();
		if (is_array($topNavTitleArr[$key])) {
			$topNavTitle = Yii::app()->request->getParam($topNavTitleArr[$key][0]);
			if ($topNavTitleArr[$key][0] == 'label_type') {
				$logisticsLableInfo = UebModel::model('LogisticsLabel')->getAttributesById($topNavTitle);
				$topNavTitle = $logisticsLableInfo['label_name'];
			}
		}else {
			$topNavTitle = $topNavTitleArr[$key];
		}
		
		//Yii::app()->session[$controller] = array();
		$topNavBarArr = Yii::app()->session[$controller];
		isset($topNavTitleArr[$key]) && $topNavBarArr[$key] = array('title'=>$topNavTitle, 'url'=>$url);
		$topNavBarArr2 = array();
		foreach ($topNavBarArr as $k => $v) {
			$topNavBarArr2[$k] = $v;
			if ($key == $k) {
				break;
			}
		}
		$topNavBarArr = $topNavBarArr2;
		Yii::app()->session[$controller] = $topNavBarArr;
		
		if ($key == self::C_PDA_PICK.self::A_MISSION_LIST_CONTENT) {
			Yii::app()->session[$key] = $url;
		}

		$navBarHtml = CHtml::link(Yii::t('pda', 'Home Page'), Yii::app()->baseUrl.PdaClientModel::createPdaClientLink('/pdaclient/index'));
		foreach ($topNavBarArr as $key => $val) {
			$navBarHtml .= (empty($navBarHtml) ? '' : '--') . CHtml::link($val['title'], $val['url']);
		}
		return $navBarHtml;
	}
	
}