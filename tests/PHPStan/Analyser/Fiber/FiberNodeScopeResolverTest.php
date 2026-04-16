<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\Type\ParameterClosureThisExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterClosureTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterOutTypeExtensionProvider;
use PHPStan\File\FileHelper;
use PHPStan\Node\DeepNodeCloner;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\Reflection\ClassReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\Type\FileTypeMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

#[RequiresPhp('>= 8.1.0')]
class FiberNodeScopeResolverTest extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/fnsr.php');
	}

	/**
	 * @param mixed ...$args
	 */
	#[DataProvider('dataFileAsserts')]
	public function testFileAsserts(
		string $assertType,
		string $file,
		...$args,
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	protected static function createNodeScopeResolver(): NodeScopeResolver
	{
		$container = self::getContainer();
		$reflectionProvider = self::createReflectionProvider();
		$typeSpecifier = $container->getService('typeSpecifier');

		return new FiberNodeScopeResolver(
			$container,
			$reflectionProvider,
			$container->getByType(InitializerExprTypeResolver::class),
			self::getReflector(),
			$container->getByType(ClassReflectionFactory::class),
			$container->getByType(ParameterOutTypeExtensionProvider::class),
			self::getParser(),
			$container->getByType(FileTypeMapper::class),
			$container->getByType(PhpDocInheritanceResolver::class),
			$container->getByType(FileHelper::class),
			$typeSpecifier,
			$container->getByType(ReadWritePropertiesExtensionProvider::class),
			$container->getByType(ParameterClosureThisExtensionProvider::class),
			$container->getByType(ParameterClosureTypeExtensionProvider::class),
			self::createScopeFactory($reflectionProvider, $typeSpecifier),
			$container->getByType(DeepNodeCloner::class),
			$container->getParameter('polluteScopeWithLoopInitialAssignments'),
			$container->getParameter('polluteScopeWithAlwaysIterableForeach'),
			$container->getParameter('polluteScopeWithBlock'),
			static::getEarlyTerminatingMethodCalls(),
			static::getEarlyTerminatingFunctionCalls(),
			$container->getParameter('exceptions')['implicitThrows'],
			$container->getParameter('treatPhpDocTypesAsCertain'),
			$container->getByType(ImplicitToStringCallHelper::class),
		);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../../conf/bleedingEdge.neon',
		];
	}

}
