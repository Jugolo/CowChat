var sys, timeout = null;

const CowChatCommand = {
  isCommand : function(data){
    return data.message.indexOf("/") === 0;
  },
  getCommand : function(data){
    if(this.isCommand(data)){
      return data.message.split(" ")[0].substr(1);
    }
    return null;
  },
  getContext : function(data){
    if(this.isCommand(data)){
      var start = this.getCommand(data).length+1;
      if(data.message.indexOf(" ") != -1){
        return data.message.substr(start+1);
      }
    }
    return data.message;
  }
};

const System = (function(){
  function System(input, gui){
    this.callback    = {};
    this.pages       = {};
    this.input       = input;
    this.gui         = gui;
    this.current = null;

    this.validCommand = [
       "join"
    ];
  }

  System.prototype.event = function(name, callback){
    if(typeof this.callback[name] === "undefined")
      this.callback[name] = [];
    this.callback[name].push(callback);
  }

  System.prototype.trigger = function(name, data){
    if(typeof this.callback[name] === "undefined")
      return;

    for(var i=0;i<this.callback[name].length;i++){
      this.callback[name][i].apply(this, data);
    }
  };

  System.prototype.inputText = function(){
     return this.input.value;
  };

  System.prototype.setInputText = function(text){
     this.input.value = text;
  };

  System.prototype.getPage = function(name){
    if(typeof this.pages[name] === "undefined")
      return null;
    return this.pages[name];
  };

  System.prototype.currentPage = function(){
    if(typeof this.pages[this.current] !== "undefined"){
      return this.pages[this.current];
    }
    return null;
  };

  System.prototype.appendPage = function(name){
     this.createPage(name);
     send(name, "/title");
     send(name, "/online");
  };

  System.prototype.createPage = function(name){
    var page = new Page(
      name,
      this.gui.createButtom(name, this),
      new UserList(this),
      this.gui.initContextContainer(),
      this
    );
    this.pages[name] = page;
    this.selectPage(name);
    return page;
  };
  
  System.prototype.selectPage = function(name){
    if(typeof this.pages[name] === "undefined"){
      return false;
    }
    
    if(typeof this.pages[this.current] !== "undefined"){
      this.pages[this.current].hide();
    }
    
    this.pages[name].show();
    this.current = name;
    return true;
  };
  
  System.prototype.isFocus = function(name){
    return this.current == name;
  };

  return System;
})();

function startChat(input, gui){
  sys = new System(input, gui);
  input.onkeyup = function(e){
    sys.trigger("input.keyup", [e]);
  };
  sys.event("input.keyup", handleInput);
  var page = sys.createPage("console");
  connect();
}

function connect(){
    stopUpdate();
    var ajax = new XMLHttpRequest();
    var chan;
    if(sys.currentPage().name == "console"){
      chan = "";
    }else{
      chan = "&channel="+sys.currentPage();
    }
    ajax.open("GET", "?_ajax=true"+chan, true);
    ajax.onreadystatechange = onAjaxRespons;
    ajax.send();
}

function onAjaxRespons(){
  if(this.readyState == 4){
    if(this.status != 200){
      sys.getPage("console").line("", "Ajax", "", "[color=red]"+encodeURI(this.responseText)+"[/color]");
      setUpdate();
      return;
    }
    var j;
    try{
      j = JSON.parse(this.responseText);
    }catch(e){
      if(e.responseText == "login"){
        location.reload();
        return;
      }else{
        sys.getPage("console").line("","","AjaxError", "[color=red]There happend a error: "+this.responseText+"[/color]");
        setUpdate();
        return;
      }
    }
    for(var i=0;i<j.message.length;i++){
       handleResponsPart(j.message[i]);
    }
    setUpdate();
  }
}

function handleResponsPart(data){
  if(data.channel == "Bot"){
    if(CowChatCommand.isCommand(data) && handleResponsPartBotCommand(data)){
      return;
    }
  }
  
  const p = sys.getPage(data.channel == "Bot" ? "console" : data.message);
  if(!p){
    sys.getPage("console").line("", "", "Bot", "[color=red]Unknown channel "+j.message[i].channel+"[/color]");
    sys.getPage("console").line(j.message[i].time, "", j.message[i].nick, j.message[i].message);
    return;
  }
  p.onRespons(data);
}

function handleResponsPartBotCommand(data){
  switch(CowScriptCommand.getCommand(data)){
    case "ban":
      onMyBan(data);
    break;
    case "kick":
      onMyKick(data);
    break;
    case "exit":
      location.reload();
    break;
    case "join":
      sys.createPage(CowScriptCommand.getContext(data));
    break;
    case "leave":
      onLeave(data);
    break;
    case "avatar":
      document.getElementById("user-avatar").style.backgroundImage = "url('"+CowScriptCommand.getContext(data)+"')";
      sys.getPage("console").line(
             j.message[i].time,
             "",
             "Bot",
             language["onAvatar"]
           );
    break;
    default:
      return false;
  }
  return true;
}

function setUpdate(){
  timeout = setTimeout(connect, updateFrame);
}

function stopUpdate(){
  if(timeout !== null){
    clearTimeout(timeout);
  }
}

function onMyBan(data){
  var channel = CowChatCommand.getContext(data);
  var page = sys.getPage(channel);
  if(!page){
    sys.getPage("console").line(
      data.time,
      "",
      "Bot",
      "[color=red]Unknown channel: "+channel+"[/color]"
    );
    return;
  }
  page.remove();
  sys.getPage("console").line(
    data.time,
    "",
    "Bot",
    "[color=red]You are bannet from the channel "+channel+"[/color]"
  );
}

function onMyKick(data){
  var message = CowChatCommand.getContext(data);
  var pos = message.indexOf(" ");
  var nick = message.substr(0, pos);
  message = message.substr(pos+1);
  pos = message.indexOf(" ");
  var channel = message.substr(0, pos);
  message = message.substr(pos+1);
  const page = sys.getPage(channel);
  if(page == null){
    sys.getPage("console").line(data.time, "", "Bot", "[color=red]Unknown channel: "+channel+"[/color]");
  }
  page.remove();
  delete sys.pages[channel];
  sys.getPage("console").line(
    data.time,
    "",
    "Bot",
    "[color=red]"+language["myKick"].replace("%nick", nick).replace("%channel", channel).replace("%message", message)+"[/color]"
  );
}

function onLeave(time, channel){
  const page = sys.getPage(channel);
  if(page == null){
    sys.getPage("console").line(time, "", "Bot", "[color=red]Unknown channel: "+channel+"[/color]");
    return;
  }
  page.remove();
  delete sys.pages[channel];
  sys.getPage("console").line(time, "", "Bot", "[color=red]"+language["myLeave"].replace("%channel", channel)+"[/color]");
}

function send(channel, message){
  stopUpdate();
  var ajax = new XMLHttpRequest();
  ajax.open("POST", "?_ajax=true&isPost=true&channel="+encodeURIComponent(channel), true);
  ajax.onreadystatechange = onAjaxRespons;
  ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  ajax.send("message="+encodeURIComponent(message));
}

//handle input from the input o.O
function handleInput(e){
  if(e.keyCode == 13 && this.inputText().trim().length != 0){
    const text = this.inputText();

    if(text.charAt(0) == "/"){
      var command;
      if((pos = text.indexOf(" ")) != -1){
        command = text.substr(1, pos-1);
      }else{
        command = text.substr(1);
      }
      if(sys.validCommand.indexOf(command) != -1){
         send("", text);
         this.setInputText("");
         return;
      }
    }

    if(this.currentPage().name == "console"){
       this.currentPage().line("", "", "Bot", "[color=red]"+language["writeConsole"]+"[/color]");
       this.setInputText("");
       return;
    }
    
     send(this.currentPage().name, this.inputText());
    this.setInputText("");
  }
}
