<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use PHPStan\Analyser\Error;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use PHPStan\Testing\PHPStanTestCase;

class BaselineIgnoredErrorsHelperTest extends PHPStanTestCase
{

	public function testEmptyBaseline(): void
	{
		$result = $this->runRemoveUnusedIgnoredErrors(
			[],
			[
				new Error(
					'Foo',
					__DIR__ . '/foo.php',
				),
			],
		);

		$this->assertCount(0, $result);
	}

	public function testRemoveUnusedIgnoreError(): void
	{
		$result = $this->runRemoveUnusedIgnoredErrors(
			[
				[
					'message' => '#^Foo#',
					'count' => 1,
					'path' => 'foo.php',
				],
			],
			[],
		);

		$this->assertCount(0, $result);
	}

	public function testeReduceErrorCount(): void
	{
		$result = $this->runRemoveUnusedIgnoredErrors(
			[
				[
					'message' => '#^Foo#',
					'count' => 2,
					'path' => 'foo.php',
				],
			],
			[
				new Error(
					'Foo',
					__DIR__ . '/foo.php',
				),
			],
		);

		$this->assertCount(1, $result);
		$this->assertSame('Foo', $result[0]->getMessage());
		$this->assertSame(__DIR__ . '/foo.php', $result[0]->getFilePath());
	}

	public function testNewError(): void
	{
		$result = $this->runRemoveUnusedIgnoredErrors(
			[
				[
					'message' => '#^Foo#',
					'count' => 1,
					'path' => 'foo.php',
				],
			],
			[
				new Error(
					'Bar',
					__DIR__ . '/bar.php',
				),
			],
		);

		$this->assertCount(0, $result);
	}

	public function testIncreaseErrorCount(): void
	{
		$result = $this->runRemoveUnusedIgnoredErrors(
			[
				[
					'message' => '#^Foo#',
					'count' => 1,
					'path' => 'foo.php',
				],
			],
			[
				new Error(
					'Foo',
					__DIR__ . '/foo.php',
				),
				new Error(
					'Foo',
					__DIR__ . '/foo.php',
				),
			],
		);

		$this->assertCount(1, $result);
		$this->assertSame('Foo', $result[0]->getMessage());
		$this->assertSame(__DIR__ . '/foo.php', $result[0]->getFilePath());
	}

	/**
	 * @param mixed[][] $baselinedErrors
	 * @param list<Error> $currentAnalysisErrors
	 * @return list<Error> errors
	 */
	private function runRemoveUnusedIgnoredErrors(array $baselinedErrors, array $currentAnalysisErrors): array
	{
		$baselineIgnoredErrorHelper = new BaselineIgnoredErrorHelper(self::getFileHelper());

		$parentDirHelper = new ParentDirectoryRelativePathHelper(__DIR__);

		return $baselineIgnoredErrorHelper->removeUnusedIgnoredErrors($baselinedErrors, $currentAnalysisErrors, $parentDirHelper);
	}

}
