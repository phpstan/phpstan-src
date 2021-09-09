<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\Neon\Neon;
use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use function mt_srand;
use function shuffle;
use function trim;

class BaselineNeonErrorFormatterTest extends ErrorFormatterTestCase
{

	public function dataFormatterOutputProvider(): iterable
	{
		yield [
			'No errors',
			0,
			0,
			0,
			[],
		];

		yield [
			'One file error',
			1,
			1,
			0,
			[
				[
					'message' => '#^Foo$#',
					'count' => 1,
					'path' => 'folder with unicode 😃/file name with "spaces" and unicode 😃.php',
				],
			],
		];

		yield [
			'Multiple file errors',
			1,
			4,
			0,
			[
				[
					'message' => "#^Bar\nBar2$#",
					'count' => 1,
					'path' => 'folder with unicode 😃/file name with "spaces" and unicode 😃.php',
				],
				[
					'message' => '#^Foo$#',
					'count' => 1,
					'path' => 'folder with unicode 😃/file name with "spaces" and unicode 😃.php',
				],
				[
					'message' => "#^Bar\nBar2$#",
					'count' => 1,
					'path' => 'foo.php',
				],
				[
					'message' => '#^Foo$#',
					'count' => 1,
					'path' => 'foo.php',
				],
			],
		];

		yield [
			'Multiple file, multiple generic errors',
			1,
			4,
			2,
			[
				[
					'message' => "#^Bar\nBar2$#",
					'count' => 1,
					'path' => 'folder with unicode 😃/file name with "spaces" and unicode 😃.php',
				],
				[
					'message' => '#^Foo$#',
					'count' => 1,
					'path' => 'folder with unicode 😃/file name with "spaces" and unicode 😃.php',
				],
				[
					'message' => "#^Bar\nBar2$#",
					'count' => 1,
					'path' => 'foo.php',
				],
				[
					'message' => '#^Foo$#',
					'count' => 1,
					'path' => 'foo.php',
				],
			],
		];
	}

	/**
	 * @dataProvider dataFormatterOutputProvider
	 *
	 * @param string $message
	 * @param int    $exitCode
	 * @param int    $numFileErrors
	 * @param int    $numGenericErrors
	 * @param mixed[] $expected
	 */
	public function testFormatErrors(
		string $message,
		int $exitCode,
		int $numFileErrors,
		int $numGenericErrors,
		array $expected
	): void
	{
		$formatter = new BaselineNeonErrorFormatter(new SimpleRelativePathHelper(self::DIRECTORY_PATH));

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput()
		), sprintf('%s: response code do not match', $message));

		$this->assertSame(trim(Neon::encode(['parameters' => ['ignoreErrors' => $expected]], Neon::BLOCK)), trim($this->getOutputContent()), sprintf('%s: output do not match', $message));
	}


	public function testFormatErrorMessagesRegexEscape(): void
	{
		$formatter = new BaselineNeonErrorFormatter(new SimpleRelativePathHelper(self::DIRECTORY_PATH));

		$result = new AnalysisResult(
			[new Error('Escape Regex with file # ~ \' ()', 'Testfile')],
			['Escape Regex without file # ~ <> \' ()'],
			[],
			[],
			false,
			null,
			true
		);
		$formatter->formatErrors(
			$result,
			$this->getOutput()
		);

		self::assertSame(
			trim(
				Neon::encode([
					'parameters' => [
						'ignoreErrors' => [
							[
								'message' => "#^Escape Regex with file \\# ~ ' \\(\\)$#",
								'count' => 1,
								'path' => 'Testfile',
							],
						],
					],
				], Neon::BLOCK)
			),
			trim($this->getOutputContent())
		);
	}

	public function testEscapeDiNeon(): void
	{
		$formatter = new BaselineNeonErrorFormatter(new SimpleRelativePathHelper(self::DIRECTORY_PATH));
		$result = new AnalysisResult(
			[new Error('Test %value%', 'Testfile')],
			[],
			[],
			[],
			false,
			null,
			true
		);

		$formatter->formatErrors(
			$result,
			$this->getOutput()
		);
		self::assertSame(
			trim(
				Neon::encode([
					'parameters' => [
						'ignoreErrors' => [
							[
								'message' => '#^Test %%value%%$#',
								'count' => 1,
								'path' => 'Testfile',
							],
						],
					],
				], Neon::BLOCK)
			),
			trim($this->getOutputContent())
		);
	}

	/**
	 * @return \Generator<int, array{list<Error>}, void, void>
	 */
	public function outputOrderingProvider(): \Generator
	{
		$errors = [
			new Error('Error #2', 'TestfileA', 1),
			new Error('A different error #1', 'TestfileA', 3),
			new Error('Second error in a different file', 'TestfileB', 4),
			new Error('Error #1 in a different file', 'TestfileB', 5),
			new Error('Second error in a different file', 'TestfileB', 6),
			new Error('Error with Windows path separators', 'TestFiles\\TestA', 1),
			new Error('Error with Unix path separators', 'TestFiles/TestA', 1),
			new Error('Error without path separators', 'TestFilesFoo', 1),
		];
		yield [$errors];
		mt_srand(0);
		for ($i = 0; $i < 3; ++$i) {
			shuffle($errors);
			yield [$errors];
		}
	}

	/**
	 * @dataProvider outputOrderingProvider
	 * @param list<Error> $errors
	 */
	public function testOutputOrdering(array $errors): void
	{
		$formatter = new BaselineNeonErrorFormatter(new SimpleRelativePathHelper(self::DIRECTORY_PATH));
		$result = new AnalysisResult(
			$errors,
			[],
			[],
			[],
			false,
			null,
			true
		);

		$formatter->formatErrors(
			$result,
			$this->getOutput()
		);
		self::assertSame(
			trim(Neon::encode([
				'parameters' => [
					'ignoreErrors' => [
						[
							'message' => '#^Error with Unix path separators$#',
							'count' => 1,
							'path' => 'TestFiles/TestA',
						],
						[
							'message' => '#^Error with Windows path separators$#',
							'count' => 1,
							'path' => 'TestFiles/TestA',
						],
						[
							'message' => '#^Error without path separators$#',
							'count' => 1,
							'path' => 'TestFilesFoo',
						],
						[
							'message' => '#^A different error \\#1$#',
							'count' => 1,
							'path' => 'TestfileA',
						],
						[
							'message' => '#^Error \\#2$#',
							'count' => 1,
							'path' => 'TestfileA',
						],
						[
							'message' => '#^Error \\#1 in a different file$#',
							'count' => 1,
							'path' => 'TestfileB',
						],
						[
							'message' => '#^Second error in a different file$#',
							'count' => 2,
							'path' => 'TestfileB',
						],
					],
				],
			], Neon::BLOCK)),
			$f = trim($this->getOutputContent())
		);
	}

}
