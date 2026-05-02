<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;
use function realpath;

final class FixIgnorePolicyFactoryTest extends PHPStanTestCase
{

	public function testIdentifierOnlyEntryWitnessesAcrossFiles(): void
	{
		$factory = $this->buildFactory([
			['identifier' => 'rule.id'],
		]);

		$policy = $factory->buildForFiles(
			[
				'F.php' => [$this->error('F.php', 'rule.id')],
				'G.php' => [$this->error('G.php', 'rule.id')],
			],
			[],
		);

		self::assertTrue($policy->shouldDrop($this->error('F.php', 'rule.id')));
		self::assertTrue($policy->shouldDrop($this->error('G.php', 'rule.id')));
	}

	public function testPathOnlyEntryWitnessesEveryIdentifierInFile(): void
	{
		$factory = $this->buildFactory([
			['message' => '#.*#', 'path' => __DIR__ . '/data/policy-factory/F.php'],
		]);

		$file = realpath(__DIR__ . '/data/policy-factory/F.php');
		self::assertNotFalse($file);

		$policy = $factory->buildForFiles(
			[$file => [$this->error($file, 'rule.id'), $this->error($file, 'rule.other')]],
			[],
		);

		self::assertTrue($policy->shouldDrop($this->error($file, 'rule.id')));
		self::assertTrue($policy->shouldDrop($this->error($file, 'rule.other')));
	}

	public function testIdentifierAndPathEntryRequiresBoth(): void
	{
		$file = realpath(__DIR__ . '/data/policy-factory/F.php');
		self::assertNotFalse($file);
		$factory = $this->buildFactory([
			['identifier' => 'rule.id', 'path' => $file],
		]);

		$policy = $factory->buildForFiles(
			[
				$file => [$this->error($file, 'rule.id')],
				'G.php' => [$this->error('G.php', 'rule.id')],
			],
			[],
		);

		self::assertTrue($policy->shouldDrop($this->error($file, 'rule.id')));
		self::assertFalse($policy->shouldDrop($this->error('G.php', 'rule.id')));
	}

	public function testMessageRegexEntryWitnessesByIdentifierOfMatchingErrors(): void
	{
		$factory = $this->buildFactory([
			['message' => '#deprecated#'],
		]);

		$policy = $factory->buildForFiles(
			[
				'F.php' => [
					$this->errorWithMessage('F.php', 'rule.id', 'this is deprecated'),
					$this->errorWithMessage('F.php', 'rule.other', 'fine'),
				],
			],
			[],
		);

		self::assertTrue($policy->shouldDrop($this->errorWithMessage('F.php', 'rule.id', 'this is deprecated')));
		self::assertFalse($policy->shouldDrop($this->errorWithMessage('F.php', 'rule.other', 'fine')));
	}

	public function testCountFieldIsIgnoredByPolicyFactory(): void
	{
		$factory = $this->buildFactory([
			['identifier' => 'rule.id', 'count' => 1],
		]);

		$policy = $factory->buildForFiles(
			[
				'F.php' => [
					$this->error('F.php', 'rule.id'),
					$this->error('F.php', 'rule.id'),
					$this->error('F.php', 'rule.id'),
				],
			],
			[],
		);

		self::assertTrue($policy->shouldDrop($this->error('F.php', 'rule.id')));
	}

	public function testEntryWithoutMatchingErrorsProducesNoWitness(): void
	{
		$factory = $this->buildFactory([
			['identifier' => 'rule.unrelated'],
		]);

		$policy = $factory->buildForFiles(
			['F.php' => [$this->error('F.php', 'rule.id')]],
			[],
		);

		self::assertFalse($policy->shouldDrop($this->error('F.php', 'rule.id')));
	}

	public function testLinesToIgnoreFlowsThroughToPolicy(): void
	{
		$factory = $this->buildFactory([]);

		$linesToIgnore = ['F.php' => [10 => [['name' => 'rule.id', 'comment' => null]]]];
		$policy = $factory->buildForFiles([], $linesToIgnore);

		$error = new Error('msg', 'F.php', 10, true, 'F.php', null, null, 10, null, 'rule.id');
		self::assertTrue($policy->shouldDrop($error));
	}

	/**
	 * @param (string|mixed[])[] $ignoreErrors
	 */
	private function buildFactory(array $ignoreErrors): FixIgnorePolicyFactory
	{
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$helper = new IgnoredErrorHelper($fileHelper, $ignoreErrors, false);
		return new FixIgnorePolicyFactory($helper, $fileHelper);
	}

	private function error(string $filePath, ?string $identifier): Error
	{
		return new Error('msg', $filePath, 10, true, $filePath, null, null, 10, null, $identifier);
	}

	private function errorWithMessage(string $filePath, ?string $identifier, string $message): Error
	{
		return new Error($message, $filePath, 10, true, $filePath, null, null, 10, null, $identifier);
	}

}
