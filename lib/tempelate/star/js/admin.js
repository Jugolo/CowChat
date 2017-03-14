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
      emptyAdmin(self);
   });
}

function admin_errormain(){
   emptyAdmin(this);
}

function emptyAdmin(page){
   page.context.innerHTML = "";
}

function requestAdmin(msg, callback, channel){
  var ajax = new XMLHttpRequest();
  ajax.open("POST", "?_ajax=true&isPost=true&noMessage=true"+(typeof channel !== "undefined" ? "&channel="+encodeURIComponent(channel) : ""), true);
  ajax.onreadystatechange = function(){
     
  };
  ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  ajax.send("message="+encodeURIComponent(msg));
}
