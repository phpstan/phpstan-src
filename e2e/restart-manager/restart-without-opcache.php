<?php declare(strict_types = 1);

use PHPStan\Turbo\TurboProcessRestarter;

// Restarts the way another program that loads PHPStan does, Rector for
// example, but without OPcache. bin/phpstan then sees the marker and does not
// restart a second time. The restart classes are loaded by file like in
// bin/phpstan, which needs to be the first to require vendor/autoload.php.
$src = __DIR__ . '/../../src';
require_once $src . '/Turbo/TurboExtensionEnabler.php';
require_once $src . '/Turbo/TurboExtensionSelector.php';
require_once $src . '/Process/Restart/RestartReason.php';
require_once $src . '/Process/Restart/OpcacheSettings.php';
require_once $src . '/Process/Restart/RestartExtensionRegistry.php';
require_once $src . '/Process/Restart/IniComposer.php';
require_once $src . '/Process/Restart/RestartExecutor.php';
require_once $src . '/Process/Restart/RestartManager.php';
require_once $src . '/Turbo/TurboProcessRestarter.php';

TurboProcessRestarter::restartIfSuitable($_SERVER['argv'], null, 'phpstan', false);

require __DIR__ . '/../../bin/phpstan';
