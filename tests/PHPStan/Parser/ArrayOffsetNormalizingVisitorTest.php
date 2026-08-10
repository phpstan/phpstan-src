<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class ArrayOffsetNormalizingVisitorTest extends PHPStanTestCase
{

	public static function dataOffsetKey(): iterable
	{
		yield ["'k'", "\$a['k']"];
		yield ['"k"', "\$a['k']"];
		yield ['"\x6b"', "\$a['k']"];
		yield ["<<<'NOWDOC'\nk\nNOWDOC", "\$a['k']"];
		yield ["<<<HEREDOC\nk\nHEREDOC", "\$a['k']"];

		// A newline in the key would break the print cache, so control
		// characters keep the escaped double-quoted spelling.
		yield ['"a\nb"', '$a["a\nb"]'];
		yield ["'a\nb'", '$a["a\nb"]'];

		yield ['1', '$a[1]'];
		yield ['0x1', '$a[1]'];
		yield ['01', '$a[1]'];
		yield ['0b1', '$a[1]'];

		yield ['"x$k"', "\$a['x' . \$k]"];
		yield ['"x{$k}"', "\$a['x' . \$k]"];
		yield ["<<<HEREDOC\nx\$k\nHEREDOC", "\$a['x' . \$k]"];

		yield ['"$k.value"', "\$a[\$k . '.value']"];
		yield ['$k . ".value"', "\$a[\$k . '.value']"];
		yield ['$k . \'.value\'', "\$a[\$k . '.value']"];
		yield ['"{$k}.value"', "\$a[\$k . '.value']"];

		// Concatenation is associative, so the nesting of the operands does
		// not matter, and neither does which syntax contributed which operand.
		yield ['$k . $j . \'.x\'', "\$a[\$k . \$j . '.x']"];
		yield ['$k . ($j . \'.x\')', "\$a[\$k . \$j . '.x']"];
		yield ['"$k$j.x"', "\$a[\$k . \$j . '.x']"];
		yield ['"$k" . "$j" . \'.x\'', "\$a[\$k . \$j . '.x']"];
		yield ["'a' . 'b'", "\$a['ab']"];

		// A one-part interpolation is a string cast that the part alone is not.
		yield ['"$k"', '$a["{$k}"]'];
		yield ['"{$k}"', '$a["{$k}"]'];
		yield ['$k', '$a[$k]'];

		// Only the offset is normalized, not what is nested inside it.
		yield ['$m["x"]', "\$a[\$m['x']]"];
		yield ['foo("x")', '$a[\foo("x")]'];
	}

	#[DataProvider('dataOffsetKey')]
	public function testOffsetKey(string $offset, string $expectedKey): void
	{
		$stmts = self::getParser()->parseString(sprintf('<?php $a[%s];', $offset));
		if (!$stmts[0] instanceof Expression || !$stmts[0]->expr instanceof ArrayDimFetch) {
			throw new ShouldNotHappenException();
		}

		$printer = self::getContainer()->getByType(ExprPrinter::class);
		$this->assertSame($expectedKey, $printer->printExpr($stmts[0]->expr));
	}

}
