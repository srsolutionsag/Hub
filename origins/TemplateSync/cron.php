<?php

/**
 * Cron
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
chdir(substr(__FILE__, 0, strpos(__FILE__, '/Customizing')));
require_once('./Customizing/global/TemplateSync/class.createTemplateListCron.php');
createTemplateListCron::initAndRun();
?>