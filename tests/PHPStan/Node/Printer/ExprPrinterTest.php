<?php declare(strict_types = 1);

namespace PHPStan\Node\Printer;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPStan\Testing\PHPStanTestCase;

class ExprPrinterTest extends PHPStanTestCase
{

	public function testNestedMultiLineFormDoesNotDependOnPrintOrder(): void
	{
		$code = '<?php $this->f(function () { return $this->g(static function () { return 1; }); });';

		$expected = self::getContainer()->getByType(ExprPrinter::class)->printExpr($this->parseOuterCall($code));

		$outer = $this->parseOuterCall($code);
		$inner = (new NodeFinder())->findFirst($outer->getArgs(), static fn ($node) => $node instanceof MethodCall && $node->name instanceof Identifier && $node->name->toString() === 'g');
		$this->assertInstanceOf(MethodCall::class, $inner);

		$printer = self::getContainer()->getByType(ExprPrinter::class);
		$printer->printExpr($inner);
		$this->assertSame($expected, $printer->printExpr($outer));
	}

	private function parseOuterCall(string $code): MethodCall
	{
		$stmts = (new ParserFactory())->createForNewestSupportedVersion()->parse($code);
		$this->assertNotNull($stmts);
		$this->assertInstanceOf(Expression::class, $stmts[0]);
		$expr = $stmts[0]->expr;
		$this->assertInstanceOf(MethodCall::class, $expr);

		return $expr;
	}

}
