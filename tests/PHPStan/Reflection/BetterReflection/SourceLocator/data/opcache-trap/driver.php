<?php declare(strict_types = 1);

// Driver for FileReadTrapStreamWrapperTest::testTrapSurvivesOpcacheCacheHit().
//
// Runs the real AutoloadSourceLocator against a Composer autoloader whose PSR-4
// prefix resolves to a file the process has already loaded - the
// php-standard-library / azjezz/psl shape, where a files-autoload bootstrap
// loads every function file at startup and the same paths stay reachable
// through the prefix. Letting the autoloader include such a path runs the file
// a second time whenever OPcache already holds the script, because the include
// is served from the cache without the trap being asked for the contents, and
// the process dies with "Cannot redeclare function OpcacheTrap\thing()".
//
// Each step prints as it goes, so a failure shows how far it got.

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Testing\PHPStanTestCase;

$loader = require __DIR__ . '/../../../../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../../../phpstan-bootstrap.php';

$opcacheStatus = opcache_get_status(false);
$opcacheEnabled = $opcacheStatus !== false && ($opcacheStatus['opcache_enabled'] ?? false) === true;
echo 'opcacheEnabled=', $opcacheEnabled ? '1' : '0', "\n";

// what the files-autoload bootstrap of such a package does at startup
require_once __DIR__ . '/thing.php';

// a real project registers its prefixes on the Composer loader that is
// already in place, rather than adding another autoloader behind it
$loader->addPsr4('OpcacheTrap\\', [__DIR__]);

$locator = new AutoloadSourceLocator(
	PHPStanTestCase::getContainer()->getByType(FileNodesFetcher::class),
	true,
);
$reflector = new DefaultReflector($locator);

// OpcacheTrap\thing is a function, not a class, but PHPStan probes the name as
// a class the same way it does for Psl\Type\optional - and the PSR-4 prefix
// sends the autoloader at the already-loaded function.php
$locator->locateIdentifier($reflector, new Identifier('OpcacheTrap\thing', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));
echo "survivedLoadedProbe=1\n";

// a name whose file nothing has loaded must still resolve
$cold = $reflector->reflectClass('OpcacheTrap\ColdClass');
echo 'resolvedCold=', $cold->getName() === 'OpcacheTrap\ColdClass' ? '1' : '0', "\n";
echo 'coldFileNotExecuted=', !function_exists('OpcacheTrap\coldThing') ? '1' : '0', "\n";
