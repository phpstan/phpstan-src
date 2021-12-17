<?php declare(strict_types = 1);

namespace PHPStan\Rules\Namespaces;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Stmt\Use_>
 */
class ExistingNamesInUseRule implements Rule
{

	private ReflectionProvider $reflectionProvider;

	private ClassCaseSensitivityCheck $classCaseSensitivityCheck;

	private bool $checkFunctionNameCase;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		bool $checkFunctionNameCase
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->checkFunctionNameCase = $checkFunctionNameCase;
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
	 * @param Node\Stmt\UseUse[] $uses
	 * @return RuleError[]
	 */
	private function checkConstants(array $uses): array
	{
		$errors = [];
		foreach ($uses as $use) {
			if ($this->reflectionProvider->hasConstant($use->name, null)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf('Used constant %s not found.', (string) $use->name))->line($use->name->getLine())->discoveringSymbolsTip()->build();
		}

		return $errors;
	}

	/**
	 * @param Node\Stmt\UseUse[] $uses
	 * @return RuleError[]
	 */
	private function checkFunctions(array $uses): array
	{
		$errors = [];
		foreach ($uses as $use) {
			if (!$this->reflectionProvider->hasFunction($use->name, null)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Used function %s not found.', (string) $use->name))->line($use->name->getLine())->discoveringSymbolsTip()->build();
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
					))->line($use->name->getLine())->build();
				}
			}
		}

		return $errors;
	}

	/**
	 * @param Node\Stmt\UseUse[] $uses
	 * @return RuleError[]
	 */
	private function checkClasses(array $uses): array
	{
		return $this->classCaseSensitivityCheck->checkClassNames(
			array_map(static fn (Node\Stmt\UseUse $use): ClassNameNodePair => new ClassNameNodePair((string) $use->name, $use->name), $uses),
		);
	}

}
