<?php declare(strict_types = 1);

namespace PHPStan\Node\Printer;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Parser\Parser;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function count;
use function get_class;
use function sprintf;

class ExprPrinterTest extends PHPStanTestCase
{

	public static function dataEquivalentSpellings(): array
	{
		return [
			'string quoting' => [
				['$a[\'test\']', '$a["test"]', '$a["\x74est"]'],
			],
			'string heredoc' => [
				['$a[\'test\']', "\$a[<<<EOT\ntest\nEOT]", "\$a[<<<'EOT'\ntest\nEOT]"],
			],
			'string escaping' => [
				['$a["a\nb"]', "\$a[<<<EOT\na\nb\nEOT]"],
			],
			'int base' => [
				['$a[1]', '$a[0x1]', '$a[01]', '$a[0b1]'],
			],
			'int separator' => [
				['$a[10]', '$a[1_0]'],
			],
			'float' => [
				['$a[1.5]', '$a[1.50]', '$a[15e-1]'],
			],
			'interpolated string' => [
				['$a["x$b"]', '$a["x{$b}"]', "\$a[<<<EOT\nx\$b\nEOT]"],
			],
			'true' => [
				['$a[true]', '$a[TRUE]', '$a[True]', '$a[\true]'],
			],
			'false' => [
				['$a[false]', '$a[FALSE]', '$a[\FALSE]'],
			],
			'null' => [
				['$a[null]', '$a[NULL]', '$a[\null]'],
			],
			'object property' => [
				['$a->b', '$a->{\'b\'}', '$a->{"b"}'],
			],
			'method name' => [
				['$a->b()', '$a->{\'b\'}()', '$a->{"b"}()'],
			],
		];
	}

	/**
	 * @param non-empty-list<string> $codes
	 */
	#[DataProvider('dataEquivalentSpellings')]
	public function testEquivalentSpellingsPrintTheSame(array $codes): void
	{
		$exprPrinter = self::getContainer()->getByType(ExprPrinter::class);

		$expected = null;
		foreach ($codes as $code) {
			$printed = $exprPrinter->printExpr($this->parseExpr($code));
			if ($expected === null) {
				$expected = $printed;
				continue;
			}

			$this->assertSame($expected, $printed, sprintf('%s should print the same as %s', $code, $codes[0]));
		}
	}

	public static function dataDifferentSpellings(): array
	{
		return [
			'numeric string vs int' => [
				'$a[1]',
				'$a[\'1\']',
			],
			'single quoted backslash is literal' => [
				'$a[\'a\nb\']',
				'$a["a\nb"]',
			],
			'different int' => [
				'$a[1]',
				'$a[10]',
			],
			'other constant case is significant' => [
				'$a[FOO]',
				'$a[foo]',
			],
		];
	}

	#[DataProvider('dataDifferentSpellings')]
	public function testDifferentSpellingsPrintDifferently(string $code, string $otherCode): void
	{
		$exprPrinter = self::getContainer()->getByType(ExprPrinter::class);

		$this->assertNotSame(
			$exprPrinter->printExpr($this->parseExpr($code)),
			$exprPrinter->printExpr($this->parseExpr($otherCode)),
		);
	}

	public function testPrintedFormNeverContainsNewline(): void
	{
		$exprPrinter = self::getContainer()->getByType(ExprPrinter::class);

		foreach (["\$a[<<<EOT\na\nb\nEOT]", "\$a[<<<'EOT'\na\nb\nEOT]", "\$a[<<<EOT\nx\$b\ny\nEOT]"] as $code) {
			$this->assertStringNotContainsString("\n", $exprPrinter->printExpr($this->parseExpr($code)), $code);
		}
	}

	private function parseExpr(string $code): Expr
	{
		/** @var Parser $parser */
		$parser = self::getContainer()->getService('currentPhpVersionRichParser');

		/** @var Stmt[] $stmts */
		$stmts = $parser->parseString(sprintf('<?php %s;', $code));
		if (count($stmts) !== 1) {
			throw new ShouldNotHappenException('Expecting code which evaluates to a single statement, got: ' . count($stmts));
		}
		if (!$stmts[0] instanceof Stmt\Expression) {
			throw new ShouldNotHappenException('Expecting code contains a single statement expression, got: ' . get_class($stmts[0]));
		}

		return $stmts[0]->expr;
	}

}
