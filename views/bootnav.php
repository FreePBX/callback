
<div id="toolbar-cbbnav">
  <a href="?display=callback" class="btn btn-default"><i class="fa fa-list"></i>&nbsp;<?php echo _("List Callbacks")?></a>
  <a href="?display=callback&view=form" class="btn btn-default"><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add Callback")?></a>
</div>
<table id="callbackgrid"
		data-url="ajax.php?module=callback&command=getJSON&jdata=grid"
		data-toolbar="#toolbar-cbbnav"
		data-cache="false"
		data-toggle="table"
		data-search="true"
		data-pagination ="true"
		class="table table-striped">
	<thead>
		<tr>
			<th data-field="callback_id" data-formatter="cbrnavFormatter"><?php echo _("Callback")?></th>
		</tr>
	</thead>
</table>
<script type="text/javascript">
  function cbrnavFormatter(v,r){
    return '<a href="?display=callback&view=form&itemid='+v+'">'+r['description']+'</a>'
  }
</script>
