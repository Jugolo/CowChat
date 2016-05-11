var channelBuffer = {};
var currentChannel = null;

function getChannel(name){
	if(typeof channelBuffer[name] == "undefined"){
		return null;
	}
	
	return channelBuffer[name];
}

function appendChannel(name){
	channelBuffer[name] = new ChannelPage(name);
	savePage(channelBuffer[name]);
}

var ChannelPage = (function(){
	function ChannelPage(name){
		this.name = name;
		this.exit = false;
                this.cache = [];
                this.users = {};
                //wee wish to get the title from here. The command from the user should be /title. To set title /title title context
                var self = this;
                title(this.name, "");
                //wee get our data :)
                online(this.name, function(users){
                  for(var i=0;i<users.length;i++){
                     self.appendUser(users[i]);
                  }
                  //wee got all commands from appendUser in buffer let send them now
                  sendBuffer.flush();
                }, function(msg){
                     self.error(msg);
                });
                //wee got all the command wee need now in the buffer let send the commands now
                sendBuffer.flush();
	}
	
        ChannelPage.prototype.updateNick = function(old, n){
           if(typeof this.users[old] !== "undefined"){
                this.users[n] = this.users[old];
                delete this.users[old];
                this.appendHTML(language("%s change nick to %s", old, n));
                if(!pageFocus(this)){
                   return;
                }
                //wee update the dom in online listso wee not show a old nick
                var dom = document.getElementsByClassName("user");
                for(var i=0;i<dom.length;i++){
                   if(dom[i].getAttribute("nick") == old){
                      var node = dom[i];
                      node.setAttribute("nick", n);
                      node.getElementsByClassName("user_nick")[0].innerHTML = n;
                      return;
                   }
                }
           }
        };

	ChannelPage.prototype.removeInaktiv = function(name){
		if(typeof this.users[name] !== "undefined" && this.users[name].inaktiv()){
			this.users[name].inaktiv(false);
			var dom = document.getElementsByClassName("user");
			for(var i=0;i<dom.length;i++){
				if(dom[i].getAttribute("nick") == name){
					dom[i].getElementsByClassName("inaktiv")[0].style.display = "none";
					return;
				}
			}
		}
	}
	
	ChannelPage.prototype.makeInaktiv = function(name){
		if(typeof this.users[name] !== "undefined"){
			this.users[name].inaktiv(true);
			var dom = document.getElementsByClassName("user");
			for(var i=0;i<dom.length;i++){
				if(dom[i].getAttribute("nick") == name){
					dom[i].getElementsByClassName("inaktiv")[0].style.display = 'block';
					return;
				}
			}
		}else{
			console.log("Unknown user in inaktive: "+name);
		}
	};

        ChannelPage.prototype.appendUser = function(user){
                var self = this;
                userInfo(this.name, user, function(info){
                   self.appendOnlineList(self.users[user] = new UserData(user, info));
                   inaktiv(self.name, user, function(respons){
                	  if(respons.message() == "YES"){
                		  self.makeInaktiv(user);
                	  } 
                   });
                   if(user == myNick){
                	   self.initLeftMenu(self.users[user]);
                   }
                   sendBuffer.flush();
               }, function(msg){
                   self.error(msg);
               });
        };
	
	ChannelPage.prototype.focus = function(){
		currentChannel = this;
		for(var nick in this.users){
			this.appendOnlineList(this.users[nick]);
		}
		
		//wee append context to the chat place
		for(var i=0;i<this.cache.length;i++){
			this.pushChat(this.cache[i]);
		}
	};
	
        ChannelPage.prototype.appendOnlineList = function(user){
            if(!pageFocus(this)){//if this channel is not on the focus wee dont add it
               return;
            }
            var html = "<div class='user' nick='"+user.nick+"'>" +
            "<h3 onclick='fane_show(this);'><span class='inaktiv'>[I]</span><span class='user_nick'>"+user.nick+"</span></h3>";
            
            document.getElementById("online").innerHTML += html+"</div>";
        };

	ChannelPage.prototype.blur = function(){
		currentChannel = null;
	};
	
	ChannelPage.prototype.title = function(){
		return this.name;
	};
	
	ChannelPage.prototype.setTitle = function(title){
		this.appendHTML("<span color='green'>Topic: "+title+"</span>")
		this.t = title;
	};
	
        ChannelPage.prototype.write = function(msg){
            function template(user, context){
               return "<span class='nick'>"+user+":</span> <span class='msg'>"+context+"</span>";
            }

            switch(msg.command()){
               case "MESSAGE":
                 this.appendHTML(template(msg.nick(), parseMsg(msg.message())));
               break;
               case "TITLE":
            	   this.setTitle(msg.message());
               break;
               case "INAKTIV":
            	   console.log("["+msg.nick()+"]");
            	   if(msg.message() == "YES"){
            		   this.makeInaktiv(msg.nick());
            		   this.appendHTML(language("%s is inaktiv", msg.nick()));
            	   }else if(msg.message() == "NO"){
            		   this.removeInaktiv(msg.nick);
            		   this.appendHTML(language("%s is no longer inaktiv", msg.nick()));
            	   }
               break;
               case "FLOOD":
            	   this.error(language("You has type message to fast. Please wait a little couple of time and try again"));
               break;
               case "NICK":
                  this.updateNick(msg.nick(), msg.messasge());
               break;
            }
        };
        
        ChannelPage.prototype.error = function(msg){
        	this.appendHTML("<span class='error'>[Error]"+language(msg)+"</span>");
        }

        ChannelPage.prototype.appendHTML = function(html){
        	var date = new Date();
            var n = "<div class='item_"+(this.cache.length%2)+" message'><span class='time'>["+controleDataNumber(date.getHours())+":"+controleDataNumber(date.getMinutes())+"]</span>"+html+"</div>";
            this.cache.push(n);
            if(pageFocus(this)){
              this.pushChat(n);
            }
        };
        
    ChannelPage.prototype.pushChat = function(msg){
    	var dom = document.getElementById("chat");
    	dom.innerHTML += msg;
    	dom.scrollTop = dom.scrollHeight;
    };

	ChannelPage.prototype.onClose = function(){
		if(!this.exit){
			leave(this.name, function(){
				delete channelBuffer[this.name];
			}, function(respons){
				//this is error
				this.write("<span style='color:red;'>[Error]"+respons.message()+"</span>");
			});
			sendBuffer.flush();
		}
	};
	
	ChannelPage.prototype.initLeftMenu = function(user){
		//title menu
		if(user.allowChangeTitle()){
			appendLeftMenu("<div onclick='changeTitle();'>"+language("Change title")+"</div>");
		}
	};
	
	return ChannelPage;
})();

function parseMsg(msg){
   //first wee take all single single block first
   msg = msg.replace(/\[(.*)\/\]/g, function(all, item){
      switch(item){
         case "br":
           return "<br>";
      }
      return all;
   });
   
   msg = msg.replace(/([a-zA-Z]*)/g, function(all, nick){
	   if(currentChannel != null){
		   if(typeof currentChannel.users[nick] !== "undefined"){
			   //this is a user nick :)
			   return "<span class='inlineNick' title='"+language("Write to %s", nick)+"' onclick='nick(\""+nick+"\");'>"+nick+"</span>";
		   }
	   }
	   return nick;
   });
   
   msg = msg.replace(/(#[a-zA-Z]*?)/g, function(all, channel){
	   
   });
   
   //wee has a lot of bb code first [i][b][url]
   var regex = /\[([a-zA-Z]*)(.*?)\](.*?)\[\/\1\]/g;
   return msg.replace(regex, function(all, identify, extra, context){
	  switch(identify){
	  case "url":
		  return "<a target='_blank' rel='nofollow' href='"+extra.substr(1)+"'>"+parseMsg(context)+"</a>";
	  case "b":
		  return "<span class='strong'>"+parseMsg(context)+"</span>";
	  case "u":
		  return "<span class='u'>"+parseMsg(context)+"</span>";
	  }
	  console.log(arg)
	  return all;
   });
}

function controleDataNumber(n){
	if(n < 10){
		return "0"+n;
	}
	
	return n;
}
