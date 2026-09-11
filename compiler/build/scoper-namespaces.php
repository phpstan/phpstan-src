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
	 * php-scoper prefixes such literals, which would turn them into class names
	 * that do not exist in the analysed code, so a patcher in scoper.inc.php
	 * strips the prefix back off. They cannot simply be added to 'excluded'
	 * because the phar bundles polyfills declaring some of them (e.g.
	 * Filter\FilterFailedException from symfony/polyfill-php85) and those
	 * declarations do have to stay prefixed.
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
];
