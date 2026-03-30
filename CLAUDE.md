# MyAdmin LXC VPS Plugin

LXC container provisioning and lifecycle plugin for the MyAdmin control panel.

## Commands

```bash
composer install                        # install PHP deps
vendor/bin/phpunit                      # run all tests
vendor/bin/phpunit tests/ -v  # verbose
vendor/bin/phpunit --coverage-clover coverage.xml --whitelist src/  # with coverage
```

## Architecture

- **Plugin class**: `src/Plugin.php` · namespace `Detain\MyAdminLxc\` · all methods `public static`
- **Templates**: `templates/*.sh.tpl` — Smarty shell scripts rendered in `getQueue()` via `\TFSmarty`
- **Tests**: `tests/PluginTest.php` · namespace `Detain\MyAdminLxc\Tests\` · PHPUnit 9 · `phpunit.xml.dist`
- **Events**: `symfony/event-dispatcher ^5.0` · handlers accept `Symfony\Component\EventDispatcher\GenericEvent`
- **Module**: `Plugin::$module = 'vps'` · service type `Plugin::$type = 'service'`
- **CI**: `.github/` contains GitHub Actions workflows for automated testing and deployment pipelines
- **IDE**: `.idea/` stores project-level IDE configuration including `inspectionProfiles/`, `deployment.xml`, and `encodings.xml`

## Hook Registration

`Plugin::getHooks()` returns hook map — keys are `{module}.{event}`:

```php
public static function getHooks()
{
    return [
        self::$module.'.settings'   => [__CLASS__, 'getSettings'],
        self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
        self::$module.'.queue'      => [__CLASS__, 'getQueue'],
    ];
}
```

- Check `$event['type'] == get_service_define('LXC')` before acting
- Call `$event->stopPropagation()` after handling
- Log with `myadmin_log(self::$module, 'info'|'error', $msg, __LINE__, __FILE__, self::$module, $id, true, false, $custid)`

## Queue / Template Rendering

`getQueue()` in `src/Plugin.php` renders `templates/{action}.sh.tpl` via `\TFSmarty`:

```php
$smarty = new \TFSmarty();
$smarty->assign($serviceInfo);
$output = $smarty->fetch(__DIR__.'/../templates/'.$serviceInfo['action'].'.sh.tpl');
$event['output'] = $event['output'].$output;
```

Existing templates: `backup.sh.tpl` · `create.sh.tpl` · `delete.sh.tpl` · `destroy.sh.tpl` · `reinstall_os.sh.tpl` · `restart.sh.tpl` · `restore.sh.tpl` · `set_slices.sh.tpl` · `start.sh.tpl` · `stop.sh.tpl`

## Settings Pattern

`getSettings()` registers module settings via the settings object:

```php
$settings->setTarget('module');
$settings->add_text_setting(self::$module, _('Label'), 'vps_slice_lxc_cost', _('Title'), _('Desc'), $settings->get_setting('VPS_SLICE_LXC_COST'));
$settings->add_dropdown_setting(self::$module, _('Label'), 'outofstock_lxc', _('Title'), _('Desc'), $settings->get_setting('OUTOFSTOCK_LXC'), ['0', '1'], ['No', 'Yes']);
$settings->setTarget('global');
```

## Conventions

- All `Plugin` methods are `public static` — no instance state
- Static properties: `$name`, `$description`, `$help`, `$module`, `$type`
- `GetList()` parses `lxc list` shell output; uses `PascalCase` (legacy — keep as-is)
- Commit messages: lowercase, descriptive (`lxc updates`, `fix queue handler`)
- i18n strings wrapped in `_()`
- Template file naming: `{action}.sh.tpl` — must match `$serviceInfo['action']`

## Testing Conventions

See `tests/PluginTest.php` for patterns:
- Use `ReflectionClass` to assert static properties, method signatures, visibility
- Test `getHooks()` keys start with `Plugin::$module.'.'`
- Assert template files exist via `assertFileExists()` on `templates/*.sh.tpl`
- No mocking of `myadmin_log` or `get_service_define` — test structure, not integration

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
