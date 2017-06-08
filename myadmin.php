<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_lxc define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Lxc Vps',
	'description' => 'Allows selling of Lxc Server and VPS License Types.  More info at https://www.netenberg.com/lxc.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a lxc license. Allow 10 minutes for activation.',
	'module' => 'vps',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-lxc-vps',
	'repo' => 'https://github.com/detain/myadmin-lxc-vps',
	'version' => '1.0.0',
	'type' => 'service',
	'hooks' => [
		/*'function.requirements' => ['Detain\MyAdminLxc\Plugin', 'Requirements'],
		'vps.settings' => ['Detain\MyAdminLxc\Plugin', 'Settings'],
		'vps.activate' => ['Detain\MyAdminLxc\Plugin', 'Activate'],
		'vps.change_ip' => ['Detain\MyAdminLxc\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminLxc\Plugin', 'Menu'] */
	],
];
