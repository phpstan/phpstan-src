<?php declare(strict_types = 1);

namespace PHPStan\Node\Printer;

use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

/**
 * The pretty printer is non-final: a subclass's overrides of protected
 * methods must take effect whichever implementation of the parent runs.
 */
class StandardPrinterSubclassTest extends TestCase
{

	private const CODE = '<?php $a + $b * foo($c, ...$d); $x?->y["z"] = new Foo(1, b: 2); fn($q) => $q . "s";';

	public function testParent(): void
	{
		$this->assertSame(
			"\$a + \$b * foo(\$c, ...\$d);\n\$x?->y[\"z\"] = new Foo(1, b: 2);\nfn(\$q) => \$q . \"s\";",
			(new Standard())->prettyPrint($this->parse()),
		);
	}

	public function testOverriddenProtectedMethods(): void
	{
		$printer = new OverridingStandardPrinter();
		$stmts = $this->parse();
		$this->assertSame(
			"<\$overridden_a + <\$overridden_b * foo(\$overridden_c, ...\$overridden_d)>>;\n\$overridden_x?->y[\"z\"] = new Foo(1, b: 2);\nfn(\$overridden_q) => <\$overridden_q . \"s\">;",
			$printer->prettyPrint($stmts),
		);
		$this->assertSame('{<$overridden_a + <$overridden_b * foo($overridden_c, ...$overridden_d)>>}', $printer->prettyPrintExpr($stmts[0]->expr));
	}

	/**
	 * The format-preserving printer passes positions and buffers by reference
	 * between its own protected methods; through a subclass they are called by
	 * name and must still write back.
	 */
	public function testFormatPreservingThroughSubclass(): void
	{
		$code = "<?php\n\nfunction (string \$s): void {\n\t`echo \"foo\";`;\n\t\$s;\n};\n";
		$parser = (new ParserFactory())->createForNewestSupportedVersion();
		$oldStmts = $parser->parse($code);
		$this->assertNotNull($oldStmts);
		$oldTokens = $parser->getTokens();
		$newStmts = (new NodeTraverser(new CloningVisitor(), new class extends NodeVisitorAbstract {

			#[Override]
			public function leaveNode(Node $node): ?Node
			{
				if ($node instanceof Node\Expr\ShellExec) {
					return new Node\Expr\FuncCall(new Node\Name('shell_exec'), [new Node\Arg(new Node\Scalar\String_('echo "foo";'))]);
				}

				return null;
			}

		}))->traverse($oldStmts);

		$this->assertSame(
			"<?php\n\nfunction (string \$s): void {\n\tshell_exec('echo \"foo\";');\n\t\$s;\n};\n",
			(new OverridingStandardPrinter())->printFormatPreserving($newStmts, $oldStmts, $oldTokens),
		);
	}

	/**
	 * @return list<Expression>
	 */
	private function parse(): array
	{
		$stmts = (new ParserFactory())->createForNewestSupportedVersion()->parse(self::CODE);
		$this->assertNotNull($stmts);
		$result = [];
		foreach ($stmts as $stmt) {
			$this->assertInstanceOf(Expression::class, $stmt);
			$result[] = $stmt;
		}

		return $result;
	}

}
