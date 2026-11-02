<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use Generator;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LineIgnoreFixerTest extends PHPStanTestCase
{

	public static function dataFix(): Generator
	{
		yield 'line comment after code' => [
			"<?php\n\$a = 1; // @phpstan-ignore-line\n\$b = 2;\n",
			2,
			['foo.bar'],
			"<?php\n\$a = 1; // @phpstan-ignore foo.bar\n\$b = 2;\n",
		];

		yield 'next-line comment, one ignore for each error' => [
			"<?php\n\t// @phpstan-ignore-next-line\n\t\$a = 1;\n",
			3,
			['foo.bar', 'baz.qux', 'foo.bar'],
			"<?php\n\t// @phpstan-ignore foo.bar, baz.qux, foo.bar\n\t\$a = 1;\n",
		];

		yield 'text after the tag becomes the comment of every identifier' => [
			"<?php\n# @phpstan-ignore-next-line because of (reasons)\n\$a = 1;\n",
			3,
			['foo.bar', 'baz.qux'],
			"<?php\n# @phpstan-ignore foo.bar (because of (reasons)), baz.qux (because of (reasons))\n\$a = 1;\n",
		];

		yield 'block comment before code on the same line' => [
			"<?php\n/* @phpstan-ignore-line */ \$a = 1;\n",
			2,
			['foo.bar'],
			"<?php\n/* @phpstan-ignore foo.bar */ \$a = 1;\n",
		];

		yield 'next-line block comment' => [
			"<?php\n/* @phpstan-ignore-next-line */\n\$a = 1;\n",
			3,
			['foo.bar'],
			"<?php\n/* @phpstan-ignore foo.bar */\n\$a = 1;\n",
		];

		yield 'one-line PHPDoc after code' => [
			"<?php\n\$a = 1; /** @phpstan-ignore-line why */\n",
			2,
			['foo.bar'],
			"<?php\n\$a = 1; /** @phpstan-ignore foo.bar (why) */\n",
		];

		yield 'next-line as the last tag of a PHPDoc, comment on several lines' => [
			"<?php\n\t/**\n\t * @noinspection Foo\n\t * @phpstan-ignore-next-line Some long\n\t *                           comment.\n\t */\n\t\$a = 1;\n",
			7,
			['foo.bar'],
			"<?php\n\t/**\n\t * @noinspection Foo\n\t * @phpstan-ignore foo.bar (Some long comment.)\n\t */\n\t\$a = 1;\n",
		];

		yield 'next-line after code moves to the end of the next line' => [
			"<?php\nfoo(\n\t1, // @phpstan-ignore-next-line why\n\t2\n);\n",
			4,
			['argument.type'],
			"<?php\nfoo(\n\t1,\n\t2 // @phpstan-ignore argument.type (why)\n);\n",
		];

		yield 'next-line after code moves to the comment at the end of the next line' => [
			"<?php\nfoo(\n\t1, /* @phpstan-ignore-next-line */\n\t2 // two\n);\n",
			4,
			['argument.type'],
			"<?php\nfoo(\n\t1,\n\t2 // two // @phpstan-ignore argument.type\n);\n",
		];
	}

	/**
	 * @param non-empty-list<string> $identifiers
	 */
	#[DataProvider('dataFix')]
	public function testFix(string $code, int $line, array $identifiers, string $expected): void
	{
		$this->assertSame($expected, self::getContainer()->getByType(LineIgnoreFixer::class)->fix($code, $line, $identifiers));
	}

	public static function dataNotFixable(): Generator
	{
		yield 'line ignore of another line' => [
			"<?php\n\$a = 1; // @phpstan-ignore-line\n\$b = 2;\n",
			3,
		];

		yield 'next-line in a PHPDoc that is not its last tag' => [
			"<?php\n/**\n * @phpstan-ignore-next-line\n * @var int \$a\n */\n\$a = 1;\n",
			6,
		];

		yield 'next-line after code when the next line has an ignore' => [
			"<?php\nfoo(\n\t1, // @phpstan-ignore-next-line\n\t2 // @phpstan-ignore baz.qux\n);\n",
			4,
		];

		yield 'next-line after code with other text before the tag' => [
			"<?php\nfoo(\n\t1, // TODO @phpstan-ignore-next-line\n\t2\n);\n",
			4,
		];

		yield 'unbalanced parentheses in the comment' => [
			"<?php\n\$a = 1; // @phpstan-ignore-line :)\n",
			2,
		];

		yield 'another ignore in the comment' => [
			"<?php\n\$a = 1; // @phpstan-ignore-line @phpstan-ignore foo.bar\n",
			2,
		];
	}

	#[DataProvider('dataNotFixable')]
	public function testNotFixable(string $code, int $line): void
	{
		$this->assertNull(self::getContainer()->getByType(LineIgnoreFixer::class)->fix($code, $line, ['foo.bar']));
	}

}
