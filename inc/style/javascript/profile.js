class Profile{
	static logout(){
		location.href = "?logout=true";
	}
	
	static show(){
		function add_field(dom, label_text, type, name, context){
				// let create a clean div
				var div = document.createElement("div");
				
				// let us create key (in table it would be <th>
				var key = document.createElement("div");
				// set width to 120
				key.style.width = "120px";
				// float left
				key.style.float = "left";
				
				// key label is where to append context in <th>
				var label = document.createElement("label");
				label.htmlFor = "popup_profile_"+name;
				// append text to label
				label.appendChild(document.createTextNode(label_text));
				// append label to key
				key.appendChild(label);
				// append key to div
				div.appendChild(key);
				
				// create value div
				var value = document.createElement("div");
				// set width to 180px
				value.style.width = "180px";
				// float left
				value.style.float = "left";
				
				// create input
				var input = document.createElement("input");
				input.type = type;
				input.name = name;
				input.id  = "popup_profile_"+name;
				if(typeof context !== "undefined"){
					input.value = context;
				}
				
				value.appendChild(input);
				
				// put value to our div
				div.appendChild(value);
				
				// append to the form
				dom.appendChild(div);
			}
			var dom = Popup.getContextDom();
			Popup.title(language("Update you profile"));
			Popup.setSize(300,300);
			
			// let us set a form here
			var form = document.createElement("form");
			// append event listener
			form.addEventListener("submit", function(obj){
				obj.preventDefault();
				var form = obj.target;
				var buffer = {};
				for(var i=0;i<form.length;i++){
					if(form[i].type != "submit"){
						buffer[form[i].name] = form[i].value;
					}
				}
				
				ajax("?profile=update&respons=json", buffer, function(respons){
					
				});
			});
			
			Plugin.call("javascript.profile.popup.befor", form);
			add_field(form, language("Username")+":", "text", "username", userdata["username"]);
			add_field(form, language("Password")+":", "password", "password");
			add_field(form, language("Repeat password")+":", "password", "repeat_password");
			add_field(form, language("Email")+":", "email", "email", userdata["email"]);
			add_field(form, language("nick")+":", "text", "nick", userdata["nick"]);
			Plugin.call("javascript.profile.popup.beforsubmit", form);
			var div = document.createElement("div");
			div.style.width = "300px";
			var input = document.createElement("input");
			input.style.width="300px";
			input.type="submit";
			input.value=language("Update you profile");
			div.appendChild(input);
			form.appendChild(div);
			dom.appendChild(form);
			Plugin.call("javascript.profile.popup.after", form);
			Popup.show();
	}
}