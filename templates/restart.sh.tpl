export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
lxc stop {$vps_vzid};
lxc start {$vps_vzid};
