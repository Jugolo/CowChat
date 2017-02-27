var commands = {};

const Page = (function(){
  function Page(name, buttom, user, context, system){
    this.buttom = buttom;
    this.user = user;
    this.name = name;
    this.context = context;
    this.system = system;
  }

  Page.prototype.remove = function(){
    if(isFocus(this.name)){
      this.system.selectPage("console");
    }
    
    this.system.gui.removeContextContainer(this.context);
    this.system.gui.removeUserlist(this.user.dom);
    removeNode(this.buttom);
  };

  Page.prototype.show = function(){
     this.user.show();
     this.system.gui.showContextContainer(this.context);
  };

  Page.prototype.hide = function(){
     this.user.hide();
     this.system.gui.hideContextContainer(this.context);
  }

  Page.prototype.onRespons = function(data){
    if(data.message.charAt(0) == "/"){
      this.onCommand(data);
      return;
    }

    this.line(data.time, data.nick == "Bot" ? "" : data.avatar, data.nick, data.message);
  };

  Page.prototype.onCommand = function(data){
    const key = data.message.split(" ")[0].substr(1);
    if(typeof commands[key] === "function"){
      commands[key].apply(this, [data]);
    }else{
      this.line(data.time, "", "Bot", "[color=red]Unknown command /"+key+"[/color]");
    }
  }

  Page.prototype.line = function(time, avatar, nick, message){
    if(!isFocus(this.name)){
      markUnread(this.name);
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
      //added in version 1.1. If there is a error to load the image hide this image
      img.onerror = function(){
        removeNode(img);
      };
      container.appendChild(img);
    }

    if(nick){
     const n = document.createElement("span");
     n.className = "nick";
     n.innerHTML = nick+":&nbsp;";
     if(nick == "Bot"){
       n.className += " bot-nick";
     }else if(this.name != "console"){
       const u = this.user.get(nick);
       if(u === null){
         sys.getPage("console").line(time, "", "Bot", "Unknown user: "+nick);
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
    m.innerHTML = bbcode(message);
    container.appendChild(m);

    this.context.appendChild(container);
  };

  return Page;
})();
