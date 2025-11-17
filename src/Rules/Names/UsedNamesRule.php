<?php declare(strict_types = 1);

namespace PHPStan\Rules\Names;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\FileNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<FileNode>
 */
#[RegisteredRule(level: 0)]
final class UsedNamesRule implements Rule
{

	public function getNodeType(): string
	{
		return FileNode::class;
	}

	/**
	 * @param FileNode $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$declaredNames = [];
		$fileScopeNames = [];
		$errors = [];
		foreach ($node->getNodes() as $oneNode) {
			if ($oneNode instanceof Namespace_) {
				$namespaceName = $oneNode->name !== null ? $oneNode->name->toString() : '';

				// A namespace declaration starts a new import scope, even when the namespace
				// has already been declared earlier in the same file.
				$namespaceScopeNames = [];
				foreach ($oneNode->stmts as $stmt) {
					foreach ($this->findErrorsForNode($stmt, $namespaceName, $declaredNames, $namespaceScopeNames) as $error) {
						$errors[] = $error;
					}
				}
				continue;
			}

			foreach ($this->findErrorsForNode($oneNode, '', $declaredNames, $fileScopeNames) as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

	/**
	 * @param array<Use_::TYPE_*, array<string, list<string>>> $declaredNames names of classes, functions and constants declared anywhere in the file, by namespace
	 * @param array<Use_::TYPE_*, list<string>> $currentScopeNames names taken in the current namespace declaration
	 * @return list<IdentifierRuleError>
	 */
	private function findErrorsForNode(Node $node, string $namespace, array &$declaredNames, array &$currentScopeNames): array
	{
		if ($node instanceof Use_) {
			return $this->findErrorsInUses($node->uses, '', $currentScopeNames, $node->type);
		}

		if ($node instanceof GroupUse) {
			$useGroupPrefix = $node->prefix->toString();
			return $this->findErrorsInUses($node->uses, $useGroupPrefix, $currentScopeNames, $node->type);
		}

		if ($node instanceof ClassLike) {
			if ($node->name === null) {
				return [];
			}
			$type = 'class';
			if ($node instanceof Interface_) {
				$type = 'interface';
			} elseif ($node instanceof Trait_) {
				$type = 'trait';
			} elseif ($node instanceof Enum_) {
				$type = 'enum';
			}
			$lowerNamespace = strtolower($namespace);
			$name = $node->name->toLowerString();
			if (
				in_array($name, $currentScopeNames[Use_::TYPE_NORMAL] ?? [], true)
				|| in_array($name, $declaredNames[Use_::TYPE_NORMAL][$lowerNamespace] ?? [], true)
			) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Cannot declare %s %s because the name is already in use.',
						$type,
						$namespace !== '' ? $namespace . '\\' . $node->name->toString() : $node->name->toString(),
					))
						->identifier(sprintf('%s.nameInUse', $type))
						->line($node->getStartLine())
						->nonIgnorable()
						->build(),
				];
			}
			$currentScopeNames[Use_::TYPE_NORMAL][] = $name;
			$declaredNames[Use_::TYPE_NORMAL][$lowerNamespace][] = $name;
			return [];
		}

		if ($node instanceof Function_) {
			$lowerNamespace = strtolower($namespace);
			$name = $node->name->toLowerString();
			if (
				in_array($name, $currentScopeNames[Use_::TYPE_FUNCTION] ?? [], true)
				|| in_array($name, $declaredNames[Use_::TYPE_FUNCTION][$lowerNamespace] ?? [], true)
			) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Cannot declare function %s() because the name is already in use.',
						$namespace !== '' ? $namespace . '\\' . $node->name->toString() : $node->name->toString(),
					))
						->identifier('function.nameInUse')
						->line($node->getStartLine())
						->build(),
				];
			}
			$currentScopeNames[Use_::TYPE_FUNCTION][] = $name;
			$declaredNames[Use_::TYPE_FUNCTION][$lowerNamespace][] = $name;
			return [];
		}

		if ($node instanceof Const_) {
			$lowerNamespace = strtolower($namespace);
			$errors = [];
			foreach ($node->consts as $constNode) {
				$name = $constNode->name->toLowerString();
				if (
					in_array($name, $currentScopeNames[Use_::TYPE_CONSTANT] ?? [], true)
					|| in_array($name, $declaredNames[Use_::TYPE_CONSTANT][$lowerNamespace] ?? [], true)
				) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot declare constant %s because the name is already in use.',
						$namespace !== '' ? $namespace . '\\' . $constNode->name->toString() : $constNode->name->toString(),
					))
						->identifier('const.nameInUse')
						->line($constNode->getStartLine())
						->build();
				}
				$currentScopeNames[Use_::TYPE_CONSTANT][] = $name;
				$declaredNames[Use_::TYPE_CONSTANT][$lowerNamespace][] = $name;
			}
			return $errors;
		}

		return [];
	}

	/**
	 * @param Node\UseItem[] $uses
	 * @param array<Use_::TYPE_*, list<string>> $currentScopeNames
	 * @param Use_::TYPE_* $useType
	 * @return list<IdentifierRuleError>
	 */
	private function findErrorsInUses(array $uses, string $useGroupPrefix, array &$currentScopeNames, int $useType): array
	{
		$errors = [];
		foreach ($uses as $use) {
			$realUseType = $this->getRealUseType($use->type, $useType);
			if ($realUseType === Use_::TYPE_UNKNOWN) {
				throw new ShouldNotHappenException();
			}
			$useAlias = $use->getAlias()->toLowerString();
			if (in_array($useAlias, $currentScopeNames[$realUseType] ?? [], true)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Cannot use %s as %s because the name is already in use.',
					$useGroupPrefix !== '' ? $useGroupPrefix . '\\' . $use->name->toString() : $use->name->toString(),
					$use->getAlias()->toString(),
				))
					->identifier('use.nameInUse')
					->line($use->getStartLine())
					->nonIgnorable()
					->build();
				continue;
			}
			$currentScopeNames[$realUseType][] = $useAlias;
		}
		return $errors;
	}

	/**
	 * @param Use_::TYPE_* $useType
	 * @param Use_::TYPE_* $parentUseType
	 * @return Use_::TYPE_*
	 */
	private function getRealUseType(int $useType, int $parentUseType): int
	{
		if ($parentUseType === Use_::TYPE_UNKNOWN) {
			return $useType;
		}
		if ($useType === Use_::TYPE_UNKNOWN) {
			return $parentUseType;
		}
		if ($useType === $parentUseType) {
			return $useType;
		}
		return Use_::TYPE_UNKNOWN;
	}

}
