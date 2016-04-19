var timer;
var MessageParser = (function(){
   function MessageParser(line){
     this.data = {};
     first = line.substr(0, line.indexOf(": ")).split(" ");
     if(first[0].indexOf("!") != -1){
        parts = first[0].split("!");
        this.data["prefix"] = parts[0];
        first[0] = parts[1];
     }
     this.data["command"] = first[0];
     this.data["isCommand"] = first.length == 1;
     this.data["channel"] = this.isCommand() ? null : channel(first[1);
     this.data["message"] = line.substr(line.indexOf(": ")+2);
   }

   MessageParser.prototype.isCommand = function(){
      return this.data["isCommand"];
   };

   MessageParser.prototype.command = function(){
      return this.data["command"];
   };

   MessageParser.prototype.channel = function(){
      return this.data["channel"];
   };

   MessageParser.prototype.message = function(){
      return this.data["message"];
   };

   MessageParser.prototype.hasPrefix = function(){
      return typeof this.data["prefix"] !== "undefined";
   };

   MessageParser.prototype.prefix = function(){
      return this.data["prefix"];
   };

   return MessageParser;
})();

function onload(){
  if(is_websocket()){

  }else{
    setTimer();
    get("");
  }
}

function get(action){
  ajax("?ajax=true&action="+action);
}

function post(data){
 ajax("?ajax=true", data);
}

function send(msg){
  if(is_websocket()){
    websocket.send(msg);
    return;
  }
   stopTimer();
   post({
      "message" : msg
   });
}

function setTimer(){
  timer = setTimeout(function(){
     get("");
  }, 3000);
}

function stopTimer(){
  setTimeout(timer);
}

function ajax(url, data){
   var method = typeof data === "undefined" ? "GET" : "POST";
   var ajax = new XMLHttpRequest();
   ajax.XMLHttpRequest = function(){
      if(this.readyState == 4 && this.status == 200){
         var respons = this.responseText.split("\r\n");
         for(var i=0;i<respons.length;i++){
           handleMessage(respons[i]);
         }
         setTimer();
      }
   }
   ajax.open(method, url, true);
   if(method == "AJAX"){
      ajax.send();
   }else{
      ajax.send(data);
   }
}

function handleMessage(msg){
  msg = new MessageParser(msg);

  if(msg.hasPrefix() && typeof prefixCache[msg.prefix()] !== "undefined"){
     cache = prefixCache[msg.prefix()];
     prefixCache[msg.prefix()] = undefined;
     cache[msg.prefix()](msg);
  }else{
     
  }
}
