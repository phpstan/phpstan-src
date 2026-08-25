<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\Testing\PHPStanTestCase;
use TestSingleFileSourceLocator\AFoo;
use function class_alias;
use function class_exists;
use function function_exists;

class AutoloadFunctionsSourceLocatorTest extends PHPStanTestCase
{

	/**
	 * A class alias created by a bootstrap-registered autoloader must be located even when a
	 * *function* of the same name exists - classes and functions live in separate symbol
	 * spaces, and Laravel's facade aliases (Cache, File, Str, ...) collide with global
	 * helpers like cache(), file() and str().
	 *
	 * @see https://github.com/phpstan/phpstan/issues/15102
	 */
	public function testLocatesAliasWhoseNameCollidesWithAFunction(): void
	{
		require_once __DIR__ . '/data/a.php';
		$this->assertTrue(function_exists('file'), 'precondition: file() is a built-in function');
		$this->assertFalse(class_exists('File', false), 'precondition: no File class yet');

		$invocations = 0;
		$GLOBALS['__phpstanAutoloadFunctions'] = [
			static function (string $class) use (&$invocations): void {
				if ($class !== 'File') {
					return;
				}

				$invocations++;
				class_alias(AFoo::class, 'File');
			},
		];

		try {
			$locator = $this->createLocator();
			$reflection = $locator->locateIdentifier(
				new DefaultReflector($locator),
				new Identifier('File', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
			);

			// Non-null is the point: before the fix this locator declined outright because a
			// function named file() exists. The reflection carries the alias *target*'s name -
			// rewriting it to the alias is RewriteClassAliasSourceLocator's job, further up the chain.
			$this->assertNotNull($reflection, 'the aliased class should be located');
			$this->assertSame(AFoo::class, $reflection->getName());

			// An autoloader that defines the class itself must not be called a second time:
			// class_alias() would then warn that the name is already in use.
			$this->assertSame(1, $invocations, 'the autoloader should run exactly once');
		} finally {
			unset($GLOBALS['__phpstanAutoloadFunctions']);
		}
	}

	/**
	 * A defining autoloader must win over a later catch-all one that would resolve the same name to
	 * an already-loaded file: the name is taken care of by the time the catch-all runs, exactly as
	 * spl_autoload_call() would stop there.
	 */
	public function testDefiningAutoloaderWinsOverALaterCatchAllOne(): void
	{
		require_once __DIR__ . '/data/a.php';
		$this->assertTrue(function_exists('hash'), 'precondition: hash() is a built-in function');
		$this->assertFalse(class_exists('Hash', false), 'precondition: no Hash class yet');

		$GLOBALS['__phpstanAutoloadFunctions'] = [
			static function (string $class): void {
				if ($class !== 'Hash') {
					return;
				}

				class_alias(AFoo::class, 'Hash');
			},
			static function (string $class): void {
				if ($class !== 'Hash') {
					return;
				}

				// A catch-all autoloader mapping names to paths - PHP_CodeSniffer's shape - landing
				// on a file that is loaded already.
				require __DIR__ . '/data/a.php';
			},
		];

		try {
			$locator = $this->createLocator();
			$reflection = $locator->locateIdentifier(
				new DefaultReflector($locator),
				new Identifier('Hash', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
			);

			$this->assertNotNull($reflection, 'the aliased class should be located');
			$this->assertSame(AFoo::class, $reflection->getName());
		} finally {
			unset($GLOBALS['__phpstanAutoloadFunctions']);
		}
	}

	private function createLocator(): AutoloadFunctionsSourceLocator
	{
		$container = self::getContainer();

		return new AutoloadFunctionsSourceLocator(
			new AutoloadSourceLocator($container->getByType(FileNodesFetcher::class), false),
			new ReflectionClassSourceLocator(
				new Locator($container->getService('phpParserDecorator')),
				new ReflectionSourceStubber(new Standard()),
			),
			false,
		);
	}

}
