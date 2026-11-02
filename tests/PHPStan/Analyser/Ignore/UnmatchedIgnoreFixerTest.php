<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use Generator;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class UnmatchedIgnoreFixerTest extends PHPStanTestCase
{

	public static function dataFix(): Generator
	{
		yield 'only identifier, comment on its own line' => [
			"<?php\n\$a = 1;\n// @phpstan-ignore foo.bar\n\$b = 2;\n",
			4,
			'foo.bar',
			"<?php\n\$a = 1;\n\$b = 2;\n",
		];

		yield 'only identifier, comment after code' => [
			"<?php\n\$a = 1; // @phpstan-ignore foo.bar (because)\n\$b = 2;\n",
			2,
			'foo.bar',
			"<?php\n\$a = 1;\n\$b = 2;\n",
		];

		yield 'block comment before code' => [
			"<?php\n/* @phpstan-ignore foo.bar */ \$a = 1;\n",
			2,
			'foo.bar',
			"<?php\n\$a = 1;\n",
		];

		yield 'first of several identifiers, with a comma in its comment' => [
			"<?php\n\$a = 1; // @phpstan-ignore foo.bar (x, y), baz.qux, a.b\n",
			2,
			'foo.bar',
			"<?php\n\$a = 1; // @phpstan-ignore baz.qux, a.b\n",
		];

		yield 'middle identifier' => [
			"<?php\n\$a = 1; // @phpstan-ignore foo.bar, baz.qux, a.b\n",
			2,
			'baz.qux',
			"<?php\n\$a = 1; // @phpstan-ignore foo.bar, a.b\n",
		];

		yield 'last identifier with its comment' => [
			"<?php\n\$a = 1; // @phpstan-ignore foo.bar (keep), baz.qux (remove)\n",
			2,
			'baz.qux',
			"<?php\n\$a = 1; // @phpstan-ignore foo.bar (keep)\n",
		];

		yield 'identifier in a docblock with only the ignore' => [
			"<?php\n/** @phpstan-ignore foo.bar */\n\$a = 1;\n",
			3,
			'foo.bar',
			"<?php\n\$a = 1;\n",
		];

		yield 'comment followed by an empty line ignores its own line' => [
			"<?php\n\$a = 1;\n// @phpstan-ignore foo.bar\n\n\$b = 2;\n",
			3,
			'foo.bar',
			"<?php\n\$a = 1;\n\n\$b = 2;\n",
		];

		yield 'next-line comment' => [
			"<?php\n// @phpstan-ignore-next-line because of reasons\n\$a = 1;\n",
			3,
			null,
			"<?php\n\$a = 1;\n",
		];

		yield 'next-line PHPDoc' => [
			"<?php\n\t/**\n\t * @phpstan-ignore-next-line\n\t */\n\t\$a = 1;\n",
			5,
			null,
			"<?php\n\t\$a = 1;\n",
		];

		yield 'line PHPDoc after code' => [
			"<?php\n\$a = 1; /** @phpstan-ignore-line */\n\$b = 2;\n",
			2,
			null,
			"<?php\n\$a = 1;\n\$b = 2;\n",
		];

		yield 'PHPDoc with other tags' => [
			"<?php\n/**\n * @var int \$a\n * @phpstan-ignore foo.bar (because)\n */\n\$a = 1;\n",
			6,
			'foo.bar',
			"<?php\n/**\n * @var int \$a\n */\n\$a = 1;\n",
		];

		yield 'identifier in a PHPDoc with other tags' => [
			"<?php\n/**\n * @var int \$a\n * @phpstan-ignore foo.bar, baz.qux\n */\n\$a = 1;\n",
			6,
			'foo.bar',
			"<?php\n/**\n * @var int \$a\n * @phpstan-ignore baz.qux\n */\n\$a = 1;\n",
		];

		yield 'next-line in a PHPDoc with other tags' => [
			"<?php\n/**\n * @var int \$a\n * @phpstan-ignore-next-line\n */\n\$a = 1;\n",
			6,
			null,
			"<?php\n/**\n * @var int \$a\n */\n\$a = 1;\n",
		];

		yield 'next-line in a PHPDoc that is not its last tag' => [
			"<?php\n/**\n * @phpstan-ignore-next-line\n * @var int \$a\n */\n\$a = 1;\n",
			4,
			null,
			"<?php\n/**\n * @var int \$a\n */\n\$a = 1;\n",
		];

		yield 'line comment after code' => [
			"<?php\n\$a = 1; // @phpstan-ignore-line\n\$b = 2;\n",
			2,
			null,
			"<?php\n\$a = 1;\n\$b = 2;\n",
		];

		yield 'comment of the other line is kept' => [
			"<?php\n// @phpstan-ignore foo.bar\n\$a = 1;\n\$b = 2; // @phpstan-ignore foo.bar\n",
			4,
			'foo.bar',
			"<?php\n// @phpstan-ignore foo.bar\n\$a = 1;\n\$b = 2;\n",
		];
	}

	#[DataProvider('dataFix')]
	public function testFix(string $code, int $line, ?string $identifier, string $expected): void
	{
		$this->assertSame($expected, self::getContainer()->getByType(UnmatchedIgnoreFixer::class)->fix($code, $line, $identifier));
	}

	public static function dataNotFixable(): Generator
	{
		yield 'comment on its own line ignores the next line, not its own' => [
			"<?php\n// @phpstan-ignore foo.bar\n\$a = 1;\n",
			2,
			'foo.bar',
		];

		yield 'other identifier' => [
			"<?php\n\$a = 1; // @phpstan-ignore foo.bar\n",
			2,
			'baz.qux',
		];

		yield 'other text in the comment' => [
			"<?php\n\$a = 1; // TODO remove @phpstan-ignore foo.bar\n",
			2,
			'foo.bar',
		];

		yield 'next-line in a docblock that is not its last tag ignores the line after the tag' => [
			"<?php\n/**\n * @phpstan-ignore-next-line\n * @var int \$a\n */\n\$a = 1;\n",
			6,
			null,
		];

		yield 'line ignore of another line' => [
			"<?php\n\$a = 1; // @phpstan-ignore-line\n\$b = 2;\n",
			3,
			null,
		];
	}

	#[DataProvider('dataNotFixable')]
	public function testNotFixable(string $code, int $line, ?string $identifier): void
	{
		$this->assertNull(self::getContainer()->getByType(UnmatchedIgnoreFixer::class)->fix($code, $line, $identifier));
	}

}
