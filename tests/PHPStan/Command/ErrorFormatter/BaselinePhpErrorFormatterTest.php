<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class BaselinePhpErrorFormatterTest extends ErrorFormatterTestCase
{

	public static function dataFormatErrors(): iterable
	{
		yield [
			[
				new Error(
					'Foo',
					__DIR__ . '/Foo.php',
					5,
				),
				new Error(
					'Foo',
					__DIR__ . '/Foo.php',
					5,
				),
				new Error(
					'Bar',
					__DIR__ . '/../Foo.php',
					5,
				),
			],
			false,
			"<?php declare(strict_types = 1);

\$ignoreErrors = [];
\$ignoreErrors[] = [
	'message' => '#^Bar$#',
	'count' => 1,
	'path' => __DIR__ . '/../Foo.php',
];
\$ignoreErrors[] = [
	'message' => '#^Foo$#',
	'count' => 2,
	'path' => __DIR__ . '/Foo.php',
];

return ['parameters' => ['ignoreErrors' => \$ignoreErrors]];
",
		];

		yield [
			[
				new Error(
					'Foo',
					__DIR__ . '/Foo.php',
					5,
				),
				new Error(
					'Foo',
					__DIR__ . '/Foo.php',
					5,
				),
				(new Error(
					'Foo with identifier',
					__DIR__ . '/Foo.php',
					5,
				))->withIdentifier('argument.type'),
				(new Error(
					'Foo with identifier',
					__DIR__ . '/Foo.php',
					6,
				))->withIdentifier('argument.type'),
			],
			false,
			"<?php declare(strict_types = 1);

\$ignoreErrors = [];
\$ignoreErrors[] = [
	'message' => '#^Foo$#',
	'count' => 2,
	'path' => __DIR__ . '/Foo.php',
];
\$ignoreErrors[] = [
	'message' => '#^Foo with identifier$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/Foo.php',
];

return ['parameters' => ['ignoreErrors' => \$ignoreErrors]];
",
		];

		yield [
			[
				new Error(
					'Foo',
					__DIR__ . '/Foo.php',
					5,
				),
				new Error(
					'Foo',
					__DIR__ . '/Foo.php',
					5,
				),
				(new Error(
					'Foo with same message, different identifier',
					__DIR__ . '/Foo.php',
					5,
				))->withIdentifier('argument.type'),
				(new Error(
					'Foo with same message, different identifier',
					__DIR__ . '/Foo.php',
					6,
				))->withIdentifier('argument.byRef'),
				(new Error(
					'Foo with another message',
					__DIR__ . '/Foo.php',
					5,
				))->withIdentifier('argument.type'),
			],
			false,
			"<?php declare(strict_types = 1);

\$ignoreErrors = [];
\$ignoreErrors[] = [
	'message' => '#^Foo$#',
	'count' => 2,
	'path' => __DIR__ . '/Foo.php',
];
\$ignoreErrors[] = [
	'message' => '#^Foo with another message$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/Foo.php',
];
\$ignoreErrors[] = [
	'message' => '#^Foo with same message, different identifier$#',
	'identifier' => 'argument.byRef',
	'count' => 1,
	'path' => __DIR__ . '/Foo.php',
];
\$ignoreErrors[] = [
	'message' => '#^Foo with same message, different identifier$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/Foo.php',
];

return ['parameters' => ['ignoreErrors' => \$ignoreErrors]];
",
		];

		yield [
			[
				new Error(
					'Foo A\\B\\C|null',
					__DIR__ . '/Foo.php',
					5,
				),
				new Error(
					'Foo A\\B\\C|null',
					__DIR__ . '/Foo.php',
					5,
				),
				(new Error(
					'Foo with same message, different identifier',
					__DIR__ . '/Foo.php',
					5,
				))->withIdentifier('argument.type'),
				(new Error(
					'Foo with same message, different identifier',
					__DIR__ . '/Foo.php',
					6,
				))->withIdentifier('argument.byRef'),
				(new Error(
					'Foo with another message',
					__DIR__ . '/Foo.php',
					5,
				))->withIdentifier('argument.type'),
			],
			true,
			"<?php declare(strict_types = 1);

\$ignoreErrors = [];
\$ignoreErrors[] = [
	'rawMessage' => 'Foo A\\\\B\\\\C|null',
	'count' => 2,
	'path' => __DIR__ . '/Foo.php',
];
\$ignoreErrors[] = [
	'rawMessage' => 'Foo with another message',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/Foo.php',
];
\$ignoreErrors[] = [
	'rawMessage' => 'Foo with same message, different identifier',
	'identifier' => 'argument.byRef',
	'count' => 1,
	'path' => __DIR__ . '/Foo.php',
];
\$ignoreErrors[] = [
	'rawMessage' => 'Foo with same message, different identifier',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/Foo.php',
];

return ['parameters' => ['ignoreErrors' => \$ignoreErrors]];
",
		];
	}

	/**
	 * @param list<Error> $errors
	 */
	#[DataProvider('dataFormatErrors')]
	public function testFormatErrors(array $errors, bool $useRawMessage, string $expectedOutput): void
	{
		$formatter = new BaselinePhpErrorFormatter(new ParentDirectoryRelativePathHelper(__DIR__), $useRawMessage);
		$formatter->formatErrors(
			new AnalysisResult(
				$errors,
				[],
				[],
				[],
				[],
				false,
				null,
				true,
				0,
				true,
				[],
			),
			$this->getOutput(),
		);

		$this->assertSame($expectedOutput, $this->getOutputContent());
	}

}
