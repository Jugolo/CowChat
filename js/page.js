function createButtom(name, onClose){
  var container = document.createElement("div");
  container.className = "channel_buttom";
  //the chat is two part one to the name and one to the close x
  var nameObj = document.createElement("span");
  nameObj.innerHTML = name;
  nameObj.className = 'name';
  nameObj.onclick = function(){selectPage(name);};
  container.appendChild(nameObj);

  if(name !== "console"){
    var close = document.createElement("span");
    close.innerHTML = " x";
    close.onclick = onClose;
    container.appendChild(close);
  }

  return container;
}

function isFocus(name){
  const buttom = document.getElementsByClassName("channel_buttom");
  for(var i=0;i<buttom.length;i++){
    if(buttom[i].getElementsByClassName("name")[0].innerHTML == name){
      return buttom[i].className.split(" ").indexOf("focus") != -1;
    }
  }
  throw "Unknown channel '"+name+"'";
}

function markUnread(name){
  const buttom = document.getElementsByClassName("channel_buttom");
  for(var i=0;i<buttom.length;i++){
    if(buttom[i].getElementsByClassName("name")[0].innerHTML == name){
      if(buttom[i].className.split(" ").indexOf("unread") == -1){
         buttom[i].className += " unread";
      }
      return;
    }
  }
}

function selectPage(name){
  const buttom = document.getElementsByClassName("channel_buttom");
  for(var i=0;i<buttom.length;i++){
    if(buttom[i].getElementsByClassName("name")[0].innerHTML == name){
      if(buttom[i].className.split(" ").indexOf("focus") != -1){
        return true;
      }
      buttom[i].className += " focus";
      sys.getPage(name).show();
      sys.trigger("page.onfocus", [name, sys.getPage(name)]);
      if(buttom[i].className.split(" ").indexOf("unread") !== -1){
         var ar = buttom[i].className.split(" ");
         ar.splice(ar.indexOf("unread"), 1);
         buttom[i].className = ar.join(" ");
      }
    }else if(buttom[i].className.split(" ").indexOf("focus") !== -1){
      var ar = buttom[i].className.split(" ");
      ar.splice(ar.indexOf("focus"), 1);
      buttom[i].className = ar.join(" ");
      const n = buttom[i].getElementsByClassName("name")[0].innerHTML;
      sys.getPage(n).hide();
      sys.trigger("page.unfocus", [n, sys.getPage(n)]);
    }
  }
}
