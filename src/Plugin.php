<?php

namespace Detain\MyAdminLxc;

use Detain\Lxc\Lxc;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminLxc
 */
class Plugin {

	public static $name = 'LXC VPS';
	public static $description = 'Allows selling of LXC VPS Types.  LXC (Linux Containers) is an operating-system-level virtualization method for running multiple isolated Linux systems (containers) on a control host using a single Linux kernel.  More info at https://linuxcontainers.org/';
	public static $help = '';
	public static $module = 'vps';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			//self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.queue_backup' => [__CLASS__, 'getQueueBackup'],
			self::$module.'.queue_restore' => [__CLASS__, 'getQueueRestore'],
			self::$module.'.queue_enable' => [__CLASS__, 'getQueueEnable'],
			self::$module.'.queue_destroy' => [__CLASS__, 'getQueueDestroy'],
			self::$module.'.queue_delete' => [__CLASS__, 'getQueueDelete'],
			self::$module.'.queue_reinstall_os' => [__CLASS__, 'getQueueReinstallOs'],
			self::$module.'.queue_update_hdsize' => [__CLASS__, 'getQueueUpdateHdsize'],
			self::$module.'.queue_start' => [__CLASS__, 'getQueueStart'],
			self::$module.'.queue_stop' => [__CLASS__, 'getQueueStop'],
			self::$module.'.queue_restart' => [__CLASS__, 'getQueueRestart'],
			self::$module.'.queue_setup_vnc' => [__CLASS__, 'getQueueSetupVnc'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['type'] == get_service_define('LXC')) {
			myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivate(GenericEvent $event) {
		if ($event['type'] == get_service_define('LXC')) {
			myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'delete', '', $serviceClass->getCustid());
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getChangeIp(GenericEvent $event) {
		if ($event['type'] == get_service_define('LXC')) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$lxc = new Lxc(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', 'IP Change - (OLD:' .$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $lxc->editIp($serviceClass->getIp(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'Lxc editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getIp());
				$serviceClass->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_lxc', 'images/icons/database_warning_48.png', 'ReUsable Lxc Licenses');
			$menu->add_link(self::$module, 'choice=none.lxc_list', 'images/icons/database_warning_48.png', 'Lxc Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.lxc_licenses_list', '/images/whm/createacct.gif', 'List all Lxc Licenses');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_page_requirement('crud_lxc_list', '/../vendor/detain/crud/src/crud/crud_lxc_list.php');
		$loader->add_page_requirement('crud_reusable_lxc', '/../vendor/detain/crud/src/crud/crud_reusable_lxc.php');
		$loader->add_requirement('get_lxc_licenses', '/../vendor/detain/myadmin-lxc-vps/src/lxc.inc.php');
		$loader->add_requirement('get_lxc_list', '/../vendor/detain/myadmin-lxc-vps/src/lxc.inc.php');
		$loader->add_page_requirement('lxc_licenses_list', '/../vendor/detain/myadmin-lxc-vps/src/lxc_licenses_list.php');
		$loader->add_page_requirement('lxc_list', '/../vendor/detain/myadmin-lxc-vps/src/lxc_list.php');
		$loader->add_requirement('get_available_lxc', '/../vendor/detain/myadmin-lxc-vps/src/lxc.inc.php');
		$loader->add_requirement('activate_lxc', '/../vendor/detain/myadmin-lxc-vps/src/lxc.inc.php');
		$loader->add_requirement('get_reusable_lxc', '/../vendor/detain/myadmin-lxc-vps/src/lxc.inc.php');
		$loader->add_page_requirement('reusable_lxc', '/../vendor/detain/myadmin-lxc-vps/src/reusable_lxc.php');
		$loader->add_requirement('class.Lxc', '/../vendor/detain/lxc-vps/src/Lxc.php');
		$loader->add_page_requirement('vps_add_lxc', '/vps/addons/vps_add_lxc.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Slice Costs', 'vps_slice_lxc_cost', 'LXC VPS Cost Per Slice:', 'LXC VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_LXC_COST'));
		//$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_lxc_server', 'LXC NJ Server', NEW_VPS_LXC_SERVER, 9, 1);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_lxc', 'Out Of Stock LXC Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LXC'), ['0', '1'], ['No', 'Yes']);
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueBackup(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Backup', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/backup.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueRestore(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Restore', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/restore.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueEnable(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Enable', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/enable.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDestroy(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Destroy', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/destroy.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDelete(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Delete', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/delete.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueReinstallOsupdateHdsize(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Reinstall Osupdate Hdsize', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/reinstall_osupdate_hdsize.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueEnableCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Enable Cd', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/enable_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDisableCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Disable Cd', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/disable_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueInsertCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Insert Cd', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/insert_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueEjectCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Eject Cd', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/eject_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueStart(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Start', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/start.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueStop(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Stop', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/stop.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueRestart(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Restart', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/restart.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueSetupVnc(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Setup Vnc', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/setup_vnc.sh.tpl');
			$event->stopPropagation();
		}
	}

}
