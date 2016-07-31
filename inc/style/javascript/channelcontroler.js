class ChannelControler{
	static setChannel(name){
		this.current_channel = name;
	}
	constructor(){
		this.channelBuffer = [];
	}
	join(name, sendJoin = true){
		if(this.channelBuffer.indexOf(name) !== -1){
			show_error(language("You are allready member of the channel %s", name));
		}else{
			append(name);
		}
	}
	
	static leave(name){
		
	}
	
	append(channel_name){
		var container = document.createElement("div");
		container.className = "channel_tab";
		
		var ul = document.createElement("ul");
		
		var li = document.createElement("li");
		li.className = "tab_name";
		li.appendChild(document.createTextNode(channel_name));
		li.addEventListener("click", function(){
			console.log("Click on the channel: "+channel_name);
		});
		ul.appendChild(li);
		
		li = document.createElement("li");
		li.className = "tab_close";
		li.appendChild(document.createTextNode("x"));
		li.addEventListener("click", function(){
			console.log("Close the channel: "+channel_name);
		});
		ul.appendChild(li);
		
		container.appendChild(ul);
		document.getElementById("channel_fane").insertBefore(container, document.getElementById("channel_fane").firstChild);
	}
}