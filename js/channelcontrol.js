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

        ChannelPage.prototype.appendUser = function(user){
                var self = this;
                userInfo(this.name, user, function(info){
                   self.users[user] = new UserData(info);
                   //wee push the user in online list :)
                   self.appendOnlineList(user);
               }, function(msg){
                   self.error(msg);
               });
        };
	
	ChannelPage.prototype.focus = function(){
		currentChannel = this;
	};
	
        ChannelPage.prototype.appendOnlineList = function(nick){
            if(!pageFocus(nick)){//if this channel is not on the focus wee dont add it
               return;
            }
            var html = "<div class='user' nick='"+nick+"'>" +
            "<div class='nick'>"+nick+"</nick>";
            
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
               var date = new Date();
               return "<span class='time'>["+date.getHours()+":"+date.getMinutes()+"]</span><span class='user'>"+user+":</span> <span class='msg'>"+context+"</span>";
            }

            switch(msg.command()){
               case "MESSAGE":
                 this.appendHTML(template(msg.nick(), parseMsg(msg.message())));
               break;
            }
        };

        ChannelPage.prototype.appendHTML = function(html){
            var n = "<div class='item_"+(this.cache.length%2)+" message'>"+html+"</div>";
            this.cache.push(n);
            if(pageFocus(this)){
              document.getElementById("chat").innerHTML +=n;
            }
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
	
	return ChannelPage;
})();

function parseMsg(msg){
   //first wee take all single single block first
   msg = msg.replace(/\[(.*)\]/g, function(all, item){
      switch(item){
         case "br":
           return "\n";
      }
      return all;
   });
   
   return msg;
}
