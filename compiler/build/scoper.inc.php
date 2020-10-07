<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$stubs = [
	'../../resources/classPhpVersions.php',
	'../../resources/functionMap.php',
	'../../resources/functionMap_php74delta.php',
	'../../resources/functionMetadata.php',
	'../../resources/functionPhpVersions.php',
	'../../vendor/hoa/consistency/Prelude.php',
];
$stubFinder = \Isolated\Symfony\Component\Finder\Finder::create();
foreach ($stubFinder->files()->name('*.php')->in([
	'../../stubs',
	'../../vendor/jetbrains/phpstorm-stubs',
	'../../vendor/phpstan/php-8-stubs/stubs',
]) as $file) {
	if ($file->getPathName() === '../../vendor/jetbrains/phpstorm-stubs/PhpStormStubsMap.php') {
		continue;
	}
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

				if (strpos($class, 'Hoa\\') === 0) {
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

					if (array_key_exists('autowired', $service) && is_array($service['autowired'])) {
						foreach ($service['autowired'] as $i => $autowiredName) {
							$service['autowired'][$i] = $prefixClass($autowiredName);
						}
					}

					$updatedNeon['services'][$key] = $service;
				}
			}

			return \Nette\Neon\Neon::encode($updatedNeon, \Nette\Neon\Neon::BLOCK);
		},
		function (string $filePath, string $prefix, string $content): string {
			if (!in_array($filePath, [
				'src/Testing/TestCaseSourceLocatorFactory.php',
				'src/Testing/TestCase.php',
			], true)) {
				return $content;
			}

			return str_replace(sprintf('%s\\Composer\\Autoload\\ClassLoader', $prefix), 'Composer\\Autoload\\ClassLoader', $content);
		},
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'vendor/jetbrains/phpstorm-stubs/PhpStormStubsMap.php') {
				return $content;
			}

			$content = str_replace('\'' . $prefix . '\\\\', '\'', $content);

			return $content;
		},
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'vendor/phpstan/php-8-stubs/Php8StubsMap.php') {
				return $content;
			}

			$content = str_replace('\'' . $prefix . '\\\\', '\'', $content);

			return $content;
		},
		function (string $filePath, string $prefix, string $content): string {
			if (!in_array($filePath, [
				'src/Type/TypehintHelper.php',
				'vendor/ondrejmirtes/better-reflection/src/Reflection/Adapter/ReflectionUnionType.php',
			], true)) {
				return $content;
			}

			return str_replace(sprintf('%s\\ReflectionUnionType', $prefix), 'ReflectionUnionType', $content);
		},
		function (string $filePath, string $prefix, string $content): string {
			return str_replace('private static final', 'private static', $content);
		},
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'vendor/hoa/stream/Stream.php') {
				return $content;
			}

			$content = str_replace('\Hoa\Consistency::registerShutdownFunction(xcallable(\'Hoa\\\\Stream\\\\Stream::_Hoa_Stream\'));', '', $content);

			return $content;
		},
	],
	'whitelist' => [
		'PHPStan\*',
		'PhpParser\*',
		'Hoa\*',
	],
	'whitelist-global-functions' => false,
	'whitelist-global-classes' => false,
];
