// JavaScript Document
var rnd=Math.random();
 if ("https:" == location.protocol)
	var protocolPath  = 'https://';
else
	var protocolPath  = 'http://';

if (window.location.hostname == 'localhost') {
	var  path       = protocolPath +  'localhost/money/';
	var actionPath	= protocolPath +  'localhost/money/';
} else if (window.location.hostname == '192.168.1.17') {
	var  path = protocolPath +  '192.168.1.17/money/';
	var  actionPath	= protocolPath +  '192.168.1.17/money/';
}

$(document).ready(function(){
		
});
		
		
