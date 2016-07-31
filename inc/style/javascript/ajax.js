function ajax(url, post, callback){
	var ajax = new XMLHttpRequest();
	var isPost = typeof post !== "undefined";
	ajax.open((isPost ? "POST" : "GET"), url, true);
	if(isPost){
		ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajax.send(encode_post(post));
	}else{
		ajax.send();
	}
	
	ajax.onreadystatechange = function(){
		if(this.readyState == 4 && this.status == 200) {
		    callback(this.responseText);
		}
	};
}

function encode_post(data){
	var buffer = [];
	for(var key in data){
		buffer.push(encodeURIComponent(key)+"="+encodeURIComponent(data[key]));
	}
	
	return buffer.join("&");
}