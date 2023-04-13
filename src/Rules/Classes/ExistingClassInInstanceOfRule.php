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
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\Instanceof_>
 */
class ExistingClassInInstanceOfRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private bool $checkClassCaseSensitivity,
	)
	{
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
					RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $lowercaseName))
						->identifier(sprintf('outOfClass.%s', $lowercaseName))
						->line($class->getLine())
						->build(),
				];
			}

			return [];
		}

		$errors = [];

		if (!$this->reflectionProvider->hasClass($name)) {
			if ($scope->isInClassExists($name)) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf('Class %s not found.', $name))
					->identifier('class.notFound')
					->line($class->getLine())
					->discoveringSymbolsTip()
					->build(),
			];
		} elseif ($this->checkClassCaseSensitivity) {
			$errors = array_merge(
				$errors,
				$this->classCaseSensitivityCheck->checkClassNames([new ClassNameNodePair($name, $class)]),
			);
		}

		$classReflection = $this->reflectionProvider->getClass($name);

		if ($classReflection->isTrait()) {
			$expressionType = $scope->getType($node->expr);

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Instanceof between %s and trait %s will always evaluate to false.',
				$expressionType->describe(VerbosityLevel::typeOnly()),
				$name,
			))->identifier('instanceof.trait')->build();
		}

		return $errors;
	}

}
