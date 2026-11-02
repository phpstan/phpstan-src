<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\Parser;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function dirname;
use function sprintf;

/**
 * A renameIn('file.php', 'foo', 'bar') call reports an error in file.php and renames foo() calls there to bar().
 *
 * @implements Rule<FuncCall>
 */
class FixOtherFileRule implements Rule
{

	public function __construct(private Parser $parser)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Name || $node->name->toLowerString() !== 'renamein') {
			return [];
		}

		$args = $node->getArgs();
		if (!$args[0]->value instanceof String_ || !$args[1]->value instanceof String_ || !$args[2]->value instanceof String_) {
			return [];
		}

		$file = dirname($scope->getFile()) . '/' . $args[0]->value->value;
		$from = $args[1]->value->value;
		$to = $args[2]->value->value;
		if ($to === '') {
			return [];
		}

		$errors = [];
		foreach ((new NodeFinder())->findInstanceOf($this->parser->parseFile($file), FuncCall::class) as $call) {
			if (!$call->name instanceof Name || $call->name->toString() !== $from) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf('Renaming %s() to %s().', $from, $to))
				->identifier('tests.renameInOtherFile')
				->file($file)
				->line($call->getStartLine())
				->fixNode($call, static function (FuncCall $call) use ($to): FuncCall {
					$call->name = new Name($to);

					return $call;
				})
				->build();
		}

		return $errors;
	}

}
