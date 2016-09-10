# WP HUD


Shows these stats:

 - Install (version, single/multi)
 - Pending updates
 - Plugins (installed, active, mu)
 - Drop-ins
 - Themes (installed, active)
 - Users (total, per-role)
 - Content (total/published per type). Not shown if multisite

## Example
```
$ wp hud
~~~ Install ~~~~~~~~~~~~~~~~~~~~~~~
Version: 4.6.1
Multisite with subdirectories
50 sites on network
~~~ Updates ~~~~~~~~~~~~~~~~~~~~~~~
1 Plugin Update
~~~ Plugins ~~~~~~~~~~~~~~~~~~~~~~~
17 installed plugins
0 active plugins
6 mu-plugins
~~~ Dropins ~~~~~~~~~~~~~~~~~~~~~~~
0 drop-ins
~~~ Themes ~~~~~~~~~~~~~~~~~~~~~~~~
7 installed themes
Active theme: Twenty Fourteen
~~~ Users ~~~~~~~~~~~~~~~~~~~~~~~~~
63 users
9 roles
3 users in administrator
1 users in editor
2 users in author
57 users in subscriber
0 users in none
```

### json

```
$ wp hud --format=json
{"version":"4.6.1","multisite":true,"multisite-subdomain":"","multisite-blogs":"50","updates":{"plugins":1,"themes":0,"wordpress":0,"translations":0},"plugins":{"installed":17,"active":0,"mu":6},"themes":{"installed":7,"active":"Twenty Fourteen"},"dropins":{"list":[],"installed":0},"users":{"total_users":63,"avail_roles":{"administrator":3,"editor":1,"author":2,"subscriber":57,"none":0},"roles_list":["administrator","editor","author","contributor","subscriber","s2member_level1","s2member_level2","s2member_level3","s2member_level4"],"roles":9},"content":[]}
```