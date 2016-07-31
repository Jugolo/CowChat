class Dom{
	static create(name){
		return new Dom(document.createElement(name));
	}
	
	static getId(name){
		return new Dom(document.getElementById(name));
	}
	
	constructor(dom){
		this.dom = dom;
	}
	
	get object(){
		return this.dom;
	}
	
	color(color){
		this.dom.style.color = color;
	}
	
	setPosisionAbsolute(){
		this.dom.style.position = "absolute"
	}
	
	top(px){
		this.dom.style.top = px;
	}
	
	left(px){
		this.dom.style.left = px;
	}
	
	right(px){
		this.dom.style.right = px;
	}
	
	height(px){
		this.dom.style.height = px;
	}
	
	context(context){
		this.dom.innerHTML = context;
	}
	
	text_align(where){
		this.dom.style.textAlign = where;
	}
	
	text_size(size){
		this.dom.style.fontSize = size;
	}
	
	attribute(name){
		return this.dom.getAttribute(name);
	}
}