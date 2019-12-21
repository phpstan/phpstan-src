<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/nette/neon/src/neon.php';

$stubs = [
	'../../src/Reflection/SignatureMap/functionMap.php',
	'../../src/Reflection/SignatureMap/functionMap_php74delta.php',
	'../../src/Reflection/SignatureMap/functionMetadata.php',
];
$stubFinder = \Isolated\Symfony\Component\Finder\Finder::create();
foreach ($stubFinder->files()->name('*.php')->in([
	'../../stubs',
	'../../vendor/jetbrains/phpstorm-stubs',
]) as $file) {
	$stubs[] = $file->getPathName();
}

return [
	'prefix' => null,
	'finders' => [],
	'files-whitelist' => $stubs,
	'patchers' => [
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'bin/phpstan') {
				return $content;
			}
			return str_replace('__DIR__ . \'/..', '\'phar://phpstan.phar', $content);
		},
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'vendor/nette/di/src/DI/Compiler.php') {
				return $content;
			}
			return str_replace('|Nette\\\\DI\\\\Statement', sprintf('|\\\\%s\\\\Nette\\\\DI\\\\Statement', $prefix), $content);
		},
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'vendor/nette/di/src/DI/Config/DefinitionSchema.php') {
				return $content;
			}
			$content = str_replace(
				sprintf('\'%s\\\\callable', $prefix),
				'\'callable',
				$content
			);
			$content = str_replace(
				'|Nette\\\\DI\\\\Definitions\\\\Statement',
				sprintf('|%s\\\\Nette\\\\DI\\\\Definitions\\\\Statement', $prefix),
				$content
			);

			return $content;
		},
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'vendor/nette/di/src/DI/Extensions/ExtensionsExtension.php') {
				return $content;
			}
			$content = str_replace(
				sprintf('\'%s\\\\string', $prefix),
				'\'string',
				$content
			);
			$content = str_replace(
				'|Nette\\\\DI\\\\Definitions\\\\Statement',
				sprintf('|%s\\\\Nette\\\\DI\\\\Definitions\\\\Statement', $prefix),
				$content
			);

			return $content;
		},
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'src/Testing/TestCase.php') {
				return $content;
			}
			return str_replace(sprintf('\\%s\\PHPUnit\\Framework\\TestCase', $prefix), '\\PHPUnit\\Framework\\TestCase', $content);
		},
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'src/Testing/LevelsTestCase.php') {
				return $content;
			}
			return str_replace(
				[sprintf('\\%s\\PHPUnit\\Framework\\AssertionFailedError', $prefix), sprintf('\\%s\\PHPUnit\\Framework\\TestCase', $prefix)],
				['\\PHPUnit\\Framework\\AssertionFailedError', '\\PHPUnit\\Framework\\TestCase'],
				$content
			);
		},
		function (string $filePath, string $prefix, string $content): string {
			if (strpos($filePath, 'src/') !== 0) {
				return $content;
			}

			$content = str_replace(sprintf('\'%s\\\\r\\\\n\'', $prefix), '\'\\\\r\\\\n\'', $content);
			$content = str_replace(sprintf('\'%s\\\\', $prefix), '\'', $content);

			return $content;
		},
		function (string $filePath, string $prefix, string $content): string {
			if (strpos($filePath, '.neon') === false) {
				return $content;
			}

			if ($content === '') {
				return $content;
			}

			$prefixClass = function (string $class) use ($prefix): string {
				if (strpos($class, 'PHPStan\\') === 0) {
					return $class;
				}

				if (strpos($class, 'PhpParser\\') === 0) {
					return $class;
				}

				if (strpos($class, '@') === 0) {
					return $class;
				}

				return $prefix . '\\' . $class;
			};

			$neon = \Nette\Neon\Neon::decode($content);
			$updatedNeon = $neon;
			if (array_key_exists('services', $neon)) {
				foreach ($neon['services'] as $key => $service) {
					if (array_key_exists('class', $service) && is_string($service['class'])) {
						$service['class'] = $prefixClass($service['class']);
					}
					if (array_key_exists('factory', $service) && is_string($service['factory'])) {
						$service['factory'] = $prefixClass($service['factory']);
					}

					$updatedNeon['services'][$key] = $service;
				}
			}

			return \Nette\Neon\Neon::encode($updatedNeon, \Nette\Neon\Neon::BLOCK);
		},
	],
	'whitelist' => [
		'PHPStan\*',
		'PhpParser\*',
	],
];
