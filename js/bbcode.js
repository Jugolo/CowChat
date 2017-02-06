var smylie = [];

var Bbcode = (function(){
  function isLetter(state){
    var i = state.s.charCodeAt(state.i);
    return i >= 97 && i <= 122;
  }

  function controleString(c, obj){
    var buffer = "";
    for(var i=0;i<c.length;i++){
      if(c.charAt(i) == "\n" && obj.lineBreak){
        buffer += "<br>";
      }else{
        buffer += c.charAt(i);
      }
    }
    return buffer;
  }
  
  function getCode(state){
    var buffer = "";
    while(isLetter(state)){
      buffer += state.s.charAt(state.i);
      state.i++;
    }
    
    return buffer;
  }

  function parseBlock(state){
    state.i++;
    var code = { code : getCode(state)};
    if(state.s.charAt(state.i) == "="){
       code.attribute = "";
       for(state.i++;state.i<state.s.length;state.i++){
          if(state.s.charAt(state.i) == "]"){
            break;
          }
          code.attribute += state.s.charAt(state.i);
       }
    }
    if(state.s.charAt(state.i) !== "]"){
      return null;
    }
    
    return code;
  }

  function evulatebb(state, obj){
    //if this is a [/tag] just return here. 
    if(state.s.charAt(state.i+1) == "/"){
      return "[";
    }
    var i=state.i;
    var start = parseBlock(state);
    if(start === null || typeof obj.bbcode[start.code] === "undefined"){
      return state.s.substr(i, (state.i-i)+1);
    }
    
    var buffer = "";
    for(state.i++;state.i<state.s.length;state.i++){
      if(state.s.charAt(state.i) == "["){
        if(state.s.charAt(state.i+1) == "/"){
          state.i++;
          var end = parseBlock(state);
          if(end != null && end.code == start.code){
            return obj.bbcode[start.code](start.attribute, buffer);
          }
          buffer += "[/"+end.code+"]";
        }else{
          buffer += evulatebb(state, obj);
        }
      }else{
        buffer += controleString(state.s.charAt(state.i), obj);
      }
    }
   
    return "["+start.code+(typeof start.attribute === "undefined" ? "" : "="+start.attribute)+"]"+buffer;
  }
  
  function renderString(state, obj){
     var buffer = "";
     for(;state.i<state.s.length;state.i++){
       if(state.s.charAt(state.i) == "["){
         buffer += evulatebb(state, obj);
         continue;
       }
       buffer += controleString(state.s.charAt(state.i), obj);
     }
     return buffer;
  }
  
  function Bbcode(){
    this.lineBreak = false;
    this.bbcode = {};
    this.pushCode("url", function(attribute, str){
       return "<a href='"+(typeof attribute === "undefined" ? "#" : attribute)+"' target='_blank'>"+str+"</a>";
    });

    this.pushCode("color", function(attribute, str){
       return "<span style='color: "+(typeof attribute === "undefined" ? "black" : attribute)+"'>"+str+"</span>";
    });
  }

  Bbcode.prototype.pushCode = function(name, func){
    this.bbcode[name] = func;
  };
  
  Bbcode.prototype.render = function(str){
    return renderString({
       s : str,
       i : 0
    }, this);
  };
  
  return new Bbcode();
})();

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
