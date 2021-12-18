<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<Node\Expr\StaticCall>
 */
class ImpossibleCheckTypeStaticMethodCallRule implements Rule
{

	private ImpossibleCheckTypeHelper $impossibleCheckTypeHelper;

	private bool $checkAlwaysTrueCheckTypeFunctionCall;

	private bool $treatPhpDocTypesAsCertain;

	public function __construct(
		ImpossibleCheckTypeHelper $impossibleCheckTypeHelper,
		bool $checkAlwaysTrueCheckTypeFunctionCall,
		bool $treatPhpDocTypesAsCertain,
	)
	{
		$this->impossibleCheckTypeHelper = $impossibleCheckTypeHelper;
		$this->checkAlwaysTrueCheckTypeFunctionCall = $checkAlwaysTrueCheckTypeFunctionCall;
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
	}

	public function getNodeType(): string
	{
		return Node\Expr\StaticCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}

		$isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $node);
		if ($isAlways === null) {
			return [];
		}

		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
			if (!$this->treatPhpDocTypesAsCertain) {
				return $ruleErrorBuilder;
			}

			$isAlways = $this->impossibleCheckTypeHelper->doNotTreatPhpDocTypesAsCertain()->findSpecifiedType($scope, $node);
			if ($isAlways !== null) {
				return $ruleErrorBuilder;
			}

			return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
		};

		if (!$isAlways) {
			$method = $this->getMethod($node->class, $node->name->name, $scope);

			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Call to static method %s::%s()%s will always evaluate to false.',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()),
				)))->build(),
			];
		} elseif ($this->checkAlwaysTrueCheckTypeFunctionCall) {
			$method = $this->getMethod($node->class, $node->name->name, $scope);

			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Call to static method %s::%s()%s will always evaluate to true.',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()),
				)))->build(),
			];
		}

		return [];
	}

	/**
	 * @param Node\Name|Expr $class
	 * @throws ShouldNotHappenException
	 */
	private function getMethod(
		$class,
		string $methodName,
		Scope $scope,
	): MethodReflection
	{
		if ($class instanceof Node\Name) {
			$calledOnType = $scope->resolveTypeByName($class);
		} else {
			$calledOnType = $scope->getType($class);
		}

		$method = $scope->getMethodReflection($calledOnType, $methodName);
		if ($method === null) {
			throw new ShouldNotHappenException();
		}

		return $method;
	}

}
