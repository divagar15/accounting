// JavaScript Document
$(document).ready(function(){
	

});

function isNumberKey(evt) {
	var keyCode = (evt.which?evt.which:(evt.keyCode?evt.keyCode:0))
	if ((keyCode == 8) || (keyCode == 46) || (keyCode == 37) || (keyCode == 9) ) return true;
	if ((keyCode < 48) || (keyCode > 57)) return false;
	return true;
}