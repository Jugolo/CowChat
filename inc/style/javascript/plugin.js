class Plugin{
	static init(){
		this.list = {};
	}
	
	static add(name, callback){
		if(typeof this.list[name] === "undefined"){
			this.list[name] = [];
		}
		
		this.list[name].push(callback);
	}
	
	static call(){
		if(arguments.length !== 0){
			
		}else{
			show_error("Plugin.call must take at least 1 agument");
		}
	}
}

Plugin.init();