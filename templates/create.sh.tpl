{assign var=params value='-'|explode:$vps_os}
{assign var=distro value=$params[0]}
{assign var=version value=$params[1]}
{assign var=bits value=$params[2]}
{assign var=ram value=$vps_slices * $settings.slice_ram}
{assign var=hd value=$settings.slice_hd * $vps_slices}
{assign var=hd value=$hd + $settings.additional_hd}
{assign var=hd value=$hd * 1024}
{if in_array($vps_custid, [2773,8,2304])}
{assign var=vcpu value=ceil($vps_slices / 2)}
{else}
{assign var=vcpu value=ceil($vps_slices / 4)}
{/if}
cp -f /etc/lxc/dnsmasq.conf /etc/lxc/dnsmasq.conf.backup;
cat /etc/lxc/dnsmasq.conf.backup |grep -v '={$vzid},' > /etc/lxc/dnsmasq.conf;
echo 'dhcp-host={$mac},{$ip}' >> /etc/lxc/dnsmasq.conf;
cp -f /etc/dhcp/dhcpd.vps /etc/dhcp/dhcpd.vps.backup;
grep -v 'fixed-address {$ip};' /etc/dhcp/dhcpd.vps.backup > /etc/dhcp/dhcpd.vps;
echo 'host {$vzid} { hardware ethernet {$mac}; fixed-address {$ip};}' >> /etc/dhcp/dhcpd.vps;
service isc-dhcp-server restart;
lxc launch images:{$vps_os} {$vzid} -c limits.memory={$ram}MB -c limits.cpu={$vcpu} -c volatile.eth0.hwaddr={$mac};
#lxc-create -n {$vzid} -t {$distro} -- --release={$version} --arch={$bits} --password {$rootpass};
#lxc-start -d -n {$vzid};
lxc config device add {$vzid} root disk path=/ pool=lxd size={$hd}GB;
lxc exec {$vzid} "echo root:{$rootpass} | chpasswd";

