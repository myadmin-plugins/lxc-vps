{assign var=ram value=$vps_slices * $settings.slice_ram}
{assign var=hd value=(($settings.slice_hd * $vps_slices) + $settings.additional_hd) * 1024}
{if in_array($vps_custid, [2773,8,2304])}
{assign var=vcpu value=ceil($vps_slices / 2)}
{else}
{assign var=vcpu value=ceil($vps_slices / 4)}
{/if}
cp -f /etc/lxc/dnsmasq.conf /etc/lxc/dnsmasq.conf.backup;
cat /etc/lxc/dnsmasq.conf.backup |grep -v -e '={$mac},' -e '={$vzid},' > /etc/lxc/dnsmasq.conf;
echo 'dhcp-host={$mac},{$ip}' >> /etc/lxc/dnsmasq.conf;
killall -HUP dnsmasq
lxc init images:{$vps_os} {$vzid}
lxc config set {$vzid} limits.memory {$ram}MB;
lxc config set {$vzid} limits.cpu {$vcpu};
lxc config set {$vzid} volatile.eth0.hwaddr {$mac};
lxc network attach br0 {$vzid} eth0
lxc config device set {$vzid} eth0 ipv4.address {$ip}
lxc config device set {$vzid} eth0 security.mac_filtering true
lxc config device add {$vzid} root disk path=/ pool=lxd size={$hd}GB;
lxc start {$vzid}
lxc exec {$vzid} "echo root:{$rootpass} | chpasswd";