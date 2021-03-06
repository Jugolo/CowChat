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
    this.system.gui.voiceUser(this.container, this.nick, this.isOp);
    this.isVoice = true;
  };

  User.prototype.devoice = function(){
    this.system.gui.devoiceUser(this.container, this.nick, this.isOp);
    this.isVoice = false;
  };

  User.prototype.inaktiv = function(){
    this.system.gui.inaktiv(this.container);
  };

  User.prototype.uninaktiv = function(){
    this.system.gui.uninaktiv(this.container);
  };

  User.prototype.updateNick = function(nick){
    this.system.gui.updateNick(this.container, nick, this.isVoice, this.isOp);
    this.nick = nick;
  };

  return User;
})();
