<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Generator;
use Nette\Neon\Neon;
use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorsConsoleStyle;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\ErrorFormatterTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use function fopen;
use function mt_srand;
use function rewind;
use function shuffle;
use function sprintf;
use function str_repeat;
use function stream_get_contents;
use function substr;
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
	 * @param mixed[] $expected
	 */
	public function testFormatErrors(
		string $message,
		int $exitCode,
		int $numFileErrors,
		int $numGenericErrors,
		array $expected,
	): void
	{
		$formatter = new BaselineNeonErrorFormatter(new SimpleRelativePathHelper(self::DIRECTORY_PATH));

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(),
			'',
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
			[],
			false,
			null,
			true,
			0,
			false,
			[],
		);
		$formatter->formatErrors(
			$result,
			$this->getOutput(),
			'',
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
				], Neon::BLOCK),
			),
			trim($this->getOutputContent()),
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
			[],
			false,
			null,
			true,
			0,
			false,
			[],
		);

		$formatter->formatErrors(
			$result,
			$this->getOutput(),
			'',
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
				], Neon::BLOCK),
			),
			trim($this->getOutputContent()),
		);
	}

	/**
	 * @return Generator<int, array{list<Error>}, void, void>
	 */
	public function outputOrderingProvider(): Generator
	{
		$errors = [
			new Error('Error #2', 'TestfileA', 1),
			new Error('A different error #1', 'TestfileA', 3),
			new Error('Second error in a different file', 'TestfileB', 4),
			new Error('Error #1 in a different file', 'TestfileB', 5),
			new Error('Second error in a different file', 'TestfileB', 6),
			new Error('Error with Windows directory separators', 'TestFiles\\TestA', 1),
			new Error('Error with Unix directory separators', 'TestFiles/TestA', 1),
			new Error('Error without directory separators', 'TestFilesFoo', 1),
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
			[],
			false,
			null,
			true,
			0,
			false,
			[],
		);

		$formatter->formatErrors(
			$result,
			$this->getOutput(),
			'',
		);
		self::assertSame(
			trim(Neon::encode([
				'parameters' => [
					'ignoreErrors' => [
						[
							'message' => '#^Error with Unix directory separators$#',
							'count' => 1,
							'path' => 'TestFiles/TestA',
						],
						[
							'message' => '#^Error with Windows directory separators$#',
							'count' => 1,
							'path' => 'TestFiles/TestA',
						],
						[
							'message' => '#^Error without directory separators$#',
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
			$f = trim($this->getOutputContent()),
		);
	}

	/**
	 * @return Generator<string, array{errors: list<Error>}>
	 */
	public function endOfFileNewlinesProvider(): Generator
	{
		$existingBaselineContentWithoutEndNewlines = 'parameters:
	ignoreErrors:
		-
			message: "#^Existing error$#"
			count: 1
			path: TestfileA';

		yield 'one error' => [
			'errors' => [
				new Error('Error #1', 'TestfileA', 1),
			],
			'existingBaselineContent' => $existingBaselineContentWithoutEndNewlines . "\n",
			'expectedNewlinesCount' => 1,
		];

		yield 'no errors' => [
			'errors' => [],
			'existingBaselineContent' => $existingBaselineContentWithoutEndNewlines . "\n",
			'expectedNewlinesCount' => 1,
		];

		yield 'one error with 2 newlines' => [
			'errors' => [
				new Error('Error #1', 'TestfileA', 1),
			],
			'existingBaselineContent' => $existingBaselineContentWithoutEndNewlines . "\n\n",
			'expectedNewlinesCount' => 2,
		];

		yield 'no errors with 2 newlines' => [
			'errors' => [],
			'existingBaselineContent' => $existingBaselineContentWithoutEndNewlines . "\n\n",
			'expectedNewlinesCount' => 2,
		];

		yield 'one error with 0 newlines' => [
			'errors' => [
				new Error('Error #1', 'TestfileA', 1),
			],
			'existingBaselineContent' => $existingBaselineContentWithoutEndNewlines,
			'expectedNewlinesCount' => 0,
		];

		yield 'one error with 3 newlines' => [
			'errors' => [
				new Error('Error #1', 'TestfileA', 1),
			],
			'existingBaselineContent' => $existingBaselineContentWithoutEndNewlines . "\n\n\n",
			'expectedNewlinesCount' => 3,
		];

		yield 'empty existing baseline' => [
			'errors' => [
				new Error('Error #1', 'TestfileA', 1),
			],
			'existingBaselineContent' => '',
			'expectedNewlinesCount' => 1,
		];

		yield 'empty existing baseline, no new errors' => [
			'errors' => [],
			'existingBaselineContent' => '',
			'expectedNewlinesCount' => 1,
		];

		yield 'empty existing baseline with a newline, no new errors' => [
			'errors' => [],
			'existingBaselineContent' => "\n",
			'expectedNewlinesCount' => 1,
		];

		yield 'empty existing baseline with 2 newlines, no new errors' => [
			'errors' => [],
			'existingBaselineContent' => "\n\n",
			'expectedNewlinesCount' => 2,
		];
	}

	/**
	 * @dataProvider endOfFileNewlinesProvider
	 *
	 * @param list<Error> $errors
	 */
	public function testEndOfFileNewlines(
		array $errors,
		string $existingBaselineContent,
		int $expectedNewlinesCount,
	): void
	{
		$formatter = new BaselineNeonErrorFormatter(new SimpleRelativePathHelper(self::DIRECTORY_PATH));
		$result = new AnalysisResult(
			$errors,
			[],
			[],
			[],
			[],
			false,
			null,
			true,
			0,
			false,
			[],
		);

		$resource = fopen('php://memory', 'w', false);
		if ($resource === false) {
			throw new ShouldNotHappenException();
		}
		$outputStream = new StreamOutput($resource, StreamOutput::VERBOSITY_NORMAL, false);

		$errorConsoleStyle = new ErrorsConsoleStyle(new StringInput(''), $outputStream);
		$output = new SymfonyOutput($outputStream, new SymfonyStyle($errorConsoleStyle));

		$formatter->formatErrors(
			$result,
			$output,
			$existingBaselineContent,
		);

		rewind($outputStream->getStream());

		$content = stream_get_contents($outputStream->getStream());
		if ($content === false) {
			throw new ShouldNotHappenException();
		}

		if ($expectedNewlinesCount > 0) {
			Assert::assertSame(str_repeat("\n", $expectedNewlinesCount), substr($content, -$expectedNewlinesCount));
		}
		Assert::assertNotSame("\n", substr($content, -($expectedNewlinesCount + 1), 1));
	}

}
