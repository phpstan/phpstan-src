<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Override;
use PHPStan\File\FileHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\PhpVersionStaticAccessor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Testing\PHPStanTestCase;
use function sys_get_temp_dir;

final class ContainerFactoryPostInitializeTest extends PHPStanTestCase
{

	private bool $bleedingEdgeBackup;

	private PhpVersion $phpVersionBackup;

	private ReflectionProvider $reflectionProviderBackup;

	#[Override]
	protected function setUp(): void
	{
		$this->bleedingEdgeBackup = BleedingEdgeToggle::isBleedingEdge();
		$this->phpVersionBackup = PhpVersionStaticAccessor::getInstance();
		$this->reflectionProviderBackup = ReflectionProviderStaticAccessor::getInstance();
	}

	#[Override]
	protected function tearDown(): void
	{
		BleedingEdgeToggle::setBleedingEdge($this->bleedingEdgeBackup);
		PhpVersionStaticAccessor::registerInstance($this->phpVersionBackup);
		ReflectionProviderStaticAccessor::registerInstance($this->reflectionProviderBackup);
	}

	public function testReappliesGlobalStateForAlreadyInitializedContainer(): void
	{
		// A separate container whose ReflectionProvider stands in for state leaked by an
		// unrelated test. Building it also makes it the "last initialized" container.
		$leakedReflectionProvider = $this->createSeparateReflectionProvider();

		$container = self::getContainer();

		// Make this container the last initialized one again, so the final
		// postInitializeContainer() call below exercises the early-return guard.
		ContainerFactory::postInitializeContainer($container);

		$expectedBleedingEdge = BleedingEdgeToggle::isBleedingEdge();
		$expectedPhpVersion = $container->getByType(PhpVersion::class);
		$expectedReflectionProvider = $container->getByType(ReflectionProvider::class);

		self::assertNotSame($expectedReflectionProvider, $leakedReflectionProvider);

		// Simulate another test / data provider leaking global state while this container
		// stays the "last initialized" one (e.g. two test classes sharing the same container).
		BleedingEdgeToggle::setBleedingEdge(!$expectedBleedingEdge);
		PhpVersionStaticAccessor::registerInstance(new PhpVersion(70100));
		ReflectionProviderStaticAccessor::registerInstance($leakedReflectionProvider);

		// Returning to the same container must restore all global state, even though the
		// expensive BetterReflection population is skipped for an already-initialized container.
		ContainerFactory::postInitializeContainer($container);

		self::assertSame($expectedBleedingEdge, BleedingEdgeToggle::isBleedingEdge());
		self::assertSame($expectedPhpVersion->getVersionId(), PhpVersionStaticAccessor::getInstance()->getVersionId());
		self::assertSame($expectedReflectionProvider, ReflectionProviderStaticAccessor::getInstance());
	}

	private function createSeparateReflectionProvider(): ReflectionProvider
	{
		$rootDir = __DIR__ . '/../../..';
		$fileHelper = new FileHelper($rootDir);
		$rootDir = $fileHelper->normalizePath($rootDir, '/');
		$containerFactory = new ContainerFactory($rootDir);
		$tmpDir = sys_get_temp_dir() . '/phpstan-tests';
		$container = $containerFactory->create($tmpDir, [
			$containerFactory->getConfigDirectory() . '/config.level8.neon',
			__DIR__ . '/../../../src/Testing/TestCase.neon',
		], []);

		return $container->getByType(ReflectionProvider::class);
	}

}
