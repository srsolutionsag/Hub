<?php

/**
 * Cron
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
if (posix_getuid() == 0) {
    echo "Running this script as root may lead to filesystem permission issues. Run this script as unprivileged user only. Aborting.\n";
    exit(1);
}
chdir(substr(__FILE__, 0, strpos(__FILE__, '/Customizing')));
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Sync/class.hubSyncCron.php');
hubSyncCron::initAndRun();
?>