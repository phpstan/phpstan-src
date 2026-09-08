<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;

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
		$paths = $this->createResolver()->resolve('lorem.php', $this->createScope());

		$this->assertContains(__DIR__ . '/lorem.php', $paths);
		$this->assertContains(__DIR__ . '/test/lorem.php', $paths);
	}

}
