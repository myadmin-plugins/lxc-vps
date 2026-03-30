---
name: shell-template
description: Creates a new Smarty shell template in `templates/` following the `{action}.sh.tpl` naming pattern. Template receives `$serviceInfo` vars via `\TFSmarty->assign()`. Use when user says 'add template', 'new lxc action', 'create shell script template', or adds an action to `getQueue()`. Do NOT use for editing `src/Plugin.php` handler logic or hook registration.
---
# shell-template

## Critical

- The template filename **must exactly match** `$serviceInfo['action']` — `getQueue()` builds the path as `__DIR__.'/../templates/'.$serviceInfo['action'].'.sh.tpl'`. A mismatch silently skips execution.
- Never use bare shell variable syntax `${VAR}` inside Smarty templates — Smarty will try to parse it. Use `{$varname}` (Smarty syntax) for all dynamic values.
- All `$serviceInfo` array keys are available as Smarty variables after `$smarty->assign($serviceInfo)`. Common keys: `{$vps_vzid}`, `{$vps_id}`, `{$vps_slices}`, `{$vps_custid}`, `{$vps_os}`, `{$vzid}`, `{$mac}`, `{$ip}`, `{$rootpass}`, `{$domain}`, `{$email}`, `{$param}`. Settings are available as `{$settings.key_name}` (dot notation).
- Do not add a shebang (`#!/bin/bash`) — the scripts are sourced/executed externally; use `export PATH=...` instead when needed.

## Instructions

1. **Determine the action name.** Identify the action string that will appear in `$serviceInfo['action']`. This becomes the filename stem (e.g., action `suspend` → file `templates/suspend.sh.tpl`). Verify no existing template already covers this action:
   ```bash
   ls templates/
   ```

2. **Create the file** at `templates/{action}.sh.tpl`. Do not create it in any subdirectory.

3. **Add PATH export** at the top for any template that runs `lxc` or system commands directly (omit for templates that call external scripts like `/admin/swift/...`):
   ```smarty
   export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
   ```

4. **Declare computed variables** with `{assign}` before using them. Use Smarty arithmetic and PHP functions directly:
   ```smarty
   {assign var=ram value=$vps_slices * $settings.slice_ram}
   {assign var=hd value=(($settings.slice_hd * $vps_slices) + $settings.additional_hd) * 1024}
   {if in_array($vps_custid, [2773,8,2304])}
   {assign var=vcpu value=ceil($vps_slices / 2)}
   {else}
   {assign var=vcpu value=ceil($vps_slices / 4)}
   {/if}
   ```
   Skip this step for simple templates that only reference `$serviceInfo` vars directly.

5. **Write the shell commands**, interpolating Smarty variables with `{$varname}`:
   - Simple action (start/stop/restart pattern):
     ```smarty
     export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
     lxc {action} {$vps_vzid};
     ```
   - External script invocation (backup pattern):
     ```smarty
     /admin/swift/vpsbackup {$vps_id} '{$email}'
     ```
   - Template with split param (restore pattern — splits `param` on `:`):  
     ```smarty
     {assign var='param' value=':'|explode:$param}
     /root/cpaneldirect/vps_swift_restore.sh {$param[0]} {$param[1]} {$vps_vzid} && \\
     curl --connect-timeout 60 --max-time 600 -k -d action={action}_status -d vps_id={$vps_id} https://{$domain}/vps_queue.php || \\
     curl --connect-timeout 60 --max-time 600 -k -d action={action}_status -d vps_id={$vps_id} https://{$domain}/vps_queue.php;
     ```

6. **End the file with a trailing newline.** All existing templates end with a blank line after the last command.

7. **Verify the template renders** by confirming the action is handled in `getQueue()` in `src/Plugin.php`. The method must reach the `$smarty->fetch(...)` call for your action. If a new action is needed, that is a separate change to `src/Plugin.php` — outside this skill's scope.

8. **Run the test suite** to confirm nothing is broken:
   ```bash
   composer test
   ```

## Examples

**User says:** "Add a `suspend` template that stops the container without deleting it"

**Actions taken:**
1. Confirm `suspend` is not already in `templates/` — not found.
2. Create `templates/suspend.sh.tpl`:
   ```smarty
   export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
   lxc stop {$vps_vzid};
   ```
3. Run `composer test` — passes.

**Result:** `templates/suspend.sh.tpl` is created. When `$serviceInfo['action'] === 'suspend'`, `getQueue()` renders this template and appends the shell output to `$event['output']`.

---

**User says:** "Add a `resize_disk` template that updates disk limits using slice count and settings"

**Actions taken:**
1. Confirm `resize_disk` is not in `templates/` — not found.
2. Create `templates/resize_disk.sh.tpl`:
   ```smarty
   {assign var=hd value=(($settings.slice_hd * $vps_slices) + $settings.additional_hd) * 1024}
   export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
   lxc config device set {$vps_vzid} root size={$hd}GB;
   ```
3. Run `composer test` — passes.

**Result:** Template correctly computes disk size from `$settings.slice_hd`, `$vps_slices`, and `$settings.additional_hd`, then runs the `lxc config device set` command.

## Common Issues

- **Template not found / silent skip:** `getQueue()` only fetches the template if `$event['type'] == get_service_define('LXC')` and the file exists at the exact path. If your new action produces no output, verify:
  1. The filename matches `$serviceInfo['action']` exactly (case-sensitive): `ls templates/ | grep {action}`
  2. The `src/Plugin.php` `getQueue()` method actually reaches the `$smarty->fetch()` line for your action (no early `return` or type mismatch).

- **Smarty parse error on `${VAR}`:** Shell variables like `${HOME}` or `${1}` inside the template cause Smarty parse failures. Escape them as `{'$'}{varname}` or use the `{literal}...{/literal}` block around shell sections containing bare `$` syntax:
  ```smarty
  {literal}
  for VAR in ${LIST[@]}; do echo $VAR; done
  {/literal}
  ```

- **Arithmetic produces float (e.g., `4.0`):** Smarty math returns floats. Wrap with `round()` or `intval()` if the shell command requires an integer:
  ```smarty
  {assign var=vcpu value=round($vps_slices / 4)}
  ```

- **`{$settings.key}` is empty:** The key name must match the lowercased constant name stored in the settings object. Check `getSettings()` in `src/Plugin.php` for the exact `add_*_setting()` call and use the second argument (the setting key string, e.g., `vps_slice_lxc_cost`) as the dot-notation key (`{$settings.vps_slice_lxc_cost}`).

- **Test failures after adding template:** The test suite (`tests/PluginTest.php`) may assert on `getHooks()` or static properties. A new template alone does not require test changes — if tests fail, the issue is in `src/Plugin.php`, not the template file.
