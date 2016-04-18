var prefixCache = [];

function rand(min, max){
    Math.floor((Math.random() * max) + min);
}

var letter = "QWERTYUIOPLKJHGFDSAZXCVBNM";
var letterNumber = 0;
var number = 0;

function createPrefix(){
  if(letterNumber >= letter.length){
    letterNumber=0;
    return createPrefix();
  }

  var l = letter.charAt(letterNumber);
  number++;
  var n = number.toString();
  if(n.length == 1){
    return l+"0000"+n;
  }else if(n.length == 2){
    return l+"000"+n;
  }else if(n.length == 3){
    return l+"00"+n;
  }else if(n.length == 4){
    return l+"0"+n;
  }else if(n.length == 5){
    return l+n;
  }

  number = 1;
  letterNumber++;
  return l+"0001";
}


function join(channel, callback){
  if(typeof channel === "array"){
    channel = channel.join(",");
  }

  var prefix = createPrefix();
  prefixCache[prefix] = callback;
  send(prefix+"JOIN: "+channel;
}
