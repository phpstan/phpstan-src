<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use function array_key_exists;
use function count;
use function sprintf;

/**
 * Reports replace functions called with an array of replacements that is shorter
 * than the array of search values. PHP silently replaces the remaining search values
 * with an empty string, which is usually a mistake.
 *
 * @implements Rule<Node\Expr\FuncCall>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 5),
	if: '%featureToggles.replaceCountMismatch%',
)]
final class ReplaceCountMismatchRule implements Rule
{

	private const FUNCTIONS = [
		'str_replace' => true,
		'str_ireplace' => true,
		'preg_replace' => true,
		'preg_filter' => true,
	];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (count($node->getArgs()) < 2) {
			return [];
		}

		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		$functionName = $functionReflection->getName();
		if (!array_key_exists($functionName, self::FUNCTIONS)) {
			return [];
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$node->getArgs(),
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);

		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);
		if ($normalizedFuncCall === null) {
			return [];
		}

		$args = $normalizedFuncCall->getArgs();
		if (count($args) < 2) {
			return [];
		}

		$searchType = $scope->getType($args[0]->value);
		$replaceType = $scope->getType($args[1]->value);
		if (!$this->hasFewerReplacements($searchType, $replaceType)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Call to %s is using fewer replacements than search values.',
				$functionName,
			))->identifier('argument.replaceCount')->build(),
		];
	}

	private function hasFewerReplacements(Type $searchType, Type $replaceType): bool
	{
		if (!$searchType->isArray()->yes() || !$replaceType->isArray()->yes()) {
			return false;
		}

		// an explicitly empty array of replacements is a deliberate way to remove all search values
		if ($replaceType->isIterableAtLeastOnce()->no()) {
			return false;
		}

		return $replaceType->getArraySize()->isSmallerThan($searchType->getArraySize(), $this->phpVersion)->yes();
	}

}
