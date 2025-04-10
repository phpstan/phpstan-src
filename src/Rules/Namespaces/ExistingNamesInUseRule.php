<?php declare(strict_types = 1);

namespace PHPStan\Rules\Namespaces;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Stmt\Use_>
 */
final class ExistingNamesInUseRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassNameCheck $classCheck,
		private bool $checkFunctionNameCase,
		private bool $discoveringSymbolsTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Use_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->type === Node\Stmt\Use_::TYPE_UNKNOWN) {
			throw new ShouldNotHappenException();
		}

		foreach ($node->uses as $use) {
			if ($use->type !== Node\Stmt\Use_::TYPE_UNKNOWN) {
				throw new ShouldNotHappenException();
			}
		}

		if ($node->type === Node\Stmt\Use_::TYPE_CONSTANT) {
			return $this->checkConstants($node->uses);
		}

		if ($node->type === Node\Stmt\Use_::TYPE_FUNCTION) {
			return $this->checkFunctions($node->uses);
		}

		return $this->checkClasses($node->uses);
	}

	/**
	 * @param Node\UseItem[] $uses
	 * @return list<IdentifierRuleError>
	 */
	private function checkConstants(array $uses): array
	{
		$errors = [];
		foreach ($uses as $use) {
			if ($this->reflectionProvider->hasConstant($use->name, null)) {
				continue;
			}

			$errorBuilder = RuleErrorBuilder::message(sprintf('Used constant %s not found.', (string) $use->name))
				->line($use->name->getStartLine())
				->identifier('constant.notFound');

			if ($this->discoveringSymbolsTip) {
				$errorBuilder->discoveringSymbolsTip();
			}

			$errors[] = $errorBuilder->build();
		}

		return $errors;
	}

	/**
	 * @param Node\UseItem[] $uses
	 * @return list<IdentifierRuleError>
	 */
	private function checkFunctions(array $uses): array
	{
		$errors = [];
		foreach ($uses as $use) {
			if (!$this->reflectionProvider->hasFunction($use->name, null)) {
				$errorBuilder = RuleErrorBuilder::message(sprintf('Used function %s not found.', (string) $use->name))
					->line($use->name->getStartLine())
					->identifier('function.notFound');

				if ($this->discoveringSymbolsTip) {
					$errorBuilder->discoveringSymbolsTip();
				}

				$errors[] = $errorBuilder->build();
			} elseif ($this->checkFunctionNameCase) {
				$functionReflection = $this->reflectionProvider->getFunction($use->name, null);
				$realName = $functionReflection->getName();
				$usedName = (string) $use->name;
				if (
					strtolower($realName) === strtolower($usedName)
					&& $realName !== $usedName
				) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Function %s used with incorrect case: %s.',
						$realName,
						$usedName,
					))
						->line($use->name->getStartLine())
						->identifier('function.nameCase')
						->build();
				}
			}
		}

		return $errors;
	}

	/**
	 * @param Node\UseItem[] $uses
	 * @return list<IdentifierRuleError>
	 */
	private function checkClasses(array $uses): array
	{
		return $this->classCheck->checkClassNames(
			array_map(static fn (Node\UseItem $use): ClassNameNodePair => new ClassNameNodePair((string) $use->name, $use->name), $uses),
		);
	}

}
