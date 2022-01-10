<?php declare(strict_types = 1);

namespace PHPStan\Rules\Whitespace;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;

/**
 * @implements Rule<FileNode>
 */
class FileWhitespaceRule implements Rule
{

	public function getNodeType(): string
	{
		return FileNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$nodes = $node->getNodes();
		if ($nodes === []) {
			return [];
		}

		$firstNode = $nodes[0];
		$messages = [];
		if ($firstNode instanceof Node\Stmt\InlineHTML && $firstNode->value === "\xef\xbb\xbf") {
			$messages[] = RuleErrorBuilder::message('File begins with UTF-8 BOM character. This may cause problems when running the code in the web browser.')->build();
		}

		$nodeTraverser = new NodeTraverser();
		$visitor = new class () extends NodeVisitorAbstract {

			/** @var Node[] */
			private array $lastNodes = [];

			/**
			 * @return int|Node|null
			 */
			public function enterNode(Node $node)
			{
				if ($node instanceof Node\Stmt\Declare_) {
					if ($node->stmts !== null && count($node->stmts) > 0) {
						$this->lastNodes[] = $node->stmts[count($node->stmts) - 1];
					}
					return null;
				}
				if ($node instanceof Node\Stmt\Namespace_) {
					if ($node->stmts !== []) {
						$this->lastNodes[] = $node->stmts[count($node->stmts) - 1];
					}
					return null;
				}
				return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
			}

			/**
			 * @return Node[]
			 */
			public function getLastNodes(): array
			{
				return $this->lastNodes;
			}

		};
		$nodeTraverser->addVisitor($visitor);
		$nodeTraverser->traverse($nodes);

		$lastNodes = $visitor->getLastNodes();
		$lastNodes[] = $nodes[count($nodes) - 1];
		foreach ($lastNodes as $lastNode) {
			if (!$lastNode instanceof Node\Stmt\InlineHTML || Strings::match($lastNode->value, '#^(\s+)$#') === null) {
				continue;
			}

			$messages[] = RuleErrorBuilder::message('File ends with a trailing whitespace. This may cause problems when running the code in the web browser. Remove the closing ?> mark or remove the whitespace.')->line($lastNode->getStartLine())->build();
		}

		return $messages;
	}

}
