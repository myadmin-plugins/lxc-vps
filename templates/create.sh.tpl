{assign var=params value='-'|explode:$vps_os}
{assign var=distro value=$params[0]}
{assign var=version value=$params[1]}
{assign var=bits value=$params[2]}
{assign var=memory value=$vps_slices * $settings['slice_ram']}
{assign var=diskspace value=$settings['slice_hd'] * $vps_slices}
{assign var=diskspace value=$diskspace + $settings['additional_hd']}
{if in_array($vps_custid, [2773,8,2304])}
{assign var=vcpu value=ceil($vps_slices / 2)}
{else}
{assign var=vcpu value=ceil($vps_slices / 4)}
{/if}
cp -f /etc/lxc/dnsmasq.conf /etc/lxc/dnsmasq.conf.backup;
cat /etc/lxc/dnsmasq.conf.backup |grep -v "={$vps_vzid}," > /etc/lxc/dnsmasq.conf;
echo "dhcp-host={$vps_vzid},{$ip}" >> /etc/lxc/dnsmasq.conf
lxc launch images:{$distro}/{$version} {$vps_vzid} -c limits.memory={$memory}MB -c limits.cpu={$vcpu}
#lxc-create -n {$vps_vzid} -t {$distro} -- --release={$version} --arch={$bits} --password {$rootpass};
#lxc-start -d -n {$vps_vzid};
echo root:{$rootpass} | lxc-attach  -n {$vps_vzid} -- chpasswd;
