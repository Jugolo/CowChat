<!DOCTYPE html>
<html>
<head>
<title><@--ECHO title--@></title>
<style>
#container {
	box-shadow: 0px 10px 16px 0px rgba(0, 0, 0, 0.2), 0px 6px 20px 0px
		rgba(0, 0, 0, 0.19) !important;
	width: 300px;
	height: 300px;
	position: absolute;
	left: 50%;
	top: 50%;
	margin: -150px 0 0 -150px;
	border-radius: 10px 10px 0 0;
}
#title {
	text-align: center;
	background-color: #737373;
	border-radius: 10px 10px 0 0;
	font-weight: bolder;
	font-size: 16px;
	height:20px;
}
.error{
 text-align:center;
 color:red;
}
.okay{
 text-align:center;
 color:green;
}
.login_container{
  width:300px;
}
.login_value, .login_key{
  width:150px;
  float:left;
}

.login_key{
  font-size:18px;
  font-weight: bolder;
}

.login_value input{
  width:150px;
  box-sizing: border-box;
}

#head_menu a{
  display:block;
  float:left;
  width:100px;
  height:25px;
  text-align:center;
  Text-decoration: none;
  color:black;
  font-weight: bolder;
  background-color: #737373;
  border-top: 1px solid #999999;
}

#head_menu a:hover{
 opacity:0.4;
}

#submit input{
  width:300px;
  box-sizing: border-box;
}
label{
 cursor:pointer;
}
</style>
</head>
<body>
<@--IF exists("error")--@>
<div class='error'>
  <@--FOR error AS e--@>
    <div><@--ECHO e--@></div>
  <@--end--@>
</div>
<@--end--@>
<@--IF exists("okay")--@>
<div class='okay'>
  <@--FOR okay AS o--@>
    <div><@--ECHO o--@></div>
  <@--end--@>
</div>
<@--end--@>
<div id="container">
 <div id="title"><@--ECHO title--@></div>
 <div id="head_menu">
   <a href='?method=login'><@--LANGUAGE |Login|--@></a>
   <a href='?method=create'><@--LANGUAGE |Create|--@></a>
   <a href='?method=geaust'><@--LANGUAGE |Geaust|--@></a>
 </div>