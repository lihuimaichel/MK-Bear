<?php
/**
 * Html is a static class that provides a collection of helper methods for creating HTML views .
 *
 * @author Bob <zunfengke@gmail.com>
 * @package application.components
 */
class Html extends CHtml {
    
    /**
     * list button
     * 
     * @param type $name
     * @param type $select
     * @param type $data
     * @param type $htmlOptions
     * @return type
     */
    public static function listButton($label, $name, $select, $data, $htmlOptions=array()) {      
		$htmlOptions['name']=$name;

		if(!isset($htmlOptions['id']))
			$htmlOptions['id']=self::getIdByName($name);
		elseif($htmlOptions['id']===false)
			unset($htmlOptions['id']);	      
		$options="\n".self::listButtonOptions($select, $data);	               
        $htmlOptions['id'] =  isset($htmlOptions['id']) ? self::ID_PREFIX.$htmlOptions['id'] : false;
        $hidden =  self::hiddenField($name, $select);
		$html = self::openTag('div', $htmlOptions);
        $html .= CHtml::label($label, $name) . '：';
        $html .= $options;
        $html .= $hidden;
        $html .= self::closeTag('div');
		
        return $html;
	}
    
    /**
     * @return string the generated list buttons options
     * 
     * @param type $selection
     * @param type $listData
     * @param type $htmlOptions
     * @return string
     */
    public static function listButtonOptions($select, $listData)
	{
        $result = null;
        $htmlOptions = array();
		foreach ($listData as $key => $val) {
			$htmlOptions['id'] = "toggle_btn_$key";
			if ( $key == $select ) {
				$htmlOptions['class'] = 'toggle_btn_down';			
			} else {
				$htmlOptions['class'] = 'toggle_btn';
			}
            $content = self::link($val, 'javascript:void(0)', $htmlOptions);
            $result .= self::tag('span', array( 'class' => 'dbl mb5' , 'id' => $key), $content);
        }

		return $result;
	}
    
	/**
     * @return string the lookup html
     * 
     * @param array $lookup:两个参数:弹框提示信息、获取数据的url
     * 
     * @return string
     */
    public static function lookup($lookup)
	{
        $result = null;
        return isset($lookup[0]) && isset($lookup[1]) 
        		? '<a style="color:red;" href="javascript:;" onclick="clearHidden();">清除</a>'.CHtml::link($lookup[0],$lookup[1],array('class'=>'btnLook','lookupGroup'=>'')) : '';
    }

}

?>
