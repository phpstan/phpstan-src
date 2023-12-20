<?php declare(strict_types = 1);

namespace PHPStan\Rules\Namespaces;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Stmt\GroupUse>
 */
class ExistingNamesInGroupUseRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private bool $checkFunctionNameCase,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\GroupUse::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->uses as $use) {
			$error = null;

			/** @var Node\Name $name */
			$name = Node\Name::concat($node->prefix, $use->name, ['startLine' => $use->getStartLine()]);
			if (
				$node->type === Use_::TYPE_CONSTANT
				|| $use->type === Use_::TYPE_CONSTANT
			) {
				$error = $this->checkConstant($name);
			} elseif (
				$node->type === Use_::TYPE_FUNCTION
				|| $use->type === Use_::TYPE_FUNCTION
			) {
				$error = $this->checkFunction($name);
			} elseif ($use->type === Use_::TYPE_NORMAL) {
				$error = $this->checkClass($name);
			} else {
				throw new ShouldNotHappenException();
			}

			if ($error === null) {
				continue;
			}

			$errors[] = $error;
		}

		return $errors;
	}

	private function checkConstant(Node\Name $name): ?IdentifierRuleError
	{
		if (!$this->reflectionProvider->hasConstant($name, null)) {
			return RuleErrorBuilder::message(sprintf('Used constant %s not found.', (string) $name))
				->discoveringSymbolsTip()
				->line($name->getStartLine())
				->identifier('constant.notFound')
				->build();
		}

		return null;
	}

	private function checkFunction(Node\Name $name): ?IdentifierRuleError
	{
		if (!$this->reflectionProvider->hasFunction($name, null)) {
			return RuleErrorBuilder::message(sprintf('Used function %s not found.', (string) $name))
				->discoveringSymbolsTip()
				->line($name->getStartLine())
				->identifier('function.notFound')
				->build();
		}

		if ($this->checkFunctionNameCase) {
			$functionReflection = $this->reflectionProvider->getFunction($name, null);
			$realName = $functionReflection->getName();
			$usedName = (string) $name;
			if (
				strtolower($realName) === strtolower($usedName)
				&& $realName !== $usedName
			) {
				return RuleErrorBuilder::message(sprintf(
					'Function %s used with incorrect case: %s.',
					$realName,
					$usedName,
				))
					->line($name->getStartLine())
					->identifier('function.nameCase')
					->build();
			}
		}

		return null;
	}

	private function checkClass(Node\Name $name): ?IdentifierRuleError
	{
		$errors = $this->classCaseSensitivityCheck->checkClassNames([
			new ClassNameNodePair((string) $name, $name),
		]);
		if (count($errors) === 0) {
			return null;
		} elseif (count($errors) === 1) {
			return $errors[0];
		}

		throw new ShouldNotHappenException();
	}

}
