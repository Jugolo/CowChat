# CowScriptChat
This chat is a old chat there is in process to work alone

# Plan for next release.
if wee cange and add every think i can think of will relate at the chat never would be published. so there for is some change and news placed to be in next version. here you can se what the plan is.

* new: append option to cache sql result from SELECT. This will be perfect when data is not updatet often (as config).
* new: create devolper mode. if devolper mode is active the chat will create new file width information about anything there happens
 in Ajax server it will create file every time a ajax call is calling.
* new: Begin to use class auto loadning and put all function in a function file. some of files will be replace.
* new: cron would be called every 5 min. and look in a cron directory after job. 
* new: The standart error handling would be throwing and ChatError. in thia way wee can offer better error handling.
* change: change the module load so you do not need to change index when you append module. from this module the defender would allow it from start and a admin can disable it widt type "/module disable [module]" and enable "/module enable [name]"
*new: append ShoutDown class where you can append functions to be called every time the server shoutdown (ajax server when the page is rended)
*new: error every time there is a throw there is not catch the error woud be logged. every module would have ther owen file.
*new: firewall log. firewall wold log event (ban, unban and so on) in file.
*new: log function "bool log(string $dir, bool $prefix = false);" there handle log. every log part would be saved in a log directory
