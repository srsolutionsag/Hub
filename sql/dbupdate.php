<#1>
<?php
/**
 * Install Base
 */
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategory.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourse.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembership.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOriginConfiguration.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/OriginProperties/class.hubOriginObjectPropertyValue.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncHistory.php');
hubOriginConfiguration::updateDB();
hubOrigin::updateDB();
hubOriginObjectPropertyValue::updateDB();
hubCategory::updateDB();
hubCourse::updateDB();
hubMembership::updateDB();
hubUser::updateDB();
hubSyncHistory::updateDB();
?>
<#2>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Configuration/class.hubConfig.php');
hubConfig::updateDB();
?>
<#3>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
hubUser::updateDB();
hubOrigin::updateDB();
?>
<#4>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategory.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourse.php');
hubCategory::updateDB();
hubCourse::updateDB();
?>
<#5>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
hubUser::updateDB();
?>
<#6>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Category/class.hubCategory.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourse.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembership.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
hubUser::updateDB();
hubCategory::updateDB();
hubMembership::updateDB();
hubCourse::updateDB();
?>
<#7>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourse.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
hubCourse::updateDB();
hubOrigin::updateDB();
?>
<#8>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Icon/class.hubIcon.php');
hubIcon::updateDB();
?>
<#9>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Icon/class.hubIcon.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOrigin.php');
/**
 * @var hubOrigin $hubOrigin
 */
hubIcon::resetDB();
hubIcon::initDir();
foreach (hubOrigin::get() as $hubOrigin) {
	if ($hubOrigin->props()->getIconPath()) {
		$hubIcon = new hubIcon();
		$hubIcon->setSizeType(hubIcon::SIZE_SMALL);
		$hubIcon->setSrHubOriginId($hubOrigin->getId());
		$hubIcon->setUsageType(hubIcon::USAGE_OBJECT);
		$hubIcon->create();
		$hubIcon->importFromPath($hubOrigin->props()->getIconPath());
		//
		$hubIcon = new hubIcon();
		$hubIcon->setSizeType(hubIcon::SIZE_MEDIUM);
		$hubIcon->setSrHubOriginId($hubOrigin->getId());
		$hubIcon->setUsageType(hubIcon::USAGE_OBJECT);
		$hubIcon->create();
		$hubIcon->importFromPath($hubOrigin->props()->getIconPath());
		//
		$hubIcon = new hubIcon();
		$hubIcon->setSizeType(hubIcon::SIZE_LARGE);
		$hubIcon->setSrHubOriginId($hubOrigin->getId());
		$hubIcon->setUsageType(hubIcon::USAGE_OBJECT);
		$hubIcon->create();
		$hubIcon->importFromPath($hubOrigin->props()->getIconPath());
	}
}
?>
<#10>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Membership/class.hubMembership.php');
hubMembership::updateDB();
?>
<#11>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Course/class.hubCourse.php');
hubCourse::updateDB();
?>
<#12>
<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Configuration/class.hubConfig.php');
hubConfig::set(hubConfig::F_MMAIL_ACTIVE, true);
hubConfig::set(hubConfig::F_MMAIL_SUBJECT, 'Neue Kursmitgliedschaft');
hubConfig::set(hubConfig::F_MMAIL_MSG,
	'Hallo [FIRSTNAME] [LASTNAME],

Sie wurden in ILIAS in folgendem Kurs eingeschrieben: [COURSE_TITLE]

Der Kurs ist gültig vom [VALIDITY_START] bis zum [VALIDITY_END].

Klicken Sie auf folgenden Link, um direkt zum Kurs zu gelangen: [COURSE_LINK]');
hubConfig::set(hubConfig::F_STANDARD_ROLE, 'User');
?>
<#13>
<?php
require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOriginConfiguration.php";
global $ilDB;
if (!$ilDB->tableColumnExists(hubOriginConfiguration::TABLE_NAME, 'exec_time')) {
	$ilDB->addTableColumn(hubOriginConfiguration::TABLE_NAME, 'exec_time',
		array('type' => 'text',
				'length' => 10,
				'notnull' => false));
}
?>

<#14>
<?php
global $ilDB;

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
hubUser::updateDB();

?>

<#15>
<?php
global $ilDB;

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/User/class.hubUser.php');
hubUser::updateDB();

?>

