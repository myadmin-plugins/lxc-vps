								echo "export PATH=\"\$PATH:/usr/sbin:/sbin:/bin:/usr/bin:\";\n";
								echo 'lxc-stop -W -k -n '.($vps['vps_vzid'] == 0 ? 'vps'.$vps['vps_id'] : $vps['vps_vzid']) . ";\n";
