const User = (function(){
  function User(system, container, nick){
    this.container = container;
    this.isOp = false;
    this.isVoice = false;
    this.nick = nick;
    this.system = system;
  }

  User.prototype.remove = function(){
    this.system.gui.removeUser(this.container);
  };

  User.prototype.op = function(){
    this.system.gui.opUser(this.container, this.nick);
    this.isOp = true;
  };

  User.prototype.deop = function(){
    this.system.gui.deopUser(this.container, this.nick, this.isVoice);
    this.isOp = false;
  };

  User.prototype.voice = function(){
    this.system.gui.voiceUser(this.container, this.nick);
    var nick = this.container.getElementsByClassName("nick")[0];
    nick.className += " voice";
    if(!this.isOp){
      nick.innerHTML = "+"+this.nick;
    }
    this.isVoice = true;
  };

  User.prototype.devoice = function(){
    const nick = this.container.getElementsByClassName("nick")[0];
    if(!this.isOp){
      nick.innerHTML = this.nick;
    }
    var split = nick.className.split(" ");
    split.splice(split.indexOf("voice"), 1);
    nick.className = split.join(" ");
    this.isVoice = false;
  };

  User.prototype.inaktiv = function(){
    this.container.getElementsByClassName("nick")[0].className += " inaktiv";
  };

  User.prototype.uninaktiv = function(){
    const dom = this.container.getElementsByClassName("nick")[0];
    var className = dom.className.split(" ");
    var pos;
    if((pos = className.indexOf("inaktiv")) !== -1){
      className.splice(pos, 1);
    }
    dom.className = className.join(" ");
  };

  User.prototype.updateNick = function(nick){
     const dom = this.container.getElementsByClassName("nick")[0];
     if(this.isOp){
       dom.innerHTML = "@"+nick;
     }else if(this.isVoice){
       dom.innerHTML = "+"+nick;
     }else{
       dom.innerHTML = nick;
     }
     this.nick = nick;
  };

  return User;
})();
