<?php declare(strict_types = 1);

namespace PHPStan;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProviderGoldenTest;
use PHPStan\Testing\PHPStanTestCase;
use function count;
use function explode;
use function ksort;
use function sort;
use function substr;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/PHPStan/Reflection/ReflectionProviderGoldenTest.php';

$symbols = require_once __DIR__ . '/phpSymbols.php';

$functions = [];
$classes = [];

foreach ($symbols as $symbol) {
	$parts = explode('::', $symbol);
	$partsCount = count($parts);

	if ($partsCount === 1) {
		$functions[] = $parts[0];
	} elseif ($partsCount === 2) {
		[$class, $method] = $parts;
		$classes[$class] ??= [
			'methods' => [],
			'properties' => [],
		];

		if (substr($method, 0, 1) === '$') {
			$classes[$class]['properties'][] = substr($method, 1);
		} else {
			$classes[$class]['methods'][] = $method;
		}
	}
}

sort($functions);
ksort($classes);

foreach ($classes as &$class) {
	sort($class['properties']);
	sort($class['methods']);
}

unset($class);
$container = PHPStanTestCase::getContainer();
$reflectionProvider = $container->getByType(ReflectionProvider::class);

$separator = '-----';

foreach ($functions as $function) {
	echo $separator . "\n";
	echo 'FUNCTION ' . $function . "\n";
	echo $separator . "\n";
	echo ReflectionProviderGoldenTest::generateFunctionDescription($function);
}

foreach ($classes as $className => $class) {
	echo $separator . "\n";
	echo 'CLASS ' . $className . "\n";
	echo $separator . "\n";
	echo ReflectionProviderGoldenTest::generateClassDescription($className);

	if (! $reflectionProvider->hasClass($className)) {
		continue;
	}

	foreach ($class['properties'] as $property) {
		echo $separator . "\n";
		echo 'PROPERTY ' . $className . '::' . $property . "\n";
		echo $separator . "\n";
		echo ReflectionProviderGoldenTest::generateClassPropertyDescription($className . '::' . $property);
	}

	foreach ($class['methods'] as $method) {
		echo $separator . "\n";
		echo 'METHOD ' . $className . '::' . $method . "\n";
		echo $separator . "\n";
		echo ReflectionProviderGoldenTest::generateClassMethodDescription($className . '::' . $method);
	}
}
