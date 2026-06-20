<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Console;

use PHPStan\IssueBot\Playground\PlaygroundError;
use PHPUnit\Framework\TestCase;
use function serialize;
use function unserialize;

class RunCommandTest extends TestCase
{

	public function testExtractFileErrors(): void
	{
		$json = [
			'totals' => ['errors' => 0, 'file_errors' => 2],
			'files' => [
				'/tmp/abc.php' => [
					'errors' => 2,
					'messages' => [
						['message' => 'Dumped type: int', 'line' => 5, 'identifier' => 'phpstan.dumpType'],
						['message' => 'Something else', 'line' => 7, 'identifier' => 'foo.bar'],
					],
				],
			],
			'errors' => [],
		];

		$errors = RunCommand::extractErrors($json, 'abc');

		self::assertCount(2, $errors);
		self::assertSame(5, $errors[0]->getLine());
		self::assertSame('Dumped type: int', $errors[0]->getMessage());
		self::assertSame('phpstan.dumpType', $errors[0]->getIdentifier());
		self::assertSame(7, $errors[1]->getLine());
	}

	public function testExtractTopLevelErrorsWhenNoFileErrors(): void
	{
		// PHPStan reports a crashing parallel worker (e.g. out of memory) as a
		// top-level error with file_errors === 0 and an empty files array. Without
		// recording it, the sample would look like it produced zero errors.
		$json = [
			'totals' => ['errors' => 1, 'file_errors' => 0],
			'files' => [],
			'errors' => [
				'Child process error (exit code 255): PHP Fatal error: Allowed memory size exhausted',
			],
		];

		$errors = RunCommand::extractErrors($json, 'abc');

		self::assertCount(1, $errors);
		self::assertSame(-1, $errors[0]->getLine());
		self::assertStringContainsString('Child process error', $errors[0]->getMessage());
		self::assertNull($errors[0]->getIdentifier());
	}

	public function testExtractBothFileAndTopLevelErrors(): void
	{
		$json = [
			'totals' => ['errors' => 2, 'file_errors' => 1],
			'files' => [
				'/tmp/abc.php' => [
					'errors' => 1,
					'messages' => [
						['message' => 'Dumped type: string', 'line' => 3, 'identifier' => 'phpstan.dumpType'],
					],
				],
			],
			'errors' => [
				'Child process error (exit code 255): out of memory',
			],
		];

		$errors = RunCommand::extractErrors($json, 'abc');

		self::assertCount(2, $errors);
		self::assertSame('Dumped type: string', $errors[0]->getMessage());
		self::assertSame(-1, $errors[1]->getLine());
		self::assertStringContainsString('Child process error', $errors[1]->getMessage());
	}

	public function testTopLevelErrorHashPathIsReplaced(): void
	{
		$json = [
			'files' => [],
			'errors' => [
				'Child process error while analysing /some/dir/abc.php in worker',
			],
		];

		$errors = RunCommand::extractErrors($json, 'abc');

		self::assertCount(1, $errors);
		self::assertStringContainsString('/tmp.php', $errors[0]->getMessage());
		self::assertStringNotContainsString('/abc.php', $errors[0]->getMessage());
	}

	public function testResultsFileShapeIsUnchanged(): void
	{
		// The results file written by RunCommand is serialize(['phpVersion' => int,
		// 'errors' => array<hash, list<PlaygroundError>>]). Recording top-level
		// errors only adds entries to the per-hash list; the structure is identical.
		$json = [
			'files' => [],
			'errors' => ['Child process error (exit code 255): out of memory'],
		];

		$allErrors = ['abc' => RunCommand::extractErrors($json, 'abc')];
		$data = ['phpVersion' => 80100, 'errors' => $allErrors];

		/** @var array{phpVersion: int, errors: array<string, list<PlaygroundError>>} $roundTripped */
		$roundTripped = unserialize(serialize($data));

		self::assertSame(80100, $roundTripped['phpVersion']);
		self::assertArrayHasKey('abc', $roundTripped['errors']);
		self::assertContainsOnlyInstancesOf(PlaygroundError::class, $roundTripped['errors']['abc']);
		self::assertCount(1, $roundTripped['errors']['abc']);
		self::assertStringContainsString('Child process error', $roundTripped['errors']['abc'][0]->getMessage());
	}

}
