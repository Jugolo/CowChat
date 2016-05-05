function title(channel, tit){
   if(channel.indexOf("#") !== 0){
     return;
   }

   send("TITLE "+channel": "+tit, function(respons){
      if(respons.command() = "ERROR"){
         getChannel(channel).error(language(respons.message()));
      }else{
         getChannel(channel).setTitle(respons.message());
      }
   });
}

function join(channel, error){
	if(channel.indexOf("#") !== 0){
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

function message(channel, message){
	send("MESSAGE "+channel+": "+cleanMessage(message));
}

function cleanMessage(message){
	message = message.replace(/\n/g, "[br]");
	return message;
}
