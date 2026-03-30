---
name: plugin-event-handler
description: Adds a new static event handler method to src/Plugin.php following the GenericEvent pattern. Registers the hook in getHooks(), checks get_service_define('LXC'), logs with myadmin_log, and calls stopPropagation(). Use when user says 'add hook', 'new event handler', 'handle event', or 'register vps event'. Do NOT use for modifying getQueue() template logic or adding Smarty template rendering.
---
# plugin-event-handler

## Critical

- All handler methods MUST be `public static` — this class has zero instance state
- ALWAYS check `$event['type'] == get_service_define('LXC')` before acting — handlers receive events for all VPS types
- ALWAYS call `$event->stopPropagation()` after handling to prevent other plugins from double-processing
- ALWAYS log with `myadmin_log` using the exact 9-argument signature (see Step 3)
- Do NOT add instance properties — `testNoInstanceProperties()` in `tests/PluginTest.php` will fail
- Do NOT skip the docblock — `testAllPublicMethodsHaveDocblocks()` enforces docblocks on all public methods
- Do NOT use this skill for `getQueue()` changes — that path renders Smarty shell templates and is handled separately

## Instructions

### Step 1 — Name the hook and method

Decide on:
- **Event key**: `{module}.{event}` — must use `self::$module` as prefix (e.g. `vps.suspend`)
- **Method name**: `get` + PascalCase of event name (e.g. `getSuspend`)

Verify no existing method with that name exists in `src/Plugin.php` before proceeding.

### Step 2 — Register the hook in `getHooks()`

In `src/Plugin.php`, add the new entry to the array returned by `getHooks()`:

```php
public static function getHooks()
{
    return [
        self::$module.'.settings'   => [__CLASS__, 'getSettings'],
        self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
        self::$module.'.queue'      => [__CLASS__, 'getQueue'],
        self::$module.'.suspend'    => [__CLASS__, 'getSuspend'],  // ← add here
    ];
}
```

Verify: `composer test -- --filter testGetHooksContainsExpectedKeys` still passes (update that test too — see Step 5).

### Step 3 — Add the handler method

Insert the new method after the last existing handler, before `GetList()`. Use this exact structure:

**Subject is a service class** (like `getActivate` / `getDeactivate`):
```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getSuspend(GenericEvent $event)
{
    if ($event['type'] == get_service_define('LXC')) {
        $serviceClass = $event->getSubject();
        myadmin_log(self::$module, 'info', self::$name.' Suspension', __LINE__, __FILE__, self::$module, $serviceClass->getId(), true, false, $serviceClass->getCustid());
        $event->stopPropagation();
    }
}
```

**Subject is a settings object** (like `getSettings` — only for settings-type events):
```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getMySettings(GenericEvent $event)
{
    /** @var \MyAdmin\Settings $settings **/
    $settings = $event->getSubject();
    $settings->setTarget('module');
    // add_text_setting / add_dropdown_setting calls here
    $settings->setTarget('global');
}
```

`myadmin_log` signature — all 9 arguments are required:
```php
myadmin_log(
    self::$module,           // module name ('vps')
    'info',                  // level: 'info' or 'error'
    'Message text',          // human-readable message
    __LINE__,                // line number
    __FILE__,                // file path
    self::$module,           // repeat module name
    $serviceClass->getId(),  // service/VPS ID
    true,                    // write to db log
    false,                   // do not email
    $serviceClass->getCustid() // customer ID
);
```

Verify: the method signature matches `testGetActivateSignature` pattern — exactly one `GenericEvent $event` parameter, no return type declaration.

### Step 4 — Run the tests

Run the full test suite via `tests/PluginTest.php`:

```
composer test
```

Expected: all pre-existing tests pass. If `testHookCount` fails, see Common Issues. If `testExpectedPublicStaticMethods` fails, see Common Issues.

### Step 5 — Update tests in `tests/PluginTest.php`

Add assertions for the new hook and handler:

```php
// In testGetHooksContainsExpectedKeys()
self::assertArrayHasKey('vps.suspend', $hooks);

// New signature test
public function testGetSuspendSignature(): void
{
    $method = $this->reflection->getMethod('getSuspend');
    $params = $method->getParameters();
    self::assertCount(1, $params);
    self::assertSame('event', $params[0]->getName());
    $type = $params[0]->getType();
    self::assertNotNull($type);
    self::assertSame(GenericEvent::class, $type->getName());
}
```

Also update `testHookCount` to expect the new count and `testExpectedPublicStaticMethods` to include the new method name.

Verify: `composer test` passes with zero failures.

## Examples

**User says:** "Add a suspend event handler for LXC VPS"

**Actions taken:**

1. Event key: `vps.suspend`, method: `getSuspend`
2. Add to `getHooks()` in `src/Plugin.php`: `self::$module.'.suspend' => [__CLASS__, 'getSuspend']`
3. Add method to `src/Plugin.php`:

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getSuspend(GenericEvent $event)
{
    if ($event['type'] == get_service_define('LXC')) {
        $serviceClass = $event->getSubject();
        myadmin_log(self::$module, 'info', self::$name.' Suspension', __LINE__, __FILE__, self::$module, $serviceClass->getId(), true, false, $serviceClass->getCustid());
        $event->stopPropagation();
    }
}
```

4. Update `tests/PluginTest.php`: add `assertArrayHasKey('vps.suspend', $hooks)`, update `testHookCount` to `assertCount(4, $hooks)`, add `getSuspend` to `testExpectedPublicStaticMethods`, add `testGetSuspendSignature()`
5. Run `composer test` — all green

**Result:** `getHooks()` now returns 4 entries; `getSuspend` exists as a public static method with correct signature; all PHPUnit tests pass.

## Common Issues

**`testHookCount` fails with `Failed asserting that 3 is identical to 4`:**
You added the method but forgot to add the entry to `getHooks()`, or you updated `getHooks()` but forgot to update the count assertion in the test. Fix: add the hook entry to `getHooks()` AND change `assertCount(3, $hooks)` → `assertCount(4, $hooks)` in `tests/PluginTest.php`.

**`testExpectedPublicStaticMethods` fails:**
The test asserts an exact list of public static methods. Add the new method name (e.g. `'getSuspend'`) to the `$expected` array in that test.

**`testAllPublicMethodsHaveDocblocks` fails:**
You added the method without a `/** ... */` docblock. Add the `@param` docblock shown in Step 3.

**`testNoInstanceProperties` fails:**
You accidentally added a non-static property. All class-level state must be `public static $propName`.

**`get_service_define('LXC')` is undefined during tests:**
This function is a MyAdmin global helper not available in unit test scope. If writing a unit test that exercises handler logic (not just signatures), mock or stub it. Signature tests using `ReflectionClass` do not call the method and are safe.

**Handler fires for non-LXC VPS types:**
You omitted the `if ($event['type'] == get_service_define('LXC'))` guard. Without it the handler acts on OpenVZ, KVM, and other VPS types. Always wrap all handler logic in this check.
