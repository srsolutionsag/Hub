<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/class.ilHubPlugin.php');
if (! ilHubPlugin::checkPreconditions()) {
}
$id = 'hub';
$version = '1.0.07';
$ilias_min_version = '4.2.0';
$ilias_max_version = '4.5.999';
$responsible = 'Fabian Schmid';
$responsible_mail = 'fs@studer-raimann.ch';
?>