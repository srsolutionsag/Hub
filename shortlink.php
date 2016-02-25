<?php
/**
 * Shortlink
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version 1.1.04
 */

chdir(substr(__FILE__, 0, strpos(__FILE__, '/Customizing')));
error_reporting(E_ALL);
ini_set('display_error', 'stdout');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Shortlink/class.hubShortlink.php');
hubShortlink::redirect($_GET['q']);