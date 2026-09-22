<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use function count;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[RegisteredRule(level: 0)]
final class CallToFunctionParametersRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider, private FunctionCallParametersCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$function = $this->reflectionProvider->getFunction($node->name, $scope);
		$functionName = SprintfHelper::escapeFormatString($function->getName());

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$node->getArgs(),
			$function->getVariants(),
			$function->getNamedArgumentsVariants(),
		);

		return $this->check->check(
			self::getVariantForArgumentsCount($node->getArgs(), $function->getVariants(), $parametersAcceptor) ?? $parametersAcceptor,
			$scope,
			$function->isBuiltin(),
			$node,
			'function',
			$function->acceptsNamedArguments(),
			'Function ' . $functionName . ' invoked with %d parameter, %d required.',
			'Function ' . $functionName . ' invoked with %d parameters, %d required.',
			'Function ' . $functionName . ' invoked with %d parameter, at least %d required.',
			'Function ' . $functionName . ' invoked with %d parameters, at least %d required.',
			'Function ' . $functionName . ' invoked with %d parameter, %d-%d required.',
			'Function ' . $functionName . ' invoked with %d parameters, %d-%d required.',
			'%s of function ' . $functionName . ' expects %s, %s given.',
			'Result of function ' . $functionName . ' (void) is used.',
			'%s of function ' . $functionName . ' is passed by reference, so it expects variables only.',
			'Unable to resolve the template type %s in call to function ' . $functionName,
			'Missing parameter $%s in call to function ' . $functionName . '.',
			'Unknown parameter $%s in call to function ' . $functionName . '.',
			'Return type of call to function ' . $functionName . ' contains unresolvable type.',
			'%s of function ' . $functionName . ' contains unresolvable type.',
			'Function ' . $functionName . ' invoked with %s, but it\'s not allowed because of @no-named-arguments.',
			'Constant %s is not allowed for %s of function ' . $functionName . '.',
			'Constants %s cannot be combined for %s of function ' . $functionName . '.',
			'Combining constants with | is not allowed for %s of function ' . $functionName . '.',
			null,
		);
	}

	/**
	 * When no variant accepts the number of arguments, they are combined into
	 * one whose parameters missing from some variant are optional, so mt_rand()
	 * with the variants () and ($min, $max) would accept a single argument.
	 * Such a count is checked against the variant closest to it instead.
	 *
	 * @param Node\Arg[] $args
	 * @param list<ParametersAcceptor> $variants
	 */
	private static function getVariantForArgumentsCount(array $args, array $variants, ParametersAcceptor $selectedParametersAcceptor): ?ParametersAcceptor
	{
		if (count($variants) < 2) {
			return null;
		}

		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return null;
			}
		}

		$argsCount = count($args);
		if (!self::acceptsArgumentsCount($selectedParametersAcceptor, $argsCount)) {
			return null;
		}

		$closestVariant = null;
		$closestDistance = null;
		foreach ($variants as $variant) {
			if (self::acceptsArgumentsCount($variant, $argsCount)) {
				return null;
			}

			[$minCount, $maxCount] = self::getArgumentsCountRange($variant);
			$isInsufficient = $argsCount < $minCount;
			$distance = $isInsufficient ? $minCount - $argsCount : $argsCount - (int) $maxCount;
			if ($closestDistance !== null && ($distance > $closestDistance || ($distance === $closestDistance && !$isInsufficient))) {
				continue;
			}

			$closestVariant = $variant;
			$closestDistance = $distance;
		}

		return $closestVariant;
	}

	private static function acceptsArgumentsCount(ParametersAcceptor $parametersAcceptor, int $argsCount): bool
	{
		[$minCount, $maxCount] = self::getArgumentsCountRange($parametersAcceptor);

		return $argsCount >= $minCount && ($maxCount === null || $argsCount <= $maxCount);
	}

	/**
	 * @return array{int, int|null}
	 */
	private static function getArgumentsCountRange(ParametersAcceptor $parametersAcceptor): array
	{
		$minCount = 0;
		foreach ($parametersAcceptor->getParameters() as $parameter) {
			if ($parameter->isOptional()) {
				continue;
			}

			$minCount++;
		}

		return [$minCount, $parametersAcceptor->isVariadic() ? null : count($parametersAcceptor->getParameters())];
	}

}
