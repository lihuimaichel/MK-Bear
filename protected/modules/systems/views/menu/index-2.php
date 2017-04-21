<div class="pageContent" >
    <div class="panelBar">
		<ul class="toolBar">
			<li><a class="add" href="demo/pagination/dialog2.html" target="dialog" mask="true"><span>添加尿检检测</span></a></li>
			<li><a class="delete" href="demo/pagination/ajaxDone3.html?uid={sid_obj}" target="ajaxTodo" title="确定要删除吗?"><span>删除</span></a></li>
			<li><a class="edit" href="demo/pagination/dialog2.html?uid={sid_obj}" target="dialog" mask="true"><span>修改</span></a></li>
			<li class="line">line</li>
			<li><a class="icon" href="demo/common/dwz-team.xls" target="dwzExport" title="实要导出这些记录吗?"><span>导出EXCEL</span></a></li>
		</ul>
	</div>
    <div layoutH="400" style="float:left; display:block; overflow:auto; width:240px; border:solid 1px #CCC; line-height:21px; background:#fff">
        <ul class="tree treeFolder">
            <li><a href="javascript">实验室检测</a>
                <ul>
                    <li><a href="demo/pagination/list1.html" target="ajax" rel="jbsxBox">尿检</a></li>
                    <li><a href="demo/pagination/list1.html" target="ajax" rel="jbsxBox">HIV检测</a></li>
                    <li><a href="demo/pagination/list1.html" target="ajax" rel="jbsxBox">HCV检测</a></li>
                    <li><a href="demo/pagination/list1.html" target="ajax" rel="jbsxBox">TB检测</a></li>
                </ul>
            </li>

         </ul>
    </div>	
	<div  layoutH="400" style="margin-left:246px;" >
		<table class="table" width="99%" layoutH="400">
		<thead>
			<tr>
				<th width="80">序号</th>
				<th width="120" orderField="number" class="asc">诊所编号</th>
				<th orderField="name">诊所名称</th>
				<th width="100">病人编号</th>
				<th width="100">病人姓名</th>
				<th width="120">尿检日期</th>
				<th width="100">尿检结果</th>
				<th width="80">检验次数</th>
			</tr>
		</thead>
		<tbody>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
		</tbody>
	</table>
	</div>	
    <div  layoutH="200" style="clear:both;" >
		<table class="table" width="99%" layoutH="400">
		<thead>
			<tr>
				<th width="80">序号</th>
				<th width="120" orderField="number" class="asc">诊所编号</th>
				<th orderField="name">诊所名称</th>
				<th width="100">病人编号</th>
				<th width="100">病人姓名</th>
				<th width="120">尿检日期</th>
				<th width="100">尿检结果</th>
				<th width="80">检验次数</th>
			</tr>
		</thead>
		<tbody>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="1">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>张三</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
			<tr target="sid_obj" rel="2">
				<td>1</td>
				<td>bj0001</td>
				<td>xxx诊所</td>
				<td>xxx</td>
				<td>李四</td>
				<td>2011-9-6</td>
				<td>xxx</td>
				<td>1</td>
			</tr>
		</tbody>
	</table>
</div>
	

