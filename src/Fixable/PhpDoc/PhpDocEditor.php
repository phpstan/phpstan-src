<?php declare(strict_types = 1);

namespace PHPStan\Fixable\PhpDoc;

use PhpParser\Comment\Doc;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\NodeVisitor\CloningVisitor;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\PhpDocParser\Printer\Printer;
use function count;

/**
 * @api
 * @internal Experimental
 */
#[AutowiredService]
final class PhpDocEditor
{

	private Lexer $lexer;

	private PhpDocParser $phpDocParser;

	public function __construct(private Printer $printer)
	{
		// format-preserving printing needs token indexes, which the parser used by analysis does not collect
		$config = new ParserConfig(['lines' => true, 'indexes' => true]);
		$constExprParser = new ConstExprParser($config);
		$this->lexer = new Lexer($config);
		$this->phpDocParser = new PhpDocParser($config, new TypeParser($config, $constExprParser), $constExprParser);
	}

	/**
	 * @param callable(Node): (Node|Node[]|null) $callback
	 */
	public function edit(\PhpParser\Node $node, callable $callback): void
	{
		$doc = $node->getDocComment();
		$phpDoc = $this->editText($doc === null ? '/** */' : $doc->getText(), $callback);
		if ($phpDoc === null) {
			$node->setAttribute('comments', []);
			return;
		}

		$node->setDocComment(new Doc($phpDoc));
	}

	/**
	 * Edits the text of a PHPDoc with format-preserving printing.
	 *
	 * @param callable(Node): (Node|Node[]|null) $callback
	 * @return string|null null when the PHPDoc is left without contents
	 */
	public function editText(string $phpDoc, callable $callback): ?string
	{
		$tokens = new TokenIterator($this->lexer->tokenize($phpDoc));
		$phpDocNode = $this->phpDocParser->parse($tokens);

		$cloningTraverser = new NodeTraverser([new CloningVisitor()]);

		/** @var PhpDocNode $newPhpDocNode */
		[$newPhpDocNode] = $cloningTraverser->traverse([$phpDocNode]);

		$traverser = new NodeTraverser([new CallbackVisitor($callback)]);

		/** @var PhpDocNode $newPhpDocNode */
		[$newPhpDocNode] = $traverser->traverse([$newPhpDocNode]);

		if (count($newPhpDocNode->children) === 0) {
			return null;
		}

		return $this->printer->printFormatPreserving($newPhpDocNode, $phpDocNode, $tokens);
	}

}
