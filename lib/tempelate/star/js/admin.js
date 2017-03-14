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
      callback(JSON.parse(this.responseText));
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
