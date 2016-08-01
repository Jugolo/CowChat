"use strict";
<@--INCLUDE javascript.command--@>
<@--INCLUDE javascript.channelcontroler--@>
<@--INCLUDE javascript.language--@>
<@--INCLUDE javascript.setting--@>
<@--INCLUDE javascript.profile--@>
<@--INCLUDE javascript.popup--@>
<@--INCLUDE javascript.ajax--@>
<@--INCLUDE javascript.plugin--@>
<@--INCLUDE javascript.context--@>
<@--INCLUDE javascript.smylie--@>

class System{
	
	constructor(){
		Language.init();
		this.channelControler = new ChannelControler();
		<@--FOR channels AS channel--@>
		this.channelControler.append("<@--ECHO channel--@>");
		<@--end--@>
		Setting.init();
		Context.listener();
	}
	
	getChannelControler(){
		return this.channelControler;
	}
}