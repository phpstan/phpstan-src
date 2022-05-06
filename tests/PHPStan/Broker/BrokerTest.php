<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\DependencyInjection\Reflection\DirectClassReflectionExtensionRegistryProvider;
use PHPStan\File\FileHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\FunctionReflectionFactory;
use PHPStan\Reflection\ReflectionProvider\SetterReflectionProviderProvider;
use PHPStan\Reflection\Runtime\RuntimeReflectionProvider;
use PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\FileTypeMapper;
use function spl_autoload_register;

class BrokerTest extends PHPStanTestCase
{

	private Broker $broker;

	protected function setUp(): void
	{
		$phpDocStringResolver = self::getContainer()->getByType(PhpDocStringResolver::class);
		$phpDocNodeResolver = self::getContainer()->getByType(PhpDocNodeResolver::class);

		$workingDirectory = __DIR__;
		$relativePathHelper = new SimpleRelativePathHelper($workingDirectory);
		$fileHelper = new FileHelper($workingDirectory);
		$anonymousClassNameHelper = new AnonymousClassNameHelper($fileHelper, $relativePathHelper);

		$classReflectionExtensionRegistryProvider = new DirectClassReflectionExtensionRegistryProvider([], []);

		$setterReflectionProviderProvider = new SetterReflectionProviderProvider();
		$reflectionProvider = new RuntimeReflectionProvider(
			$setterReflectionProviderProvider,
			$classReflectionExtensionRegistryProvider,
			$this->createMock(FunctionReflectionFactory::class),
			new FileTypeMapper($setterReflectionProviderProvider, $this->getParser(), $phpDocStringResolver, $phpDocNodeResolver, $anonymousClassNameHelper, self::getContainer()->getByType(FileHelper::class)),
			self::getContainer()->getByType(PhpDocInheritanceResolver::class),
			self::getContainer()->getByType(PhpVersion::class),
			self::getContainer()->getByType(NativeFunctionReflectionProvider::class),
			self::getContainer()->getByType(StubPhpDocProvider::class),
			self::getContainer()->getByType(PhpStormStubsSourceStubber::class),
		);
		$setterReflectionProviderProvider->setReflectionProvider($reflectionProvider);
		$this->broker = new Broker(
			$reflectionProvider,
			[],
		);
		$classReflectionExtensionRegistryProvider->setBroker($this->broker);
	}

	public function testClassNotFound(): void
	{
		$this->expectException(ClassNotFoundException::class);
		$this->expectExceptionMessage('NonexistentClass');
		$this->broker->getClass('NonexistentClass');
	}

	public function testFunctionNotFound(): void
	{
		$this->expectException(FunctionNotFoundException::class);
		$this->expectExceptionMessage('Function nonexistentFunction not found while trying to analyse it - discovering symbols is probably not configured properly.');

		$scope = $this->createMock(Scope::class);
		$scope->method('getNamespace')
			->willReturn(null);
		$this->broker->getFunction(new Name('nonexistentFunction'), $scope);
	}

	public function testClassAutoloadingException(): void
	{
		$this->expectException(ClassAutoloadingException::class);
		$this->expectExceptionMessage('thrown while looking for class NonexistentClass.');
		spl_autoload_register(static function (): void {
			require_once __DIR__ . '/../Analyser/data/parse-error.php';
		}, true, true);
		$this->broker->hasClass('NonexistentClass');
	}

}
