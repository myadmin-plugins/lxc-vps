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
			self::$module.'.queue' => [__CLASS__, 'getQueue'],
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
	public static function getQueue(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('LXC')])) {
			$vps = $event->getSubject();
			myadmin_log(self::$module, 'info', self::$name.' Queue '.ucwords(str_replace('_', ' ', $vps['action'])), __LINE__, __FILE__);
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign($vps);
			echo $smarty->fetch(__DIR__.'/../templates/'.$vps['action'].'.sh.tpl');
			$event->stopPropagation();
		}
	}
}
