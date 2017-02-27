function removeNode(node){
  node.parentElement.removeChild(node);
}

function markUnread(name){
  
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
    if(page.system.isFocus(page.name)){
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
  };
  
  removeUserlist : function(dom){
    removeNode(dom);
  },
  
  removeContextContainer : function(dom){
    removeNode(dom);
  },
  
  showContextContainer : function(dom){
    dom.style.display = "block";
  },
  
  hideContextContainer : function(dom){
    dom.style.display = "none";
  }
};
