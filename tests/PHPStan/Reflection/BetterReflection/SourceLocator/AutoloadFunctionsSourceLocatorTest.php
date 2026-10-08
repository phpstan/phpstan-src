<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use LogicException;
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

class AutoloadFunctionsSourceLocatorTest extends PHPStanTestCase
{

	/**
	 * A bootstrap autoloader that throws for names outside its own scope must not abort the
	 * analysis: this locator asks every registered autoloader, while at runtime the class loader
	 * that resolves the class first means the throwing one is never invoked for that name.
	 *
	 * @see https://github.com/phpstan/phpstan/issues/14976
	 */
	public function testAThrowingAutoloaderDoesNotStopTheRemainingOnes(): void
	{
		require_once __DIR__ . '/data/a.php';
		$this->assertFalse(class_exists('ThrowingAutoloaderAlias', false), 'precondition: the alias does not exist yet');

		$GLOBALS['__phpstanAutoloadFunctions'] = [
			static function (string $class): void {
				throw new LogicException('this should not happen');
			},
			static function (string $class): void {
				if ($class !== 'ThrowingAutoloaderAlias') {
					return;
				}

				class_alias(AFoo::class, 'ThrowingAutoloaderAlias');
			},
		];

		try {
			$locator = $this->createLocator();
			$reflection = $locator->locateIdentifier(
				new DefaultReflector($locator),
				new Identifier('ThrowingAutoloaderAlias', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
			);

			$this->assertNotNull($reflection, 'the class defined by the second autoloader should be located');
			$this->assertSame(AFoo::class, $reflection->getName());
		} finally {
			unset($GLOBALS['__phpstanAutoloadFunctions']);
		}
	}

	/**
	 * Nothing resolves the name, so the locator declines - the point is that it declines instead of
	 * letting the autoloader's exception surface as an internal error.
	 */
	public function testAThrowingAutoloaderMakesTheLocatorDecline(): void
	{
		$GLOBALS['__phpstanAutoloadFunctions'] = [
			static function (string $class): void {
				throw new LogicException('this should not happen');
			},
		];

		try {
			$locator = $this->createLocator();
			$reflection = $locator->locateIdentifier(
				new DefaultReflector($locator),
				new Identifier('NeverDefinedByAnyAutoloader', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
			);

			$this->assertNull($reflection);
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
		);
	}

}
