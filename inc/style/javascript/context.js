class Context{
	static listener(){
		window.onclick = function(event) {
			 if(!event.target.matches('.wait_close')) {
				 
			 }
		}
	}
	static trigger(obj){
		if(obj.id === ""){
			console.log("A context menu button should has a id");
			return;
		}
		
		var dom = document.querySelector("[for='"+obj.id+"']");
		if(this.isVisbele(dom)){
			dom.style.display = "none";
			console.log("It can be seen");
		}else{
			dom.style.display = "block";
			dom.style.zIndex = 2;
			dom.style.removeProperty("bottom");
			dom.style.removeProperty("top");
			dom.className += " wait_close";
			// find out if the menu should go up or down (wee alwey trying to
			// show it down)
			var rect = dom.getBoundingClientRect();
			var button = obj.getBoundingClientRect();
			console.log(button.bottom);
			console.log(button);
			dom.style.top = ((button.bottom - button.height - rect.height))+"px";
			dom.style.left   = button.left+"px";
			dom.style.border = "1px solid black";
			dom.style.position = "absolute";
			dom.style.width = "auto";
			dom.style.height = "auto";
		}
	}
	
	static isVisbele(dom){
		return window.getComputedStyle(dom, null).getPropertyValue('display') === "block";
	}
}