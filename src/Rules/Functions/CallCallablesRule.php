<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\InaccessibleMethod;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function sprintf;
use function ucfirst;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class CallCallablesRule implements Rule
{

	public function __construct(
		private FunctionCallParametersCheck $check,
		private RuleLevelHelper $ruleLevelHelper,
		private bool $reportMaybes,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\FuncCall::class;
	}

	public function processNode(
		Node $node,
		Scope $scope,
	): array
	{
		if (!$node->name instanceof Node\Expr) {
			return [];
		}

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $node->name),
			'Invoking callable on an unknown class %s.',
			static fn (Type $type): bool => $type->isCallable()->yes(),
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}

		$isCallable = $type->isCallable();
		if ($isCallable->no()) {
			return [
				RuleErrorBuilder::message(
					sprintf('Trying to invoke %s but it\'s not a callable.', $type->describe(VerbosityLevel::value())),
				)->identifier('callable.nonCallable')->build(),
			];
		}
		if ($this->reportMaybes && $isCallable->maybe()) {
			return [
				RuleErrorBuilder::message(
					sprintf('Trying to invoke %s but it might not be a callable.', $type->describe(VerbosityLevel::value())),
				)->identifier('callable.nonCallable')->build(),
			];
		}

		$parametersAcceptors = $type->getCallableParametersAcceptors($scope);
		$messages = [];

		if (
			count($parametersAcceptors) === 1
			&& $parametersAcceptors[0] instanceof InaccessibleMethod
		) {
			$method = $parametersAcceptors[0]->getMethod();
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Call to %s method %s() of class %s.',
				$method->isPrivate() ? 'private' : 'protected',
				$method->getName(),
				$method->getDeclaringClass()->getDisplayName(),
			))->identifier('callable.inaccessibleMethod')->build();
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$node->getArgs(),
			$parametersAcceptors,
		);

		if ($type instanceof ClosureType) {
			$callableDescription = 'closure';
		} else {
			$callableDescription = sprintf('callable %s', SprintfHelper::escapeFormatString($type->describe(VerbosityLevel::value())));
		}

		return array_merge(
			$messages,
			$this->check->check(
				$parametersAcceptor,
				$scope,
				false,
				$node,
				[
					ucfirst($callableDescription) . ' invoked with %d parameter, %d required.',
					ucfirst($callableDescription) . ' invoked with %d parameters, %d required.',
					ucfirst($callableDescription) . ' invoked with %d parameter, at least %d required.',
					ucfirst($callableDescription) . ' invoked with %d parameters, at least %d required.',
					ucfirst($callableDescription) . ' invoked with %d parameter, %d-%d required.',
					ucfirst($callableDescription) . ' invoked with %d parameters, %d-%d required.',
					'Parameter %s of ' . $callableDescription . ' expects %s, %s given.',
					'Result of ' . $callableDescription . ' (void) is used.',
					'Parameter %s of ' . $callableDescription . ' is passed by reference, so it expects variables only.',
					'Unable to resolve the template type %s in call to ' . $callableDescription,
					'Missing parameter $%s in call to ' . $callableDescription . '.',
					'Unknown parameter $%s in call to ' . $callableDescription . '.',
					'Return type of call to ' . $callableDescription . ' contains unresolvable type.',
					'Parameter %s of ' . $callableDescription . ' contains unresolvable type.',
				],
				'callable',
			),
		);
	}

}
