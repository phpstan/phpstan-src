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
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Reflection\PartialFunctionApplicationParameters;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<FunctionCallableNode>
 */
#[RegisteredRule(level: 0)]
final class FunctionCallableRule implements Rule
{

	/**
	 * PHP throws "Cannot call %s() dynamically" when these are called through
	 * a closure: a first-class callable of them throws on every call,
	 * a partial application of them already when it is created.
	 */
	private const DYNAMIC_CALL_FORBIDDEN_FUNCTIONS = [
		'compact',
		'extract',
		'func_get_arg',
		'func_get_args',
		'func_num_args',
		'get_defined_vars',
	];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private RuleLevelHelper $ruleLevelHelper,
		private FunctionCallParametersCheck $parametersCheck,
		#[AutowiredParameter]
		private bool $checkFunctionNameCase,
		#[AutowiredParameter]
		private bool $reportMaybes,
	)
	{
	}

	public function getNodeType(): string
	{
		return FunctionCallableNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if (!$node->getOriginalNode()->isFirstClassCallable()) {
			if (!$scope->getPhpVersion()->supportsPartialFunctionApplication()->yes()) {
				return [
					RuleErrorBuilder::message('Partial function application is supported only on PHP 8.6 and later.')
						->nonIgnorable()
						->identifier('callable.notSupported')
						->build(),
				];
			}
		} elseif (!$scope->getPhpVersion()->supportsFirstClassCallables()->yes()) {
			return [
				RuleErrorBuilder::message('First-class callables are supported only on PHP 8.1 and later.')
					->nonIgnorable()
					->identifier('callable.notSupported')
					->build(),
			];
		}

		$functionName = $node->getName();
		if ($functionName instanceof Node\Name) {
			$functionNameName = $functionName->toString();
			if ($this->reflectionProvider->hasFunction($functionName, $scope)) {
				$function = $this->reflectionProvider->getFunction($functionName, $scope);
				$errors = [];
				if ($this->checkFunctionNameCase) {
					/** @var string $calledFunctionName */
					$calledFunctionName = $this->reflectionProvider->resolveFunctionName($functionName, $scope);
					if (
						strtolower($function->getName()) === strtolower($calledFunctionName)
						&& $function->getName() !== $calledFunctionName
					) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'Call to function %s() with incorrect case: %s',
							$function->getName(),
							$functionNameName,
						))->identifier('function.nameCase')->build();
					}
				}

				if (in_array(strtolower($function->getName()), self::DYNAMIC_CALL_FORBIDDEN_FUNCTIONS, true)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot create callable from %s(), it cannot be called dynamically.',
						$function->getName(),
					))->identifier('callable.notSupported')->build();
				}

				if (!$node->getOriginalNode()->isFirstClassCallable()) {
					$errors = array_merge($errors, $this->parametersCheck->checkPartialApplication(
						PartialFunctionApplicationParameters::selectVariant($node->getArgs(), $function->getVariants(), $function->getNamedArgumentsVariants()),
						$scope,
						$function->isBuiltin(),
						$node->getOriginalNode(),
						'function',
						$function->acceptsNamedArguments(),
						'function ' . SprintfHelper::escapeFormatString($function->getName()),
						$function,
					));
				}

				return $errors;
			}

			if ($scope->isInFunctionExists($functionNameName)) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf('Function %s not found.', $functionNameName))
					->identifier('function.notFound')
					->build(),
			];
		}

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $functionName),
			'Creating callable from an unknown class %s.',
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
					sprintf('Creating callable from %s but it\'s not a callable.', $type->describe(VerbosityLevel::value())),
				)->identifier('callable.nonCallable')->build(),
			];
		}
		if ($this->reportMaybes && $isCallable->maybe()) {
			return [
				RuleErrorBuilder::message(
					sprintf('Creating callable from %s but it might not be a callable.', $type->describe(VerbosityLevel::value())),
				)->identifier('callable.nonCallable')->build(),
			];
		}

		if ($node->getOriginalNode()->isFirstClassCallable() || !$isCallable->yes()) {
			return [];
		}

		$parametersAcceptors = $type->getCallableParametersAcceptors($scope);
		$acceptsNamedArguments = TrinaryLogic::createYes();
		foreach ($parametersAcceptors as $parametersAcceptor) {
			$acceptsNamedArguments = $acceptsNamedArguments->and($parametersAcceptor->acceptsNamedArguments());
		}

		return $this->parametersCheck->checkPartialApplication(
			PartialFunctionApplicationParameters::selectVariant($node->getArgs(), $parametersAcceptors, null),
			$scope,
			false,
			$node->getOriginalNode(),
			'callable',
			$acceptsNamedArguments,
			$type instanceof ClosureType ? 'closure' : sprintf('callable %s', SprintfHelper::escapeFormatString($type->describe(VerbosityLevel::value()))),
			null,
		);
	}

}
