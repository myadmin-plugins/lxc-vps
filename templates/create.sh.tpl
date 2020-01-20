{assign var=ram value=$vps_slices * $settings.slice_ram}
{assign var=hd value=(($settings.slice_hd * $vps_slices) + $settings.additional_hd) * 1024}
{if in_array($vps_custid, [2773,8,2304])}
{assign var=vcpu value=ceil($vps_slices / 2)}
{else}
{assign var=vcpu value=ceil($vps_slices / 4)}
{/if}
cp -f /etc/lxc/dnsmasq.conf /etc/lxc/dnsmasq.conf.backup;
cat /etc/lxc/dnsmasq.conf.backup |grep -v -e ',{$ip}$' -e '={$mac},' -e '={$vzid},' > /etc/lxc/dnsmasq.conf;
echo 'dhcp-host={$mac},{$ip}' >> /etc/lxc/dnsmasq.conf;
cat /etc/lxc/dnsmasq.conf  > /var/snap/lxd/common/lxd/networks/*/dnsmasq.raw;
killall -HUP dnsmasq
if [ "{$vps_os}" = "wordpress" ]; then
  lxc init {$vps_os} {$vzid}
else
  lxc init images:{$vps_os} {$vzid}
fi
lxc config set {$vzid} limits.memory {$ram}MB;
lxc config set {$vzid} limits.cpu {$vcpu};
lxc config set {$vzid} volatile.eth0.hwaddr {$mac};
lxc network attach br0 {$vzid} eth0
lxc config device set {$vzid} eth0 ipv4.address {$ip}
#lxc config device set {$vzid} eth0 security.mac_filtering true
lxc config device add {$vzid} root disk path=/ pool=lxd size={$hd}GB;
lxc start {$vzid} || lxc info --show-log {$vzid}
lxc exec {$vzid} -- bash -l -c 'x=0; while [ 0 ]; do x=$(($x + 1)); ping -c 2 4.2.2.2; if [ $? -eq 0 ] || [ "$x" = "20" ]; then break; else sleep 1s; fi; done'
lxc exec {$vzid} -- bash -l -c "echo ALL: ALL >> /etc/hosts.allow;"
lxc exec {$vzid} -- bash -l -c 'if [ -e /etc/apt ]; then e=0; apt-get update; while [ $e -eq 0 ]; do apt-get install openssh-server -y && e=1 || sleep 30s; done; fi;'
lxc exec {$vzid} -- bash -l -c "if [ -e /etc/yum ]; then yum install openssh-server -y; fi;"
lxc exec {$vzid} -- bash -l -c 'sed s#"^\#*PermitRootLogin .*$"#"PermitRootLogin yes"#g -i /etc/ssh/sshd_config';
lxc exec {$vzid} -- bash -l -c "echo root:{$rootpass} | chpasswd"
lxc exec {$vzid} -- bash -l -c "/etc/init.d/ssh restart || /etc/init.d/sshd restart; systemctl restart sshd;"
lxc exec {$vzid} -- bash -l -c "locale-gen --purge en_US.UTF-8"
lxc exec {$vzid} -- bash -l -c "echo -e 'LANG=\"en_US.UTF-8\"\nLANGUAGE=\"en_US:en\"\n' > /etc/default/locale"
