var StarGui = {
  initUserList : function(){
    var element = document.createElement("div");
    element.className = "user-list";
    document.getElementById("ulist-container").appendChild(element);
    return element;
  },
  
  initContextContainer : function(){
    const context = document.createElement("div");
    context.className = "context-container";
    document.getElementById("pageContainer").appendChild(context);
    return context;
  }
};
