function nick(to){
   send("NICK: "+to);
}

/**
 * Get the info about the user.
 * @param string channel the name of the channel 
 * @param string nick the nick of the user (Username is never used in this chat. it is only admin)
 * @param function callback to call when the result is success
 * @param function error to call when the resuslt is error
 * @return null.
 */
function userInfo(channel, nick, callback, error){
   send("INFO "+channel+": "+nick, function(respons){
      if(respons.command() == "ERROR" && typeof error !== "undefined"){
         getChannel(channel).error(respons.message());
      }else{
         //it a single string as key=vale;key=value
         var values = {};
         var part = respons.message().split(";");
         for(var i=0;i<part.length;i++){
            var value = part[i].split("=");
            values[value[0]]= value[1];
         } 
         callback(values);
      }
   });
}

function title(channel, tit){
   if(channel.indexOf("#") !== 0){
     return;
   }

   send("TITLE "+channel+": "+tit, function(respons){
      if(respons.command() == "ERROR"){
         getChannel(channel).error(language(respons.message()));
      }else{
         getChannel(channel).setTitle(respons.message());
      }
   });
}

function join(channel, error, noBuffer){
	if(channel.indexOf("#") !== 0){
		if(typeof error !== "undefined")
			error(language("A channel name should alweys start width #"));
	  return;
	}
	send("JOIN: "+channel.trim(), function(respons){
		if(respons.command() == "ERROR"){
			//ohh no some thinks dont works :(
			if(typeof error !== "undefined"){
				error(respons);
			}
		}else if(respons.command() == "JOIN"){
			appendChannel(respons.message());
		}else if(respons.command() == "TITLE"){
			getChannel(respons.channel()).setTitle(respons.message());
		}
	});
	if(typeof noBuffer !== "undefined" && noBuffer){
		sendBuffer.flush();
	}
}

function leave(name, success, error){
	if(name.indexOf("#") == -1){
		return;
	}
	
	send("LEAVE: "+name, function(respons){
		if(respons.command() == "ERROR"){
			if(typeof error != "undefined"){
				error(respons);
			}
		}else{
			if(typeof success != "undefined"){
				success(respons);
			}
		}
	});
}

function online(channel, callback, error){
   if(channel.indexOf("#") !== 0){
      return;
   }

   send("ONLINE: "+channel, function(respons){
       if(respons.command() == "ERROR" && typeof error !== "undefined"){
          error(respons.message());
       }else{
          callback(respons.message().split(","));//the message is nick,nick,nick
       }
   });
}

/**
 * Get all inaktiv in the channel
 * @param channel the channel name
 * @param callback a function to handle the list
 */
function inaktiv(channel, nick, callback){
    if(channel.indexOf("#") !== 0){
		return;
	}
	
	send("INAKTIV "+channel+": "+nick, callback);
}

function message(channel, message){
	send("MESSAGE "+channel+": "+cleanMessage(message));
}

function cleanMessage(message){
	message = message.replace(/\n/g, "[br/]");
	return message;
}

function kick(username, channel, flush){
	send("KICK "+channel+": "+username);
	if(typeof flush !== "undefined" && flush){
		sendBuffer.flush();
	}
}
