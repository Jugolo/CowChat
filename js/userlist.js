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
      const my = self.users[myNick];
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

    this.dom.appendChild(container);
    this.users[nick] = new User(
      this.sys,
      this.sys.appendUser(this.dom, avatar, nick),
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
