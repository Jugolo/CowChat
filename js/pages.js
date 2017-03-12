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
     this.system.gui.showContextContainer(this);
  };

  Page.prototype.hide = function(){
     this.user.hide();
     this.system.gui.hideContextContainer(this);
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
    this.system.gui.appendMessage(
      this,
      time,
      avatar,
      nick,
      bbcode(message)
      );
  };

  return Page;
})();
