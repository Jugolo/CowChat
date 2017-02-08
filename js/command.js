//<-Plugin handler
commands["pluginlist"] = function(data){
  var plugin = data.substr(12).split(",");
  if(plugin.length == 0){
    this.line(
      data.time,
      "",
      "Bot",
      "[color=red]"+language["noPlugin"]+"[/color]"
      );
    return;
  }
}
//->
commands["kick"] = function(data){
  var message = data.message.substr(6);
  var pos = message.indexOf(" ");
  var nick    = message.substr(0, pos);
  message = message.substr(pos+1);
  pos = message.indexOf(" ");
  var kicked = message.substr(0, pos);
  if(!this.user.remove(kicked)){
     sys.getPage("console").line(data.time, "", "Bot", "[color=red]Unkown user: "+kicked+"[/color]");
     return;
  }
  this.line(
    data.time,
    "",
    "Bot",
    "[color=red]"+language["onKick"].replace("%nick", nick).replace("%kicked", kicked).replace("%message", message.substr(pos+1))+"[/color]"
  );
};

commands["unignore"] = function(data){
  this.line(
    data.time,
    "",
    "Bot",
    "[color=green]"+language["onUnignore"].replace("%nick", data.message.substr(10))+"[/color]"
  );
}

commands["ignore"] = function(data){
  this.line(
    data.time,
    "",
    "Bot",
    "[color=green]"+language["onIgnore"].replace("%nick", data.message.substr(8))+"[/color]"
  );
};

commands["leave"] = function(data){
   const nick = data.message.substr(7);
   if(!this.user.remove(nick)){
     sys.getPage("console").line(data.time, "", "Bot", "[color=red]Unkown user: "+nick+"[/color]");
     return;
   }

   this.line(data.time, "", "Bot", "[color=red]"+language["onLeave"].replace("%nick", nick)+"[/color]");
};

commands["msg"] = function(data){
  const clean = data.message.substr(5);
  const to = clean.substr(0, clean.indexOf(" "));
  const msg = clean.substr(clean.indexOf(" ")+1);
  this.line(data.time, data.avatar, "", " [color=yellow]("+data.nick+"->"+to+")"+msg+"[/color]");
};

commands["online"] = function(data){
  var uPart = data.message.substr(8).split(" ");
  for(var ui=0;ui<uPart.length;ui++){
    var part = uPart[ui].split("|");
    var inaktiv = false;
    var op = false;
    var voice = false;
    if(part[1].indexOf("[i]") == 0){
      inaktiv = true;
      part[1] = part[1].substr(3);
    }
    if(part[1].charAt(0) == "@"){
      op = true;
      part[1] = part[1].substr(1);
    }else if(part[1].charAt(0) == "+"){
      voice = true;
      part[1] = part[1].substr(1);
    }
    var user = this.user.get(part[1].trim());
    if(user){
      continue;
    }
    
    this.user.append(part[1].trim(), part[0]);
    if(op){
      this.user.get(part[1].trim()).op();
    }else if(voice){
      this.user.get(part[1].trim()).voice();
    }

    if(inaktiv){
      this.user.get(part[1].trim()).inaktiv();
    }
  }
};

commands["join"] = function(data){
  const nick = data.message.substr(6);
  if(this.user.get(nick))
    return;
  this.line(data.time, "", "Bot", "[color=green]"+language["newJoin"].replace("%nick", data.message.substr(6))+"[/color]");
  this.user.append(data.message.substr(6), data.avatar);
};

commands["title"] = function(data){
  this.line(data.time, "", "", "[color=green]"+language["onTitle"].replace("%title", data.message.substr(7))+"[/color]");
};

commands["inaktiv"] = function(data){
  const nick = data.message.substr(9);
  this.line(
     data.time,
     "",
     "Bot",
     "[color=red]"+language["inaktiv"].replace("%nick", nick)+"[/color]"
  );
  this.user.get(nick).inaktiv();
};

commands["notInaktiv"] = function(data){
   const nick = data.message.substr(12);
   this.line(
      data.time,
      "",
      "Bot",
      "[color=green]"+language["uninaktiv"].replace("%nick", nick)+"[/color]"
   );
   this.user.get(nick).uninaktiv();
};

commands["nick"] = function(data){
   const nick = data.message.substr(6).split(" ");
   const user = this.user.get(nick[0]);
   if(user == null){
     this.line(data.time, "", "Bot", "Unknown user '"+nick[0]+"'");
     return;
   }
   this.user.updateNick(nick[0], nick[1]);
   this.line(
       data.time,
       "",
       "Bot",
       "[color=green]"+language["updateNick"].replace("%old", nick[0]).replace("%new", nick[1])+"[/color]"
   );
};

commands["error"] = function(data){
   const errorMsg = data.message.substr(7);
   if(typeof language["error"][errorMsg] === "undefined"){
       this.line(data.time, "", "Bot", "[color=red]"+language["unknown_error"]+"[/color]");
       return;
   }
   this.line(data.time, "", "Bot", "[color=red]"+language["error"][errorMsg]+"[/color]");
};

commands["mode"] = function(data){
  const mode = data.message.substr(6).split(" ");
  var user;
  if((user = this.user.get(mode[0])) != null){
     switch(mode[1]){
        case "+o":  
           user.op();
           this.line(
              data.time,
              "",
              "Bot",
              "[color=green]"+language["op"].replace("%nick", mode[0])+"[/color]"
           );
        break;
        case "-o":
           user.deop();
           this.line(
             data.time,
             "",
             "Bot",
             "[color=red]"+language["deop"].replace("%nick", mode[0])+"[/color]"
           );
        break;
        case "+v":
           user.voice();
           this.line(
             data.time,
             "",
             "Bot",
             "[color=green]"+language["voice"].replace("%nick", mode[0])+"[/color]"
           );
        break;
        case "-v":
           user.devoice();
           this.line(
             data.time,
             "",
             "Bot",
             "[color=red]"+language["devoice"].replace("%nick", mode[0])+"[/color]"
           );
        break;
        case "+b":
          this.user.remove(mode[0]);
          this.line(
            data.time,
            "",
            "Bot",
            "[color=red]"+language["ban"].replace("%nick", mode[0])+"[/color]"
          );
        break;
        case "-b":
          this.line(
            data.time,
            "",
            "Bot",
            "[color=green]"+language["unban"].replace("%nick", mode[0])+"[/color]"
          );
        brak;
        default:
           this.line(data.time, "", "Bot", "[color=red]Unknown mode "+mode[1]+"[/color]");
     }
  }else{
      this.line(data.time, "", "Bot", "[color=red]Unknown user: "+mode[0]+"[/color]");
  }
}
