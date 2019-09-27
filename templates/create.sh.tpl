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
killall -HUP dnsmasq
lxc init images:{$vps_os} {$vzid}
lxc config set {$vzid} limits.memory {$ram}MB;
lxc config set {$vzid} limits.cpu {$vcpu};
lxc config set {$vzid} volatile.eth0.hwaddr {$mac};
lxc network attach br0 {$vzid} eth0
lxc config device set {$vzid} eth0 ipv4.address {$ip}
lxc config device set {$vzid} eth0 security.mac_filtering true
lxc config device add {$vzid} root disk path=/ pool=lxd size={$hd}GB;
lxc start {$vzid} || lxc info --show-log {$vzid}
lxc exec {$vzid} -- bash -c 'x=0; while [ 0 ]; do x=$(($x + 1)); ping -c 2 4.2.2.2; if [ $? -eq 0 ] || [ "$x" = "20" ]; then break; else sleep 1s; fi; done'
lxc exec {$vzid} -- bash -c "echo ALL: ALL >> /etc/hosts.allow;"
lxc exec {$vzid} -- bash -c "if [ -e /etc/apt ]; then apt update; apt install openssh-server -y; fi;"
lxc exec {$vzid} -- bash -c "if [ -e /etc/yum ]; then yum install openssh-server -y; fi;"
lxc exec {$vzid} -- sed s#"^\#*PermitRootLogin .*$"#"PermitRootLogin yes"#g -i /etc/ssh/sshd_config;
lxc exec {$vzid} -- bash -c "echo root:{$rootpass} | chpasswd"
lxc exec {$vzid} -- /etc/init.d/ssh restart;
lxc exec {$vzid} -- /etc/init.d/sshd restart;
lxc exec {$vzid} -- systemctl restart sshd;
lxc exec {$vzid} -- locale-gen --purge en_US.UTF-8
lxc exec {$vzid} -- bash -c "echo -e 'LANG=\"en_US.UTF-8\"\nLANGUAGE=\"en_US:en\"\n' > /etc/default/locale"
