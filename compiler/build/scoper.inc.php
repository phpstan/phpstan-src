<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$stubs = [
	'../../resources/functionMap.php',
	'../../resources/functionMap_php74delta.php',
	'../../resources/functionMap_php80delta.php',
	'../../resources/functionMetadata.php',
	'../../vendor/hoa/consistency/Prelude.php',
	'../../vendor/composer/InstalledVersions.php',
	'../../vendor/composer/installed.php',
];
$stubFinder = \Isolated\Symfony\Component\Finder\Finder::create();
foreach ($stubFinder->files()->name('*.php')->in([
	'../../stubs',
	'../../vendor/jetbrains/phpstorm-stubs',
	'../../vendor/phpstan/php-8-stubs/stubs',
	'../../vendor/symfony/polyfill-php80',
	'../../vendor/symfony/polyfill-php81',
	'../../vendor/symfony/polyfill-mbstring',
	'../../vendor/symfony/polyfill-intl-normalizer',
	'../../vendor/symfony/polyfill-php73',
	'../../vendor/symfony/polyfill-php74',
	'../../vendor/symfony/polyfill-intl-grapheme',
]) as $file) {
	if ($file->getPathName() === '../../vendor/jetbrains/phpstorm-stubs/PhpStormStubsMap.php') {
		continue;
	}
	$stubs[] = $file->getPathName();
}

if ($_SERVER['PHAR_CHECKSUM'] ?? false) {
	$prefix = '_PHPStan_checksum';
} else {
	exec('git rev-parse --short HEAD', $gitCommitOutputLines, $gitExitCode);
	if ($gitExitCode !== 0) {
		die('Could not get Git commit');
	}

	$prefix = sprintf('_PHPStan_%s', $gitCommitOutputLines[0]);
}

return [
	'prefix' => $prefix,
	'finders' => [],
	'exclude-files' => $stubs,
	'patchers' => [
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'bin/phpstan') {
				return $content;
			}
			return str_replace('__DIR__ . \'/..', '\'phar://phpstan.phar', $content);
		},
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'bin/phpstan') {
				return $content;
			}
			return str_replace(sprintf('%s\\\\__PHPSTAN_RUNNING__', $prefix), '__PHPSTAN_RUNNING__', $content);
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
				'bin/phpstan',
				'src/Testing/TestCaseSourceLocatorFactory.php',
				'src/Testing/PHPStanTestCase.php',
				'vendor/ondrejmirtes/better-reflection/src/SourceLocator/Type/ComposerSourceLocator.php',
			], true)) {
				return $content;
			}

			return str_replace(sprintf('%s\\Composer\\Autoload\\ClassLoader', $prefix), 'Composer\\Autoload\\ClassLoader', $content);
		},
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'src/Internal/ComposerHelper.php') {
				return $content;
			}

			return str_replace(sprintf('%s\\Composer\\InstalledVersions', $prefix), 'Composer\\InstalledVersions', $content);
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
			if ($filePath !== 'vendor/ondrejmirtes/better-reflection/src/SourceLocator/SourceStubber/PhpStormStubsSourceStubber.php') {
				return $content;
			}

			return str_replace('Core/Core_d.php', 'Core/Core_d.stub', $content);
		},
		function (string $filePath, string $prefix, string $content): string {
			if ($filePath !== 'vendor/ondrejmirtes/better-reflection/src/SourceLocator/SourceStubber/PhpStormStubsSourceStubber.php') {
				return $content;
			}

			return str_replace(sprintf('\'%s\\\\JetBrains\\\\', $prefix), '\'JetBrains\\\\', $content);
		},
		function (string $filePath, string $prefix, string $content): string {
			if (!str_starts_with($filePath, 'vendor/nikic/php-parser/lib')) {
				return $content;
			}

			return str_replace(sprintf('use %s\\PhpParser;', $prefix), 'use PhpParser;', $content);
		},
	],
	'exclude-namespaces' => [
		'PHPStan',
		'PHPUnit',
		'PhpParser',
		'Hoa',
		'Symfony\Polyfill\Php80',
		'Symfony\Polyfill\Php81',
		'Symfony\Polyfill\Mbstring',
		'Symfony\Polyfill\Intl\Normalizer',
		'Symfony\Polyfill\Php73',
		'Symfony\Polyfill\Php74',
		'Symfony\Polyfill\Intl\Grapheme',
	],
	'expose-global-functions' => false,
	'expose-global-classes' => false,
];
