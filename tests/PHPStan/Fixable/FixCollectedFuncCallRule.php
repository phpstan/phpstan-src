<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Parser\Parser;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;

/**
 * Renames foo() calls to bar(). The calls are collected per file and fixed in a CollectedDataNode rule,
 * which parses the files itself, without the parser cache of the analysis.
 *
 * @implements Rule<CollectedDataNode>
 */
class FixCollectedFuncCallRule implements Rule
{

	public function __construct(private Parser $parser)
	{
	}

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->get(FooFuncCallCollector::class) as $file => $lines) {
			foreach ((new NodeFinder())->findInstanceOf($this->parser->parseFile($file), FuncCall::class) as $call) {
				if (!$call->name instanceof Name || $call->name->toLowerString() !== 'foo') {
					continue;
				}
				if (!in_array($call->getStartLine(), $lines, true)) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message('Renaming foo() to bar().')
					->identifier('tests.renameFoo')
					->file($file)
					->line($call->getStartLine())
					->fixNode($call, static function (FuncCall $call): FuncCall {
						$call->name = new Name('bar');

						return $call;
					})
					->build();
			}
		}

		return $errors;
	}

}
