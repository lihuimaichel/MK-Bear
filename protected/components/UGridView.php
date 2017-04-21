<?php
/**
 * UGridView class file.
 *
 * @author Bob <zunfengke@gmail.com>
 */

Yii::import('zii.widgets.CBaseListView');
Yii::import('zii.widgets.grid.CDataColumn');
Yii::import('zii.widgets.grid.CLinkColumn');
Yii::import('zii.widgets.grid.CButtonColumn');
Yii::import('zii.widgets.grid.CCheckBoxColumn');

/**
 * CGridView displays a list of data items in terms of a table.
 *
 * Each row of the table represents the data of a single data item, and a column usually represents
 * an attribute of the item (some columns may correspond to complex expression of attributes or static text).
 *
 * CGridView supports both sorting and pagination of the data items. The sorting
 * and pagination can be done in AJAX mode or normal page request. A benefit of using CGridView is that
 * when the user browser disables JavaScript, the sorting and pagination automatically degenerate
 * to normal page requests and are still functioning as expected.
 *
 * CGridView should be used together with a {@link IDataProvider data provider}, preferrably a
 * {@link CActiveDataProvider}.
 *
 * The minimal code needed to use CGridView is as follows:
 *
 * <pre>
 * $dataProvider=new CActiveDataProvider('Post');
 *
 * $this->widget('zii.widgets.grid.CGridView', array(
 *     'dataProvider'=>$dataProvider,
 * ));
 * </pre>
 *
 * The above code first creates a data provider for the <code>Post</code> ActiveRecord class.
 * It then uses CGridView to display every attribute in every <code>Post</code> instance.
 * The displayed table is equiped with sorting and pagination functionality.
 *
 * In order to selectively display attributes with different formats, we may configure the
 * {@link CGridView::columns} property. For example, we may specify only the <code>title</code>
 * and <code>create_time</code> attributes to be displayed, and the <code>create_time</code>
 * should be properly formatted to show as a time. We may also display the attributes of the related
 * objects using the dot-syntax as shown below:
 *
 * <pre>
 * $this->widget('zii.widgets.grid.CGridView', array(
 *     'dataProvider'=>$dataProvider,
 *     'columns'=>array(
 *         'title',          // display the 'title' attribute
 *         'category.name',  // display the 'name' attribute of the 'category' relation
 *         'content:html',   // display the 'content' attribute as purified HTML
 *         array(            // display 'create_time' using an expression
 *             'name'=>'create_time',
 *             'value'=>'date("M j, Y", $data->create_time)',
 *         ),
 *         array(            // display 'author.username' using an expression
 *             'name'=>'authorName',
 *             'value'=>'$data->author->username',
 *         ),
 *         array(            // display a column with "view", "update" and "delete" buttons
 *             'class'=>'CButtonColumn',
 *         ),
 *     ),
 * ));
 * </pre>
 *
 * Please refer to {@link columns} for more details about how to configure this property.
 *
 * @property boolean $hasFooter Whether the table should render a footer.
 * This is true if any of the {@link columns} has a true {@link CGridColumn::hasFooter} value.
 * @property CFormatter $formatter The formatter instance. Defaults to the 'format' application component.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package zii.widgets.grid
 * @since 1.1
 */
class UGridView extends CBaseListView
{
	const FILTER_POS_HEADER='header';
	const FILTER_POS_FOOTER='footer';
	const FILTER_POS_BODY='body';

	private $_formatter;
	/**
	 * @var array grid column configuration. Each array element represents the configuration
	 * for one particular grid column which can be either a string or an array.
	 *
	 * When a column is specified as a string, it should be in the format of "name:type:header",
	 * where "type" and "header" are optional. A {@link CDataColumn} instance will be created in this case,
	 * whose {@link CDataColumn::name}, {@link CDataColumn::type} and {@link CDataColumn::header}
	 * properties will be initialized accordingly.
	 *
	 * When a column is specified as an array, it will be used to create a grid column instance, where
	 * the 'class' element specifies the column class name (defaults to {@link CDataColumn} if absent).
	 * Currently, these official column classes are provided: {@link CDataColumn},
	 * {@link CLinkColumn}, {@link CButtonColumn} and {@link CCheckBoxColumn}.
	 */
	public $columns=array();
    
    /**
     * default columns pairs
     * 
     * @var type 
     */
    public $defaultColumnPairs = array();
    
    public static $joinAttributeLabels = array();
    
	/**
	 * @var array the CSS class names for the table body rows. If multiple CSS class names are given,
	 * they will be assigned to the rows sequentially and repeatedly. This property is ignored
	 * if {@link rowCssClassExpression} is set. Defaults to <code>array('odd', 'even')</code>.
	 * @see rowCssClassExpression
	 */
	public $rowCssClass=array('odd','even');
	/**
	 * @var string a PHP expression that is evaluated for every table body row and whose result
	 * is used as the CSS class name for the row. In this expression, the variable <code>$row</code>
	 * stands for the row number (zero-based), <code>$data</code> is the data model associated with
	 * the row, and <code>$this</code> is the grid object.
	 * @see rowCssClass
	 * @deprecated in 1.1.13
	 */
	public $rowCssClassExpression;
	/**
	 * @var string a PHP expression that is evaluated for every table body row and whose result
	 * is used as additional HTML attributes for the row. In this expression, the variable <code>$row</code>
	 * stands for the row number (zero-based), <code>$data</code> is the data model associated with
	 * the row, and <code>$this</code> is the grid object.
	 * @since 1.1.13
	 */
	public $rowHtmlOptionsExpression;
	/**
	 * @var boolean whether to display the table even when there is no data. Defaults to true.
	 * The {@link emptyText} will be displayed to indicate there is no data.
	 */
	public $showTableOnEmpty=true;
	/**
	 * @var mixed the ID of the container whose content may be updated with an AJAX response.
	 * Defaults to null, meaning the container for this grid view instance.
	 * If it is set false, it means sorting and pagination will be performed in normal page requests
	 * instead of AJAX requests. If the sorting and pagination should trigger the update of multiple
	 * containers' content in AJAX fashion, these container IDs may be listed here (separated with comma).
	 */
	public $ajaxUpdate;
	/**
	 * @var string the jQuery selector of the HTML elements that may trigger AJAX updates when they are clicked.
	 * These tokens are recognized: {page} and {sort}. They will be replaced with the pagination and sorting links selectors.
	 * Defaults to '{page}, {sort}', that means that the pagination links and the sorting links will trigger AJAX updates.
	 * Tokens are available from 1.1.11
	 *
	 * Note: if this value is empty an exception will be thrown.
	 *
	 * Example (adding a custom selector to the default ones):
	 * <pre>
	 *  ...
	 *  'updateSelector'=>'{page}, {sort}, #mybutton',
	 *  ...
	 * </pre>
	 * @since 1.1.7
	 */
	public $updateSelector='{page}, {sort}';
	/**
	 * @var string a javascript function that will be invoked if an AJAX update error occurs.
	 *
	 * The function signature is <code>function(xhr, textStatus, errorThrown, errorMessage)</code>
	 * <ul>
	 * <li><code>xhr</code> is the XMLHttpRequest object.</li>
	 * <li><code>textStatus</code> is a string describing the type of error that occurred.
	 * Possible values (besides null) are "timeout", "error", "notmodified" and "parsererror"</li>
	 * <li><code>errorThrown</code> is an optional exception object, if one occurred.</li>
	 * <li><code>errorMessage</code> is the CGridView default error message derived from xhr and errorThrown.
	 * Usefull if you just want to display this error differently. CGridView by default displays this error with an javascript.alert()</li>
	 * </ul>
	 * Note: This handler is not called for JSONP requests, because they do not use an XMLHttpRequest.
	 *
	 * Example (add in a call to CGridView):
	 * <pre>
	 *  ...
	 *  'ajaxUpdateError'=>'function(xhr,ts,et,err){ $("#myerrordiv").text(err); }',
	 *  ...
	 * </pre>
	 */
	public $ajaxUpdateError;
	/**
	 * @var string the name of the GET variable that indicates the request is an AJAX request triggered
	 * by this widget. Defaults to 'ajax'. This is effective only when {@link ajaxUpdate} is not false.
	 */
	public $ajaxVar='ajax';
	/**
	 * @var mixed the URL for the AJAX requests should be sent to. {@link CHtml::normalizeUrl()} will be
	 * called on this property. If not set, the current page URL will be used for AJAX requests.
	 * @since 1.1.8
	 */
	public $ajaxUrl;
	/**
	 * @var string a javascript function that will be invoked before an AJAX update occurs.
	 * The function signature is <code>function(id,options)</code> where 'id' refers to the ID of the grid view,
	 * 'options' the AJAX request options  (see jQuery.ajax api manual).
	 */
	public $beforeAjaxUpdate;
	/**
	 * @var string a javascript function that will be invoked after a successful AJAX response is received.
	 * The function signature is <code>function(id, data)</code> where 'id' refers to the ID of the grid view,
	 * 'data' the received ajax response data.
	 */
	public $afterAjaxUpdate;
	/**
	 * @var string a javascript function that will be invoked after the row selection is changed.
	 * The function signature is <code>function(id)</code> where 'id' refers to the ID of the grid view.
	 * In this function, you may use <code>$(gridID).yiiGridView('getSelection')</code> to get the key values
	 * of the currently selected rows (gridID is the DOM selector of the grid).
	 * @see selectableRows
	 */
	public $selectionChanged;
	/**
	 * @var integer the number of table body rows that can be selected. If 0, it means rows cannot be selected.
	 * If 1, only one row can be selected. If 2 or any other number, it means multiple rows can be selected.
	 * A selected row will have a CSS class named 'selected'. You may also call the JavaScript function
	 * <code>$(gridID).yiiGridView('getSelection')</code> to retrieve the key values of the currently selected
	 * rows (gridID is the DOM selector of the grid).
	 */
	public $selectableRows=1;
	/**
	 * @var string the base script URL for all grid view resources (eg javascript, CSS file, images).
	 * Defaults to null, meaning using the integrated grid view resources (which are published as assets).
	 */
	public $baseScriptUrl;
	/**
	 * @var string the URL of the CSS file used by this grid view. Defaults to null, meaning using the integrated
	 * CSS file. If this is set false, you are responsible to explicitly include the necessary CSS file in your page.
	 */
	public $cssFile;
	/**
	 * @var string the text to be displayed in a data cell when a data value is null. This property will NOT be HTML-encoded
	 * when rendering. Defaults to an HTML blank.
	 */
	public $nullDisplay='&nbsp;';
	/**
	 * @var string the text to be displayed in an empty grid cell. This property will NOT be HTML-encoded when rendering. Defaults to an HTML blank.
	 * This differs from {@link nullDisplay} in that {@link nullDisplay} is only used by {@link CDataColumn} to render
	 * null data values.
	 * @since 1.1.7
	 */
	public $blankDisplay='&nbsp;';
	/**
	 * @var string the CSS class name that will be assigned to the widget container element
	 * when the widget is updating its content via AJAX. Defaults to 'grid-view-loading'.
	 * @since 1.1.1
	 */
	public $loadingCssClass='grid-view-loading';
	/**
	 * @var string the jQuery selector of filter input fields.
	 * The token '{filter}' is recognized and it will be replaced with the grid filters selector.
	 * Defaults to '{filter}'.
	 *
	 * Note: if this value is empty an exception will be thrown.
	 *
	 * Example (adding a custom selector to the default one):
	 * <pre>
	 *  ...
	 *  'filterSelector'=>'{filter}, #myfilter',
	 *  ...
	 * </pre>
	 * @since 1.1.13
	 */
	public $filterSelector='{filter}';
	/**
	 * @var string the CSS class name for the table row element containing all filter input fields. Defaults to 'filters'.
	 * @see filter
	 * @since 1.1.1
	 */
	public $filterCssClass='filters';
	/**
	 * @var string whether the filters should be displayed in the grid view. Valid values include:
	 * <ul>
	 *    <li>header: the filters will be displayed on top of each column's header cell.</li>
	 *    <li>body: the filters will be displayed right below each column's header cell.</li>
	 *    <li>footer: the filters will be displayed below each column's footer cell.</li>
	 * </ul>
	 * @see filter
	 * @since 1.1.1
	 */
	public $filterPosition='body';
	/**
	 * @var CModel the model instance that keeps the user-entered filter data. When this property is set,
	 * the grid view will enable column-based filtering. Each data column by default will display a text field
	 * at the top that users can fill in to filter the data.
	 * Note that in order to show an input field for filtering, a column must have its {@link CDataColumn::name}
	 * property set or have {@link CDataColumn::filter} as the HTML code for the input field.
	 * When this property is not set (null) the filtering is disabled.
	 * @since 1.1.1
	 */
	public $filter;
	/**
	 * @var boolean whether to hide the header cells of the grid. When this is true, header cells
	 * will not be rendered, which means the grid cannot be sorted anymore since the sort links are located
	 * in the header. Defaults to false.
	 * @since 1.1.1
	 */
	public $hideHeader=false;
	/**
	 * @var boolean whether to leverage the {@link https://developer.mozilla.org/en/DOM/window.history DOM history object}.  Set this property to true
	 * to persist state of grid across page revisits.  Note, there are two limitations for this feature:
	 * <ul>
	 *    <li>this feature is only compatible with browsers that support HTML5.</li>
	 *    <li>expect unexpected functionality (e.g. multiple ajax calls) if there is more than one grid/list on a single page with enableHistory turned on.</li>
	 * </ul>
	 * @since 1.1.11
	 */
	public $enableHistory=false;
    
    public $toolBar = null;       
    
    /**
     * table html options
     * @var type 
     */
    public $tableOptions = array();
    
    /**
     * view template
     * @var string 
     */
    public $template = null;
    
    /**
     * help tag
     * 
     * @var string 
     */
    public $helpTag = null;
    
    /**
     * order bar
     * 
     * @var array
     */
    public $orderBar = array();
    
    public $totalItem = null;
    
    public $target = null;
    
    public $on = null;
    
    public $tableFormOptions = array();
    
    public $hideAllPages = false;
    
    public static $rowBackgroundConfig = array();
    
    /**
     * @var boolean whether to display the search panel when there is no data. Defaults to false.
     */
    public $searchNoDisplay=false;
    
    public $print = false;
    public $DWZ=true;
    
    public $helpBar = false;
    
    public $filterColumn = true;
    
    public $adjustColumnsBar = true;
    
	/**
	 * Initializes the grid view.
	 * This method will initialize required property values and instantiate {@link columns} objects.
     * 
	 */
	public function init()
	{
		parent::init();
        $this->target = Yii::app()->request->getParam('target');
        $this->on = Yii::app()->request->getParam('on');
        $this->searchNoDisplay = !empty($_REQUEST['searchNoDisplay']) ? $_REQUEST['searchNoDisplay'] : $this->searchNoDisplay;
        if ( empty($this->template) ) {
            $this->template = THelper::getListTpl();
        }
        
        $this->itemsCssClass = 'table';
		if(empty($this->updateSelector))
			throw new CException(Yii::t('zii','The property updateSelector should be defined.'));
		if(empty($this->filterSelector))
			throw new CException(Yii::t('zii','The property filterSelector should be defined.'));

		if(!isset($this->htmlOptions['class']))
			$this->htmlOptions['class']='grid-view';

		if($this->baseScriptUrl===null)
			$this->baseScriptUrl=Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('zii.widgets.assets')).'/gridview';
		if($this->cssFile!==false)
		{
			if($this->cssFile===null)
				$this->cssFile=$this->baseScriptUrl.'/styles.css';
			Yii::app()->getClientScript()->registerCssFile($this->cssFile);
		}
		
		$this->initColumns();
	}

	/**
	 * Creates column objects and initializes them.
	 */
	protected function initColumns()
	{
		if($this->columns===array())
		{
			if($this->dataProvider instanceof CActiveDataProvider)
				$this->columns=$this->dataProvider->model->attributeNames();
			elseif($this->dataProvider instanceof IDataProvider)
			{
				// use the keys of the first row of data as the default columns
				$data=$this->dataProvider->getData();
				if(isset($data[0]) && is_array($data[0]))
					$this->columns=array_keys($data[0]);
			}
		}
		$id=$this->getId();
        $this->filterColumns();
		foreach($this->columns as $i=>$column)
		{
            if(is_string($column)) {               
                $column=$this->createDataColumn($column);                              
            }				
			else
			{
				if(!isset($column['class']))
					$column['class']='UDataColumn';
				$column=Yii::createComponent($column, $this);
			}
			if(!$column->visible)
			{
				unset($this->columns[$i]);
				continue;
			}
			if($column->id===null)
				$column->id=$id.'_c'.$i;	
			$this->columns[$i]=$column;
		}
		foreach($this->columns as $k => $column){
		   $column->init(); 
		}
			
	}

	/**
	 * Creates a {@link CDataColumn} based on a shortcut column specification string.
	 * @param string $text the column specification string
	 * @return CDataColumn the column instance
	 */
	protected function createDataColumn($text)
	{
		if(!preg_match('/^([\w\.]+)(:(\w*))?(:(.*))?$/',$text,$matches))
			throw new CException(Yii::t('zii','The column must be specified in the format of "Name:Type:Label", where "Type" and "Label" are optional.'));
		$column=new UDataColumn($this);
		$column->name=$matches[1];
		if(isset($matches[3]) && $matches[3]!=='')
			$column->type=$matches[3];
		if(isset($matches[5]))
			$column->header=$matches[5];
		return $column;
	}

	/**
	 * Registers necessary client scripts.
	 */
	public function registerClientScript()
	{
		$id=$this->getId();

		if($this->ajaxUpdate===false)
			$ajaxUpdate=false;
		else
			$ajaxUpdate=array_unique(preg_split('/\s*,\s*/',$this->ajaxUpdate.','.$id,-1,PREG_SPLIT_NO_EMPTY));
		$options=array(
			'ajaxUpdate'=>$ajaxUpdate,
			'ajaxVar'=>$this->ajaxVar,
			'pagerClass'=>$this->pagerCssClass,
			'loadingClass'=>$this->loadingCssClass,
			'filterClass'=>$this->filterCssClass,
			'tableClass'=>$this->itemsCssClass,
			'selectableRows'=>$this->selectableRows,
			'enableHistory'=>$this->enableHistory,
			'updateSelector'=>$this->updateSelector,
			'filterSelector'=>$this->filterSelector
		);
		if($this->ajaxUrl!==null)
			$options['url']=CHtml::normalizeUrl($this->ajaxUrl);
		if($this->enablePagination)
			$options['pageVar']=$this->dataProvider->getPagination()->pageVar;
		foreach(array('beforeAjaxUpdate', 'afterAjaxUpdate', 'ajaxUpdateError', 'selectionChanged') as $event)
		{
			if($this->$event!==null)
			{
				if($this->$event instanceof CJavaScriptExpression)
					$options[$event]=$this->$event;
				else
					$options[$event]=new CJavaScriptExpression($this->$event);
			}
		}

		$options=CJavaScript::encode($options);
		$cs=Yii::app()->getClientScript();
		$cs->registerCoreScript('jquery');
		$cs->registerCoreScript('bbq');
		if($this->enableHistory)
			$cs->registerCoreScript('history');
		$cs->registerScriptFile($this->baseScriptUrl.'/jquery.yiigridview.js',CClientScript::POS_END);
		$cs->registerScript(__CLASS__.'#'.$id,"jQuery('#$id').yiiGridView($options);");
	}
	
	public function renderPrintList(){
// 		$str = '';
// 		$currentModel = get_class( $this->filter);
// 		echo '<div id="w_list_print_'.$currentModel.'">';
		$this->renderTotalItem();
		$this->renderItems();
// 		echo '</div>';
	}

	
	public function renderTotalItem()
	{
		$str = '';
		if(isset($this->totalItem)){
			$str .='<div id="totalItem" style="text-align:left;border-color:#70b3fa;border-style: solid;border-width: 0 0 1px;padding: 5px;">';
			$class = isset($this->totalItem['htmlOptions']['class']) ? 'class='.$this->totalItem['htmlOptions']['class'] : '';
			$str .='<li><span '.$class.' style='.$this->totalItem['htmlOptions']['style'].'>';
			if($this->totalItem['text']){
				$str .= $this->totalItem['text'];
			}
			$str .='</span></li>';
			$str .='</div>';
		}
		echo $str;
	}

	/**
	 * Renders the data items for the grid view.
	 */
	public function renderItems()
	{
		if($this->dataProvider->getItemCount()>0 || $this->showTableOnEmpty)
		{			
            $this->tableOptions['class'] = $this->itemsCssClass;
            $this->tableOptions['width'] = '100%';
            if ( $this->target ) {
                if ( $this->target == 'dialog' ) {
                    $this->tableOptions['targetType'] = "dialog";                  
                } else {
                    $this->tableOptions['rel'] = $this->target;
                }
            }
            if ( $this->tableFormOptions ) {
                echo CHtml::openTag('form', $this->tableFormOptions);
            }
            echo CHtml::openTag('table', $this->tableOptions);
            
			$this->renderTableHeader();
			ob_start();
			$this->renderTableBody();
			$body=ob_get_clean();
			$this->renderTableFooter();
			$target = $this->target;
			echo $body; // TFOOT must appear before TBODY according to the standard.
			echo "</table>";
            if ( $this->tableFormOptions ) {
                echo "</form>";
            }
		}
		else
			$this->renderEmptyText();
	}

	/**
	 * Renders the table header.
	 */
	public function renderTableHeader() {
		if (!$this->hideHeader) {
			echo "<thead>\n";			
			echo "<tr>\n";
			//输出th
			foreach($this->columns as $column)
				$column->renderHeaderCell();
			echo "</tr>\n";
			echo "</thead>\n";
		} elseif ($this->filter!==null && 
                ($this->filterPosition===self::FILTER_POS_HEADER || 
                $this->filterPosition===self::FILTER_POS_BODY)) {
			echo "<thead>\n";			
			echo "</thead>\n";
		}
	}

	/**
	 * Renders the filter.
	 * @since 1.1.1
	 */
	public function renderFilter()
	{
		if($this->filter!==null) {
			return $this->filter->filter();
		}
	}

	/**
	 * Renders the table footer.
	 */
	public function renderTableFooter()
	{
		$hasFilter=$this->filter!==null && $this->filterPosition===self::FILTER_POS_FOOTER;
		$hasFooter=$this->getHasFooter();
		if($hasFilter || $hasFooter)
		{
			echo "<tfoot>\n";
			if($hasFooter)
			{
				echo "<tr>\n";
				foreach($this->columns as $column)
					$column->renderFooterCell();
				echo "</tr>\n";
			}
			if($hasFilter)
				$this->renderFilter();
			echo "</tfoot>\n";
		}
	}

	/**
	 * Renders the table body.
	 */
	public function renderTableBody()
	{
		$data = $this->dataProvider->getData();
		$n=count($data);
		echo "<tbody>\n";
		if($n>0)
		{
			for($row=0;$row<$n;++$row){
				$this->renderTableRow($row);
			}
		}
		else
		{
			echo '<tr><td colspan="'.count($this->columns).'" class="empty">';
			$this->renderEmptyText();
			echo "</td></tr>\n";
		}
		echo "</tbody>\n";
	}
    
    /**
	 * Renders the pager.
	 */
	public function renderPager() {
		
		if(!$this->enablePagination){
			return;
		}
		$pager=array();
		$class='ULinkPager';
		if(is_string($this->pager))
			$class=$this->pager;
		elseif(is_array($this->pager))
		{
			$pager=$this->pager;
			if(isset($pager['class']))
			{
				$class=$pager['class'];
				unset($pager['class']);
			}
		}
		$pager['pages']=$this->dataProvider->getPagination();
		if ($pager['pages']->getPageCount()>1)
		{			
			$this->widget($class,$pager);			
		}
		else
			$this->widget($class,$pager);
	}
    
    /**
     * Renders the pager hidden.
     * 
     * @return type
     */
    public function renderPagerhidden() {
        if (!$this->enablePagination)
			return;
        $pages =$this->dataProvider->getPagination();
        $html = '';
        $html .= CHtml::hiddenField('pageNum', $pages->getCurrentPage());
        $html .= CHtml::hiddenField('numPerPage', $pages->getPageSize());
        $html .= CHtml::hiddenField('orderField', @$_REQUEST['orderField']);
        $html .= CHtml::hiddenField('orderDirection', @$_REQUEST['orderDirection']);
        $html .= CHtml::hiddenField('pagesChecked', @$_REQUEST['pagesChecked']);
        $html .= CHtml::hiddenField('filterToggle', isset($_REQUEST['filterToggle']) ? $_REQUEST['filterToggle'] : 1);
        if ( $this->target ) {
            $html .= CHtml::hiddenField('target', $this->target);
        }
        
        if ( $this->on ) {
            $html .= CHtml::hiddenField('on', $this->on);
        }
        return $html;
    }

	/**
	 * Renders a table body row.
	 * @param integer $row the row number (zero-based).
	 */
	public function renderTableRow($row)
	{
		$htmlOptions=array();
		if($this->rowHtmlOptionsExpression!==null)
		{
			$data=$this->dataProvider->data[$row];
			$options=$this->evaluateExpression($this->rowHtmlOptionsExpression,array('row'=>$row,'data'=>$data));
			if(is_array($options))
				$htmlOptions = $options;
		}
		if($this->rowCssClassExpression!==null)
		{
			$data=$this->dataProvider->data[$row];
			$class=$this->evaluateExpression($this->rowCssClassExpression,array('row'=>$row,'data'=>$data));
		}
		elseif(is_array($this->rowCssClass) && ($n=count($this->rowCssClass))>0)
			$class=$this->rowCssClass[$row%$n];

		if(!empty($class))
		{
			if(isset($htmlOptions['class']))
				$htmlOptions['class'].=' '.$class;
			else
				$htmlOptions['class']=$class;
		}
		echo CHtml::openTag('tr', $htmlOptions)."\n";
        foreach($this->columns as $colNum => $column) {
            $column->renderDataCell($row, $colNum);
        }
		echo "</tr>\n";
	}
    /**
     * renders mouserright toolbar
     * 
     */
	public function renderMouserRight(){    
		
		$div='<div class="mouse_right_menu_auto" id="mouse_right_menu" style="display:none;position:absolute;z-index:999;"><ul>';
		if (empty($this->toolBar)){
			return null;
		}		
		if (is_string($this->toolBar)){
        	$div= $this->toolBar;
       	}else if(is_array($this->toolBar)){
            foreach ($this->toolBar as $item){ 
                if(!isset($item['htmlOptions']['onclick'])){
                    $item['htmlOptions']['onclick'] = '';
                }
            	if(!$item['htmlOptions']['onclick']){
            		$item['htmlOptions']['onclick'] = 'turnOff(this)';
            	}
            	$item['htmlOptions']['class'] = $item['htmlOptions']['class']?$item['htmlOptions']['class'].' trigger':'trigger';        
            	$div.="<li>".CHtml::link($item['text'],$item['url'],$item['htmlOptions'])."</li>";           	
            }
       	}
//        	foreach ($this->columns as $val) {
//        		if(isset($val['class']) && $val['class'] == 'CButtonColumn'){
//        			foreach ($val['buttons'] as $key => $value){       				
//        				$url=substr(strstr($value['url'],"/"),0,strpos(strstr($value['url'],"/"),'"'));       				
//        				$value['options']['class'] = $value['options']['class']?$value['options']['class'].' trigger':'trigger';
//        				$div.="<li>".CHtml::link($value['label'],$url,$value['options'])."</li>";   
//        			}      			
//        		}
//        	} 
		$div.="</ul></div>";   
       //	$div .= "<li style='font-size:13px;background-color: transparent;border: 1px solid #fff;color: #000;display: block; margin: 0;padding:3px;text-align: left;'><a href='javascript:copyContent();'>" . Yii::t('system', 'Copy') . "</a></li>";
       	//$div .= "<li style='font-size:13px;background-color: transparent;border: 1px solid #fff;color: #000;display: block; margin: 0;padding:3px;text-align: left;'><a href='javascript:export();'>" . Yii::t('system', 'Export') . "</a></li></ul></div>";
       	echo $div;
	}
    /**
     * Renders a tool bar.
     * 
     * @return null
     */
    public function renderToolBar() { 
    	if(!$this->hideAllPages)
    		$this->pageSelectedAll();
    	
        if (empty($this->toolBar))
            return null;
        if ( is_string($this->toolBar) ) {
            echo $this->toolBar;
        } else if (is_array($this->toolBar)){      	
            foreach ($this->toolBar as $item) {  

            	if( isset( $item['items'] ) ){//Drop List
            		echo '<li>';
            		if( isset($item['title']) ){
            			$dropDownArr = array(
            				'' => $item['title'],
            			);
            		}else{
	            		$dropDownArr = array();
            		}
            		$i = 0;
            		$aHidden = '';
            		foreach( $item['items'] as $itm ){
            			$i++;
            			$action = '';
            			if( !isset($itm['htmlOptions']) ){
            				$itm['htmlOptions'] = array();
            			}
            			if( isset($itm['actionUrl']) ){
            				$action = '$(this).parent().find(\'a.action_hidden'.$i.'\').click()';
            				$itm['htmlOptions']['style'] = isset($itm['htmlOptions']['style']) ? $itm['htmlOptions']['style'].'display:none;' : 'display:none;';
            				$itm['htmlOptions']['class'] = isset($itm['htmlOptions']['class']) ? $itm['htmlOptions']['class'].' action_hidden'.$i : 'action_hidden'.$i;
            				$aHidden .= CHtml::link('', $itm['actionUrl'], $itm['htmlOptions']);
            			}elseif( isset($itm['actionScript']) ) {
            				$action = $itm['actionScript'];
            			}
            			$dropDownArr[$action] = $itm['text'];
            		}
            		if (! isset($item['htmlOptions'])) {
            			$item['htmlOptions'] = array();
            		}
            		if( !isset($item['title']) ){
            			$item['htmlOptions']['empty'] = Yii::t('system', 'Please Select');
            		}
            		
            		$item['htmlOptions']['onChange'] = 'eval(this.value)';
            		echo CHtml::dropDownList('', '', $dropDownArr, $item['htmlOptions']);
            		echo $aHidden;
            		echo '</li>';
            	}else{
            		$showLink = true;
	                if ($this->on && ! empty($item['on']) && 
	                        !in_array($this->on, $item['on'])) {
	                    continue;
	                }
	                $text = '<span>'.$item['text'].'</span>';
	                if (! isset($item['url']) ) {
	                    $item['url'] = 'javascript::void(0);' ;
	                }else{
	                	if(strpos($item['url'],'http')!==0 && strpos($item['url'],'javascript')===false){
	                		$dep = explode('/',rtrim($item['url'],'/'));
	                		$action = end($dep);$controller = prev($dep);
	                		$module = str_replace('/'.$controller.'/'.$action, '', rtrim($item['url'],'/'));
	                		$resourse = 'resource_'.ltrim($module,'/').'_'.$controller.'_'.$action;
	                		//echo $resourse;
// 	                		if(!UebModel::checkAccess($resourse)){
// 	                			$showLink = false;
// 	                		}
	                	}
	                }
	                
	                if (! isset($item['htmlOptions'])) {
	                    $item['htmlOptions'] = array();
	                }                
	                echo '<li>';
	                if(isset($item['type']) && $item['type'] == 'button') {
	                	echo CHtml::button($item['text'], $item['htmlOptions']);
	                }else{
	                	if($showLink){
		            		echo CHtml::link($text, $item['url'], $item['htmlOptions']);
	                	}
	                }                
	                echo '</li>';           
            	}            
            }
        }
    }
    
    public function renderHelpBar() {
    	if($this->helpBar){
	    	$str = '';
	    	$str .= '<div class="help_bar hide" id="help_bar">';
	    	$str .= $this->renderHelpContent();
	    	$str .= '</div>';
	    	echo $str;
    	}
    }
    
    /**
     * Renders help content
     */
    public function renderHelpContent() {        
        if ( empty($this->helpTag) ) { 
            if (stripos($_SERVER['REQUEST_URI'], '?') !== false ) {
               $requestUri = explode("?", $_SERVER['REQUEST_URI']);
               $server = $requestUri[0];
           } else {
               $server = $_SERVER['REQUEST_URI'];
           }  
           if (stripos(trim($server, '/'), '/') !== false ) { 
               $serverArr = explode("/", trim($server, '/'));
               $this->helpTag = $serverArr[0] .'/'.$serverArr[1]. '/'.$serverArr[2];              
           }
        } 
        $helpContent =  UebModel::model('pagehelp')->getHelpContentByTag($this->helpTag);
        return $helpContent;
    }
    
    /**
     * Renders filter column bar
     */
    public function renderAdjustColumnsBar() {
    	if($this->adjustColumnsBar){
	    	echo '<div class="filter_column_bar hide" id="filter_column_bar" >';
	        $listOptions = $this->defaultColumnPairs;
	        if ( empty($listOptions) ) return null;
	        $target = $this->target;
	        $on = !empty($this->on) ? $this->on : '';
	        $className = get_class($this->filter);  
	        $classNameKey =  !empty($this->on) ? $className. '-'. $this->on : $className;
	        $selectedArr = UebModel::model('UserListShow')->getShowValuesByKey($classNameKey);
	        echo '<select id="fiter_column" name="fiter_column[]" size="4" multiple="multiple" style="width: 800px; display: none;" class="chosen-select" data-placeholder=""  >';
	        foreach ($listOptions as $key => $val) {
	            echo "<option value='{$key}' ";
	            if ( in_array($key, $selectedArr) !== false || empty($selectedArr) ) {
	                echo 'selected = "selected"';
	            }
	            echo " >{$val}</option>";
	        }        
	        echo '</select>';   
	        echo CHtml::button(Yii::t('system', 'Refresh'), array( 
	                'style' => 'margin-left:20px;', 
	                'id'    => 'filterColumnRefreshBtn'
	            ));
	        echo "
	            <script> 
	             $(function(){
	                var p = '$target' == 'dialog' ? $.pdialog.getCurrent() : navTab.getCurrentPanel(); 
	                $('.chosen-select', p).chosen({});
	                $('#fiter_column_chosen', p).css('width', '90%');                
	                $('#filterColumnRefreshBtn', p).click(function(){
	                    var fieldValueArr = $('#fiter_column', p).val();
	                    if ( fieldValueArr.length > 0 ) {
	                        var fieldValues = fieldValueArr.join(',')
	                        $.ajax({
	                            type: 'post',
	                            url: '/users/userlistshow/refresh',
	                            data: {fieldValues: fieldValues, className: '$className', on: '$on'},
	                            async: false,             
	                            success: function(data) {                
	                                if (data) {
	                                     $('form:first', p).find(':submit:first').submit();
	                                }                       
	                            }
	                        });
	                    }
	                    
	                });
	             });                            
	            </script>";
	        echo '</div>';
    	}
    }

    /**
     * Renders order bar.
     * 
     * @return null
     */
    public function renderOrderBar() {
    	echo '<li class="orderBar" style="float:right;padding-right:20px;" >';
        if (empty($this->orderBar)) {
            if ( $orderOptions = $this->filter->orderOptions() ) {
               $this->orderBar = array( 'orderOptions' => $orderOptions);
            } else {
                 return null;   
            }                              
        }          
        
        if ( is_string($this->orderBar) ) {
            echo $this->orderBar;
        } else if (is_array($this->orderBar)) {
            if (! isset($this->orderBar['htmlOptions'])) {
                $this->orderBar['htmlOptions'] = array();
            }                
            echo  '<span>';
            echo CHtml::label(Yii::t('system', 'Order').'：', 'order_field', array( 'style' => 'font-size:13px;line-height: 200%;'));;
            echo '</span>';
            echo  '<span>';
            $target = $this->target;
            $orderOptions = array_merge(array( "" => Yii::t('system', 'Please Select')), $this->orderBar['orderOptions']); 
            echo CHtml::dropDownList('searchOrderField', @$_REQUEST['orderField'], $orderOptions, $this->orderBar['htmlOptions']);
            $ascHtmlOptions = array( 'id' => 'asc', 'onclick' => "$.searchOrder(this, '$target')");
            $descHtmlOptions = array( 'id' => 'desc', 'onclick' => "$.searchOrder(this, '$target')");
            
            if ( isset($_REQUEST['orderDirection']) && !empty($_REQUEST['orderField']) && 
                    in_array($_REQUEST['orderField'], array_keys($orderOptions))) {
                if ( $_REQUEST['orderDirection'] == 'asc' ) {
                    $ascHtmlOptions['style'] = 'color:blue;font-weight: bold;';
                } else {
                    $descHtmlOptions['style'] = 'color:blue;font-weight: bold;';
                }
            }
            
            echo CHtml::button(Yii::t('system', 'Asc'), $ascHtmlOptions);           
            echo CHtml::button(Yii::t('system', 'Desc'), $descHtmlOptions);
            echo '</span>';
        }
        if($this->filterColumn){
        	echo $this->filterColumn();
        }
        echo '</li>';
    }
    
    public function filterColumn(){
    	return '<a href="javascript:void(0);"  class="btn_filter_column" title="'. Yii::t('system', 'Filter Column') .'"><span>》</span></a>';
    }
    
    /**
     * Renders export bar.
     * 
     * @return null
     */
    public function renderExportBar() {   

            
    }

    /**
	 * @return boolean whether the table should render a footer.
	 * This is true if any of the {@link columns} has a true {@link CGridColumn::hasFooter} value.
	 */
	public function getHasFooter()
	{
		foreach($this->columns as $column)
			if($column->getHasFooter())
				return true;
		return false;
	}

	/**
	 * @return CFormatter the formatter instance. Defaults to the 'format' application component.
	 */
	public function getFormatter()
	{
		if($this->_formatter===null)
			$this->_formatter=Yii::app()->format;
		return $this->_formatter;
	}

	/**
	 * @param CFormatter $value the formatter instance
	 */
	public function setFormatter($value)
	{
		$this->_formatter=$value;
	}
    
    /**
     * filter columns 
     * 
     * @return void
     */
    protected function filterColumns() {
    	$className = get_class($this->filter);
        $attributeLabels = $this->filter->attributeLabels();
        $relations = $this->filter->relations();
        $classNameKey =  !empty($this->on) ? $className. '-'. $this->on : $className;
        $selectedColumns = UebModel::model('UserListShow')->getShowValuesByKey($classNameKey);      
        
        foreach ($this->columns as $key => $val) {
            if ( (isset($val['class']) && ($val['class'] == 'CCheckBoxColumn' || 
                    $val['class'] == 'CButtonColumn')) || !isset($val['name']) ||
                    $val['name'] == 'id') {
                continue;
            }
            
            if (is_string($val)) {
                if ( stripos($val, ".") !== false ) {
                    $fieldArr = explode(".", $val);                                                        
                    $this->_joinAttributeLabels($relations[$fieldArr[0]][1]);
                    $this->defaultColumnPairs[$val] = self::$joinAttributeLabels[$relations[$fieldArr[0]][1]][$fieldArr[1]];
                } else {
                    $this->defaultColumnPairs[$val] = $attributeLabels[$val];
                } 
                
                if (! empty($selectedColumns) && !in_array($val, $selectedColumns)) {
                    unset($this->columns[$key]);                  
                }
            } else {
                if ( stripos($val['name'], ".") !== false ) {
                    $fieldArr = explode(".", $val['name']);
                    $this->_joinAttributeLabels($relations[$fieldArr[0]][1]);
                    $this->defaultColumnPairs[$val] = isset(self::$joinAttributeLabels[$relations[$fieldArr[0]][1]][$fieldArr[1]]) ?
                        self::$joinAttributeLabels[$relations[$fieldArr[0]][1]][$fieldArr[1]] : $val['header'];
                } else {
                	$val['header'] = isset($val['header'])?$val['header']:'';
                    $this->defaultColumnPairs[$val['name']] = isset($attributeLabels[$val['name']]) ? 
                        $attributeLabels[$val['name']] : $val['header'];
                }
                
                if (! empty($selectedColumns) && isset($val['name']) && !in_array($val['name'], $selectedColumns)) {
                    unset($this->columns[$key]);
                    continue;
                }
                
                if ( isset($val['on']) ) {
                    $filterOn = preg_split('/\s*,\s*/',trim($val['on']),-1,PREG_SPLIT_NO_EMPTY);
                    if (! empty($this->on) && isset($filterOn) && 
                            !in_array($this->on, $filterOn)) {
                       unset($this->columns[$key]);                   
                    }                       
                }               
            }                                 
        }   
       $this->renderMouserRight();
      }
    
    protected function _joinAttributeLabels($key) {
        if ( empty(self::$joinAttributeLabels[$key]) ) {
            self::$joinAttributeLabels[$key] = UebModel::model($key)
                ->attributeLabels();
        } 
    }

    /**
     * Page selected all
     */
    public function pageSelectedAll() {
        $checked = Yii::app()->request->getParam('pagesChecked', false);          
        $checkBox = CHtml::checkBox('allPagesSelected', empty($checked) ? false : true, array(
            'id'        => 'allPagesSelected',
            'group'     => strtolower($this->dataProvider->id).'-grid_c0[]',
            'onclick'   => "allPagesSelected(this, '#". strtolower($this->dataProvider->id) ."-grid_c0_all');",          
        ));                 
        echo "<li>
                $checkBox
                ".Yii::t('system', 'All the pages selected')."
                <script>    
                $(function() {
                    var pageSelectedId = '#". strtolower($this->dataProvider->id) ."-grid_c0_all'; 
                    var p = '$this->target' == 'dialog' ? $.pdialog.getCurrent() : navTab.getCurrentPanel();                         
                    allPagesSelected($('#allPagesSelected', p), pageSelectedId);                   
                });
                 function allPagesSelected(obj, pageSelectedId) {                 
                    var p = '$this->target' == 'dialog' ? $.pdialog.getCurrent() : navTab.getCurrentPanel();
                    var checked = !$.isEmptyObject($(obj).attr('checked')) ? true : false,
                        _name = $(obj).attr('group'),
                        checkboxLi = $(p).find(':checkbox[name=\"'+_name+'\"]');                          
                    if ( checked ) {                       
                        $(pageSelectedId, p).attr({'checked':true, 'disabled': true});
                        checkboxLi.attr({'checked':true, 'disabled': true})
                        $('#pagesChecked', p).val(1);
                    } else {                       
                        $(pageSelectedId, p).attr({'checked':false, 'disabled': false});
                        checkboxLi.attr({'checked':false, 'disabled': false})
                        $('#pagesChecked', p).val(0);
                    }                       
                 }
                </script>
            </li>";
    }
    
    public function renderPageHeader(){
    	$tpl = '';
    	if(!$this->searchNoDisplay){
    		$curUrl = Yii::app()->request->getUrl();
	    	$tpl .=  '<div class="pageHeader" style="border:1px #B8D0D6 solid"> ';
	    	$htmlOptions = array(
	    			'id'        => 'pagerForm',
	    			'action'    => $curUrl,
	    			'method'    => 'post',
	    			'name'		=> $this->id
	    	);
	    	$target = Yii::app()->request->getParam('target', null);
	    	if ( $target == 'dialog' ) {
	    		$htmlOptions['onsubmit'] = "return dwzSearch(this, 'dialog');";
	    	} else if (! empty ($target) ){
	    		$htmlOptions['onsubmit'] = "return divSearch(this, '$target');";
	    	} else {
	    		$htmlOptions['onsubmit'] =  "return navTabSearch(this);";
	    	}
	    	if(!$this->DWZ){
	    		unset($htmlOptions['onsubmit']);
	    	}
	    	
	    	if($this->print){
	    		$htmlOptions['onsubmit'] =  "";
	    	}

	    	$filterToggleFlag = Yii::app()->request->getParam('filterToggle', 1);
	    	$filterToggleFlag = isset($_REQUEST['filterToggle']) && $_REQUEST['filterToggle'] > 0 ? $_REQUEST['filterToggle'] : '';
	    	if ( $filterToggleFlag ) {
	    			$filterToggleBtn = '<a href="javascript:void(0);" class="filterCollapsing" title="'. Yii::t('system', 'Expand') .'" style="float:right; margin-right:5px;margin-top:2px;" >'. Yii::t('system', 'Expand') .'</a>  ';
	    	} else {
	    	      $filterToggleBtn = '<a href="javascript:void(0);" class="filterExpand" title="'. Yii::t('system', 'Collapsing') .'" style="float:right; margin-right:5px;margin-top:2px;" >'. Yii::t('system', 'Collapsing') .'</a>  ';
	    	}
	    	//1127行：敲回车模拟TAB键功能
	    	/*
	    	 * /*
		    	document.onkeydown = function(e){
		    	var code;
		    	if (!e) {
		    	var e = window.event;
		    	}
		    	if (e.keyCode) {
		    	code = e.keyCode;
		    	}
		    	else if (e.which) {
		    	code = e.which;
		    	}
		    	if (code == 8) {
		    	if (document.activeElement.readOnly == true || document.activeElement.disabled == true)
		    	{ return false; }
		    	}
		    	if (code == 13) {
		    	if (document.activeElement.type == "textarea") {
		    	return;
		    	}
		    	if (document.activeElement.type != "submit" && document.activeElement.type != "button") {
		    	// event.keyCode = 9;
		    	focunext(e);
		    	return false;
		    	}
		    	}
		    	};
		    	*/
	    	if($this->DWZ){
	    	$tpl .= '<script>
	    		$(function(){
	    			var $p = "'.$target.'" == "dialog" ? $.pdialog.getCurrent() : navTab.getCurrentPanel();
	    			$("form:first", $p).find(":text:first").focus();
	    			$("form:first", $p).find(":text:first").select();
	    			var pageHeaderH = $(\'.pageHeader\', $p).height();
	    			var diffH = pageHeaderH - parseInt($("#yw0", $p).attr( \'tabindex\')) ;
	    			$(\'.gridScroller\', $p).css( \'height\',  $(\'.gridScroller\', $p).height() - diffH);
	    			$("#yw0", $p).attr( "tabindex", $(".pageHeader", $p).height());
	    			$("a.btn_help", $p).unbind("click").click(function(event){
	    			event.preventDefault();
	    			if ($("#help_bar", $p)){
	    				$("#help_bar", $p).toggleClass("hide");
		    		}
		    	});
		    	
		    	$("a.filterExpand, a.filterCollapsing", $p).unbind("click").click(function(event){
		    	event.preventDefault();
		    	if ( $(this).attr(\'class\') == \'filterExpand\') {
		    	$(this).text($.regional.products.msg.unfold);
		    	$(this).removeClass(\'filterExpand\').addClass(\'filterCollapsing\');
		    	$(this).attr({title: $.regional.products.msg.unfold});
		    	$(\'#filterToggle\', $p).val(\'1\');
		    	} else {
		    	$(this).text($.regional.products.msg.fold);
		    	$(this).removeClass(\'filterCollapsing\').addClass(\'filterExpand\');
		    	$(this).attr({title: $.regional.products.msg.fold});
		    	$(\'#filterToggle\', $p).val(\'0\');
		    	}
		    	
		    	$(\'.searchContent .filterToggle\', $p).each(function(i){
		    	$(this).prev(\'div\').toggleClass(\'hide\');
		    	$(this).toggleClass("hide");
		    	});
		    	
		    	var pageHeaderH = $(\'.pageHeader\', $p).height();
		    	var diffH = pageHeaderH - parseInt($("#yw0", $p).attr( \'tabindex\')) ;
		    	$(\'.gridScroller\', $p).css( \'height\',  $(\'.gridScroller\', $p).height() - diffH);
		    	$("#yw0", $p).attr( \'tabindex\', pageHeaderH);
		    	});
		    	
		    	$("a.btn_filter_column", $p).unbind("click").click(function(event){
		    	event.preventDefault();
		    	if ($("#filter_column_bar", $p)){
		    	$("#filter_column_bar", $p).toggleClass("hide");
		    	}
		    	});
	    		
	    		$("#reset", $p).click(function(){
	    			$(".searchBar input[type=checkbox]", $p).attr("checked", false);
	    			$(".searchBar input[type=text]", $p).val("");
	    			$(".searchBar input[type=hidden]", $p).val("");
	    			$(".searchBar select", $p).val("");
	    					
    				})
		    	});
	    		
		    </script>';
	    	}
	    	$tpl .= CHtml::openTag('form', $htmlOptions);
	    	$tpl .= $this->renderPagerhidden();
	    	$tpl .= '<div class="searchBar">
	    	<div class="searchContent">';
	    	$tpl .= $this->renderFilter();
	    	$tpl .= '</div>
	    	<div class="subBar">
	    	<ul>
	    	<li>
	    	<div class="buttonActive">
	    	<div class="buttonContent">
	    	<button type="submit">' . Yii::t('system', 'Search') . '</button>
	    	</div>
	    	</div>
	    	</li>';
	    	if($this->DWZ){
		    	$tpl .= '<li>
		    	<a class="button" id="reset" title="' . Yii::t('system', 'Empty query') . '"><span>' . Yii::t('system', 'Reset') . '</span></a>
		    	<a href="javascript:void(0);" class="btn_help" title="'. Yii::t('system', 'View Help') .'">'. Yii::t('system', 'Help') .'</a>
		    	</li>
		    	<li>'. $filterToggleBtn .'</li>';
	    	}
	    	$tpl .= '</ul>
	    	</div>
	    	</div>
	    	</form>
	    	</div>';
    	}
    	echo $tpl;
    }
}
