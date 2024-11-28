<?php

declare(strict_types=1);

use PHPStan\DependencyInjection\ProjectConfig\CacheConfig;
use PHPStan\DependencyInjection\ProjectConfig\ExceptionsCheckConfig;
use PHPStan\DependencyInjection\ProjectConfig\ExceptionsConfig;
use PHPStan\DependencyInjection\ProjectConfig\ExcludePathsConfig;
use PHPStan\DependencyInjection\ProjectConfig\IgnoreErrorsConfig;
use PHPStan\DependencyInjection\ProjectConfig\ParametersConfig;
use PHPStan\DependencyInjection\ProjectConfig\ProjectConfig;

return new ProjectConfig(
	includes: [
		'../vendor/phpstan/phpstan-deprecation-rules/rules.neon',
		'../vendor/phpstan/phpstan-nette/rules.neon',
		'../vendor/phpstan/phpstan-phpunit/extension.neon',
		'../vendor/phpstan/phpstan-phpunit/rules.neon',
		'../vendor/phpstan/phpstan-strict-rules/rules.neon',
		'../conf/bleedingEdge.neon',
		'../phpstan-baseline.neon',
		'../phpstan-baseline.php',
		'ignore-by-php-version.neon.php',
		'ignore-by-architecture.neon.php',
	],
	parameters: new ParametersConfig(
		level: 8,
		paths: [
			__DIR__ . '/PHPStan',
			__DIR__ . '/../src',
			__DIR__ . '/../tests',
		],
		bootstrapFiles: [__DIR__ . '/../tests/phpstan-bootstrap.php'],
		cache: new CacheConfig(nodesByStringCountMax: 128),
		checkUninitializedProperties: true,
		checkMissingCallableSignature: true,
		excludePaths: new ExcludePathsConfig(
			analyseAndScan: [
				__DIR__ . '/../tests/*/data/*',
				__DIR__ . '/../tests/tmp/*',
				__DIR__ . '/../tests/PHPStan/Analyser/nsrt/*',
				__DIR__ . '/../tests/PHPStan/Analyser/traits/*',
				__DIR__ . '/../tests/notAutoloaded/*',
				__DIR__ . '/../tests/PHPStan/Reflection/UnionTypesTest.php',
				__DIR__ . '/../tests/PHPStan/Reflection/MixedTypeTest.php',
				__DIR__ . '/../tests/e2e/magic-setter/*',
				__DIR__ . '/../tests/PHPStan/Rules/Properties/UninitializedPropertyRuleTest.php',
				__DIR__ . '/../tests/PHPStan/Command/IgnoredRegexValidatorTest.php',
				__DIR__ . '/../src/Command/IgnoredRegexValidator.php',
			],
		),
		exceptions: new ExceptionsConfig(
			uncheckedExceptionClasses: [
				'PHPStan\ShouldNotHappenException',
				'Symfony\Component\Console\Exception\InvalidArgumentException',
				'PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileLocation',
				'PHPStan\BetterReflection\SourceLocator\Exception\InvalidArgumentException',
				'Symfony\Component\Finder\Exception\DirectoryNotFoundException',
				'InvalidArgumentException',
				'PHPStan\DependencyInjection\ParameterNotFoundException',
				'PHPStan\DependencyInjection\DuplicateIncludedFilesException',
				'PHPStan\Analyser\UndefinedVariableException',
				'RuntimeException',
				'Nette\Neon\Exception',
				'Nette\Utils\JsonException',
				'PHPStan\File\CouldNotReadFileException',
				'PHPStan\File\CouldNotWriteFileException',
				'PHPStan\Parser\ParserErrorsException',
				'ReflectionException',
				'Nette\Utils\AssertionException',
				'PHPStan\File\PathNotFoundException',
				'PHPStan\Broker\ClassNotFoundException',
				'PHPStan\Broker\FunctionNotFoundException',
				'PHPStan\Broker\ConstantNotFoundException',
				'PHPStan\Reflection\MissingMethodFromReflectionException',
				'PHPStan\Reflection\MissingPropertyFromReflectionException',
				'PHPStan\Reflection\MissingConstantFromReflectionException',
				'PHPStan\Type\CircularTypeAliasDefinitionException',
				'PHPStan\Broker\ClassAutoloadingException',
				'LogicException',
				'Error',
			],
			check: new ExceptionsCheckConfig(missingCheckedExceptionInThrows: true, tooWideThrowType: true),
		),
		ignoreErrors: [
			'#^Dynamic call to static method PHPUnit\\\Framework\\\\\S+\(\)\.$#',
			'#should be contravariant with parameter \$node \(PhpParser\\\Node\) of method PHPStan\\\Rules\\\Rule<PhpParser\\\Node>::processNode\(\)$#',
			'#Variable property access on PhpParser\\\Node#',
			'#Test::data[a-zA-Z0-9_]+\(\) return type has no value type specified in iterable type#',
			new IgnoreErrorsConfig(
				message: '#Fetching class constant class of deprecated class DeprecatedAnnotations\\\DeprecatedFoo.#',
				path: __DIR__ . '/../tests/PHPStan/Reflection/Annotations/DeprecatedAnnotationsTest.php',
			),
			new IgnoreErrorsConfig(
				message: '#Fetching class constant class of deprecated class DeprecatedAnnotations\\\DeprecatedWithMultipleTags.#',
				path: __DIR__ . '/../tests/PHPStan/Reflection/Annotations/DeprecatedAnnotationsTest.php',
			),
			new IgnoreErrorsConfig(
				message: '#^Variable property access on T of PHPStan\\\Rules\\\RuleError\.$#',
				path: __DIR__ . '/../src/Rules/RuleErrorBuilder.php',
			),
			new IgnoreErrorsConfig(
				message: '#^Parameter \#1 (?:\$argument|\$objectOrClass) of class ReflectionClass constructor expects class\-string\<PHPStan\\\ExtensionInstaller\\\GeneratedConfig\>\|PHPStan\\\ExtensionInstaller\\\GeneratedConfig, string given\.$#',
				count: 1,
				path: __DIR__ . '/../src/Command/CommandHelper.php',
			),
			new IgnoreErrorsConfig(
				message: '#^Parameter \#1 (?:\$argument|\$objectOrClass) of class ReflectionClass constructor expects class\-string\<PHPStan\\\ExtensionInstaller\\\GeneratedConfig\>\|PHPStan\\\ExtensionInstaller\\\GeneratedConfig, string given\.$#',
				count: 1,
				path: __DIR__ . '/../src/Diagnose/PHPStanDiagnoseExtension.php',
			),
			'#^Short ternary operator is not allowed#',
		],
		reportStaticMethodSignatures: true,
		tmpDir: '%rootDir%/tmp',
		stubFiles: [
			__DIR__ . '/stubs/ReactChildProcess.stub',
			__DIR__ . '/stubs/ReactStreams.stub',
			__DIR__ . '/stubs/NetteDIContainer.stub',
			__DIR__ . '/stubs/PhpParserName.stub',
		],
	),
	_extra: [
		'services' => [
			[
				'class' => 'PHPStan\Build\ServiceLocatorDynamicReturnTypeExtension',
				'tags' => ['phpstan.broker.dynamicMethodReturnTypeExtension'],
			],
			[
				'class' => 'PHPStan\Build\ContainerDynamicReturnTypeExtension',
				'tags' => ['phpstan.broker.dynamicMethodReturnTypeExtension'],
			],
		]
	]
);
