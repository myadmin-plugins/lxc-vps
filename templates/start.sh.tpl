export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
lxc-start -d -n {if $vps_vzid == 0}vps{$vps_id}{else}{$vps_vzid}{/if};
