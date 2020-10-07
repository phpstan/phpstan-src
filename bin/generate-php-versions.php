#!/usr/bin/env php
<?php declare(strict_types=1);

use PhpParser\Parser;
use PHPStan\DependencyInjection\ContainerFactory;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;

(function () {
	require_once __DIR__ . '/../vendor/autoload.php';

	$containerFactory = new ContainerFactory(__DIR__ . '/..');
	$container = $containerFactory->create(
		__DIR__ . '/../tmp',
		[],
		[]
	);

	$versions = [70100, 70200, 70300, 70400, 80000];
	$stubbers = [];
	foreach ($versions as $phpVersion) {
		$stubbers[$phpVersion] = new PhpStormStubsSourceStubber(
			$container->getByType(Parser::class),
			$phpVersion
		);
	}

	$getMinMax = function (array $notSupportedVersions) use ($versions): array {
		$supportedVersions = array_diff($versions, $notSupportedVersions);
		$min = null;
		$max = null;
		if (count($supportedVersions) > 0) {
			$versionMin = min($supportedVersions);
			if ($versionMin !== $versions[0]) {
				$min = $versionMin;
			}

			$versionMax = max($supportedVersions);
			if ($versionMax !== $versions[count($versions) - 1]) {
				$max = $versions[array_search($versionMax, $versions, true) + 1];
			}
		}

		return [$min, $max];
	};

	$writeFile = function (array $records, string $fileName): void {
		$template = <<<'PHP'
<?php declare(strict_types = 1);

return %s;
PHP;

		\PHPStan\File\FileWriter::write($fileName, sprintf($template, var_export($records, true)));
	};

	$functions = [];
	foreach (\JetBrains\PHPStormStub\PhpStormStubsMap::FUNCTIONS as $function => $file) {
		$notSupportedVersions = [];
		foreach ($stubbers as $phpVersion => $stubber) {
			if ($stubber->isPresentFunction($function) !== false) {
				continue;
			}

			$notSupportedVersions[] = $phpVersion;
		}

		if (count($notSupportedVersions) === 0) {
			continue;
		}

		$functions[strtolower($function)] = $getMinMax($notSupportedVersions);
	}

	$writeFile($functions, __DIR__ . '/../resources/functionPhpVersions.php');

	$classes = [];
	foreach (\JetBrains\PHPStormStub\PhpStormStubsMap::CLASSES as $class => $file) {
		$notSupportedVersions = [];
		foreach ($stubbers as $phpVersion => $stubber) {
			if ($stubber->isPresentClass($class) !== false) {
				continue;
			}

			$notSupportedVersions[] = $phpVersion;
		}

		if (count($notSupportedVersions) === 0) {
			continue;
		}

		$classes[strtolower($class)] = $getMinMax($notSupportedVersions);
	}

	$writeFile($classes, __DIR__ . '/../resources/classPhpVersions.php');

})();
