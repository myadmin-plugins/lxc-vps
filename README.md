# MyAdmin LXC VPS Plugin

[![Tests](https://github.com/detain/myadmin-lxc-vps/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-lxc-vps/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-lxc-vps/version)](https://packagist.org/packages/detain/myadmin-lxc-vps)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-lxc-vps/downloads)](https://packagist.org/packages/detain/myadmin-lxc-vps)
[![License](https://poser.pugx.org/detain/myadmin-lxc-vps/license)](https://packagist.org/packages/detain/myadmin-lxc-vps)

LXC (Linux Containers) virtual private server plugin for the [MyAdmin](https://github.com/detain/myadmin) control panel. This package provides provisioning, lifecycle management, and billing integration for LXC-based VPS instances, including container creation, start/stop, backup/restore, and OS reinstallation through Smarty-based shell templates.

## Features

- Event-driven architecture using Symfony EventDispatcher
- Container lifecycle management (create, start, stop, restart, destroy)
- Backup and restore support
- Configurable slice-based billing
- Shell template rendering via Smarty for server-side operations

## Requirements

- PHP >= 5.0
- ext-soap
- Symfony EventDispatcher ^5.0

## Installation

```sh
composer require detain/myadmin-lxc-vps
```

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html) license.
