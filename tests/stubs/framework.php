<?php

declare(strict_types=1);

/**
 * Stand-ins for the MyAdmin framework services that src/Plugin.php calls out to.
 *
 * MyAdmin itself is not a dependency of this package, so these stubs are what make
 * it possible to register the plugin's hooks on a real event dispatcher and invoke
 * them inside the test suite. Everything observable is recorded on FrameworkSpy.
 */

namespace MyAdmin {
    use Detain\MyAdminLxc\Tests\Support\FrameworkSpy;

    class App
    {
        /**
         * The service-type id that get_service_define('LXC') resolves to in tests.
         * Handlers guard on it, so tests use this constant to drive the guard.
         */
        public const LXC_SERVICE_TYPE = 42;

        /**
         * @param  string $service
         * @return int
         */
        public static function getServiceDefine($service)
        {
            return $service === 'LXC' ? self::LXC_SERVICE_TYPE : -1;
        }

        /**
         * @return object
         */
        public static function history()
        {
            return new class () {
                /**
                 * @param  mixed ...$args
                 * @return void
                 */
                public function add(...$args)
                {
                    FrameworkSpy::$history[] = $args;
                }
            };
        }
    }
}

namespace {
    use Detain\MyAdminLxc\Tests\Support\FrameworkSpy;

    if (!function_exists('myadmin_log')) {
        /**
         * @param  mixed ...$args
         * @return void
         */
        function myadmin_log(...$args)
        {
            FrameworkSpy::$logs[] = $args;
        }
    }

    // gettext is not guaranteed to be enabled on every CI leg.
    if (!function_exists('_')) {
        /**
         * @param  string $string
         * @return string
         */
        function _($string)
        {
            return $string;
        }
    }

    if (!class_exists('TFSmarty')) {
        /**
         * Renders a marker instead of running Smarty: the plugin's behaviour under
         * test is which template it picks and what it does with the result, not
         * Smarty's own rendering.
         */
        class TFSmarty
        {
            /**
             * @var array<string, mixed>
             */
            public $vars = [];

            /**
             * @param  array<string, mixed>|string $name
             * @param  mixed                       $value
             * @return $this
             */
            public function assign($name, $value = null)
            {
                if (is_array($name)) {
                    $this->vars = array_merge($this->vars, $name);
                } else {
                    $this->vars[$name] = $value;
                }
                return $this;
            }

            /**
             * @param  string $template
             * @return string
             */
            public function fetch($template)
            {
                FrameworkSpy::$renderedTemplates[] = $template;
                return '#rendered:' . basename($template) . ':' . ($this->vars['vps_hostname'] ?? '?') . "\n";
            }
        }
    }
}
