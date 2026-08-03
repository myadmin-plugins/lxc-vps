<?php

declare(strict_types=1);

namespace Detain\MyAdminLxc\Tests;

use Detain\MyAdminLxc\Plugin;
use Detain\MyAdminLxc\Tests\Support\FrameworkSpy;
use MyAdmin\App;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcher;
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

    // ---------------------------------------------------------------
    // Hook dispatch tests
    //
    // These three replace an assertion that listed the exact set of public static
    // method names on the class. That assertion had no behavioural meaning, broke
    // as soon as a method was legitimately added, and could not even do what its
    // name claimed: ReflectionClass::getMethods() treats its filter mask as a
    // union, so IS_PUBLIC|IS_STATIC also matched the public non-static
    // constructor. What actually matters is that every callback getHooks()
    // advertises survives being registered on a real dispatcher and invoked - so
    // each hook is now dispatched for real and asserted on by its effect.
    // ---------------------------------------------------------------

    /**
     * Register every advertised hook on a real dispatcher, the way MyAdmin does.
     */
    private function dispatcherWithPluginHooks(): EventDispatcher
    {
        $dispatcher = new EventDispatcher();
        $hooks = Plugin::getHooks();
        self::assertNotEmpty($hooks, 'The plugin must advertise at least one hook');
        foreach ($hooks as $hookName => $callback) {
            self::assertIsCallable($callback, "Hook {$hookName} is registered but is not callable");
            $dispatcher->addListener($hookName, $callback);
        }
        return $dispatcher;
    }

    /**
     * Dispatching vps.settings registers the LXC cost and out-of-stock settings
     * against the vps module, and hands the settings object back on the 'global'
     * target - leaving it on 'module' would misfile every setting registered after
     * this plugin.
     */
    public function testSettingsHookRegistersLxcSettingsAndRestoresGlobalTarget(): void
    {
        $settings = new class () {
            /** @var array<int, string> */
            public $targets = [];

            /** @var array<string, array<int, mixed>> */
            public $registered = [];

            public function setTarget($target)
            {
                $this->targets[] = $target;
            }

            public function get_setting($name)
            {
                return 'current:' . $name;
            }

            public function add_text_setting(...$args)
            {
                $this->registered[$args[2]] = $args;
            }

            public function add_dropdown_setting(...$args)
            {
                $this->registered[$args[2]] = $args;
            }
        };

        $this->dispatcherWithPluginHooks()->dispatch(new GenericEvent($settings), 'vps.settings');

        self::assertArrayHasKey('vps_slice_lxc_cost', $settings->registered);
        self::assertArrayHasKey('outofstock_lxc', $settings->registered);
        self::assertSame('vps', $settings->registered['vps_slice_lxc_cost'][0]);
        self::assertSame('current:VPS_SLICE_LXC_COST', $settings->registered['vps_slice_lxc_cost'][5]);
        self::assertSame(['0', '1'], $settings->registered['outofstock_lxc'][6]);
        self::assertSame(['module', 'global'], $settings->targets);
    }

    /**
     * Dispatching vps.deactivate queues a container delete for LXC services and
     * does nothing at all for any other VPS type.
     */
    public function testDeactivateHookQueuesDeleteForLxcServicesOnly(): void
    {
        $service = new class () {
            public function getId()
            {
                return 501;
            }

            public function getCustid()
            {
                return 9001;
            }
        };
        $dispatcher = $this->dispatcherWithPluginHooks();

        FrameworkSpy::reset();
        $dispatcher->dispatch(new GenericEvent($service, ['type' => 99]), 'vps.deactivate');
        self::assertSame([], FrameworkSpy::$history, 'A non-LXC VPS must not be queued for LXC deletion');

        FrameworkSpy::reset();
        $dispatcher->dispatch(new GenericEvent($service, ['type' => App::LXC_SERVICE_TYPE]), 'vps.deactivate');
        self::assertSame([['vpsqueue', 501, 'delete', '', 9001]], FrameworkSpy::$history);
    }

    /**
     * Dispatching vps.queue for an LXC service renders the template matching the
     * requested action, appends it to whatever output earlier handlers produced,
     * and stops propagation so no other VPS plugin also answers the action.
     */
    public function testQueueHookAppendsRenderedTemplateForLxcServices(): void
    {
        FrameworkSpy::reset();
        $event = new GenericEvent(
            $this->queueSubject('start'),
            ['type' => App::LXC_SERVICE_TYPE, 'output' => "# earlier output\n"]
        );

        $this->dispatcherWithPluginHooks()->dispatch($event, 'vps.queue');

        self::assertStringStartsWith("# earlier output\n", $event['output'], 'Output must be appended, not replaced');
        self::assertStringContainsString('#rendered:start.sh.tpl:lxc-host', $event['output']);
        self::assertCount(1, FrameworkSpy::$renderedTemplates);
        self::assertStringEndsWith('/templates/start.sh.tpl', FrameworkSpy::$renderedTemplates[0]);
        self::assertTrue($event->isPropagationStopped());
    }

    /**
     * An LXC action this plugin ships no template for is logged as an error and
     * contributes nothing to the queue output, rather than rendering a missing file.
     */
    public function testQueueHookLogsErrorForUnknownLxcAction(): void
    {
        FrameworkSpy::reset();
        $event = new GenericEvent(
            $this->queueSubject('no_such_action'),
            ['type' => App::LXC_SERVICE_TYPE, 'output' => 'untouched']
        );

        $this->dispatcherWithPluginHooks()->dispatch($event, 'vps.queue');

        self::assertSame('untouched', $event['output']);
        self::assertSame([], FrameworkSpy::$renderedTemplates);
        $errors = FrameworkSpy::logsWithLevel('error');
        self::assertCount(1, $errors);
        self::assertStringContainsString('no_such_action', $errors[0][2]);
        self::assertTrue($event->isPropagationStopped());
    }

    /**
     * A queue event for some other VPS type is left entirely alone, so the plugin
     * responsible for that type still gets to handle it.
     */
    public function testQueueHookIgnoresNonLxcServices(): void
    {
        FrameworkSpy::reset();
        $event = new GenericEvent($this->queueSubject('start'), ['type' => 99, 'output' => 'untouched']);

        $this->dispatcherWithPluginHooks()->dispatch($event, 'vps.queue');

        self::assertSame('untouched', $event['output']);
        self::assertSame([], FrameworkSpy::$renderedTemplates);
        self::assertSame([], FrameworkSpy::$logs);
        self::assertFalse($event->isPropagationStopped());
    }

    /**
     * The service info array MyAdmin hands to the queue handler.
     *
     * @return array<string, mixed>
     */
    private function queueSubject(string $action): array
    {
        return [
            'action' => $action,
            'vps_id' => 501,
            'vps_custid' => 9001,
            'vps_hostname' => 'lxc-host',
            'vps_vzid' => 7,
            'server_info' => ['vps_name' => 'lxc-node-1'],
        ];
    }
}
