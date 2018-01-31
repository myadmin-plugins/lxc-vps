{assign var=params value='-'|explode:$vps_os}
{assign var=distro value=$params[0]}
{assign var=version value=$params[1]}
{assign var=bits value=$params[2]}
lxc-create -n {$vps_vzid} -t {$distro} -- --release={$version} --arch={$bits} --password {$rootpass};
lxc-start -d -n {$vps_vzid};
echo root:{$rootpass} | lxc-attach  -n {$vps_vzid} -- chpasswd;
