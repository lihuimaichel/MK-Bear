<?php

/**
 * ULinkPager class file.
 *
 * @author Bob <zunfengke@gmail.com> 
 * @package application.components
 */
class ULinkPager extends CBasePager {  
    
    /**
     * @var array HTML attributes for the pager container tag.
     */
    public $htmlOptions = array();
    
    public $target = null;
    
    public $targetType = null;
    
    public $header = null;
    
    public $footer = null;
    
    public $cssFile = null;

    /**
     * Initializes the pager by setting some default property values.
     */
    public function init() {               
        if (!isset($this->htmlOptions['id']))
            $this->htmlOptions['id'] = $this->getId();
        if (!isset($this->htmlOptions['class']))
            $this->htmlOptions['class'] = 'panelBar bottompanelBar';
        
        $this->target = Yii::app()->request->getParam('target', null);
    }

    /**
     * Executes the widget.
     * This overrides the parent implementation by displaying the generated page buttons.
     */
    public function run() {
        //$this->registerClientScript();
        $pages = $this->createPages();
        $pagination = $this->createPagination();      
        if (empty($pages))
            return;
        echo $this->header;
        echo CHtml::tag('div', $this->htmlOptions, $pages. $pagination);
        echo $this->footer;
    }
    
    public function createPages() {
        $pages = '<div class="pages">';
        $pages .= '<span>'. Yii::t('system', 'Show').'</span>';
        $pages .= '<select class="combox" name="numPerPage"'; 
        if ( isset($this->target) ) {
            if ( $this->target == 'dialog' ) {
                $pages .= 'onchange="dwzPageBreak({targetType: \'dialog\', numPerPage: \'20\'})"';
            } else {
                $pages .= "onchange=\"navTabPageBreak({numPerPage: this.value}, '{$this->target}')\"";
            }
        } else {
            $pages .= 'onchange = "navTabPageBreak({numPerPage: this.value})"';
        } 
        /**updated by ethan 2014.08.18,解决明明每页100条，底下却显示20条/页
        $numPerPage = Yii::app()->request->getParam('numPerPage', Yii::app()->params['per_page_num']);
        */
        $numPerPage = isset($_REQUEST['numPerPage']) ? $_REQUEST['numPerPage'] : Yii::app()->params['per_page_num'];
        $pages .= ' >';
        foreach ( array(20,30, 50, 100, 200,300,1000,2000) as $val ) {
            $pages .= '<option value="'. $val .'" ';
            if ( $val == $numPerPage ) {
                $pages .= 'selected = selected';
            }
            $pages .= ' >'.$val.'</option>';
        }              
        $pages .= '</select>';
        $pages .= '<span>'. Yii::t('system', 'Item'). ','.Yii::t('system', 'Total');
        $pages .= $this->getItemCount(). Yii::t('system', 'Item').'</span>';
	    $pages .= '<span>&nbsp;&nbsp;&nbsp;&nbsp; '. Yii::t('system', 'Execution time').'：&nbsp;&nbsp;<font color="red">'. CHelper::profilingTime().'(S)</font></span>';
        
        $pages .= ' </div>'; 
        
        return $pages;
    }

    public function createPagination() {
        $pagination  = '<div class="pagination" ';
        if ( isset($this->target) ) {
            if ( $this->target == 'dialog' ) {
                $pagination .= 'targetType="dialog"';
            } else {
                $pagination .= "rel = '{$this->target}'";
            }
            
        } 
        if ( isset($this->targetType) ) {
             $pagination .= " targetType = '{$this->targetType}'";
        }
        $pagination .= 'totalCount="'. $this->getItemCount() . '" numPerPage="';
        $pagination .= $this->getPageSize() . '" pageNumShown="10" currentPage="';
        $pagination .= $this->getCurrentPage() + 1 . '">';
        $pagination .= '</div>';
        
        return $pagination; 
    }  

    /**
     * Registers the needed client scripts (mainly CSS file).
     */
    public function registerClientScript() {
        if ($this->cssFile !== false)
            self::registerCssFile($this->cssFile);
    }

    /**
     * Registers the needed CSS file.
     * @param string $url the CSS URL. If null, a default CSS URL will be used.
     */
    public static function registerCssFile($url = null) {
        if ($url === null)
            $url = CHtml::asset(Yii::getPathOfAlias('system.web.widgets.pagers.pager') . '.css');
        Yii::app()->getClientScript()->registerCssFile($url);
    }

}
