								echo "export PATH=\"\$PATH:/usr/sbin:/sbin:/bin:/usr/bin:\";\n";
								echo 'lxc-start -d -n '.($vps['vps_vzid'] == 0 ? 'vps'.$vps['vps_id'] : $vps['vps_vzid']) . ";\n";
