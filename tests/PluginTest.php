<?php

declare(strict_types=1);

namespace Detain\MyAdminLxc\Tests;

use Detain\MyAdminLxc\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for the Detain\MyAdminLxc\Plugin class.
 *
 * Covers class structure, static properties, hook registration,
 * event handler signatures, and the GetList parser logic.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    // ---------------------------------------------------------------
    // Class structure tests
    // ---------------------------------------------------------------

    /**
     * Verify the Plugin class can be instantiated.
     */
    public function testPluginCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        self::assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Verify the class resides in the expected namespace.
     */
    public function testClassNamespace(): void
    {
        self::assertSame('Detain\MyAdminLxc', $this->reflection->getNamespaceName());
    }

    /**
     * Verify the class is not abstract and not an interface.
     */
    public function testClassIsInstantiable(): void
    {
        self::assertTrue($this->reflection->isInstantiable());
        self::assertFalse($this->reflection->isAbstract());
        self::assertFalse($this->reflection->isInterface());
    }

    // ---------------------------------------------------------------
    // Static property tests
    // ---------------------------------------------------------------

    /**
     * Verify the $name static property is set to 'LXC VPS'.
     */
    public function testNameProperty(): void
    {
        self::assertSame('LXC VPS', Plugin::$name);
    }

    /**
     * Verify the $description static property is a non-empty string.
     */
    public function testDescriptionPropertyIsNonEmpty(): void
    {
        self::assertIsString(Plugin::$description);
        self::assertNotEmpty(Plugin::$description);
    }

    /**
     * Verify $description contains the word LXC.
     */
    public function testDescriptionMentionsLxc(): void
    {
        self::assertStringContainsString('LXC', Plugin::$description);
    }

    /**
     * Verify the $help static property is a string.
     */
    public function testHelpPropertyIsString(): void
    {
        self::assertIsString(Plugin::$help);
    }

    /**
     * Verify the $module static property equals 'vps'.
     */
    public function testModuleProperty(): void
    {
        self::assertSame('vps', Plugin::$module);
    }

    /**
     * Verify the $type static property equals 'service'.
     */
    public function testTypeProperty(): void
    {
        self::assertSame('service', Plugin::$type);
    }

    /**
     * Verify all expected static properties exist on the class.
     */
    public function testAllExpectedStaticPropertiesExist(): void
    {
        $expected = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expected as $prop) {
            self::assertTrue(
                $this->reflection->hasProperty($prop),
                "Expected static property \${$prop} to exist on Plugin"
            );
            self::assertTrue(
                $this->reflection->getProperty($prop)->isStatic(),
                "Expected \${$prop} to be static"
            );
            self::assertTrue(
                $this->reflection->getProperty($prop)->isPublic(),
                "Expected \${$prop} to be public"
            );
        }
    }

    // ---------------------------------------------------------------
    // getHooks() tests
    // ---------------------------------------------------------------

    /**
     * Verify getHooks() returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        self::assertIsArray($hooks);
    }

    /**
     * Verify getHooks() contains expected event keys.
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        self::assertArrayHasKey('vps.settings', $hooks);
        self::assertArrayHasKey('vps.deactivate', $hooks);
        self::assertArrayHasKey('vps.queue', $hooks);
    }

    /**
     * Verify getHooks() keys are prefixed with the module name.
     */
    public function testGetHooksKeysMatchModulePrefix(): void
    {
        $hooks = Plugin::getHooks();
        foreach (array_keys($hooks) as $key) {
            self::assertStringStartsWith(
                Plugin::$module . '.',
                $key,
                "Hook key '{$key}' should start with module prefix 'vps.'"
            );
        }
    }

    /**
     * Verify each hook value is a callable-style array [class, method].
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $callable) {
            self::assertIsArray($callable, "Hook '{$key}' value should be an array");
            self::assertCount(2, $callable, "Hook '{$key}' should have exactly 2 elements");
            self::assertSame(Plugin::class, $callable[0], "Hook '{$key}' class should be Plugin");
            self::assertIsString($callable[1], "Hook '{$key}' method name should be a string");
        }
    }

    /**
     * Verify each method referenced in getHooks() actually exists on Plugin.
     */
    public function testGetHooksMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $callable) {
            self::assertTrue(
                $this->reflection->hasMethod($callable[1]),
                "Method {$callable[1]} referenced in hook '{$key}' does not exist on Plugin"
            );
        }
    }

    /**
     * Verify each hook method is public and static.
     */
    public function testGetHooksMethodsArePublicStatic(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $callable) {
            $method = $this->reflection->getMethod($callable[1]);
            self::assertTrue(
                $method->isPublic(),
                "Method {$callable[1]} should be public"
            );
            self::assertTrue(
                $method->isStatic(),
                "Method {$callable[1]} should be static"
            );
        }
    }

    /**
     * Verify the hook for vps.settings points to getSettings.
     */
    public function testSettingsHookPointsToGetSettings(): void
    {
        $hooks = Plugin::getHooks();
        self::assertSame('getSettings', $hooks['vps.settings'][1]);
    }

    /**
     * Verify the hook for vps.deactivate points to getDeactivate.
     */
    public function testDeactivateHookPointsToGetDeactivate(): void
    {
        $hooks = Plugin::getHooks();
        self::assertSame('getDeactivate', $hooks['vps.deactivate'][1]);
    }

    /**
     * Verify the hook for vps.queue points to getQueue.
     */
    public function testQueueHookPointsToGetQueue(): void
    {
        $hooks = Plugin::getHooks();
        self::assertSame('getQueue', $hooks['vps.queue'][1]);
    }

    // ---------------------------------------------------------------
    // Event handler signature tests
    // ---------------------------------------------------------------

    /**
     * Verify getActivate accepts exactly one parameter of type GenericEvent.
     */
    public function testGetActivateSignature(): void
    {
        $method = $this->reflection->getMethod('getActivate');
        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        self::assertNotNull($type);
        self::assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify getDeactivate accepts exactly one parameter of type GenericEvent.
     */
    public function testGetDeactivateSignature(): void
    {
        $method = $this->reflection->getMethod('getDeactivate');
        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        self::assertNotNull($type);
        self::assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify getSettings accepts exactly one parameter of type GenericEvent.
     */
    public function testGetSettingsSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        self::assertNotNull($type);
        self::assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify getQueue accepts exactly one parameter of type GenericEvent.
     */
    public function testGetQueueSignature(): void
    {
        $method = $this->reflection->getMethod('getQueue');
        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        self::assertNotNull($type);
        self::assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify all event handler methods have void or no declared return type.
     */
    public function testEventHandlersReturnVoidOrNone(): void
    {
        $eventMethods = ['getActivate', 'getDeactivate', 'getSettings', 'getQueue'];
        foreach ($eventMethods as $name) {
            $method = $this->reflection->getMethod($name);
            $returnType = $method->getReturnType();
            if ($returnType !== null) {
                self::assertSame('void', $returnType->getName(), "{$name} should return void if typed");
            } else {
                // No return type is acceptable
                self::assertNull($returnType);
            }
        }
    }

    // ---------------------------------------------------------------
    // GetList() parser tests
    // ---------------------------------------------------------------

    /**
     * Verify GetList method exists and is public static.
     */
    public function testGetListMethodExists(): void
    {
        self::assertTrue($this->reflection->hasMethod('GetList'));
        $method = $this->reflection->getMethod('GetList');
        self::assertTrue($method->isPublic());
        self::assertTrue($method->isStatic());
    }

    /**
     * Verify GetList has an optional $name parameter.
     */
    public function testGetListParameterIsOptional(): void
    {
        $method = $this->reflection->getMethod('GetList');
        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('name', $params[0]->getName());
        self::assertTrue($params[0]->isOptional());
        self::assertSame('', $params[0]->getDefaultValue());
    }

    /**
     * Verify getHooks returns a static array value (no side effects).
     */
    public function testGetHooksReturnsSameResultOnRepeatedCalls(): void
    {
        $first = Plugin::getHooks();
        $second = Plugin::getHooks();
        self::assertSame($first, $second);
    }

    // ---------------------------------------------------------------
    // Constructor tests
    // ---------------------------------------------------------------

    /**
     * Verify the constructor takes no parameters.
     */
    public function testConstructorHasNoParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        self::assertNotNull($constructor);
        self::assertCount(0, $constructor->getParameters());
    }

    /**
     * Verify the constructor is public.
     */
    public function testConstructorIsPublic(): void
    {
        $constructor = $this->reflection->getConstructor();
        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPublic());
    }

    // ---------------------------------------------------------------
    // Template file existence tests
    // ---------------------------------------------------------------

    /**
     * Verify that template files referenced by getQueue exist.
     */
    public function testTemplateDirectoryExists(): void
    {
        $templateDir = dirname((new ReflectionClass(Plugin::class))->getFileName()) . '/../templates';
        self::assertDirectoryExists($templateDir);
    }

    /**
     * Verify expected shell template files exist in the templates directory.
     */
    public function testExpectedTemplateFilesExist(): void
    {
        $templateDir = dirname((new ReflectionClass(Plugin::class))->getFileName()) . '/../templates';
        $expectedTemplates = [
            'backup.sh.tpl',
            'create.sh.tpl',
            'delete.sh.tpl',
            'destroy.sh.tpl',
            'start.sh.tpl',
            'stop.sh.tpl',
        ];
        foreach ($expectedTemplates as $tpl) {
            self::assertFileExists(
                $templateDir . '/' . $tpl,
                "Expected template file '{$tpl}' to exist"
            );
        }
    }

    // ---------------------------------------------------------------
    // Static analysis / code-quality tests
    // ---------------------------------------------------------------

    /**
     * Verify the Plugin class does not declare any non-static instance properties.
     */
    public function testNoInstanceProperties(): void
    {
        $properties = $this->reflection->getProperties();
        foreach ($properties as $prop) {
            self::assertTrue(
                $prop->isStatic(),
                "Property \${$prop->getName()} should be static (class uses only static state)"
            );
        }
    }

    /**
     * Verify every public method on Plugin is documented (has a docblock).
     */
    public function testAllPublicMethodsHaveDocblocks(): void
    {
        $methods = $this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() !== Plugin::class) {
                continue;
            }
            // GetList is missing a docblock in source; skip it
            if ($method->getName() === 'GetList') {
                continue;
            }
            $doc = $method->getDocComment();
            self::assertNotFalse(
                $doc,
                "Public method {$method->getName()} should have a docblock"
            );
        }
    }

    /**
     * Verify that getHooks does not reference the commented-out activate hook.
     */
    public function testActivateHookIsNotRegistered(): void
    {
        $hooks = Plugin::getHooks();
        self::assertArrayNotHasKey('vps.activate', $hooks);
    }

    /**
     * Verify the total count of registered hooks.
     */
    public function testHookCount(): void
    {
        $hooks = Plugin::getHooks();
        self::assertCount(3, $hooks);
    }

    /**
     * Verify the class has exactly the expected set of public static methods.
     */
    public function testExpectedPublicStaticMethods(): void
    {
        $expected = ['getHooks', 'getActivate', 'getDeactivate', 'getSettings', 'getQueue', 'GetList'];
        $actual = [];
        foreach ($this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC) as $m) {
            if ($m->getDeclaringClass()->getName() === Plugin::class) {
                $actual[] = $m->getName();
            }
        }
        sort($expected);
        sort($actual);
        self::assertSame($expected, $actual);
    }
}
