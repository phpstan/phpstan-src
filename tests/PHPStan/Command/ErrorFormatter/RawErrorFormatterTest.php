<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Override;
use PHPStan\Internal\AgentDetector;
use PHPStan\Testing\ErrorFormatterTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function getenv;
use function putenv;
use function sprintf;

class RawErrorFormatterTest extends ErrorFormatterTestCase
{

	/** @var array<string, string|false> */
	private array $originalEnvVars = [];

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();
		foreach (AgentDetector::ENV_VARS as $var) {
			$this->originalEnvVars[$var] = getenv($var);
			putenv($var);
		}
	}

	#[Override]
	protected function tearDown(): void
	{
		foreach (AgentDetector::ENV_VARS as $var) {
			putenv($var . ($this->originalEnvVars[$var] !== false ? '=' . $this->originalEnvVars[$var] : ''));
		}
	}

	public static function dataFormatterOutputProvider(): iterable
	{
		yield [
			'message' => 'No errors',
			'exitCode' => 0,
			'numFileErrors' => 0,
			'numGenericErrors' => 0,
			'verbose' => false,
			'expected' => '',
		];

		yield [
			'message' => 'One file error',
			'exitCode' => 1,
			'numFileErrors' => 1,
			'numGenericErrors' => 0,
			'verbose' => false,
			'expected' => '/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4:Foo' . "\n",
		];

		yield [
			'message' => 'One generic error',
			'exitCode' => 1,
			'numFileErrors' => 0,
			'numGenericErrors' => 1,
			'verbose' => false,
			'expected' => '?:?:first generic error' . "\n",
		];

		yield [
			'message' => 'Multiple file errors',
			'exitCode' => 1,
			'numFileErrors' => 4,
			'numGenericErrors' => 0,
			'verbose' => false,
			'expected' => '/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:2:Bar
Bar2
/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4:Foo
/data/folder/with space/and unicode 😃/project/foo.php:1:Foo<Bar>
/data/folder/with space/and unicode 😃/project/foo.php:5:Bar
Bar2
',
		];

		yield [
			'message' => 'Multiple generic errors',
			'exitCode' => 1,
			'numFileErrors' => 0,
			'numGenericErrors' => 2,
			'verbose' => false,
			'expected' => '?:?:first generic error
?:?:second generic<error>
',
		];

		yield [
			'message' => 'Multiple file, multiple generic errors',
			'exitCode' => 1,
			'numFileErrors' => 4,
			'numGenericErrors' => 2,
			'verbose' => false,
			'expected' => '?:?:first generic error
?:?:second generic<error>
/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:2:Bar
Bar2
/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4:Foo
/data/folder/with space/and unicode 😃/project/foo.php:1:Foo<Bar>
/data/folder/with space/and unicode 😃/project/foo.php:5:Bar
Bar2
',
		];

		yield [
			'message' => 'One file error with tip',
			'exitCode' => 1,
			'numFileErrors' => [5, 1],
			'numGenericErrors' => 0,
			'verbose' => false,
			'expected' => '/data/folder/with space/and unicode 😃/project/foo.php:5:Foobar\Buz
',
		];

		yield [
			'message' => 'One file error with tip and verbose',
			'exitCode' => 1,
			'numFileErrors' => [5, 1],
			'numGenericErrors' => 0,
			'verbose' => true,
			'expected' => '/data/folder/with space/and unicode 😃/project/foo.php:5:Foobar\Buz [identifier=foobar.buz]
',
		];
	}

	/**
	 * @param array{int, int}|int $numFileErrors
	 */
	#[DataProvider('dataFormatterOutputProvider')]
	public function testFormatErrors(
		string $message,
		int $exitCode,
		array|int $numFileErrors,
		int $numGenericErrors,
		bool $verbose,
		string $expected,
	): void
	{
		$formatter = new RawErrorFormatter();

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(false, $verbose),
		), sprintf('%s: response code do not match', $message));

		$this->assertSame($expected, $this->getOutputContent(false, $verbose), sprintf('%s: output do not match', $message));
	}

	public function testFormatErrorsInAgent(): void
	{
		putenv('AI_AGENT=1');

		$formatter = new RawErrorFormatter();

		$this->assertSame(1, $formatter->formatErrors(
			$this->getAnalysisResult([5, 1], 0),
			$this->getOutput(false, false),
		));

		$this->assertSame(
			'/data/folder/with space/and unicode 😃/project/foo.php:5:Foobar\Buz [identifier=foobar.buz]' . "\n",
			$this->getOutputContent(false, false),
		);
	}

}
