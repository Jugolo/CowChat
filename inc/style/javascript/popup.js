class Popup{
	static show(){
		document.getElementById("popup").style.display="block";
	}
	
	static close(){
		document.getElementById("popup").style.display="none";
		this.unset();
	}
	
	static unset(){
		this.title("");
		this.getContextDom().innerHTML = "";
	}
	
	static title(title){
		document.getElementById("popup_title").innerHTML = title;
	}
	
	static setSize(width, height){
		var dom = document.getElementById("popup");
	    dom.style.width = width+"px";
	    dom.style.height = height+"px";
	    dom.style.margin = (-(height/2))+"px 0 0 "+(-(width/2))+"px";
	}
	
	static addContext(context){
		document.getElementById("popup_context").innerHTML += context;
	}
	
	static getContextDom(){
		return document.getElementById("popup_context");
	}
}