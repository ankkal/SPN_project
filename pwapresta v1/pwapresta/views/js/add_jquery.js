var myScript = document.currentScript,
mySrc = myScript.getAttribute('src');
var url = mySrc.replace("add_jquery.js", "jquery.js"); ;

if(!window.jQuery)
{
	document.write("<script type='text/javascript' src='"+url+"'></script>");
}
