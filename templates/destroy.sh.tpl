export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
lxc stop -f {$vps_vzid};
lxc delete -f {$vps_vzid};
