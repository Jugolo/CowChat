var pageBuffer = [];

function renderPage(callback){
  for(var i=0;i<pageBuffer.length;i++){
     if(callback(pageBuffer[i])){
        break;
     }
  }
}

function savePage(page){
	pageBuffer.push(page);
	//wee append a tab to the top menu where the user can close it and push it and so on
    var tab = document.createElement("div");
    tab.className = "tab";
	
	//wee need to set <| as the begin :)
	var leftWing = document.createElement("div");
	leftWing.innerHTML = " ";
	leftWing.className = "leftWing";
	tab.appendChild(leftWing);
	
	//wee append the name here
	var main = document.createElement("div");
	main.className = 'tab_title';
	main.innerHTML = page.title();
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

   document.getElementById("topMenu").insertBefore(tab, document.getElementById("topMenu").firstChild);	
}
