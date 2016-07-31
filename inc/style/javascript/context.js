class Context{
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
			//find out if the menu should go up or down (wee alwey trying to show it down)
			var rect = dom.getBoundingClientRect();
			console.log(rect.bottom);
			console.log(document.documentElement.clientHeight);
			//wee calculate the bottom point of the list
			if((rect.bottom+20) > document.documentElement.clientHeight){
				console.log("Up");
				//push the posision to the top and up
				console.log("offsetTop: "+obj.offsetTop);
				console.log("bottom: "+dom.style.bottom);
				dom.style.bottom = (obj.offsetTop + 10)+"px";
				dom.style.left   = obj.getBoundingClientRect().left+"px";
			}else{
				console.log("Down");
				console.log((obj.offsetBottom + 10)+"px");
				dom.style.top = (obj.offsetBottom + 10)+"px";
				dom.style.left = obj.getBoundingClientRect().left+"px";
			}
			
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