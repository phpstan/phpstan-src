<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Testing\ErrorFormatterTestCase;

class RawErrorFormatterTest extends ErrorFormatterTestCase
{

	public function dataFormatterOutputProvider(): iterable
	{
		yield [
			'No errors',
			0,
			0,
			0,
			'',
		];

		yield [
			'One file error',
			1,
			1,
			0,
			'/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4:Foo' . "\n",
		];

		yield [
			'One generic error',
			1,
			0,
			1,
			'?:?:first generic error' . "\n",
		];

		yield [
			'Multiple file errors',
			1,
			4,
			0,
			'/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:2:Bar' . "\n" .
			'/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4:Foo' . "\n" .
			'/data/folder/with space/and unicode 😃/project/foo.php:1:Foo' . "\n" .
			'/data/folder/with space/and unicode 😃/project/foo.php:5:Bar' . "\n",
		];

		yield [
			'Multiple generic errors',
			1,
			0,
			2,
			'?:?:first generic error' . "\n" .
			'?:?:second generic error' . "\n",
		];

		yield [
			'Multiple file, multiple generic errors',
			1,
			4,
			2,
			'?:?:first generic error' . "\n" .
		'?:?:second generic error' . "\n" .
		'/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:2:Bar' . "\n" .
		'/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4:Foo' . "\n" .
		'/data/folder/with space/and unicode 😃/project/foo.php:1:Foo' . "\n" .
		'/data/folder/with space/and unicode 😃/project/foo.php:5:Bar' . "\n",
		];
	}

	/**
	 * @dataProvider dataFormatterOutputProvider
	 *
	 * @param string $message
	 * @param int    $exitCode
	 * @param int    $numFileErrors
	 * @param int    $numGenericErrors
	 * @param string $expected
	 */
	public function testFormatErrors(
		string $message,
		int $exitCode,
		int $numFileErrors,
		int $numGenericErrors,
		string $expected
	): void
	{
		$formatter = new RawErrorFormatter();

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput()
		), sprintf('%s: response code do not match', $message));

		$this->assertEquals($expected, $this->getOutputContent(), sprintf('%s: output do not match', $message));
	}

}
