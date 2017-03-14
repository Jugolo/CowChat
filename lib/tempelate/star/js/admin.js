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
      if(getCommand(data) == "userlist"){
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
               td.innerHTML = data[i];
               tr.appendChild(td);
            }
            table.appendChild(tr);
         }
         
         self.context.appendChild(table);
      }
   });
}

function admin_errormain(){
   emptyAdmin(this);
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
       var json = JSON.parse(this.responseText);
      for(var i=0;i<json.length;i++){
         callback(json[i]);
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
