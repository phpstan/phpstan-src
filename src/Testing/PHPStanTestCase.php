<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use Override;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\DirectInternalScopeFactoryFactory;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\ConfiguredPhpVersionRangeHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProvider\DirectReflectionProviderProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\Constant\OversizedArrayBuilder;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry;
use PHPStan\Type\TypeAliasResolver;
use PHPStan\Type\UnaryOperatorTypeSpecifyingExtensionRegistry;
use PHPStan\Type\UsefulTypeAliasResolver;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use function count;
use function implode;
use function rtrim;
use function sprintf;
use const DIRECTORY_SEPARATOR;

/** @api */
abstract class PHPStanTestCase extends TestCase
{

	use PHPStanTestCaseTrait;

	/**
	 * Re-register the runtime container as the global static reflection provider before
	 * every test. Enable this in tests that construct Type objects directly and assert
	 * PHP-version-dependent reflection results, so a foreign PhpVersion leaked by another
	 * test can't flake them. See https://github.com/phpstan/phpstan/issues/14860
	 */
	protected bool $reinitializeContainerBeforeEachTest = false;

	#[Override]
	protected function setUp(): void
	{
		if (!$this->reinitializeContainerBeforeEachTest) {
			return;
		}

		self::getContainer();
	}

	public static function getParser(): Parser
	{
		/** @var Parser $parser */
		$parser = self::getContainer()->getService('defaultAnalysisParser');
		return $parser;
	}

	/** @api */
	public static function createReflectionProvider(): ReflectionProvider
	{
		return self::getContainer()->getByType(ReflectionProvider::class);
	}

	public static function getReflector(): Reflector
	{
		return self::getContainer()->getService('betterReflectionReflector');
	}

	public static function getClassReflectionExtensionRegistryProvider(): ClassReflectionExtensionRegistryProvider
	{
		return self::getContainer()->getByType(ClassReflectionExtensionRegistryProvider::class);
	}

	/**
	 * @param string[] $dynamicConstantNames
	 */
	public static function createScopeFactory(ReflectionProvider $reflectionProvider, TypeSpecifier $typeSpecifier, array $dynamicConstantNames = []): ScopeFactory
	{
		$container = self::getContainer();

		if (count($dynamicConstantNames) === 0) {
			$dynamicConstantNames = $container->getParameter('dynamicConstantNames');
		}

		$reflectionProviderProvider = new DirectReflectionProviderProvider($reflectionProvider);
		$composerPhpVersionFactory = $container->getByType(ComposerPhpVersionFactory::class);
		$constantResolver = new ConstantResolver($reflectionProviderProvider, $dynamicConstantNames, new ConfiguredPhpVersionRangeHelper(null, $composerPhpVersionFactory), container: $container);

		$initializerExprTypeResolver = new InitializerExprTypeResolver(
			$constantResolver,
			$reflectionProviderProvider,
			$container->getByType(PhpVersion::class),
			$container->getByType(OperatorTypeSpecifyingExtensionRegistry::class),
			$container->getByType(UnaryOperatorTypeSpecifyingExtensionRegistry::class),
			new OversizedArrayBuilder(),
			$container->getParameter('usePathConstantsAsConstantString'),
		);

		return new ScopeFactory(
			new DirectInternalScopeFactoryFactory(
				$container,
				$reflectionProvider,
				$initializerExprTypeResolver,
				$container->getExtensionsCollection(ExpressionTypeResolverExtension::class),
				$container->getByType(ExprPrinter::class),
				$typeSpecifier,
				new PropertyReflectionFinder(),
				self::getParser(),
				$container->getByType(PhpVersion::class),
				$container->getByType(AttributeReflectionFactory::class),
				$container->getParameter('phpVersion'),
				$constantResolver,
			),
		);
	}

	/**
	 * @param array<string, string> $globalTypeAliases
	 */
	public static function createTypeAliasResolver(array $globalTypeAliases, ReflectionProvider $reflectionProvider): TypeAliasResolver
	{
		$container = self::getContainer();

		return new UsefulTypeAliasResolver(
			$globalTypeAliases,
			$container->getByType(TypeStringResolver::class),
			$container->getByType(TypeNodeResolver::class),
			$reflectionProvider,
			0,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return true;
	}

	/**
	 * Provides a DIRECTORY_SEPARATOR agnostic assertion helper, to compare file paths.
	 *
	 */
	protected function assertSamePaths(string $expected, string $actual, string $message = ''): void
	{
		$expected = $this->getFileHelper()->normalizePath($expected);
		$actual = $this->getFileHelper()->normalizePath($actual);

		$this->assertSame($expected, $actual, $message);
	}

	/**
	 * @param Error[]|string[] $errors
	 */
	protected function assertNoErrors(array $errors): void
	{
		try {
			$this->assertCount(0, $errors);
		} catch (ExpectationFailedException $e) {
			$messages = [];
			foreach ($errors as $error) {
				if ($error instanceof Error) {
					$messages[] = sprintf("- %s\n  in %s on line %d%s\n", rtrim($error->getMessage(), '.'), $error->getFile(), $error->getLine() ?? 0, $error->getTip() !== null ? sprintf("\n💡 %s", $error->getTip()) : '');
				} else {
					$messages[] = $error;
				}
			}

			$this->fail($e->getMessage() . "\n\nEmitted errors:\n" . implode("\n", $messages));
		}
	}

	protected function skipIfNotOnWindows(): void
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			return;
		}

		self::markTestSkipped();
	}

	protected function skipIfNotOnUnix(): void
	{
		if (DIRECTORY_SEPARATOR === '/') {
			return;
		}

		self::markTestSkipped();
	}

}
