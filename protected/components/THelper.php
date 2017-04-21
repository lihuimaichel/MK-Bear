<?php

/**
 * Tpl Helper Class
 * @package Application.components
 * @auther Bob <zunfengke@gmail.com>
 */
class THelper {

    /**
     * get list template
     * 
     * @return string
     */
    public static function getListTpl() {
    	$tpl = '';
    	$tpl .= '{pageHeader}';
        $tpl .= '<div class="pageContent">
        	{helpbar}
            <div class="panelBar">
                <ul class="toolBar">                      
                    {toolBar}
                    {exportBar}
                    {orderBar}
                </ul>
            </div>
            {adjustColumnsBar}
            {printList}
            {pager}               
        </div>';               
        return $tpl;
    }
    
    /**
     * get pda list template
     * 
     * @return string
     */
    public static function getPdaListTpl() {
    	$tpl = '';
    	$tpl .= '{pageHeader}';
        $tpl .= '<div class="pageContent" style="border-left:1px #B8D0D6 solid;border-right:1px #B8D0D6 solid">
            <div class="panelBar">       
                <ul class="toolBar">
                    {toolBar}
                </ul>
            </div>
            {items}
            {pager}
        </div>';               
            
        return $tpl;
    }
}

?>
