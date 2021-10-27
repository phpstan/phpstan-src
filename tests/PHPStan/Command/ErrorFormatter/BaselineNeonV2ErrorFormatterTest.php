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

class BaselineNeonV2ErrorFormatterTest extends ErrorFormatterTestCase
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
					'rawMessage' => 'Foo',
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
					'rawMessage' => "Bar\nBar2",
					'count' => 1,
					'path' => 'folder with unicode 😃/file name with "spaces" and unicode 😃.php',
				],
				[
					'rawMessage' => 'Foo',
					'count' => 1,
					'path' => 'folder with unicode 😃/file name with "spaces" and unicode 😃.php',
				],
				[
					'rawMessage' => "Bar\nBar2",
					'count' => 1,
					'path' => 'foo.php',
				],
				[
					'rawMessage' => 'Foo',
					'count' => 1,
					'path' => 'foo.php',
				],
			],
		];
	}

	/**
	 * @dataProvider dataFormatterOutputProvider
	 *
	 * @param string $rawMessage
	 * @param int    $exitCode
	 * @param int    $numFileErrors
	 * @param int    $numGenericErrors
	 * @param mixed[] $expected
	 */
	public function testFormatErrors(
		string $rawMessage,
		int $exitCode,
		int $numFileErrors,
		int $numGenericErrors,
		array $expected
	): void
	{
		$formatter = new BaselineNeonV2ErrorFormatter(new SimpleRelativePathHelper(self::DIRECTORY_PATH));

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput()
		), sprintf('%s: response code do not match', $rawMessage));

		$this->assertSame(trim(Neon::encode(['parameters' => ['ignoreErrors' => $expected]], Neon::BLOCK)), trim($this->getOutputContent()), sprintf('%s: output do not match', $rawMessage));
	}

}
