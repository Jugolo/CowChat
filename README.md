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
* new: append ShoutDown class where you can append functions to be called every time the server shoutdown (ajax server when the page is rended)
* new: error every time there is a throw there is not catch the error woud be logged. every module would have ther owen file.
* new: firewall log. firewall wold log event (ban, unban and so on) in file.
* new: log function "bool log(string $dir, bool $prefix = false);" there handle log. every log part would be saved in a log directory
* new: when user create a account there will be sent a email to activate the account.
* new: when defender ip ban a user there will be sent a email to the user with information about the ban.
* new: start project ChatPlugin. the class would be in devolper mode so do not use a lot of time on create plugin becuse there are no guarantee it would work in next versions.
* new: commands: "/show plugin" to show all plugin. "/show plugin enabled" to show all plugin there is enabled. "/show plugin disabled" show plugin there is not enabled. "/plugin [name] [enable/deable]" to enable or debale plugin.
* change: only support php version 7 or heigher.
* new: a better config handling. When a admin visit the chat there will in left menu where a option "Configuration" when click on this there will be a popup width all config. 

# PHP version 
the first version of chat has no requirement for php version but the ajax part of the chat need to be very fast to load.
the older version of php is fast but it is not fast enough so for be sure the chat work as i will the chat would not work on lower version.
