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
		this.focus();
	}
	
	ChannelPage.prototype.focus = function(){
		currentChannel = this;
	};
	
	ChannelPage.prototype.blur = function(){
		currentChannel = null;
	};
	
	ChannelPage.prototype.title = function(){
		return this.name;
	};
	
	ChannelPage.prototype.setTitle = function(title){
		this.write("<span color='green'>Topic: "+title+"</span>")
		this.t = title;
	};
	
        ChannelPage.prototype.write = function(msg){
            function template(user, context){
               var date = new Date();
               return "<div class='msg'><span class='time'>["+date.getHours()+":"+date.getMinutes()+"]</span><span class='user'>"+user+":</span> <span class='message'>"+msg+"</span></div>";
            }

            switch(msg.command()){
               case "MESSAGE":
                 this.appendHtml(template(msg.nick(), parseMsg(msg)));
               break;
            }
        };

        ChannelPage.prototype.appendHTML = function(html){
          
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
