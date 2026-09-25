<?php declare(strict_types=1);

return [
	/**
	 * Namespaces php-scoper leaves alone completely - neither declarations nor
	 * references to them are prefixed.
	 */
	'excluded' => [
		'PHPStan',
		// the native turbo extension's classes — must match the loaded
		// extension exactly, never prefixed (segment-aware matching means the
		// PHPStan entry above does not cover this name)
		'PHPStanTurbo',
		'PHPUnit',
		'PhpParser',
		'Hoa',
		'Symfony\Polyfill\Php80',
		'Symfony\Polyfill\Php81',
		'Symfony\Polyfill\Php83',
		'Symfony\Polyfill\Php84',
		'Symfony\Polyfill\Php85',
		'Symfony\Polyfill\Mbstring',
		'Symfony\Polyfill\Intl\Normalizer',
		'Symfony\Polyfill\Intl\Grapheme',
	],

	/**
	 * Namespaces of classes that belong to the analysed code, referenced from
	 * src/ through string literals like `new ObjectType('BcMath\Number')`.
	 *
	 * php-scoper prefixes such literals even when they name internal PHP
	 * classes, which would turn them into class names that do not exist in the
	 * analysed code, so a patcher in scoper.inc.php strips the prefix back off.
	 * ScoperClassNameStringsTest fails when src/ references a namespace that is
	 * missing here.
	 */
	'unprefixedClassNameStringsInSrc' => [
		'BcMath',
		'Dom',
		'Ds',
		'FFI',
		'Filter',
		'Foobar',
		'PDO',
	],

	/**
	 * Files that refer to the analysed project's Composer\Autoload\ClassLoader,
	 * not to the phar's own prefixed copy - an instanceof against the prefixed
	 * name never matches the project's autoloader.
	 *
	 * A patcher in scoper.inc.php strips the prefix back off in these files.
	 * ScoperComposerClassLoaderTest fails when a file in src/ or bin/ refers to
	 * the class without being listed here.
	 */
	'unprefixedComposerClassLoaderIn' => [
		'bin/phpstan',
		'src/Reflection/BetterReflection/SourceLocator/AutoloadSourceLocator.php',
		'src/Testing/TestCaseSourceLocatorFactory.php',
		'src/Testing/PHPStanTestCase.php',
		'vendor/ondrejmirtes/better-reflection/src/SourceLocator/Type/ComposerSourceLocator.php',
	],
];
