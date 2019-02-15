# ILIAS Hub-Plugin

An ILIAS-Plugin for middleware-synchronization of external systems to ILIAS. Several "Origins" can be added and configured to get data from external systems. Only small PHP-Classes have to be implemented (connect to the service, read the data and save the data in the middleware-tables).

## Supported Objects
* Categories
* Courses
* Users
* Course-Memberships 

## Documentation
Download the full Documentation here: https://github.com/studer-raimann/Hub/blob/master/doc/Dokumentation.docx?raw=true

### Dependencies
* ILIAS 5.3
* PHP >=5.6
* [composer](https://getcomposer.org)
* [srag/librariesnamespacechanger](https://packagist.org/packages/srag/librariesnamespacechanger)
* [srag/removeplugindataconfirm](https://packagist.org/packages/srag/removeplugindataconfirm)


Please use it for further development!

### Adjustment suggestions
* Adjustment suggestions by pull requests on https://git.studer-raimann.ch/ILIAS/Plugins/Hub/tree/develop
* Adjustment suggestions which are not yet worked out in detail by Jira tasks under https://jira.studer-raimann.ch/projects/PLHUB2
* Bug reports under https://jira.studer-raimann.ch/projects/PLHUB2
* For external users you can report it at https://plugins.studer-raimann.ch/goto.php?target=uihk_srsu_PLHUB2

### Development
If you want development in this plugin you should install this plugin like follow:

Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
git clone -b develop git@git.studer-raimann.ch:ILIAS/Plugins/Hub.git Hub
```

### ILIAS Plugin SLA

Wir lieben und leben die Philosophie von Open Source Software! Die meisten unserer Entwicklungen, welche wir im Kundenauftrag oder in Eigenleistung entwickeln, stellen wir öffentlich allen Interessierten kostenlos unter https://github.com/studer-raimann zur Verfügung.

Setzen Sie eines unserer Plugins professionell ein? Sichern Sie sich mittels SLA die termingerechte Verfügbarkeit dieses Plugins auch für die kommenden ILIAS Versionen. Informieren Sie sich hierzu unter https://studer-raimann.ch/produkte/ilias-plugins/plugin-sla.

Bitte beachten Sie, dass wir nur Institutionen, welche ein SLA abschliessen Unterstützung und Release-Pflege garantieren.
