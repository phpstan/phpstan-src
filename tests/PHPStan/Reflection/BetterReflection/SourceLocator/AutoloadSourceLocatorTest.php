<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Composer\Autoload\ClassLoader;
use Phar;
use PharData;
use PharException;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantIntegerType;
use TestSingleFileSourceLocator\AFoo;
use TestSingleFileSourceLocator\InCondition;
use function array_merge;
use function class_alias;
use function sys_get_temp_dir;
use function uniqid;

function testFunctionForLocator(): void // phpcs:disable
{
	echo 'test';
}

class AutoloadSourceLocatorTest extends PHPStanTestCase
{

	public function testAutoloadEverythingInFile(): void
	{
		$locator = new AutoloadSourceLocator(self::getContainer()->getByType(FileNodesFetcher::class), true);
		$reflector = new DefaultReflector($locator);
		$aFoo = $reflector->reflectClass(AFoo::class);
		$this->assertNotNull($aFoo->getFileName());
		$this->assertSame('a.php', basename($aFoo->getFileName()));

		$testFunctionReflection = $reflector->reflectFunction('PHPStan\\Reflection\\BetterReflection\\SourceLocator\testFunctionForLocator');
		$this->assertSame(str_replace('\\', '/', __FILE__), $testFunctionReflection->getFileName());

		$someConstant = $reflector->reflectConstant('TestSingleFileSourceLocator\\SOME_CONSTANT');
		$this->assertNotNull($someConstant->getFileName());
		$this->assertSame('a.php', basename($someConstant->getFileName()));

		$initializerExprTypeResolver = self::getContainer()->getByType(InitializerExprTypeResolver::class);
		$someConstantValue = $initializerExprTypeResolver->getType(
			$someConstant->getValueExpression(),
			InitializerExprContext::fromGlobalConstant($someConstant),
		);
		$this->assertInstanceOf(ConstantIntegerType::class, $someConstantValue);
		$this->assertSame(1, $someConstantValue->getValue());

		$anotherConstant = $reflector->reflectConstant('TestSingleFileSourceLocator\\ANOTHER_CONSTANT');
		$this->assertNotNull($anotherConstant->getFileName());
		$this->assertSame('a.php', basename($anotherConstant->getFileName()));
		$anotherConstantValue = $initializerExprTypeResolver->getType(
			$anotherConstant->getValueExpression(),
			InitializerExprContext::fromGlobalConstant($anotherConstant),
		);
		$this->assertInstanceOf(ConstantIntegerType::class, $anotherConstantValue);
		$this->assertSame(2, $anotherConstantValue->getValue());

		$doFooFunctionReflection = $reflector->reflectFunction('TestSingleFileSourceLocator\\doFoo');
		$this->assertSame('TestSingleFileSourceLocator\\doFoo', $doFooFunctionReflection->getName());
		$this->assertNotNull($doFooFunctionReflection->getFileName());
		$this->assertSame('a.php', basename($doFooFunctionReflection->getFileName()));

		class_exists(InCondition::class);
		$classInCondition = $reflector->reflectClass(InCondition::class);
		$classInConditionFilename = $classInCondition->getFileName();
		$this->assertNotNull($classInConditionFilename);
		$this->assertSame('a.php', basename($classInConditionFilename));
		$this->assertSame(InCondition::class, $classInCondition->getName());
		$this->assertSame(25, $classInCondition->getStartLine());
		$this->assertInstanceOf(ReflectionClass::class, $classInCondition->getParentClass());
		$this->assertSame(AFoo::class, $classInCondition->getParentClass()->getName());
	}

	public function testClassAlias(): void
	{
		class_alias(AFoo::class, 'A_Foo');
		$locator = new AutoloadSourceLocator(self::getContainer()->getByType(FileNodesFetcher::class), true);
		$reflector = new DefaultReflector($locator);
		$class = $reflector->reflectClass('A_Foo');
		$this->assertSame(AFoo::class, $class->getName());
	}

	/**
	 * PHPStan's own classes live in the phar, behind a Composer ClassLoader that
	 * maps PHPStan\ to phar://.../src - and realpath() cannot resolve such a path.
	 * A tar archive stands in for the phar: PharData writes one even with
	 * phar.readonly on, and phar:// reads it the same way.
	 *
	 * @throws PharException
	 */
	public function testClassInsidePharBehindComposerClassLoader(): void
	{
		$archive = sys_get_temp_dir() . '/phpstan-autoload-source-locator-' . uniqid() . '.tar';
		$pharData = new PharData($archive);
		$pharData->addFromString('src/InPhar.php', "<?php\n\nnamespace AutoloadSourceLocatorInPhar;\n\nclass InPhar\n{\n\n}\n");
		unset($pharData);

		$loader = new ClassLoader();
		$loader->addPsr4('AutoloadSourceLocatorInPhar\\', ['phar://' . $archive . '/src']);
		$loader->register(true);

		try {
			$locator = new AutoloadSourceLocator(self::getContainer()->getByType(FileNodesFetcher::class), true);
			$reflector = new DefaultReflector($locator);
			$class = $reflector->reflectClass('AutoloadSourceLocatorInPhar\InPhar');
			$this->assertSame('InPhar', $class->getShortName());

			$fileName = $class->getFileName();
			$this->assertNotNull($fileName);
			$this->assertStringStartsWith('phar://', $fileName);
		} finally {
			$loader->unregister();
			Phar::unlinkArchive($archive);
		}
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/disableCache.neon',
			],
		);
	}

}
