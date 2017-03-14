function open_admin(){
   var page = sys.createPage("Admin");
   admin_menu(page, "User", admin_usermain);
   admin_menu(page, "Error", admin_errormain);
}

function admin_menu(page, str, onclick){
   page.user.append(str);
   page.user.get(str).container.getElementsByClassName("nick")[0].onclick = function(){
      onclick.apply(page, []);
   };
}

function admin_usermain(){
   
}

function admin_errormain(){
   
}
