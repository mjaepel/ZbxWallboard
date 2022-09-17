# ZbxWallboard
![](docs/Screenshots/ZbxWallboard_RegularView.png)

## Description
This tool allows you to build a Wallboard with all active triggers/problems. 
No tables. No small texts. It's used to get an overview from distance.

Use big screen in the office and load the wallboard there. All people in the office could see the problems without keep Zabbix open on the own screen.

## Features
* Overview about active triggers/problems
* Filter by hostgroup and severity
* Display option to hide acknowledged or maintenances
* configurable max count of problems
* acknowledgement of active triggers/problems
* very important: lunch reminder :D
* additional Icinga Backend as a little code-workaround
  * Sorry Zabbix Community. I need it. 
    * But there is an "ENABLE => false" Option in config.php :P
  * it shows Icinga service and host problems in the same manner as active Zabbix triggers. An "Icinga"-Badge is added in the right bottom corner (additional to maintenances/ack flags)

# Installation
* Download
* Unpack
* rename config.php.example to config.php
* modify API credentials in config.php and maybe some other things. :)

# Zabbix feature request
* I created a official feature request:
  * https://support.zabbix.com/browse/ZBXNEXT-4808
* The _Monitoring -> Problems_ view have the same features (some of them much better than in ZbxWallboard). The only missing thing is the display format. :(
  * (and the lunch reminder :D)

# Additional Screenshots
![](docs/Screenshots/ZbxWallboard_LunchReminder.png)
![](docs/Screenshots/ZbxWallboard_NoProblems.png)
![](docs/Screenshots/ZbxWallboard_Acknowledges.png)
![](docs/Screenshots/ZbxWallboard_ManyProblems.png)
