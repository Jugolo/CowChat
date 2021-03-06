function removeNode(node){
  node.parentElement.removeChild(node);
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

function markRead(name){
  const buttom = document.getElementsByClassName("channel_buttom");
  for(var i=0;i<buttom.length;i++){
    if(buttom[i].getElementsByClassName("name")[0].innerHTML == name){
      if(buttom[i].className.split(" ").indexOf("unread") !== -1){
         var ar = buttom[i].className.split(" ");
         ar.splice(ar.indexOf("unread"), 1);
         buttom[i].className = ar.join(" ");
      }
      return;
    }
  }
}

var StarGui = {
  initUserList : function(){
    var element = document.createElement("div");
    element.className = "user-list";
    document.getElementById("ulist-container").appendChild(element);
    return element;
  },
  
  initContextContainer : function(){
    const context = document.createElement("div");
    context.className = "context-container";
    document.getElementById("pageContainer").appendChild(context);
    return context;
  },
  
  appendMessage : function(page, time, avatar, nick, message){
    if(!page.system.isFocus(page.name)){
      markUnread(page.name);//the main system do not support this!! so wee do it
    }
    
    const container = document.createElement("div");
    container.className = "line";
    
    if(time){
      const t = document.createElement("span");
      t.className = "time";
      t.innerHTML = "["+time+"]";
      container.appendChild(t);
    }

    if(avatar){
      const img = document.createElement("span");
      img.className = "message-avatar";
      img.style.backgroundImage = "url('"+avatar+"')";
      container.appendChild(img);
    }

    if(nick){
     const n = document.createElement("span");
     n.className = "nick";
     n.innerHTML = nick+":&nbsp;";
     if(nick == "Bot"){
       n.className += " bot-nick";
     }else if(this.name != "console"){
       const u = page.user.get(nick);
       if(u === null){
         page.system.getPage("console").line(time, "", "Bot", "Unknown user: "+nick);
       }else{
         if(u.isOp){
           n.className += " op";
           n.innerHTML = "@"+n.innerHTML;
         }else if(u.isVoice){
           n.className += " voice";
           n.innerHTML = "+"+n.innerHTML;
         }
       }
     }
     container.appendChild(n);
    }

    const m = document.createElement("span");
    m.className = "message";
    m.innerHTML = message;
    container.appendChild(m);

    page.context.appendChild(container);
  },
  
  appendUser : function(userlist, dom, avatar, nick){
    const container = document.createElement("div");
    container.className = "user-item";

    if(avatar){
      const a = document.createElement("span");
      a.style.backgroundImage = "url('"+avatar+"')";
      a.className = "user-list-avatar";
      container.appendChild(a);
    }

    const name = document.createElement("span");
    name.className = "nick";
    name.innerHTML = nick;
    container.appendChild(name);
    const self = this;
    name.onclick = function(){
      const my = userlist.users[myNick];
      if(!my){
        return;
      }
      var m = [];
      if(nick == myNick){
        m.push([
          function(){
            var newNick = prompt(language["writeNick"]);
            if(newNick){
              send("Bot", "/nick "+newNick);
            }
          },
          language["changeNick"]
        ]);
      }else if(my.isOp){
        m.push([
          function(){
            alert(typeof sys.currentPage());
            send(sys.currentPage().name, "/kick "+nick);
          },
          language["kick"]
        ]);
        m.push([
          function(){
            send(sys.currentPage().name, "/ban "+nick);
          },
          language["ban"]
        ]);
      }
       
      for(var i=0;i<m.length;i++){
         const item = document.createElement("div");
         item.onclick = m[i][0];
         item.innerHTML = m[i][1];
         menu.appendChild(item);
      }
      if(menu.offsetParent == null){
        menu.style.display = "block";
      }else{
        menu.style.display = "none";
        menu.innerHTML = "";
      }
    };

    const menu = document.createElement("div");
    menu.className = "menu";
    menu.style.display = "none";
    container.appendChild(menu);

    dom.appendChild(container);
    return container;
  },
  
  createButtom : function(name, system){
    var container = document.createElement("div");
    container.className = "channel_buttom";
    //the chat is two part one to the name and one to the close x
    var nameObj = document.createElement("span");
    nameObj.innerHTML = name;
    nameObj.className = 'name';
    nameObj.onclick = function(){system.selectPage(name);};
    container.appendChild(nameObj);

    if(name !== "console"){
      var close = document.createElement("span");
      close.innerHTML = " x";
      close.onclick = function(){
        system.getPage(name).remove();
      };
      container.appendChild(close);
    }
    document.getElementById("chat-top").appendChild(container);
    return container;
  },

  
  removeUserlist : function(dom){
    removeNode(dom);
  },
  
  removeContextContainer : function(dom){
    removeNode(dom);
  },
  
  showContextContainer : function(page){
    markRead(page.name);
    page.context.style.display = "block";
  },
  
  hideContextContainer : function(page){
    page.context.style.display = "none";
  },
  
  showUserContainer : function(userlist){
    userlist.dom.style.display = "block";
  },
  
  hideUserContainer : function(userlist){
    userlist.dom.style.display = "none";
  },
  
  removeUser : function(dom){
    removeNode(dom);
  },
  
  opUser : function(dom, n){
    const nick = dom.getElementsByClassName("nick")[0];
    nick.innerHTML = "@"+n;
    nick.className += " op";

  },
  
  deopUser : function(dom, n, isVoice){
    const nick = dom.getElementsByClassName("nick")[0];
    if(isVoice){
      nick.innerHTML = "+"+n;
    }else{
      nick.innerHTML = n;
    }
    var split = nick.className.split(" ");
    split.splice(split.indexOf("op"), 1);
    nick.className = split.join(" ");
  },
  
  voiceUser : function(dom, n, isOp){
    var nick = dom.getElementsByClassName("nick")[0];
    nick.className += " voice";
    if(!isOp){
      nick.innerHTML = "+"+n;
    }
  },
  
  devoiceUser : function(dom, n, isOp){
    const nick = dom.getElementsByClassName("nick")[0];
    if(!isOp){
      nick.innerHTML = n;
    }
    var split = nick.className.split(" ");
    split.splice(split.indexOf("voice"), 1);
    nick.className = split.join(" ");
  },
  
  inaktiv : function(dom){
    dom.getElementsByClassName("nick")[0].className += " inaktiv";
  },
  
  uninaktiv : function(dom){
    dom = dom.getElementsByClassName("nick")[0];
    var className = dom.className.split(" ");
    var pos;
    if((pos = className.indexOf("inaktiv")) !== -1){
      className.splice(pos, 1);
    }
    dom.className = className.join(" ");
  },
  
  updateNick : function(dom, nick, isVoice, isOp){
     dom = dom.getElementsByClassName("nick")[0];
     if(isOp){
       dom.innerHTML = "@"+nick;
     }else if(isVoice){
       dom.innerHTML = "+"+nick;
     }else{
       dom.innerHTML = nick;
     }
  }
};
