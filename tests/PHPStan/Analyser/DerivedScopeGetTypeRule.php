<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * Derives a scope via assignExpression() (pinning a call-site literal onto a
 * parameter variable, the way callback-analysing tooling re-analyses a callee
 * body with more specific argument types) and asks the type of the real
 * argument node on the derived scope. The pinned type must win over the
 * walk-position stored result.
 *
 * @implements Rule<FuncCall>
 */
class DerivedScopeGetTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name || $node->name->toLowerString() !== 'target') {
			return [];
		}
		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		$pinnedType = new ConstantStringType('weight');
		$derivedScope = $scope->assignExpression(new Variable('key'), $pinnedType, $pinnedType);
		$argExpr = $node->getArgs()[0]->value;

		return [
			RuleErrorBuilder::message(sprintf(
				'%s / %s',
				$derivedScope->getType($argExpr)->describe(VerbosityLevel::precise()),
				$derivedScope->getNativeType($argExpr)->describe(VerbosityLevel::precise()),
			))
				->identifier('tests.derivedScopeGetType')
				->build(),
		];
	}

}
