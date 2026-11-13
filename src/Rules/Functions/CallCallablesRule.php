<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\InaccessibleMethod;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function in_array;
use function sprintf;
use function ucfirst;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[RegisteredRule(level: 2)]
final class CallCallablesRule implements Rule
{

	public function __construct(
		private FunctionCallParametersCheck $check,
		private RuleLevelHelper $ruleLevelHelper,
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter]
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
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
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

		$acceptsNamedArguments = TrinaryLogic::createYes();
		foreach ($parametersAcceptors as $parametersAcceptor) {
			$acceptsNamedArguments = $acceptsNamedArguments->and($parametersAcceptor->acceptsNamedArguments());
		}

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

		$namedArgumentsVariants = $this->getCalleeNamedArgumentsVariants($type, $node, $scope);
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$node->getArgs(),
			$parametersAcceptors,
			$namedArgumentsVariants,
		);

		foreach ($node->getArgs() as $arg) {
			if ($arg->name === null || !self::isBoundByPartialApplication($type, $arg->name->toString())) {
				continue;
			}
			$messages[] = RuleErrorBuilder::message(sprintf('Argument for parameter $%s has already been bound by the partial function application.', $arg->name->toString()))
				->identifier('argument.duplicate')
				->line($arg->getStartLine())
				->build();
		}

		if ($type instanceof ClosureType) {
			$callableDescription = 'closure';
		} else {
			$callableDescription = sprintf('callable %s', SprintfHelper::escapeFormatString($type->describe(VerbosityLevel::value())));
		}

		return array_merge(
			$messages,
			$this->check->check(
				FunctionCallParametersCheck::getVariantForArgumentsCount($node->getArgs(), $parametersAcceptors, $parametersAcceptor) ?? $parametersAcceptor,
				$scope,
				false,
				$node,
				'callable',
				$acceptsNamedArguments,
				ucfirst($callableDescription) . ' invoked with %d parameter, %d required.',
				ucfirst($callableDescription) . ' invoked with %d parameters, %d required.',
				ucfirst($callableDescription) . ' invoked with %d parameter, at least %d required.',
				ucfirst($callableDescription) . ' invoked with %d parameters, at least %d required.',
				ucfirst($callableDescription) . ' invoked with %d parameter, %d-%d required.',
				ucfirst($callableDescription) . ' invoked with %d parameters, %d-%d required.',
				'%s of ' . $callableDescription . ' expects %s, %s given.',
				'Result of ' . $callableDescription . ' (void) is used.',
				'%s of ' . $callableDescription . ' is passed by reference, so it expects variables only.',
				'Unable to resolve the template type %s in call to ' . $callableDescription,
				'Missing parameter $%s in call to ' . $callableDescription . '.',
				'Unknown parameter $%s in call to ' . $callableDescription . '.',
				'Return type of call to ' . $callableDescription . ' contains unresolvable type.',
				'%s of ' . $callableDescription . ' contains unresolvable type.',
				ucfirst($callableDescription) . ' invoked with %s, but it\'s not allowed because of @no-named-arguments.',
				'Constant %s is not allowed for %s of ' . $callableDescription . '.',
				'Constants %s cannot be combined for %s of ' . $callableDescription . '.',
				'Combining constants with | is not allowed for %s of ' . $callableDescription . '.',
				null,
				null,
				$namedArgumentsVariants !== null ? $parametersAcceptors : [],
			),
		);
	}

	/**
	 * A first-class callable of a multi-variant built-in is a union of closures, one per variant,
	 * but PHP resolves named arguments against the one signature of its callee.
	 *
	 * @return list<ParametersAcceptor>|null
	 */
	private function getCalleeNamedArgumentsVariants(Type $type, Node\Expr\FuncCall $node, Scope $scope): ?array
	{
		$members = $type instanceof UnionType ? $type->getTypes() : [$type];
		$origin = null;
		foreach ($members as $member) {
			if (!$member instanceof ClosureType) {
				return null;
			}
			$memberOrigin = $member->getOrigin();
			if ($memberOrigin === null || !$memberOrigin->isFirstClassCallable()) {
				return null;
			}
			if ($origin !== null && !$origin->equals($memberOrigin)) {
				return null;
			}
			$origin = $memberOrigin;
		}
		if ($origin === null) {
			return null;
		}

		$call = $origin->createCall($node, $members[0]);
		if ($call instanceof Node\Expr\FuncCall && $call->name instanceof Node\Name) {
			if (!$this->reflectionProvider->hasFunction($call->name, $scope)) {
				return null;
			}

			return $this->reflectionProvider->getFunction($call->name, $scope)->getNamedArgumentsVariants();
		}
		if ($call instanceof Node\Expr\MethodCall) {
			$calledOn = $call->var;
		} elseif ($call instanceof Node\Expr\StaticCall) {
			$calledOn = $call->class;
		} else {
			return null;
		}
		if (!$calledOn instanceof TypeExpr || !$call->name instanceof Node\Identifier) {
			return null;
		}

		return $scope->getMethodReflection($calledOn->getExprType(), $call->name->toString())?->getNamedArgumentsVariants();
	}

	/**
	 * Whether every closure the type can be is a variadic one a partial function application
	 * created with the parameter bound - its call then throws "Named parameter overwrites
	 * previous argument". A closure that is not variadic reports the name as unknown instead.
	 */
	private static function isBoundByPartialApplication(Type $type, string $name): bool
	{
		$members = $type instanceof UnionType ? $type->getTypes() : [$type];
		foreach ($members as $member) {
			if (!$member instanceof ClosureType || !$member->isVariadic()) {
				return false;
			}
			$origin = $member->getOrigin();
			if ($origin === null || !in_array($name, $origin->getBoundParameterNames(), true)) {
				return false;
			}
		}

		return true;
	}

}
