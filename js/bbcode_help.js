var smylie = [];

function bbcode(str){
  if(typeof str === "undefined"){
    return "";
  }
  for(var i=0;i<smylie.length;i++){
    while(str.indexOf(smylie[i].tag) !== -1){
      str = str.replace(smylie[i].tag, "<img src='"+smylie[i].url+"' class='smylie'>");
    }
  }
  return Bbcode.render(str);
}
