<@--INCLUDE javascript.dom--@>
function show_error(message){
	var dom = Dom.create("div");
	dom.color("red");
	dom.setPosisionAbsolute();
	dom.top("10px");
	dom.left("10px");
	dom.right("10px");
	dom.height("auto");
	dom.context(message);
	dom.text_align("center");
	dom.text_size("24px");
	document.body.appendChild(dom.object);
	setTimeout(function(){
		dom.object.parentElement.removeChild(dom.object);
	}, 5000);
}