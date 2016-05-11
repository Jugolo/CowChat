var callbackBuffer = {};
var letterNumber = 0;
var letter = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
var number = 1;
var senderTimer = null;

var SendBuffer = (function(){
  function SendBuffer(){
	  this.buffer = [];
  }
  
  SendBuffer.prototype.send = function(msg){
	  this.buffer.push(msg);
  };
  
  SendBuffer.prototype.flush = function(){
	if(senderTimer != null){
		clearTimeout(senderTimer);
	}
	if(this.buffer.length != 0){
		post("?ajax=true", {"message" : this.buffer.join("\r\n")}, function(){
			if(sendType == "AJAX"){
				senderTimer = setTimeout(function(){
					get("?ajax=true");
				}, 2500);
			}
		});
		this.buffer = [];
	}  
  };
  
  return SendBuffer;
})();

var sendBuffer = new SendBuffer();

function prefix(){
	if(letterNumber >= letter.length){
		letterNumber=0;
	}
	var n = number.toString();
	number++;
	if(n.length == 5){
		return letter.charAt(letterNumber)+n;
	}else if(n.length == 4){
		return letter.charAt(letterNumber)+"0"+n;
	}else if(n.length == 3){
		return letter.charAt(letterNumber)+"00"+n;
	}else if(n.length == 2){
		return letter.charAt(letterNumber)+"000"+n;
	}else if(n.length == 1){
		return letter.charAt(letterNumber)+"0000"+n;
	}
	
	number = 1;
	letterNumber++;
	return prefix();
}

function post(url, data, callback){
	return ajax(url, data, callback);
}

function get(url){
	if(senderTimer != null){
		clearTimeout(senderTimer);
	}
	return ajax(url, undefined, function(){
		senderTimer = setTimeout(function(){get("?ajax=true")}, 2500);
	});
}

function ajax(url, data, callback){
	var a = new XMLHttpRequest();
	var method = typeof data == "undefined" ? "GET" : "POST";
    a.open(method, url, true);
	if(method == "GET"){
		a.send()
	}else{
		a.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		function render(d){
			var parts = [];
			for(key in d){
				parts.push(encodeURIComponent(key)+"="+encodeURIComponent(d[key]));
			}
			
			return parts.join("&");
		};
		a.send(render(data));
	}
    a.onreadystatechange = function(){
		if(this.readyState == 4 && this.status == 200){
			var parts = this.responseText.split("\r\n");
			for(var i=0;i<parts.length;i++){
				//controle if this is login message
				if(parts[i] == "LOGIN: REQUID"){
					location.assign("?login=false&page=login.html&defender=enabled");
					return;
				}
				onIncomming(parts[i]);
			}
			
			if(typeof callback !== "undefined"){
				callback();
			}
		}
	}
}

function send(msg, callback){
	if(typeof callback !== "undefined"){
		var p = prefix();
		msg = p+"!"+msg;
		callbackBuffer[p] = callback;
	}
	
	if(sendType == "AJAX"){
		sendBuffer.send(msg);
	}
}

var MessageParser  = (function(){
	function MessageParser(msg){
		this.data = {};
		var first = msg.substr(0, msg.indexOf(": ")).split(" ");
		if(first[0].indexOf("!") != -1){
			var parts = first[0].split("!");
			this.data["prefix"] = parts[0];
			first[0] = parts[1];
		}

                //some respons from the server has a user nick (never username)
                if(first[0].indexOf("@") != -1){
                        parts = first[0].split("@");
                        this.data["nick"] = parts[0];
                        first[0] = parts[1];
                }
		
		this.data["command"] = first[0];
		this.data["channel"] = first.length <= 1 ? null : first[1];
		this.data["message"] = msg.substr(msg.indexOf(": ")+2);
	}
	
	MessageParser.prototype.hasPrefix = function(){
		return typeof this.data["prefix"] !== "undefined";
	};
	
	MessageParser.prototype.prefix = function(){
		return this.data["prefix"];
	};

        MessageParser.prototype.nick = function(){
                return this.data["nick"];
        };
	
	MessageParser.prototype.command = function(){
		return this.data["command"];
	};
	
	MessageParser.prototype.channel = function(){
		return this.data["channel"];
	};

        MessageParser.prototype.hasChannel = function(){
                return this.channel() != null;
        };
	
	MessageParser.prototype.message = function(){
		return this.data["message"];
	};
	
	return MessageParser;
})();

function onIncomming(msg){
	msg = new MessageParser(msg);
	if(msg.hasPrefix() && typeof callbackBuffer[msg.prefix()] !== "undefined"){
		var buffer = callbackBuffer[msg.prefix()];
		delete callbackBuffer[msg.prefix()];
		buffer(msg);
	}else{
		if(msg.hasChannel()){
                   var found = renderPage(function(page){
                      if(msg.channel() == page.title()){
                           page.write(msg);
                           return true;
                      }
                      return false;
                   });
                }else{
                   switch(msg.command()){
                      case "NICK":
                        onNick(msg);
                      break;
                   }
                }
	}
}

function onNick(msg){
     //if the old nick this user update 'myNick'
     if(msg.nick() == myNick){
        myNick = msg.message();
     }

     for(var i=0;i<channelBuffer.length;i++){
        channelBuffer[i].updateNick(msg.nick(), msg.message());
     }
}
