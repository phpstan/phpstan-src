<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\Parser;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function dirname;

/**
 * renameAll('other.php') reports one error that renames foo() to bar() in this file
 * and to bar(1) in other.php. renameAllOrNothing('other.php') additionally removes the condition
 * of an if, which cannot be removed, so none of its changes are applied.
 *
 * @implements Rule<FuncCall>
 */
class MultiFixRule implements Rule
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
		if (!$node->name instanceof Name || !$node->getArgs()[0]->value instanceof String_) {
			return [];
		}

		$name = $node->name->toLowerString();
		if ($name !== 'renameall' && $name !== 'renameallornothing') {
			return [];
		}

		$otherFile = dirname($scope->getFile()) . '/' . $node->getArgs()[0]->value->value;
		$builder = RuleErrorBuilder::message('Renaming foo() to bar().')->identifier('tests.multiFix');
		foreach ($this->findFooCalls($scope->getFile()) as $call) {
			$builder->fixNode($call, static function (FuncCall $call): FuncCall {
				$call->name = new Name('bar');

				return $call;
			});
		}
		foreach ($this->findFooCalls($otherFile) as $call) {
			$builder->fixNode($call, static function (FuncCall $call): FuncCall {
				$call->name = new Name('bar');

				return $call;
			}, $otherFile);
			// applied after the rename, to the renamed call
			$builder->fixNode($call, static function (FuncCall $call): FuncCall {
				$call->args[] = new Arg(new Int_(1));

				return $call;
			}, $otherFile);
		}

		if ($name === 'renameallornothing') {
			foreach ((new NodeFinder())->findInstanceOf($this->parser->parseFile($scope->getFile()), If_::class) as $if) {
				$builder->removeNode($if->cond);
			}
		}

		return [$builder->build()];
	}

	/**
	 * @return list<FuncCall>
	 */
	private function findFooCalls(string $file): array
	{
		$calls = [];
		foreach ((new NodeFinder())->findInstanceOf($this->parser->parseFile($file), FuncCall::class) as $call) {
			if (!$call->name instanceof Name || $call->name->toLowerString() !== 'foo') {
				continue;
			}

			$calls[] = $call;
		}

		return $calls;
	}

}
