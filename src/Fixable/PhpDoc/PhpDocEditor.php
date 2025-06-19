<?php declare(strict_types = 1);

namespace PHPStan\Fixable\PhpDoc;

use PhpParser\Comment\Doc;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\NodeVisitor\CloningVisitor;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Printer\Printer;
use function count;

/**
 * @api
 * @internal Experimental
 */
#[AutowiredService]
final class PhpDocEditor
{

	public function __construct(private Printer $printer, private Lexer $lexer, private PhpDocParser $phpDocParser)
	{
	}

	/**
	 * @param callable(Node): (Node|Node[]|null) $callback
	 */
	public function edit(\PhpParser\Node $node, callable $callback): void
	{
		$doc = $node->getDocComment();
		if ($doc === null) {
			$phpDoc = '/** */';
		} else {
			$phpDoc = $doc->getText();
		}
		$tokens = new TokenIterator($this->lexer->tokenize($phpDoc));
		$phpDocNode = $this->phpDocParser->parse($tokens);

		$cloningTraverser = new NodeTraverser([new CloningVisitor()]);

		/** @var PhpDocNode $newPhpDocNode */
		[$newPhpDocNode] = $cloningTraverser->traverse([$phpDocNode]);

		$traverser = new NodeTraverser([new CallbackVisitor($callback)]);

		/** @var PhpDocNode $newPhpDocNode */
		[$newPhpDocNode] = $traverser->traverse([$newPhpDocNode]);

		if (count($newPhpDocNode->children) === 0) {
			$node->setAttribute('comments', []);
			return;
		}

		$doc = new Doc($this->printer->printFormatPreserving($newPhpDocNode, $phpDocNode, $tokens));
		$node->setDocComment($doc);
	}

}
