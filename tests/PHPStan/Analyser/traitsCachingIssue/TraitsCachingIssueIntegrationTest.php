<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\Testing\PHPStanTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function file_exists;

/**
 * @group exec
 */
class TraitsCachingIssueIntegrationTest extends PHPStanTestCase
{

	/** @var string|null */
	private $originalTraitOneContents;

	/** @var string|null */
	private $originalTraitTwoContents;

	public function tearDown(): void
	{
		parent::tearDown();

		$this->deleteCache();

		if ($this->originalTraitOneContents !== null) {
			$this->revertTrait(__DIR__ . '/data/TraitOne.php', $this->originalTraitOneContents);
		}

		if ($this->originalTraitTwoContents !== null) {
			$this->revertTrait(__DIR__ . '/data/TraitTwo.php', $this->originalTraitTwoContents);
		}
	}

	public function dataCachingIssue(): array
	{
		return [
			[
				false,
				false,
				[],
			],
			[
				false,
				true,
				[
					'Method class@anonymous/TestClassUsingTrait.php:20::doBar() should return stdClass but returns Exception.',
				],
			],
			[
				true,
				false,
				[
					'Method TraitsCachingIssue\TestClassUsingTrait::doBar() should return stdClass but returns Exception.',
				],
			],
			[
				true,
				true,
				[
					'Method TraitsCachingIssue\TestClassUsingTrait::doBar() should return stdClass but returns Exception.',
					'Method class@anonymous/TestClassUsingTrait.php:20::doBar() should return stdClass but returns Exception.',
				],
			],
		];
	}

	/**
	 * @dataProvider dataCachingIssue
	 * @param bool $changeOne
	 * @param bool $changeTwo
	 * @param string[] $expectedErrors
	 */
	public function testCachingIssue(
		bool $changeOne,
		bool $changeTwo,
		array $expectedErrors
	): void
	{
		$this->deleteCache();
		[$statusCode, $errors] = $this->runPhpStan();
		$this->assertSame([], $errors);
		$this->assertSame(0, $statusCode);

		if ($changeOne) {
			$this->originalTraitOneContents = $this->changeTrait(__DIR__ . '/data/TraitOne.php');
		}
		if ($changeTwo) {
			$this->originalTraitTwoContents = $this->changeTrait(__DIR__ . '/data/TraitTwo.php');
		}

		$fileHelper = new FileHelper(__DIR__);

		$errorPath = $fileHelper->normalizePath(__DIR__ . '/data/TestClassUsingTrait.php');
		[$statusCode, $errors] = $this->runPhpStan();

		if (count($expectedErrors) === 0) {
			$this->assertSame(0, $statusCode);
			$this->assertArrayNotHasKey($errorPath, $errors);
			return;
		}

		$this->assertSame(1, $statusCode);
		$this->assertArrayHasKey($errorPath, $errors);
		$this->assertSame(count($expectedErrors), $errors[$errorPath]['errors']);

		foreach ($errors[$errorPath]['messages'] as $i => $error) {
			$this->assertSame($expectedErrors[$i], $error['message']);
		}
	}

	/**
	 * @return array{int, mixed[]}
	 */
	private function runPhpStan(): array
	{
		$phpstanBinPath = __DIR__ . '/../../../../bin/phpstan';
		exec(sprintf('%s %s clear-result-cache --configuration %s', escapeshellarg(PHP_BINARY), $phpstanBinPath, escapeshellarg(__DIR__ . '/phpstan.neon')), $clearResultCacheOutputLines, $clearResultCacheExitCode);
		if ($clearResultCacheExitCode !== 0) {
			throw new \PHPStan\ShouldNotHappenException('Could not clear result cache.');
		}

		exec(
			sprintf(
				'%s %s analyse --no-progress --level 8 --configuration %s --error-format json %s',
				escapeshellarg(PHP_BINARY),
				$phpstanBinPath,
				escapeshellarg(__DIR__ . '/phpstan.neon'),
				escapeshellarg(__DIR__ . '/data')
			),
			$output,
			$statusCode
		);
		$stringOutput = implode("\n", $output);
		$json = \Nette\Utils\Json::decode($stringOutput, \Nette\Utils\Json::FORCE_ARRAY);

		return [$statusCode, $json['files']];
	}

	private function deleteCache(): void
	{
		$dir = __DIR__ . '/tmp/cache';
		if (!file_exists($dir)) {
			return;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(__DIR__ . '/tmp/cache', RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $fileinfo) {
			if ($fileinfo->isDir()) {
				rmdir($fileinfo->getRealPath());
				continue;
			}

			unlink($fileinfo->getRealPath());
		}
	}

	private function changeTrait(string $traitPath): string
	{
		$originalTraitContents = FileReader::read($traitPath);
		$traitContents = str_replace('use stdClass as Foo;', 'use Exception as Foo;', $originalTraitContents);
		$result = file_put_contents($traitPath, $traitContents);
		if ($result === false) {
			$this->fail(sprintf('Could not save file %s', $traitPath));
		}

		return $originalTraitContents;
	}

	private function revertTrait(string $traitPath, string $originalTraitContents): void
	{
		$result = file_put_contents($traitPath, $originalTraitContents);
		if ($result === false) {
			$this->fail(sprintf('Could not save file %s', $traitPath));
		}
	}

}
