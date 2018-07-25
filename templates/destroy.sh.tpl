export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
lxc stop -f {if $vps_vzid == 0}vps{$vps_id}{else}{$vps_vzid}{/if};
lxc delete -f {if $vps_vzid == 0}vps{$vps_id}{else}{$vps_vzid}{/if};
