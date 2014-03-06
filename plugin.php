<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.ilHubPlugin.php');
if (! ilHubPlugin::checkPreconditions()) {
	//ilUtil::sendFailure('hub needs ActiveRecord (https://svn.ilias.de/svn/ilias/branches/sr/ActiveRecord) and ilRouterGUI (https://svn.ilias.de/svn/ilias/branches/sr/Router)');
}
$id = 'hub';
$version = '1.0.02';
$ilias_min_version = '4.2.0';
$ilias_max_version = '4.3.999';
$responsible = 'Fabian Schmid';
$responsible_mail = 'fs@studer-raimann.ch';
?>