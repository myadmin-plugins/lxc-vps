export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
lxc-stop -t 10 -n {if $vps_vzid == 0}vps{$vps_id}{else}{$vps_vzid}{/if};
lxc-start -d -n {if $vps_vzid == 0}vps{$vps_id}{else}{$vps_vzid}{/if};
