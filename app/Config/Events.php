<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

 Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        Services::toolbar()->respond();
        // Hot Reload route - for framework use on the hot reloader.
        if (ENVIRONMENT === 'development') {
            Services::routes()->get('__hot-reload', static function (): void {
                (new HotReloader())->run();
            });
        }
    }

    //load php hooks library
    require_once(APPPATH . "ThirdParty/PHP-Hooks/php-hooks.php");

    helper('plugin');

    define('PLUGINPATH', ROOTPATH . 'plugins/'); //define plugin path
    define('PLUGIN_URL_PATH', 'plugins/'); //define plugin path

    load_plugin_indexes();

    include APPPATH . 'Config/RiseHooks.php';
    include APPPATH . 'Config/RiseCustomHooks.php';
});

function load_plugin_indexes() {
    $plugins = file_get_contents(APPPATH . "Config/activated_plugins.json");
    $plugins = @json_decode($plugins);

    if (!($plugins && is_array($plugins) && count($plugins))) {
        return false;
    }

    foreach ($plugins as $plugin) {
        $index_file = PLUGINPATH . $plugin . '/index.php';

        if (file_exists($index_file)) {
            include $index_file;
        }
    }
}
