<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Hub/classes/Origin/class.hubOriginFormGUI.php');

/**
 * Class hubOriginExport
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.1.04
 */
class hubOriginExport {

	/**
	 * @param array $file_array
	 *
	 * @return bool
	 */
	public static function import(array $file_array) {
		$tmp_name = $file_array['import_file']['tmp_name'];
		$name = $file_array['import_file']['name'];
		$name_no_suffix = basename($file_array['import_file']['name'], '.zip');
		$tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
		if ($tmp_name) {
			ilUtil::moveUploadedFile($tmp_name, $name, $tmp_dir . '/' . $name);
			ilUtil::unzip($tmp_dir . '/' . $name, true);
			$settings = json_decode(file_get_contents($tmp_dir . '/' . $name_no_suffix . '/settings.json'));
			$path = hubOrigin::getOriginsPathForUsageType($settings->usage_type);
			if (is_writable($path)) {
				$hubOrigin = new hubOrigin();
				$hubOrigin->setTitle($settings->title);
				$hubOrigin->setDescription($settings->description);
				$hubOrigin->setMatchingKeyIlias($settings->matching_key_ilias);
				$hubOrigin->setMatchingKeyOrigin($settings->matching_key_origin);
				$hubOrigin->setUsageType($settings->usage_type);
				$hubOrigin->setClassName($settings->class_name);
				$hubOrigin->setActive($settings->active);
				$hubOrigin->setConfType($settings->conf_type);
				$hubOrigin->conf()->setFilePath($settings->file_path);
				$hubOrigin->conf()->setSrvUsername($settings->db_username);
				$hubOrigin->conf()->setSrvPassword($settings->db_password);
				$hubOrigin->conf()->setSrvHost($settings->db_host);
				$hubOrigin->conf()->setSrvDatabase($settings->db_database);
				$hubOrigin->conf()->setSrvPort($settings->db_port);
				$hubOrigin->conf()->setSrvSearchBase($settings->db_search_base);
				$hubOrigin->conf()->setNotificationEmail($settings->notification_email);
				$hubOrigin->conf()->setSummaryEmail($settings->summary_email);
				$hubOrigin->create();
				hubOriginObjectProperties::getInstance($hubOrigin->getId())->importRaw((array)$settings);

				ilUtil::createDirectory($path . $name_no_suffix);
				if (ilUtil::rCopy($tmp_dir . '/' . $name_no_suffix, $path . $name_no_suffix)) {
					ilUtil::sendSuccess('Origin imported');

					return true;
				} else {
					ilUtil::sendFailure('Origin import failed');

					return false;
				}
			} else {
				ilUtil::sendFailure('Origin already exists');

				return false;
			}
		}
	}


	/**
	 * @param hubOrigin $origin
	 */
	public static function export(hubOrigin $origin) {
		$form = new hubOriginFormGUI(new hubGUI(), $origin);
		$array = $form->getValues();
		$tmp_dir = ilUtil::ilTempnam();
		ilUtil::makeDir($tmp_dir);
		$zip_base_dir = $tmp_dir . DIRECTORY_SEPARATOR . $origin->getClassName();
		ilUtil::makeDir($zip_base_dir);
		$tmpzipfile = $tmp_dir . DIRECTORY_SEPARATOR . $origin->getClassName() . '.zip';
		file_put_contents($zip_base_dir . '/settings.json', json_encode($array));
		ilUtil::rCopy($origin->getClassPath(), $zip_base_dir);
		try {
			ilUtil::zip($zip_base_dir, $tmpzipfile);
			rename($tmpzipfile, $zipfile = ilUtil::ilTempnam());
			ilUtil::delDir($tmp_dir);
			ilUtil::deliverFile($zipfile, $origin->getClassName() . '.zip', '', false, true, true);
		} catch (ilFileException $e) {
			ilUtil::sendInfo($e->getMessage(), true);
		}
	}
}
