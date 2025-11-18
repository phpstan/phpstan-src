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
		$usedNames = [];
		$errors = [];
		foreach ($node->getNodes() as $oneNode) {
			if ($oneNode instanceof Namespace_) {
				$namespaceName = $oneNode->name !== null ? $oneNode->name->toString() : '';
				foreach ($oneNode->stmts as $stmt) {
					foreach ($this->findErrorsForNode($stmt, $namespaceName, $usedNames) as $error) {
						$errors[] = $error;
					}
				}
				continue;
			}

			foreach ($this->findErrorsForNode($oneNode, '', $usedNames) as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

	/**
	 * @param array<Use_::TYPE_*, array<string, list<string>>>  $usedNames
	 * @return list<IdentifierRuleError>
	 */
	private function findErrorsForNode(Node $node, string $namespace, array &$usedNames): array
	{
		$lowerNamespace = strtolower($namespace);
		if ($node instanceof Use_) {
			return $this->findErrorsInUses($node->uses, '', $lowerNamespace, $usedNames, $node->type);
		}

		if ($node instanceof GroupUse) {
			$useGroupPrefix = $node->prefix->toString();
			return $this->findErrorsInUses($node->uses, $useGroupPrefix, $lowerNamespace, $usedNames, $node->type);
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
			$name = $node->name->toLowerString();
			if (in_array($name, $usedNames[Use_::TYPE_NORMAL][$lowerNamespace] ?? [], true)) {
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
			$usedNames[Use_::TYPE_NORMAL][$lowerNamespace][] = $name;
			return [];
		}

		if ($node instanceof Function_) {
			$name = $node->name->toLowerString();
			if (in_array($name, $usedNames[Use_::TYPE_FUNCTION][$lowerNamespace] ?? [], true)) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Cannot declare function %s() because the name is already in use.',
						$namespace !== '' ? $namespace . '\\' . $node->name->toString() : $node->name->toString(),
					))
						->identifier('function.nameInUse')
						->line($node->getStartLine())
						->nonIgnorable()
						->build(),
				];
			}
			$usedNames[Use_::TYPE_FUNCTION][$lowerNamespace][] = $name;
			return [];
		}

		if ($node instanceof Const_) {
			$errors = [];
			foreach ($node->consts as $constNode) {
				$name = $constNode->name->toLowerString();
				if (in_array($name, $usedNames[Use_::TYPE_CONSTANT][$lowerNamespace] ?? [], true)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot declare constant %s because the name is already in use.',
						$namespace !== '' ? $namespace . '\\' . $constNode->name->toString() : $constNode->name->toString(),
					))
						->identifier('const.nameInUse')
						->line($constNode->getStartLine())
						->nonIgnorable()
						->build();
				}
				$usedNames[Use_::TYPE_CONSTANT][$lowerNamespace][] = $name;
			}
			return $errors;
		}

		return [];
	}

	/**
	 * @param Node\UseItem[] $uses
	 * @param array<Use_::TYPE_*, array<string, list<string>>> $usedNames
	 * @param Use_::TYPE_*  $useType
	 * @return list<IdentifierRuleError>
	 */
	private function findErrorsInUses(array $uses, string $useGroupPrefix, string $lowerNamespace, array &$usedNames, int $useType): array
	{
		$errors = [];
		foreach ($uses as $use) {
			$realUseType = $this->getRealUseType($use->type, $useType);
			if ($realUseType === Use_::TYPE_UNKNOWN) {
				throw new ShouldNotHappenException();
			}
			$useAlias = $use->getAlias()->toLowerString();
			if (in_array($useAlias, $usedNames[$realUseType][$lowerNamespace] ?? [], true)) {
				if ($realUseType === Use_::TYPE_FUNCTION) {
					$displayedUseType = 'use function';
					$identifierUseType = 'useFunction';
				} elseif ($realUseType === Use_::TYPE_CONSTANT) {
					$displayedUseType = 'use const';
					$identifierUseType = 'useConst';
				} else {
					$displayedUseType = 'use';
					$identifierUseType = 'use';
				}
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Cannot %s %s as %s because the name is already in use.',
					$displayedUseType,
					$useGroupPrefix !== '' ? $useGroupPrefix . '\\' . $use->name->toString() : $use->name->toString(),
					$use->getAlias()->toString(),
				))
					->identifier(sprintf('%s.nameInUse', $identifierUseType))
					->line($use->getStartLine())
					->nonIgnorable()
					->build();
				continue;
			}
			$usedNames[$realUseType][$lowerNamespace][] = $useAlias;
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
