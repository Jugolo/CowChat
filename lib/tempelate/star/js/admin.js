function open_admin(){
   var page = sys.createPage("Admin");
   admin_menu(page, "User", admin_usermain);
   admin_menu(page, "Error", admin_errormain);
   admin_usermain.apply(page, []);
}

function admin_menu(page, str, onclick){
   page.user.append(str);
   page.user.get(str).container.getElementsByClassName("nick")[0].onclick = function(){
      onclick.apply(page, []);
   };
}

function admin_usermain(){
   var self = this;
   requestAdmin("/userlist", function(data){
      if(CowChatCommand.getCommand(data) == "userlist"){
         emptyAdmin(self);
         var table = document.createElement("table");
         table.className = "userlist";
         var tr = document.createElement("tr");
         var td = document.createElement("th");
         td.innerHTML = "id";
         tr.appendChild(td);
         td = document.createElement("th");
         td.innerHTML = "Username";
         tr.appendChild(td);
         td = document.createElement("th");
         td.innerHTML = "Nick";
         tr.appendChild(td);
         table.appendChild(tr);
         var parts = data.message.substr(10).split(" ");
         for(var i=0;i<parts.length;i++){
            var data = parts[i].split(",");
            tr = document.createElement("tr");
            for(var j=0;j<data.length;j++){
               td = document.createElement("td");
               td.innerHTML = data[j];
               tr.appendChild(td);
            }
            table.appendChild(tr);
         }
         
         self.context.appendChild(table);
         return true;
      }
      return false;
   });
}

function admin_errormain(){
   var self = this;
   requestAdmin("/errorlist", function(data){
      if(CowChatCommand.getCommand(data) == "errorlist"){
         emptyAdmin(self);
         var table = document.createElement("table");
         table.className = "userlist";
         var tr = document.createElement("tr");
         var td = document.createElement("th");
         td.innerHTML = "id";
         tr.appendChild(td);
         td = document.createElement("th");
         td.innerHTML = "Error number";
         tr.appendChild(td);
         td = document.createElement("th");
         td.innerHTML = "Error message";
         tr.appendChild(td);
         td = document.createElement("th");
         td.innerHTML = "File";
         tr.appendChild(td);
         td = document.createElement("th");
         td.innerHTML = "Line";
         tr.appendChild(td);
         td = document.createElement("th");
         td.innerHTML = "Seen";
         tr.appendChild(td);
         td = document.createElement("th");
         td.innerHTML = "Time";
         tr.appendChild(td);
         table.appendChild(tr);
         var info = JSON.parse(window.atob(data.message.substr(11)));
         for(var i=0;i<info.length;i++){
            tr = document.createElement("tr");
            for(var key in info[i]){
               td = document.createElement("td");
               td.innerHTML = key == "seen" ? (info[i][key] == 2 ? "No" : "Yes") : info[i][key];
               tr.appendChild(td);
            }
            table.appendChild(tr);
         }
         self.context.appendChild(table);
         return true;
      }
      return false;
   });
}

function emptyAdmin(page){
   page.context.innerHTML = "";
}

function requestAdmin(msg, callback, channel){
   stopUpdate();//no need to the system to request news
  var ajax = new XMLHttpRequest();
  ajax.open("POST", "?_ajax=true&isPost=true&noMessage=true"+(typeof channel !== "undefined" ? "&channel="+encodeURIComponent(channel) : ""), true);
  ajax.onreadystatechange = function(){
   if(this.readyState == 4){
    if(this.status != 200){
      sys.getPage("console").line("", "Ajax", "", "[color=red]"+encodeURI(this.responseText)+"[/color]");
      setUpdate();
      return;
    }
    try{
       var json = JSON.parse(this.responseText).message;
      for(var i=0;i<json.length;i++){
         if(!callback(json[i])){
            handleResponsPart(json[i]);
         }
      }
    }catch(e){
      if(e.responseText == "login"){
        location.reload();
        return;
      }else{
        sys.getPage("console").line("","","AjaxError", "[color=red]There happend a error: "+this.responseText+"[/color]");
        setUpdate();
        return;
      }
    }
    setUpdate();
   }
  };
  ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  ajax.send("message="+encodeURIComponent(msg));
}
