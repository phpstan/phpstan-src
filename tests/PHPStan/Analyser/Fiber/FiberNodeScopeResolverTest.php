<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\File\FileHelper;
use PHPStan\Node\DeepNodeCloner;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\Reflection\ClassReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\FunctionParameterClosureThisExtension;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\MethodParameterClosureThisExtension;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\StaticMethodParameterClosureThisExtension;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
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
			$container->getExtensionsCollection(FunctionParameterOutTypeExtension::class),
			$container->getExtensionsCollection(MethodParameterOutTypeExtension::class),
			$container->getExtensionsCollection(StaticMethodParameterOutTypeExtension::class),
			self::getParser(),
			$container->getByType(FileTypeMapper::class),
			$container->getByType(PhpDocInheritanceResolver::class),
			$container->getByType(FileHelper::class),
			$typeSpecifier,
			$container->getExtensionsCollection(ReadWritePropertiesExtension::class),
			$container->getExtensionsCollection(FunctionParameterClosureThisExtension::class),
			$container->getExtensionsCollection(MethodParameterClosureThisExtension::class),
			$container->getExtensionsCollection(StaticMethodParameterClosureThisExtension::class),
			$container->getExtensionsCollection(FunctionParameterClosureTypeExtension::class),
			$container->getExtensionsCollection(MethodParameterClosureTypeExtension::class),
			$container->getExtensionsCollection(StaticMethodParameterClosureTypeExtension::class),
			self::createScopeFactory($reflectionProvider, $typeSpecifier),
			$container->getByType(DeepNodeCloner::class),
			$container->getParameter('polluteScopeWithLoopInitialAssignments'),
			$container->getParameter('polluteScopeWithAlwaysIterableForeach'),
			$container->getParameter('polluteScopeWithBlock'),
			$container->getParameter('exceptions')['implicitThrows'],
			$container->getParameter('treatPhpDocTypesAsCertain'),
			$container->getByType(ImplicitToStringCallHelper::class),
			$container->getByType(ExpressionResultFactory::class),
		);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../../conf/bleedingEdge.neon',
		];
	}

}
