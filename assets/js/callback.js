var theForm = document.edit;
theForm.description.focus();

function edit_onsubmit() {
	setDestinations(edit,1);
	
	defaultEmptyOk = false;
		var sizeDisplayName = 50;	
	
        	if (!isCorrectLength(theForm.description.value, sizeDisplayName))
        	        return warnInvalid(theForm.description, _('The callback description provided is too long.'));
	if (!isAlphanumeric(theForm.description.value))
		return warnInvalid(theForm.description, _("Please enter a valid Description"));
		
	if (!validateDestinations(edit,1,true))
		return false;
	
	return true;
}
function linkFormatter(value){
	var html = '<a href="?display=callback&view=form&itemid='+value+'"><i class="fa fa-pencil"></i></a>';
	html += '&nbsp;<a href="?display=callback&action=delete&itemid='+value+'" class="delAction"><i class="fa fa-trash"></i></a>';    
	return html;
}