const UserList = (function(){
  function UserList(sys){
    this.sys = sys;
    this.dom = sys.gui.initUserList();
    this.users = {};
  }

  UserList.prototype.show = function(){
    this.dom.style.display = "block";
  };

  UserList.prototype.hide = function(){
    this.dom.style.display = "none";
  };

  UserList.prototype.remove = function(nick){
    if(typeof this.users[nick] === "undefined"){
      return false;
    }
    this.users[nick].remove(nick);
    delete this.users[nick];
    return true;
  };

  UserList.prototype.append = function(nick, avatar){
    this.users[nick] = new User(
      this.sys,
      this.sys.gui.appendUser(this.dom, avatar, nick),
      nick
    );
  };

  UserList.prototype.updateNick = function(old, news){
     if(typeof this.users[old] === "undefined"){
       return false;
     }

     this.users[old].updateNick(news);
     this.users[news] = this.users[old];
     delete this.users[old];
  };

  UserList.prototype.get = function(nick){
    if(typeof this.users[nick] === "undefined")
      return null;
    return this.users[nick];
  };

  return UserList;
})();
