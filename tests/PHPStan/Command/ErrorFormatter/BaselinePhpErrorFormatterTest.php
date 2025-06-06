<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;

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
	}

	/**
	 * @dataProvider dataFormatErrors
	 * @param list<Error> $errors
	 */
	public function testFormatErrors(array $errors, string $expectedOutput): void
	{
		$formatter = new BaselinePhpErrorFormatter(new ParentDirectoryRelativePathHelper(__DIR__));
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
