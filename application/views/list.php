<!DOCTYPE html>
<html>
<head>
	<meta charset="GBK">
	<title>ZookeeperExplore</title>
	<link rel="stylesheet" type="text/css" href="/easyui/themes/default/easyui.css">
	<link rel="stylesheet" type="text/css" href="/easyui/themes/icon.css">
	<script type="text/javascript" src="/easyui/jquery-1.8.0.min.js"></script>
	<script type="text/javascript" src="/easyui/jquery.easyui.min.js"></script>
</head>
<body class="easyui-layout">
	<div data-options="region:'north',border:false" style="height:60px;background:#B3DFDA;padding:10px">没事写写代码，养养动物</div>
	<div data-options="region:'west',split:true,title:'Explore'" style="width:200px;padding:10px;">
		<ul id="tree"></ul>
		<div id="folder_menu" class="easyui-menu" style="width:120px;">
			<div onclick="add_folder()" data-options="iconCls:'icon-add'">add folder</div>
			<div onclick="add_file()" data-options="iconCls:'icon-add'">add file</div>
            <div class="menu-sep"></div>
			<div onclick="del_folder()" data-options="iconCls:'icon-remove'">del folder</div>
            <div class="menu-sep"></div>
		</div>
		<div id="file_menu" class="easyui-menu" style="width:120px;">
			<div onclick="edit_file()" data-options="iconCls:'icon-edit'">edit file</div>
            <div class="menu-sep"></div>
			<div onclick="del_file()" data-options="iconCls:'icon-remove'">del file</div>
            <div class="menu-sep"></div>
		</div>
	</div>
	<div data-options="region:'center',title:'Edit Area'">
        <form id="edit" style="display:none">
            <div>
            Charset:<select name="charset" class="charset"><option value="GBK">GBK</option><option value="UTF-8">UTF-8</option></select>
            <button>Save</button>
            </div>
            <div style="width:80%;height:500px;">
            <textarea style="width:100%;height:100%;" class="content"></textarea>
            </div>
        </form>
        <form id="add" style="display:none">
            <div>
            Charset:<select name="charset" class="charset"><option value="GBK">GBK</option><option value="UTF-8">UTF-8</option></select>
            FileName:<input class="filename" type="text"/>
            <button>Save</button>
            </div>
            <div style="width:80%;height:500px;">
            <textarea style="width:100%;height:100%;" class="content"></textarea>
            </div>
        </form>
    </div>
<script>
$('#tree').tree({
	url: '/operation/show',
	animate: true,
	onContextMenu: function(e,node){
		e.preventDefault();
		$(this).tree('select',node.target);
        var pos = { left: e.pageX, top: e.pageY };
        if ( node.state ) { //folder
            $('#folder_menu').menu('show',pos);
        } else {
            $('#file_menu').menu('show',pos);
        }
	}
});

function add_folder(){
    $.messager.prompt('add_folder', 'Folder name:', function(r){
        if ( !r ) { return false; }
        var tree = $('#tree');
        var node = tree.tree('getSelected');
        var path = node.id+'/'+r;
        $.get('/operation/make_path',{path:path}, function(res) {
            if ( res=='Y' ) {
                if ( node.state=='open' ) {
                    tree.tree('append', {
                        parent: (node?node.target:null),
                        data: [{ text: r ,state: 'closed', id:path}]
                    });
                }
            }else {
                alert('wrong');
            }
        });
    });
}
function del_file(){
	var node = $('#tree').tree('getSelected');
    $.messager.confirm('Sure?', 'Are you confirm this?', function(r){
        if (!r){ return ; }
        $.get('/operation/del_file', {path:node.id},function(r) {
            if ( r=='Y' ) {
                $('#tree').tree('remove', node.target);
            } else {
                alert('wrong');
            }
        });
    });
}
function del_folder(){
	var node = $('#tree').tree('getSelected');
    $.messager.confirm('Sure?', 'Are you confirm this?', function(r){
        if (!r){ return ; }
        $.get('/operation/del_folder', {path:node.id},function(r) {
            if ( r=='Y' ) {
                $('#tree').tree('remove', node.target);
            } else {
                alert('wrong');
            }
        });
    });
}
function edit_file() {
    $('#add').hide();
    var form = $('#edit');
    var node = $('#tree').tree('getSelected');
    form.attr('path', node.id);
    $.getJSON('/operation/get_file',{path:node.id}, function(r) {
        form.find('.charset option[value="'+r.charset+'"]').attr('selected',true);
        form.find('.content').val(r.content);
        form.show();
    });
}
function add_file() {
    $('#edit').hide();
    var form = $('#add');
    var node = $('#tree').tree('getSelected');
    form.attr('path', node.id);
    form.find('.charset option[value="GBK"]').attr('selected',true);
    form.find('.content').val('');
    form.find('.filename').val('');
    form.show();
}
$('#add button').click(function() {
    var form = $('#add');
    var filename = form.find('.filename').val();
    if ( !filename ) { return ; }
    var path = form.attr('path')+'/'+filename;
    var charset = form.find('.charset option:selected').val();
    var content = form.find('.content').val();
    $.post('/operation/save_file',{
        'path':path,
        'charset':charset,
        'content':content
    }, function(r) {
        if ( r=='Y' ) {
            var node = $('#tree').tree('getSelected');
            if ( node.state=='open' ) {
                $('#tree').tree('append', {
                    parent: (node?node.target:null),
                    data: [{ text: filename, id:path }]
                });
            }
            $('#add').hide();
        } else {
            alert('wrong');
        }
    });
    return false;
});
$('#edit button').click(function() {
    var form = $('#edit');
    var path = form.attr('path');
    var charset = form.find('.charset option:selected').val();
    var content = form.find('.content').val();
    $.post('/operation/save_file',{
        'path':path,
        'charset':charset,
        'content':content
    }, function(r) {
        if ( r=='Y' ) {
            alert('success');
            $('#edit').hide();
        } else {
            alert('wrong');
        }
    });
    return false;
});
$('#edit .charset').change(function() {
    var form = $('#edit');
    $.getJSON('/operation/get_file',{path:form.attr('path'), charset:form.find('.charset option:selected').val()},function(r) {
        form.find('.content').val(r.content);
    });
});
</script>
</body>
</html>
