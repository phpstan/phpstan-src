<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\NoopExpressionNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<NoopExpressionNode>
 */
#[RegisteredRule(level: 4)]
final class CallToFunctionStatementWithoutSideEffectsRule implements Rule
{

	public const PHPSTAN_TESTING_FUNCTIONS = [
		'PHPStan\\dumpNativeType',
		'PHPStan\\dumpType',
		'PHPStan\\dumpPhpDocType',
		'PHPStan\\debugScope',
		'PHPStan\\Testing\\assertType',
		'PHPStan\\Testing\\assertNativeType',
		'PHPStan\\Testing\\assertSuperType',
		'PHPStan\\Testing\\assertVariableCertainty',
	];

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return NoopExpressionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$expr = $node->getOriginalExpr();
		if ($expr instanceof Node\Expr\BinaryOp\Pipe) {
			$expr = $expr->right;
		}
		if (!$expr instanceof Node\Expr\FuncCall) {
			return [];
		}

		$funcCall = $expr;
		if (!($funcCall->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($funcCall->name, $scope)) {
			return [];
		}

		$function = $this->reflectionProvider->getFunction($funcCall->name, $scope);
		if (count($function->getAsserts()->getAsserts()) > 0) {
			return [];
		}

		$functionName = $function->getName();
		if (in_array($functionName, self::PHPSTAN_TESTING_FUNCTIONS, true)) {
			return [];
		}

		$functionResult = $scope->getType($funcCall);
		if ($functionResult->isExplicitNever()->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Call to function %s() on a separate line has no effect.',
				$function->getName(),
			))->identifier('function.resultUnused')->build(),
		];
	}

}
