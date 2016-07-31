<@--INCLUDE javascript.phpjs--@>
function language(){
	  var lang_arg = [];
	  
	  if(typeof Language.langauge[arguments[0]] !== "undefined"){
	    lang_arg.push(Language.langauge[arguments[0]]);
	  }else{
	    lang_arg.push(arguments[0]);
	    console.log("[Language]Unknown language key: "+arguments[0]);
	  }
	  
	  if(arguments.length > 1){
	     for(var i=1;i < arguments.length;i++){
	       lang_arg.push(arguments[i]);
	     }
	  }
	  
	  return sprintf.apply(this,lang_arg);
	}

class Language{
	static init(){
		var reponsback = false;
		var self = this;
		ajax("?language=yes&respons=json", undefined, function(respons){
			if(respons == "" || respons.indexOf("error: ") !== -1){
				show_error("Error in language get: "+respons);
			}
			
			self.langauge = JSON.parse(respons);
		});
	}
}