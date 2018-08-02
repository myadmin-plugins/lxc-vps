{assign var=memory value=$settings['slice_ram']}
{assign var=memory value=$memory * $vps_slices}
{assign var=diskspace value=$settings['slice_hd'] * $vps_slices}
{assign var=diskspace value=$diskspace + $settings['additional_hd']}
{if in_array($vps_custid, [2773,8,2304])}
{assign var=vcpu value=ceil($vps_slices / 2)}
{else}
{assign var=vcpu value=ceil($vps_slices / 4)}
{/if}
{assign var=cpushares value=$vps_slices * 512}
{assign var=ioweight value=37 * $vps_slices}
{assign var=ioweight value=$ioweight + 400}
#set -x;
lxc stop {$vps_vzid};
lxc config set {$vps_vzid} limits.memory {$memory}MB;
lxc config set {$vps_vzid} limits.cpu {$vcpu};
lxc config set {$vps_vzid} 
zfs set readonly=off quota=50G refquota=50G lxd/containers/{$vps_vzid}
lxc config device set {$vps_vzid} root size {$diskspace}GB
lxc start {$vps_vzid};
