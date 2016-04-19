var timer;
var MessageParser = (function(){
   function MessageParser(line){

   }

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
}
