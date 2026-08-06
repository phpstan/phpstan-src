<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\Error;
use PHPUnit\Framework\TestCase;
use function array_keys;

final class ResultCachePathTransformerTest extends TestCase
{

	// A phar-style anchor: the phpstan install sits under the project's vendor dir, so project code
	// is above the anchor and relativizes to "../../.." offsets that are stable across checkouts.
	private const ANCHOR_A = '/home/ci/build-123/vendor/phpstan/phpstan';

	private const ANCHOR_B = '/srv/runner/x9/vendor/phpstan/phpstan';

	public function testPathRebasesFromOneAnchorToAnother(): void
	{
		$a = new ResultCachePathTransformer(self::ANCHOR_A);
		$b = new ResultCachePathTransformer(self::ANCHOR_B);

		$relative = $a->relativizePath('/home/ci/build-123/src/Service.php');
		// project code is above the phar dir, so it relativizes to a "../" offset, not an absolute path
		$this->assertSame('../../../src/Service.php', $relative);

		// reading the same relative path against a different anchor yields the file at its new location
		$this->assertSame('/srv/runner/x9/src/Service.php', $b->absolutizePath($relative));
	}

	public function testSameAnchorRoundTripIsIdentity(): void
	{
		$a = new ResultCachePathTransformer(self::ANCHOR_A);

		$original = '/home/ci/build-123/tests/FooTest.php';
		$this->assertSame($original, $a->absolutizePath($a->relativizePath($original)));
	}

	public function testPathOutsideAnchorStaysAbsolute(): void
	{
		$a = new ResultCachePathTransformer(self::ANCHOR_A);
		$b = new ResultCachePathTransformer(self::ANCHOR_B);

		// no shared prefix with the anchor: left absolute (ccache rule), so it survives a move unchanged
		$outside = '/usr/share/php/global-stub.php';
		$relative = $a->relativizePath($outside);
		$this->assertSame($outside, $relative);
		$this->assertSame($outside, $b->absolutizePath($relative));
	}

	public function testErrorsRebaseKeysAndObjects(): void
	{
		$a = new ResultCachePathTransformer(self::ANCHOR_A);
		$b = new ResultCachePathTransformer(self::ANCHOR_B);

		$errorsByFile = [
			'/home/ci/build-123/src/Service.php' => [
				new Error('oops', '/home/ci/build-123/src/Service.php', 10),
			],
		];

		$rebased = $b->absolutizeErrors($a->relativizeErrors($errorsByFile));

		$this->assertSame(['/srv/runner/x9/src/Service.php'], array_keys($rebased));
		$error = $rebased['/srv/runner/x9/src/Service.php'][0];
		$this->assertSame('/srv/runner/x9/src/Service.php', $error->getFile());
		$this->assertSame('/srv/runner/x9/src/Service.php', $error->getFilePath());
		$this->assertSame('oops', $error->getMessage());
		$this->assertSame(10, $error->getLine());
	}

	public function testErrorInTraitRebasesAllThreePaths(): void
	{
		$a = new ResultCachePathTransformer(self::ANCHOR_A);
		$b = new ResultCachePathTransformer(self::ANCHOR_B);

		$error = new Error(
			'trait oops',
			'/home/ci/build-123/src/UsingClass.php',
			7,
			true,
			'/home/ci/build-123/src/UsingClass.php',
			'/home/ci/build-123/src/MyTrait.php',
		);

		$rebased = $b->absolutizeErrors($a->relativizeErrors(['/home/ci/build-123/src/UsingClass.php' => [$error]]));
		$rebasedError = $rebased['/srv/runner/x9/src/UsingClass.php'][0];

		$this->assertSame('/srv/runner/x9/src/UsingClass.php', $rebasedError->getFile());
		$this->assertSame('/srv/runner/x9/src/UsingClass.php', $rebasedError->getFilePath());
		$this->assertSame('/srv/runner/x9/src/MyTrait.php', $rebasedError->getTraitFilePath());
	}

	public function testDependenciesRebaseKeysAndValueLists(): void
	{
		$a = new ResultCachePathTransformer(self::ANCHOR_A);
		$b = new ResultCachePathTransformer(self::ANCHOR_B);

		$dependencies = [
			'/home/ci/build-123/src/A.php' => [
				'fileHash' => 'abc',
				'dependentFiles' => ['/home/ci/build-123/src/B.php', '/home/ci/build-123/src/C.php'],
				'usedTraitDependentFiles' => ['/home/ci/build-123/src/T.php'],
			],
		];

		$rebased = $b->absolutizeDependencies($a->relativizeDependencies($dependencies));

		$this->assertSame(['/srv/runner/x9/src/A.php'], array_keys($rebased));
		$entry = $rebased['/srv/runner/x9/src/A.php'];
		$this->assertSame('abc', $entry['fileHash']);
		$this->assertSame(
			['/srv/runner/x9/src/B.php', '/srv/runner/x9/src/C.php'],
			$entry['dependentFiles'],
		);
		$this->assertArrayHasKey('usedTraitDependentFiles', $entry);
		$this->assertSame(['/srv/runner/x9/src/T.php'], $entry['usedTraitDependentFiles']);
	}

	public function testCompoundTraitContextKeyRebasesOnlyThePath(): void
	{
		$a = new ResultCachePathTransformer(self::ANCHOR_A);
		$b = new ResultCachePathTransformer(self::ANCHOR_B);

		$linesToIgnore = [
			'/home/ci/build-123/src/UsingClass.php' => [
				'/home/ci/build-123/src/MyTrait.php (in context of class App\\UsingClass)' => [12 => 'foo.bar'],
			],
		];

		$rebased = $b->absolutizeCompoundKeyed($a->relativizeCompoundKeyed($linesToIgnore));

		$this->assertSame(['/srv/runner/x9/src/UsingClass.php'], array_keys($rebased));
		$this->assertSame(
			['/srv/runner/x9/src/MyTrait.php (in context of class App\\UsingClass)'],
			array_keys($rebased['/srv/runner/x9/src/UsingClass.php']),
		);
	}

	public function testMetaRebasesPathBearingKeys(): void
	{
		$a = new ResultCachePathTransformer(self::ANCHOR_A);
		$b = new ResultCachePathTransformer(self::ANCHOR_B);

		$meta = [
			'cacheVersion' => 'v14-relativePaths',
			'analysedPaths' => ['/home/ci/build-123/src'],
			'scannedFiles' => ['/home/ci/build-123/stubs/x.stub' => 'h1'],
			'composerInstalled' => [
				'/home/ci/build-123/vendor/composer/installed.php' => [
					'versions' => [
						'acme/lib' => ['install_path' => '/home/ci/build-123/vendor/acme/lib'],
					],
				],
			],
			'level' => '9',
		];

		$rebased = $b->absolutizeMeta($a->relativizeMeta($meta));

		$this->assertSame(['/srv/runner/x9/src'], $rebased['analysedPaths']);
		$this->assertSame(['/srv/runner/x9/stubs/x.stub' => 'h1'], $rebased['scannedFiles']);
		$this->assertSame(
			'/srv/runner/x9/vendor/acme/lib',
			$rebased['composerInstalled']['/srv/runner/x9/vendor/composer/installed.php']['versions']['acme/lib']['install_path'],
		);
		// non-path keys are untouched
		$this->assertSame('v14-relativePaths', $rebased['cacheVersion']);
		$this->assertSame('9', $rebased['level']);
	}

	public function testProjectConfigRebasesPathsAndTmpDirButNotPlaceholders(): void
	{
		$a = new ResultCachePathTransformer(self::ANCHOR_A);
		$b = new ResultCachePathTransformer(self::ANCHOR_B);

		$projectConfig = [
			'parameters' => [
				'level' => 9,
				'paths' => ['/home/ci/build-123/src'],
				'tmpDir' => '/home/ci/build-123/tmp',
				'editorUrl' => '%relFile%',
			],
		];

		$rebased = $b->absolutizeProjectConfig($a->relativizeProjectConfig($projectConfig));

		$this->assertSame(['/srv/runner/x9/src'], $rebased['parameters']['paths']);
		$this->assertSame('/srv/runner/x9/tmp', $rebased['parameters']['tmpDir']);
		// a placeholder value is not a path and must not be rewritten
		$this->assertSame('%relFile%', $rebased['parameters']['editorUrl']);
		$this->assertSame(9, $rebased['parameters']['level']);
	}

}
