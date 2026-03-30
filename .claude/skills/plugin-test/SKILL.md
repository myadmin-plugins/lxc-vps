---
name: plugin-test
description: Writes PHPUnit test methods in `tests/PluginTest.php` for the myadmin-lxc-vps plugin using `ReflectionClass` to assert static properties, hook structure, method signatures, and template file existence. Use when user says 'add test', 'write tests for', 'test coverage', or 'test the plugin'. Do NOT use for integration tests that require runtime functions like `myadmin_log`, `get_service_define`, or `get_module_settings`.
---
# plugin-test

## Critical

- **Never** call `Plugin::getActivate()`, `Plugin::getDeactivate()`, `Plugin::getSettings()`, or `Plugin::getQueue()` directly in tests — they depend on `myadmin_log`, `get_service_define`, and `\TFSmarty` which are not available in unit test context.
- **Never** call `Plugin::GetList()` directly — it executes `shell_exec('lxc list ...')` on the host.
- All assertions use `self::assert*()` (not `$this->assert*()`). PHPUnit 9 static style only.
- Tests go in `tests/PluginTest.php`, namespace `Detain\MyAdminLxc\Tests`, class `PluginTest extends TestCase`.
- `$this->reflection` is a `ReflectionClass<Plugin>` set up in `setUp()` — use it for all structural introspection.
- Template path is derived via `dirname((new ReflectionClass(Plugin::class))->getFileName()) . '/../templates'` — never hard-code absolute paths.

## Instructions

1. **Open the test file.** Read `tests/PluginTest.php` to understand which sections already exist. The file is divided into comment-block sections: `Class structure tests`, `Static property tests`, `getHooks() tests`, `Event handler signature tests`, `GetList() parser tests`, `Constructor tests`, `Template file existence tests`, `Static analysis / code-quality tests`.

   Verify the file has the header before proceeding:
   ```php
   declare(strict_types=1);
   namespace Detain\MyAdminLxc\Tests;
   use Detain\MyAdminLxc\Plugin;
   use PHPUnit\Framework\TestCase;
   use ReflectionClass;
   use Symfony\Component\EventDispatcher\GenericEvent;
   ```

2. **Identify the target category** for the new test(s). Match the user's request to one of the existing section groups. Add the new method inside the matching `// ---` comment block.

3. **Write the test method** following this exact template:
   ```php
   /**
    * Verify [one-line description of what is asserted].
    */
   public function test[PascalCaseName](): void
   {
       // use $this->reflection for structural checks
       // use Plugin::$prop / Plugin::method() for value checks
       self::assert*(...);
   }
   ```
   - Method name: `test` + PascalCase description of the assertion.
   - Return type: always `: void`.
   - Docblock: one-line `Verify ...` sentence.
   - Use `self::assertSame` for exact equality, `self::assertIsString` / `self::assertIsArray` for type checks, `self::assertFileExists` / `self::assertDirectoryExists` for filesystem checks.

4. **Static property tests** — access via `Plugin::$propName` directly:
   ```php
   self::assertSame('expected', Plugin::$name);
   self::assertIsString(Plugin::$description);
   self::assertNotEmpty(Plugin::$description);
   ```

5. **Hook structure tests** — always call `Plugin::getHooks()` and assert against the returned array:
   ```php
   $hooks = Plugin::getHooks();
   self::assertArrayHasKey('vps.settings', $hooks);
   self::assertSame(Plugin::class, $hooks['vps.settings'][0]);
   self::assertSame('getSettings', $hooks['vps.settings'][1]);
   ```

6. **Method signature tests** — use `$this->reflection->getMethod('methodName')` and inspect parameters:
   ```php
   $method = $this->reflection->getMethod('getQueue');
   $params = $method->getParameters();
   self::assertCount(1, $params);
   self::assertSame('event', $params[0]->getName());
   self::assertSame(GenericEvent::class, $params[0]->getType()->getName());
   ```

7. **Template file existence tests** — derive the path from reflection, never hard-code it:
   ```php
   $templateDir = dirname((new ReflectionClass(Plugin::class))->getFileName()) . '/../templates';
   self::assertFileExists($templateDir . '/create.sh.tpl');
   ```
   Template names follow `{action}.sh.tpl`. Known actions: `backup`, `create`, `delete`, `destroy`, `reinstall_os`, `restart`, `restore`, `set_slices`, `start`, `stop`.

8. **Run tests to verify.** After editing the file:
   ```bash
   vendor/bin/phpunit
   ```
   All tests must pass (green). Fix any failures before finishing.

## Examples

**User says:** "Add a test that verifies the reinstall_os template exists."

**Actions taken:**
- Locate the `Template file existence tests` section in `tests/PluginTest.php`.
- Add inside that section:

```php
/**
 * Verify the reinstall_os shell template exists.
 */
public function testReinstallOsTemplateExists(): void
{
    $templateDir = dirname((new ReflectionClass(Plugin::class))->getFileName()) . '/../templates';
    self::assertFileExists(
        $templateDir . '/reinstall_os.sh.tpl',
        "Expected template file 'reinstall_os.sh.tpl' to exist"
    );
}
```

- Run `vendor/bin/phpunit` — confirm green.

---

**User says:** "Add a test that the $help property is a string."

**Actions taken:**
- Locate the `Static property tests` section.
- Add:

```php
/**
 * Verify the $help static property is a string.
 */
public function testHelpPropertyIsString(): void
{
    self::assertIsString(Plugin::$help);
}
```

- Run `vendor/bin/phpunit` — confirm green.

## Common Issues

- **`Error: Call to undefined function myadmin_log()`** — you called an event handler method directly (e.g. `Plugin::getDeactivate(...)`). Use `ReflectionClass` to inspect signatures instead; never invoke event handlers in unit tests.

- **`Error: Call to undefined function get_service_define()`** — same cause as above. Remove any direct call to `getActivate`, `getDeactivate`, `getQueue`, or `getSettings`.

- **`Failed asserting that file exists` for a template** — the path is wrong. Always derive it via `dirname((new ReflectionClass(Plugin::class))->getFileName()) . '/../templates'`. Confirm the file is present: `ls templates/` from the package root.

- **`Method Plugin::XYZ does not exist`** in a `ReflectionClass` call — the method name is wrong or was not yet added to `src/Plugin.php`. Check `Plugin.php` with `grep 'public static function' src/Plugin.php` before referencing it in a test.

- **`PHPUnit\Framework\TestCase not found`** — run `composer install` to install PHPUnit 9 into `vendor/`.

- **Duplicate test method name** — PHPUnit silently skips duplicates. Always search `tests/PluginTest.php` for the method name before adding it: `grep 'function testMyName' tests/PluginTest.php`.