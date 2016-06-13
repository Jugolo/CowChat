var pageBuffer = [];
var currentPage = null;

function renderPage(callback){
  for(var i=0;i<pageBuffer.length;i++){
     if(callback(pageBuffer[i])){
        break;
     }
  }
}

function pageFocus(page){
   if(currentPage == null){
     return false;
   }
   return currentPage == page;
}

function setFocus(obj){
	if(currentPage != null){
		currentPage.blur();
	}
	
	// wee unset the diffrence pages :)
	document.getElementById("chat").innerHTML = "";
	document.getElementById("online").innerHTML = "";
	document.getElementById("leftMenuItem").innerHTML = "";
	currentPage = obj;
	obj.focus();
}

function removePage(obj){
	var index = pageBuffer.indexOf(obj);
	if(index == -1){
		console.log("unknown page: "+obj.title());
		return false;
	}
	
	pageBuffer.splice(index, 1);
	//render all fane :)
	var doms = document.getElementsByClassName("tab");
	for(var i=0;i<doms.length;i++){
		var title = doms[i].getElementsByClassName("tab_title")[0];
		if(title.innerHTML == obj.title()){
			doms[i].parentNode.removeChild(doms[i]);
		}
	}
	
	if(pageBuffer.length != 0){
		setFocus(pageBuffer[0]);
	}
}

function savePage(page){
	pageBuffer.push(page);
	// wee append a tab to the top menu where the user can close it and push it
	// and so on
    var tab = document.createElement("div");
    tab.className = "tab";
	
	// wee need to set <| as the begin :)
	var leftWing = document.createElement("div");
	leftWing.innerHTML = " ";
	leftWing.className = "leftWing";
	tab.appendChild(leftWing);
	
	// wee append the name here
	var main = document.createElement("div");
	main.className = 'tab_title';
	main.innerHTML = page.title();
	main.onclick = function(){
		setFocus(page);
	};
	tab.appendChild(main);
	
	var close = document.createElement("div");
	close.className = "tab_close";
	close.innerHTML = "";
	close.onclick = function(){
		if(typeof page.onClose !== "undefined"){
			page.onClose();
			tab.parentNode.removeChild(tab);
		}
	};
	tab.appendChild(close);
	
	var rightWing = document.createElement("div");
	rightWing.className = "rightWing";
	rightWing.innerHTML = " ";
	tab.appendChild(rightWing);
    setFocus(page);

   document.getElementById("topMenu").insertBefore(tab, document.getElementById("topMenu").firstChild);	
}

function appendLeftMenu(html){
	document.getElementById("leftMenuItem").innerHTML += html;
}
