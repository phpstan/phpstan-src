<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\Instanceof_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\Instanceof_>
 */
class ExistingClassInInstanceOfRule implements Rule
{

	private ReflectionProvider $reflectionProvider;

	private ClassCaseSensitivityCheck $classCaseSensitivityCheck;

	private bool $checkClassCaseSensitivity;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		bool $checkClassCaseSensitivity,
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->checkClassCaseSensitivity = $checkClassCaseSensitivity;
	}

	public function getNodeType(): string
	{
		return Instanceof_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$class = $node->class;
		if (!($class instanceof Node\Name)) {
			return [];
		}

		$name = (string) $class;
		$lowercaseName = strtolower($name);

		if (in_array($lowercaseName, [
			'self',
			'static',
			'parent',
		], true)) {
			if (!$scope->isInClass()) {
				return [
					RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $lowercaseName))->line($class->getLine())->build(),
				];
			}

			return [];
		}

		if (!$this->reflectionProvider->hasClass($name)) {
			if ($scope->isInClassExists($name)) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf('Class %s not found.', $name))->line($class->getLine())->discoveringSymbolsTip()->build(),
			];
		} elseif ($this->checkClassCaseSensitivity) {
			return $this->classCaseSensitivityCheck->checkClassNames([new ClassNameNodePair($name, $class)]);
		}

		return [];
	}

}
