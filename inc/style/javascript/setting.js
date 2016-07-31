class Setting{
	static init(){
		var dom = document.getElementById("setting_but");
		//make on mouse over so wee can change diffrence thinks
		dom.addEventListener("mouseover", Setting.mouseover);
		dom.addEventListener("mouseout", function(dom){
			dom.target.style.backgroundColor = "#ffffff";
			document.getElementById("setting_but_img").style.backgroundColor = "#ffffff";
		});
		dom.addEventListener("click", Setting.onClick);
		this.status = "open";
	}
	
	static open(){
		var fane = document.getElementById("setting_fane");
		var cFane = document.getElementById("channel_fane");
		var context = document.getElementById("setting_context");
		if(fane.style.width === ""){
			fane.style.width = "40px";
		}
		if(context.style.width === ""){
			context.style.width = "0px";
		}
		this.status = "open";
		document.getElementById("setting_but").className = "open";
		var self = this;
		
		var callback = function(){
			//if the fane is more end 240px stop wee are in full width
			var width = parseInt(fane.style.width);
			if(width >= 241 || self.status != "open"){
				document.getElementById("setting_but").className = "open";
				return;
			}
			
			fane.style.width = (width+1)+"px";
			cFane.style.right = (width+1)+"px";
			if(width+1 != 41){
				context.style.width = (parseInt(context.style.width)+1)+"px";
			}
			setTimeout(callback, 1)
		}
		
		setTimeout(callback, 1);
	}
	
	static close(){
		var fane = document.getElementById("setting_fane");
		var cFane = document.getElementById("channel_fane");
		var context = document.getElementById("setting_context");
		this.status = "close";
		var self = this;
		var callback = function(){
			//if the fane is more end 240px stop wee are in full width
			var width = parseInt(fane.style.width);
			if(width <= 40 || self.status != "close"){
				document.getElementById("setting_but").className = "close";
				return;
			}
			
			fane.style.width = (width-1)+"px";
			cFane.style.right = (width-1)+"px";
			context.style.width = (parseInt(context.style.width)-1)+"px";
			setTimeout(callback, 2)
		}
		
		setTimeout(callback, 2);
	}
	
	static onClick(){
		if(Setting.isOpen()){
			Setting.close();
		}else{
			Setting.open();//open the settign fane and show the context
		}
	}
	
	static mouseover(){
		//first wee need to find out if the button is open or close
		if(Setting.isOpen()){
			document.getElementById("setting_but").style.backgroundColor = "#ff0000";
			document.getElementById("setting_but_img").style.backgroundColor = "#ff0000";
		}else{
			document.getElementById("setting_but").style.backgroundColor = "#66ff66";
			document.getElementById("setting_but_img").style.backgroundColor = "#66ff66";
		}
	}
	
	static isOpen(){
		return document.getElementById("setting_but").className.split(" ").indexOf("open") !== -1;
	}
}