<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use function array_map;
use function str_replace;

final class IncludedFilePathResolverTest extends PHPStanTestCase
{

	private function createResolver(): IncludedFilePathResolver
	{
		return new IncludedFilePathResolver(__DIR__, self::getContainer()->getByType(FileHelper::class));
	}

	private function createScope(): Scope
	{
		$scope = $this->createMock(Scope::class);
		$scope->method('isInTrait')->willReturn(false);
		$scope->method('getFile')->willReturn(__DIR__ . '/test/lorem.php');

		return $scope;
	}

	public function testPathWithAnUnregisteredStreamWrapperHasNoCandidates(): void
	{
		// is_file() on it raises "Unable to find the wrapper" instead of answering, and the path
		// cannot become readable later either - vfsStream registers its wrapper from a test's
		// setUp(), which never runs inside PHPStan.
		$this->assertSame([], $this->createResolver()->resolve('vfs://drupal/sites/default/x.php', $this->createScope()));
	}

	public function testPathWithARegisteredStreamWrapperIsKept(): void
	{
		$this->assertSame(['php://memory'], $this->createResolver()->resolve('php://memory', $this->createScope()));
	}

	public function testRelativePathIsResolvedAgainstEveryDirectory(): void
	{
		// Compared with forward slashes so the expectations hold on Windows too, where
		// absolutizePath() joins with a backslash.
		$paths = array_map(
			static fn (string $path): string => str_replace('\\', '/', $path),
			$this->createResolver()->resolve('lorem.php', $this->createScope()),
		);
		$directory = str_replace('\\', '/', __DIR__);

		$this->assertContains($directory . '/lorem.php', $paths);
		$this->assertContains($directory . '/test/lorem.php', $paths);
	}

}
