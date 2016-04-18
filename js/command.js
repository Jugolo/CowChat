function rand(min, max){
    Math.floor((Math.random() * max) + min);
}

var letter = "QWERTYUIOPLKJHGFDSAZXCVBNM";
var letterNumber = 0;
var number = 0;

function createPrefix(){
  if(letterNumber >= letter.length){
    letterNumber=0;
    return createPrefix;
  }
}
