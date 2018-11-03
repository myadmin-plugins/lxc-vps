# Everything You Need to Know about Linux Containers

# Everything You Need to Know about Linux Containers, Part I: Linux Control Groups and Process Isolation

by [Petros Koutoupis](https://www.linuxjournal.com/users/petros-koutoupis)

on August 21, 2018

_Everyone's heard the term, but what exactly are containers?_

The software enabling this technology comes in many forms, with Docker as the most popular. The recent rise in popularity of container technology within the data center is a direct result of its portability and ability to isolate working environments, thus limiting its impact and overall footprint to the underlying computing system. To understand the technology completely, you first need to understand the many pieces that make it all possible.

_Sidenote: people often ask about the difference between containers and virtual machines. Both have a specific purpose and place with very little overlap, and one doesn't obsolete the other. A container is meant to be a lightweight environment that you spin up to host one to a few isolated applications at bare-metal performance. You should opt for virtual machines when you want to host an entire operating system or ecosystem or maybe to run applications incompatible with the underlying environment._

### Linux Control Groups

Truth be told, certain software applications in the wild may need to be controlled or limited—at least for the sake of stability and, to some degree, security. Far too often, a bug or just bad code can disrupt an entire machine and potentially cripple an entire ecosystem. Fortunately, a way exists to keep those same applications in check. Control groups (cgroups) is a kernel feature that limits, accounts for and isolates the CPU, memory, disk I/O and network's usage of one or more processes.

Originally developed by Google, the cgroups technology eventually would find its way to the Linux kernel mainline in version 2.6.24 (January 2008). A redesign of this technology—that is, the addition of kernfs (to split some of the sysfs logic)—would be merged into both the 3.15 and 3.16 kernels.

The primary design goal for cgroups was to provide a unified interface to manage processes or whole operating-system-level virtualization, including Linux Containers, or LXC (a topic I plan to revisit in more detail in a follow-up article). The cgroups framework provides the following:

*   **Resource limiting:** a group can be configured not to exceed a specified memory limit or use more than the desired amount of processors or be limited to specific peripheral devices.
*   **Prioritization:** one or more groups may be configured to utilize fewer or more CPUs or disk I/O throughput.
*   **Accounting:** a group's resource usage is monitored and measured.
*   **Control:** groups of processes can be frozen or stopped and restarted.

A cgroup can consist of one or more processes that are all bound to the same set of limits. These groups also can be hierarchical, which means that a subgroup inherits the limits administered to its parent group.

The Linux kernel provides access to a series of controllers or subsystems for the cgroup technology. The controller is responsible for distributing a specific type of system resources to a set of one or more processes. For instance, the `memory` controller is what limits memory usage while the `cpuacct` controller monitors CPU usage.

You can access and manage cgroups both directly and indirectly (with LXC, libvirt or Docker), the first of which I cover here via sysfs and the `libcgroups` library. To follow along with the examples here, you first need to install the necessary packages. On Red Hat Enterprise Linux or CentOS, type the following on the command line:

    $ sudo yum install libcgroup libcgroup-tools

On Ubuntu or Debian, type:

    $ sudo apt-get install libcgroup1 cgroup-tools

For the example application, I'm using a simple shell script file called test.sh, and it'll be running the following two commands in an infinite `while` loop:

    $ cat test.sh#!/bin/shwhile [ 1 ]; do        echo "hello world"        sleep 60done

### The Manual Approach

With the proper packages installed, you can configure your cgroups directly via the sysfs hierarchy. For instance, to create a cgroup named `foo` under the `memory` subsystem, create a directory named foo in /sys/fs/cgroup/memory:

    $ sudo mkdir /sys/fs/cgroup/memory/foo

By default, every newly created cgroup will inherit access to the system's entire pool of memory. For some applications, primarily those that continue to allocate more memory but refuse to free what already has been allocated, that may not be such a great idea. To limit an application to a reasonable limit, you'll need to update the `memory.limit_in_bytes` file.

Limit the memory for anything running under the cgroup `foo` to 50MB:

    $ echo 50000000 | sudo tee ↪/sys/fs/cgroup/memory/foo/memory.limit_in_bytes

Verify the setting:

    $ sudo cat memory.limit_in_bytes50003968

Note that the value read back always will be a multiple of the kernel's page size (that is, 4096 bytes or 4KB). This value is the smallest allocatable size of memory.

Launch the application:

    $ sh ~/test.sh &

Using its Process ID (PID), move the application to cgroup `foo` under the `memory` controller:

    $ echo 2845 > /sys/fs/cgroup/memory/foo/cgroup.procs

Using the same PID number, list the running process and verify that it's running within the desired cgroup:

    $ ps -o cgroup 2845CGROUP8:memory:/foo,1:name=systemd:/user.slice/user-0.slice/↪session-4.scope

You also can monitor what's currently being used by that cgroup by reading the desired files. In this case, you'll want to see the amount of memory allocated by your process (and spawned subprocesses):

    $ cat /sys/fs/cgroup/memory/foo/memory.usage_in_bytes253952

### When a Process Goes Astray

Now let's re-create the same scenario, but instead of limiting the cgroup `foo` to 50MB of memory, you'll limit it to 500 bytes:

    $ echo 500 | sudo tee /sys/fs/cgroup/memory/foo/↪memory.limit_in_bytes

_Note: if a task exceeds its defined limits, the kernel will intervene and, in some cases, kill that task._

Again, when you read the value back, it always will be a multiple of the kernel page size. So, although you set it to 500 bytes, it's really set to 4 KB:

    $ cat /sys/fs/cgroup/memory/foo/memory.limit_in_bytes4096

Launch the application, move it into the cgroup and monitor the system logs:

    $ sudo tail -f /var/log/messagesOct 14 10:22:40 localhost kernel: sh invoked oom-killer: ↪gfp_mask=0xd0, order=0, oom_score_adj=0Oct 14 10:22:40 localhost kernel: sh cpuset=/ mems_allowed=0Oct 14 10:22:40 localhost kernel: CPU: 0 PID: 2687 Comm: ↪sh Tainted: GOE  ------------   3.10.0-327.36.3.el7.x86_64 #1Oct 14 10:22:40 localhost kernel: Hardware name: innotek GmbHVirtualBox/VirtualBox, BIOS VirtualBox 12/01/2006Oct 14 10:22:40 localhost kernel: ffff880036ea5c00 ↪0000000093314010 ffff88000002bcd0 ffffffff81636431Oct 14 10:22:40 localhost kernel: ffff88000002bd60 ↪ffffffff816313cc 01018800000000d0 ffff88000002bd68Oct 14 10:22:40 localhost kernel: ffffffffbc35e040 ↪fffeefff00000000 0000000000000001 ffff880036ea6103Oct 14 10:22:40 localhost kernel: Call Trace:Oct 14 10:22:40 localhost kernel: [<ffffffff81636431>] ↪dump_stack+0x19/0x1bOct 14 10:22:40 localhost kernel: [<ffffffff816313cc>] ↪dump_header+0x8e/0x214Oct 14 10:22:40 localhost kernel: [<ffffffff8116d21e>] ↪oom_kill_process+0x24e/0x3b0Oct 14 10:22:40 localhost kernel: [<ffffffff81088e4e>] ? ↪has_capability_noaudit+0x1e/0x30Oct 14 10:22:40 localhost kernel: [<ffffffff811d4285>] ↪mem_cgroup_oom_synchronize+0x575/0x5a0Oct 14 10:22:40 localhost kernel: [<ffffffff811d3650>] ? ↪mem_cgroup_charge_common+0xc0/0xc0Oct 14 10:22:40 localhost kernel: [<ffffffff8116da94>] ↪pagefault_out_of_memory+0x14/0x90Oct 14 10:22:40 localhost kernel: [<ffffffff8162f815>] ↪mm_fault_error+0x68/0x12bOct 14 10:22:40 localhost kernel: [<ffffffff816422d2>] ↪__do_page_fault+0x3e2/0x450Oct 14 10:22:40 localhost kernel: [<ffffffff81642363>] ↪do_page_fault+0x23/0x80Oct 14 10:22:40 localhost kernel: [<ffffffff8163e648>] ↪page_fault+0x28/0x30Oct 14 10:22:40 localhost kernel: Task in /foo killed as ↪a result of limit of /fooOct 14 10:22:40 localhost kernel: memory: usage 4kB, limit ↪4kB, failcnt 8Oct 14 10:22:40 localhost kernel: memory+swap: usage 4kB, ↪limit 9007199254740991kB, failcnt 0Oct 14 10:22:40 localhost kernel: kmem: usage 0kB, limit ↪9007199254740991kB, failcnt 0Oct 14 10:22:40 localhost kernel: Memory cgroup stats for /foo: ↪cache:0KB rss:4KB rss_huge:0KB mapped_file:0KB swap:0KB ↪inactive_anon:0KB active_anon:0KB inactive_file:0KB ↪active_file:0KB unevictable:0KBOct 14 10:22:40 localhost kernel: [ pid ]   uid  tgid total_vm ↪rss nr_ptes swapents oom_score_adj nameOct 14 10:22:40 localhost kernel: [ 2687]     0  2687    28281 ↪347     12        0             0 shOct 14 10:22:40 localhost kernel: [ 2702]     0  2702    28281 ↪50    7        0             0 shOct 14 10:22:40 localhost kernel: Memory cgroup out of memory: ↪Kill process 2687 (sh) score 0 or sacrifice childOct 14 10:22:40 localhost kernel: Killed process 2702 (sh) ↪total-vm:113124kB, anon-rss:200kB, file-rss:0kBOct 14 10:22:41 localhost kernel: sh invoked oom-killer: ↪gfp_mask=0xd0, order=0, oom_score_adj=0[ ... ]

Notice that the kernel's Out-Of-Memory Killer (or oom-killer) stepped in as soon as the application hit that 4KB limit. It killed the application, and it's no longer running. You can verify this by typing:

    $ ps -o cgroup 2687CGROUP

### Using libcgroup

Many of the earlier steps described here are simplified by the administration utilities provided in the `libcgroup` package. For example, a single command invocation using the `cgcreate` binary takes care of the process of creating the sysfs entries and files.

To create the group named `foo` under the `memory` subsystem, type the following:

    $ sudo cgcreate -g memory:foo

_Note: libcgroup provides a mechanism for managing tasks in control groups._

Using the same methods as before, you can begin to set thresholds:

    $ echo 50000000 | sudo tee ↪/sys/fs/cgroup/memory/foo/memory.limit_in_bytes

Verify the newly configured setting:

    $ sudo cat memory.limit_in_bytes50003968

Run the application in the cgroup `foo` using the `cgexec` binary:

    $ sudo cgexec -g memory:foo ~/test.sh

Using its PID number, verify that the application is running in the cgroup and under defined subsystem (`memory`):

    $  ps -o cgroup 2945CGROUP6:memory:/foo,1:name=systemd:/user.slice/user-0.slice/↪session-1.scope

If your application is no longer running and you want to clean up and remove the cgroup, you would do that by using the `cgdelete` binary. To remove group `foo` from under the `memory` controller, type:

    $ sudo cgdelete memory:foo

### Persistent Groups

You also can accomplish all of the above from a simple configuration file and the starting of a service. You can define all of your cgroup names and attributes in the /etc/cgconfig.conf file. The following appends a few attributes for the group `foo`:

    $ cat /etc/cgconfig.conf##  Copyright IBM Corporation. 2007##  Authors:     Balbir Singh <balbir@linux.vnet.ibm.com>#  This program is free software; you can redistribute it#  and/or modify it under the terms of version 2.1 of the GNU#  Lesser General Public License as published by the Free#  Software Foundation.##  This program is distributed in the hope that it would be#  useful, but WITHOUT ANY WARRANTY; without even the implied#  warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR#  PURPOSE.### By default, we expect systemd mounts everything on boot,# so there is not much to do.# See man cgconfig.conf for further details, how to create# groups on system boot using this file.group foo {  cpu {    cpu.shares = 100;  }  memory {    memory.limit_in_bytes = 5000000;  }}

The `cpu.shares` options defines the CPU priority of the group. By default, all groups inherit 1,024 shares or 100% of CPU time. By bringing this value down to something a bit more conservative, like 100, the group will be limited to approximately 10% of the CPU time.

As discussed earlier, a process running within a cgroup also can be limited to the amount of CPUs (cores) it can access. Add the following section to the same cgconfig.conf file and under the desired group name:

    cpuset {  cpuset.cpus="0-5";}

With this limit, this cgroup will bind the application to cores 0 to 5—that is, it will see only the first six CPU cores on the system.

Next, you need to load this configuration using the `cgconfig` service. First, enable `cgconfig` to load the above configuration on system boot up:

    $ sudo systemctl enable cgconfigCreate symlink from /etc/systemd/system/sysinit.target.wants/↪cgconfig.serviceto /usr/lib/systemd/system/cgconfig.service.

Now, start the `cgconfig` service and load the same configuration file manually (or you can skip this step and reboot the system):

    $ sudo systemctl start cgconfig

Launch the application into the cgroup `foo` and bind it to your `memory` and `cpu` limits:

    $ sudo cgexec -g memory,cpu,cpuset:foo ~/test.sh &

With the exception of launching the application into the predefined cgroup, all the rest will persist across system reboots. However, you can automate that process by defining a startup init script dependent on the`cgconfig` service to launch that same application.

### Summary

Often it becomes necessary to limit one or more tasks on a machine. Control groups provide that functionality, and by leveraging it, you can enforce strict hardware and software limitations to some of your most vital or uncontrollable applications. If one application does not set an upper threshold or limit the amount of memory it can consume on a system, cgroups can address that. If another application tends to be a bit of a CPU hog, again, cgroups has got you covered. You can accomplish so much with cgroups, and with a little time invested, you'll restore stability, security and sanity back into your operating environment.

In Part II of this series, I move beyond Linux control groups and shift focus to how technologies like Linux Containers make use of them.

# Everything You Need to Know about Linux Containers, Part II: Working with Linux Containers (LXC)

by [Petros Koutoupis](https://www.linuxjournal.com/users/petros-koutoupis)

on August 27, 2018

_[Part I of this Deep Dive on containers](https://www.linuxjournal.com/content/everything-you-need-know-about-linux-containers-part-i-linux-control-groups-and-process) introduces the idea of kernel control groups, or cgroups, and the way you can isolate, limit and monitor selected userspace applications. Here, I dive a bit deeper and focus on the next step of process isolation—that is, through containers, and more specifically, the Linux Containers (LXC) framework._

Containers are about as close to bare metal as you can get when running virtual machines. They impose very little to no overhead when hosting virtual instances. First introduced in 2008, LXC adopted much of its functionality from the Solaris Containers (or Solaris Zones) and FreeBSD jails that preceded it. Instead of creating a full-fledged virtual machine, LXC enables a virtual environment with its own process and network space. Using namespaces to enforce process isolation and leveraging the kernel's very own control groups (cgroups) functionality, the feature limits, accounts for and isolates CPU, memory, disk I/O and network usage of one or more processes. Think of this userspace framework as a very advanced form of `chroot`.

Note: LXC uses namespaces to enforce process isolation, alongside the kernel's very own cgroups to account for and limit CPU, memory, disk I/O and network usage across one or more processes.

But what exactly are containers? The short answer is that containers decouple software applications from the operating system, giving users a clean and minimal Linux environment while running everything else in one or more isolated "containers". The purpose of a container is to launch a limited set of applications or services (often referred to as microservices) and have them run within a self-contained sandboxed environment.

Note: the purpose of a container is to launch a limited set of applications or services and have them run within a self-contained sandboxed environment.

_Figure 1\. A Comparison of Applications Running in a Traditional Environment to Containers_

This isolation prevents processes running within a given container from monitoring or affecting processes running in another container. Also, these containerized services do not influence or disturb the host machine. The idea of being able to consolidate many services scattered across multiple physical servers into one is one of the many reasons data centers have chosen to adopt the technology.

Container features include the following:

*   Security: network services can be run in a container, which limits the damage caused by a security breach or violation. An intruder who successfully exploits a security hole on one of the applications running in that container is restricted to the set of actions possible within that container.
*   Isolation: containers allow the deployment of one or more applications on the same physical machine, even if those applications must operate under different domains, each requiring exclusive access to its respective resources. For instance, multiple applications running in different containers can bind to the same physical network interface by using distinct IP addresses associated with each container.
*   Virtualization and transparency: containers provide the system with a virtualized environment that can hide or limit the visibility of the physical devices or system's configuration underneath it. The general principle behind a container is to avoid changing the environment in which applications are running with the exception of addressing security or isolation issues.

### Using the LXC Utilities

For most modern Linux distributions, the kernel is enabled with cgroups, but you most likely still will need to install the LXC utilities.

If you're using Red Hat or CentOS, you'll need to install the EPEL repositories first. For other distributions, such as Ubuntu or Debian, simply type:

    $ sudo apt-get install lxc

Now, before you start tinkering with those utilities, you need to configure your environment. And before doing that, you need to verify that your current user has both a `uid` and `gid` entry defined in /etc/subuid and /etc/subgid:

    $ cat /etc/subuidpetros:100000:65536$ cat /etc/subgidpetros:100000:65536

Create the ~/.config/lxc directory if it doesn't already exist, and copy the /etc/lxc/default.conf configuration file to ~/.config/lxc/default.conf. Append the following two lines to the end of the file:

    lxc.id_map = u 0 100000 65536lxc.id_map = g 0 100000 65536

It should look something like this:

    $ cat ~/.config/lxc/default.conflxc.network.type = vethlxc.network.link = lxcbr0lxc.network.flags = uplxc.network.hwaddr = 00:16:3e:xx:xx:xxlxc.id_map = u 0 100000 65536lxc.id_map = g 0 100000 65536

Append the following to the /etc/lxc/lxc-usernet file (replace the first column with your user name):

    petros veth lxcbr0 10

The quickest way for these settings to take effect is either to reboot the node or log the user out and then log back in.

Once logged back in, verify that the `veth` networking driver is currently loaded:

    $ lsmod|grep vethveth                   16384  0

If it isn't, type:

    $ sudo modprobe veth

You now can use the LXC utilities to download, run and manage Linux containers.

Next, download a container image and name it "example-container". When you type the following command, you'll see a long list of supported containers under many Linux distributions and versions:

    $ sudo lxc-create -t download -n example-container

You'll be given three prompts to pick the distribution, release and architecture. I chose the following:

    Distribution: ubuntuRelease: xenialArchitecture: amd64

Once you make a decision and press Enter, the rootfs will be downloaded locally and configured. For security reasons, each container does not ship with an OpenSSH server or user accounts. A default root password also is not provided. In order to change the root password and log in, you must run either an `lxc-attach` or `chroot` into the container directory path (after it has been started).

Start the container:

    $ sudo lxc-start -n example-container -d

The `-d` option dæmonizes the container, and it will run in the background. If you want to observe the boot process, replace the `-d` with `-F`, and it will run in the foreground, ending at a login prompt.

You may experience an error similar to the following:

    $ sudo lxc-start -n example-container -dlxc-start: tools/lxc_start.c: main: 366 The containerfailed to start.lxc-start: tools/lxc_start.c: main: 368 To get more details,run the container in foreground mode.lxc-start: tools/lxc_start.c: main: 370 Additional informationcan be obtained by setting the --logfile and --logpriorityoptions.

If you do, you'll need to debug it by running the `lxc-start` service in the foreground:

    $ sudo lxc-start -n example-container -Flxc-start: conf.c: instantiate_veth: 2685 failed to create veth pair (vethQ4NS0B and vethJMHON2): Operation not supported    lxc-start: conf.c: lxc_create_network: 3029 failed to    create netdev    lxc-start: start.c: lxc_spawn: 1103 Failed to create    the network.    lxc-start: start.c: __lxc_start: 1358 Failed to spawn    container "example-container".    lxc-start: tools/lxc_start.c: main: 366 The container failed    to start.    lxc-start: tools/lxc_start.c: main: 370 Additional information    can be obtained by setting the --logfile and --logpriority    options.

From the example above, you can see that the `veth` module probably isn't inserted. After inserting it, it resolved the issue.

Anyway, open up a second terminal window and verify the status of the container:

    $ sudo lxc-info -n example-containerName:           example-containerState:          RUNNINGPID:            1356IP:             10.0.3.28CPU use:        0.29 secondsBlkIO use:      16.80 MiBMemory use:     29.02 MiBKMem use:       0 bytesLink:           vethPRK7YU TX bytes:      1.34 KiB RX bytes:      2.09 KiB Total bytes:   3.43 KiB

Another way to do this is by running the following command to list all installed containers:

    $ sudo lxc-ls -fNAME         STATE   AUTOSTART GROUPS IPV4      IPV6example-container RUNNING 0         -      10.0.3.28 -

But there's a problem—you still can't log in! Attach directly to the running container, create your users and change all relevant passwords using the `passwd` command:

    $ sudo lxc-attach -n example-containerroot@example-container:/#root@example-container:/# useradd petrosroot@example-container:/# passwd petrosEnter new UNIX password:Retype new UNIX password:passwd: password updated successfully

Once the passwords are changed, you'll be able to log in directly to the container from a console and without the `lxc-attach` command:

    $ sudo lxc-console -n example-container

If you want to connect to this running container over the network, install the OpenSSH server:

    root@example-container:/# apt-get install openssh-server

Grab the container's local IP address:

    root@example-container:/# ip addr show eth0|grep inet    inet 10.0.3.25/24 brd 10.0.3.255 scope global eth0    inet6 fe80::216:3eff:fed8:53b4/64 scope link

Then from the host machine and in a new console window, type:

    $ ssh 10.0.3.25

Voilà! You now can `ssh` in to the running container and type your user name and password.

On the host system, and not within the container, it's interesting to observe which LXC processes are initiated and running after launching a container:

    $ ps aux|grep lxc|grep -v greproot       861  0.0  0.0 234772  1368 ?        Ssl  11:01 ↪0:00 /usr/bin/lxcfs /var/lib/lxcfs/lxc-dns+  1155  0.0  0.1  52868  2908 ?        S    11:01 ↪0:00 dnsmasq -u lxc-dnsmasq --strict-order ↪--bind-interfaces --pid-file=/run/lxc/dnsmasq.pid ↪--listen-address 10.0.3.1 --dhcp-range 10.0.3.2,10.0.3.254 ↪--dhcp-lease-max=253 --dhcp-no-override ↪--except-interface=lo --interface=lxcbr0 ↪--dhcp-leasefile=/var/lib/misc/dnsmasq.lxcbr0.leases ↪--dhcp-authoritativeroot      1196  0.0  0.1  54484  3928 ?        Ss   11:01 ↪0:00 [lxc monitor] /var/lib/lxc example-containerroot      1658  0.0  0.1  54780  3960 pts/1    S+   11:02 ↪0:00 sudo lxc-attach -n example-containerroot      1660  0.0  0.2  54464  4900 pts/1    S+   11:02 ↪0:00 lxc-attach -n example-container

To stop a container, type (from the host machine):

    $ sudo lxc-stop -n example-container

Once stopped, verify the state of the container:

    $ sudo lxc-ls -fNAME         STATE   AUTOSTART GROUPS IPV4 IPV6example-container STOPPED 0         -      -    -$ sudo lxc-info -n example-containerName:           example-containerState:          STOPPED

To destroy a container completely—that is, purge it from the host system—type:

    $ sudo lxc-destroy -n example-containerDestroyed container example-container

Once destroyed, verify that it has been removed:

    $ sudo lxc-info -n example-containerexample-container doesn't exist$ sudo lxc-ls -f$

Note: if you attempt to destroy a running container, the command will fail and inform you that the container is still running:

    $ sudo lxc-destroy -n example-containerexample-container is running

A container must be stopped before it is destroyed.

### Advanced Configurations

At times, it may be necessary to configure one or more containers to accomplish one or more tasks. LXC simplifies this by having the administrator modify the container's configuration file located in /var/lib/lxc:

    $ sudo su# cd /var/lib/lxc# lsexample-container

The container's parent directory will consist of at least two files: 1) the container config file and 2) the container's entire rootfs:

    # cd example-container/# lsconfig  rootfs

Say you want to autostart the container labeled example-container on host system boot up. To do this, you'd need to append the following lines to the container's configuration file, /var/lib/lxc/example-container/config:

    # Enable autostartlxc.start.auto = 1

After you restart the container or reboot the host system, you should see something like this:

    $ sudo lxc-ls -fNAME              STATE   AUTOSTART GROUPS IPV4      IPV6example-container RUNNING 1         -      10.0.3.25 -

Notice how the `AUTOSTART` field is now set to "1".

If, on container boot up, you want the container to bind mount a directory path living on the host machine, append the following lines to the same file:

    # Bind mount system path to local pathlxc.mount.entry = /mnt mnt none bind 0 0

With the above example and when the container gets restarted, you'll see the contents of the host's /mnt directory accessible to the container's local /mnt directory.

### Privileged vs. Unprivileged Containers

You often may stumble across LXC-related content discussing the idea of a privileged container and an unprivileged container. But what are those exactly? The concept is pretty straightforward, and an LXC container can run in either configuration.

By design, an unprivileged container is considered safer and more secure than a privileged one. An unprivileged container runs with a mapping of the container's root UID to a non-root UID on the host system. This makes it more difficult for attackers compromising a container to gain root privileges to the underlying host machine. In short, if attackers manage to compromise your container through, for example, a known software vulnerability, they immediately will find themselves with no rights on the host machine.

Privileged containers can and will expose a system to such attacks. That's why it's good practice to run few containers in privileged mode. Identify the containers that require privileged access, and be sure to make extra efforts to update routinely and lock them down in other ways.

### And, What about Docker?

I spent a considerable amount of time talking about Linux Containers, but what about Docker? It _is_ the most deployed container solution in production. Since its initial launch, Docker has taken the Linux computing world by storm. Docker is an Apache-licensed open-source containerization technology designed to automate the repetitive task of creating and deploying microservices inside containers. Docker treats containers as if they were extremely lightweight and modular virtual machines. Initially, Docker was built on top of LXC, but it has since moved away from that dependency, resulting in a better developer and user experience. Much like LXC, Docker continues to make use of the kernel cgroup subsystem. The technology is more than just running containers, it also eases the process of creating containers, building images, sharing those built images and versioning them.

Docker primarily focuses on:

*   Portability: Docker provides an image-based deployment model. This type of portability allows for an easier way to share an application or set of services (with all of their dependencies) across multiple environments.
*   Version control: a single Docker image is made up of a series of combined layers. A new layer is created whenever the image is altered. For instance, a new layer is created every time a user specifies a command, such as `run` or `copy`. Docker will reuse these layers for new container builds. Layering to Docker is its very own method of version control.
*   Rollback: again, every Docker image has layers. If you don't want to use the currently running layer, you can roll back to a previous version. This type of agility makes it easier for software developers to integrate and deploy their software technology continuously.
*   Rapid deployment: provisioning new hardware often can take days. And, the amount of effort and overhead to get it installed and configured is quite burdensome. With Docker, you can avoid all of that by reducing the time it takes to get an image up and running to a matter of seconds. When you're done with a container, you can destroy it just as easily.

Fundamentally, both Docker and LXC are very similar. They both are userspace and lightweight virtualization platforms that implement cgroups and namespaces to manage resource isolation. However, there are a number of distinct differences between the two.

**Process Management**

Docker restricts containers to run as a single process. If your application consists of X number of concurrent processes, Docker will want you to run X number of containers, each with its own distinct process. This is not the case with LXC, which runs a container with a conventional init process and, in turn, can host multiple processes inside that same container. For example, if you want to host a LAMP (Linux + Apache + MySQL + PHP) server, each process for each application will need to span across multiple Docker containers.

**State Management**

Docker is designed to be stateless, meaning it doesn't support persistent storage. There are ways around this, but again, it's only necessary when the process requires it. When a Docker image is created, it will consist of read-only layers. This will not change. During runtime, if the process of the container makes any changes to its internal state, a diff between the internal state and the current state of the image will be maintained until either a commit is made to the Docker image (creating a new layer) or until the container is deleted, resulting in that diff disappearing.

**Portability**

This word tends to be overused when discussing Docker—that's because it's the single-most important advantage Docker has over LXC. Docker does a much better job of abstracting away the networking, storage and operating system details from the application. This results in a truly configuration-independent application, guaranteeing that the environment for the application always will remain the same, regardless of the machine on which it is enabled.

Docker is designed to benefit both developers and system administrators. It has made itself an integral part of many DevOps (developers + operations) toolchains. Developers can focus on writing code without having to worry about the system ultimately hosting it. With Docker, there's no need to install and configure complex databases or worry about switching between incompatible language toolchain versions. Docker gives the operations staff flexibility, often reducing the number of physical systems needed to host some of the smaller and more basic applications. Docker streamlines software delivery. New features and bug/security fixes reach the customer quickly without any hassle, surprises or downtime.

### Summary

Isolating processes for the sake of infrastructure security and system stability isn't as painful as it sounds. The Linux kernel provides all the necessary facilities to enable simple-to-use userspace applications, such as LXC (and even Docker), to manage micro-instances of an operating system with its local services in an isolated and sandboxed environment.

In Part III of this series, I describe container orchestration with Kubernetes.

### Resources

*   [Linux Containers—linuxcontainers.org](https://linuxcontainers.org/)
*   [Docker vs LXC](https://www.upguard.com/articles/docker-vs-lxc)
*   [Debian Wiki: LXC](https://wiki.debian.org/LXC)
*   [LXC—Ubuntu Documentation](https://help.ubuntu.com/lts/serverguide/lxc.html)
*   [GitHub—LXC](https://github.com/lxc/lxc)
