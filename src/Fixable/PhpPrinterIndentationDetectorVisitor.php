<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Internal\TokenStream;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use function count;
use function in_array;
use function is_array;
use function preg_match;
use function preg_match_all;
use function property_exists;
use function strlen;
use const PREG_SET_ORDER;

final class PhpPrinterIndentationDetectorVisitor extends NodeVisitorAbstract
{

	public string $indentCharacter = ' ';

	public int $indentSize = 4;

	public function __construct(private TokenStream $origTokens)
	{
	}

	#[Override]
	public function enterNode(Node $node): ?int
	{
		if ($node instanceof Node\Stmt\Namespace_ || $node instanceof Node\Stmt\Declare_) {
			return null;
		}
		if (!property_exists($node, 'stmts')) {
			return null;
		}

		if (!is_array($node->stmts) || count($node->stmts) === 0) {
			return null;
		}

		$firstStmt = $node->stmts[0];
		if (!$firstStmt instanceof Node) {
			return null;
		}
		$text = $this->origTokens->getTokenCode($node->getStartTokenPos(), $firstStmt->getStartTokenPos(), 0);

		$c = preg_match_all('~\n([\\x09\\x20]*)~', $text, $matches, PREG_SET_ORDER);
		if (in_array($c, [0, false], true)) {
			return null;
		}

		$char = '';
		$size = 0;
		foreach ($matches as $match) {
			$l = strlen($match[1]);
			if ($l === 0) {
				continue;
			}

			$char = $match[1];
			$size = $l;
			break;
		}

		if ($size > 0) {
			$d = preg_match('~^(\\x20+)$~', $char);
			if ($d !== false && $d > 0) {
				$size = strlen($char);
				$char = ' ';
			}

			$this->indentCharacter = $char;
			$this->indentSize = $size;

			return NodeVisitor::STOP_TRAVERSAL;
		}

		return null;
	}

}
