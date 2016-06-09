var UserData = (function(){
	function UserData(nick, group){
		this.nick = nick;
		this.group = group;
		this.data = {
				'inaktiv' : false
		};
	}
	
	UserData.prototype.allowChangeTitle = function(){
		return this.group["changeTitle"] == "Y";
	};
	
	UserData.prototype.allowKick = function(){
		return this.group["kick"] == "Y";
	};
	
	/**
	 * Mark user as inaktiv or get inaktiv status
	 */
	UserData.prototype.inaktiv = function(inaktiv){
		if(typeof inaktiv !== "undefined"){
			this.data["inaktiv"] = inaktiv;
		}
		
		return this.data["inaktiv"];
	}
	
	return UserData;
})();