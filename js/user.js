const User = (function(){
  function User(container, nick){
    this.container = container;
    this.isOp = false;
    this.isVoice = false;
    this.nick = nick;
  }

  User.prototype.remove = function(){
     removeNode(this.container);
  };

  User.prototype.op = function(){
    const nick = this.container.getElementsByClassName("nick")[0];
    nick.innerHTML = "@"+this.nick;
    nick.className += " op";
    this.isOp = true;
  };

  User.prototype.deop = function(){
    const nick = this.container.getElementsByClassName("nick")[0];
    if(this.isVoice){
      nick.innerHTML = "+"+this.nick;
    }else{
      nick.innerHTML = this.nick;
    }
    var split = nick.className.split(" ");
    split.splice(split.indexOf("op"), 1);
    nick.className = split.join(" ");
    this.isOp = false;
  };

  User.prototype.voice = function(){
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
