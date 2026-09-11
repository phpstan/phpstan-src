<?php declare(strict_types = 1);

namespace PHPStan\Rules\Names;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Enum_;
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
	 * @param array<string, string[]> $declaredNames names of classes declared anywhere in the file, by namespace
	 * @param string[] $currentScopeNames names taken in the current namespace declaration
	 * @return list<IdentifierRuleError>
	 */
	private function findErrorsForNode(Node $node, string $namespace, array &$declaredNames, array &$currentScopeNames): array
	{
		if ($node instanceof Use_) {
			if ($this->shouldBeIgnored($node)) {
				return [];
			}
			return $this->findErrorsInUses($node->uses, '', $currentScopeNames);
		}

		if ($node instanceof GroupUse) {
			if ($this->shouldBeIgnored($node)) {
				return [];
			}
			$useGroupPrefix = $node->prefix->toString();
			return $this->findErrorsInUses($node->uses, $useGroupPrefix, $currentScopeNames);
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
				in_array($name, $currentScopeNames, true)
				|| in_array($name, $declaredNames[$lowerNamespace] ?? [], true)
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
			$currentScopeNames[] = $name;
			$declaredNames[$lowerNamespace][] = $name;
			return [];
		}

		return [];
	}

	/**
	 * @param Node\UseItem[] $uses
	 * @param string[] $currentScopeNames
	 * @return list<IdentifierRuleError>
	 */
	private function findErrorsInUses(array $uses, string $useGroupPrefix, array &$currentScopeNames): array
	{
		$errors = [];
		foreach ($uses as $use) {
			if ($this->shouldBeIgnored($use)) {
				continue;
			}
			$useAlias = $use->getAlias()->toLowerString();
			if (in_array($useAlias, $currentScopeNames, true)) {
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
			$currentScopeNames[] = $useAlias;
		}
		return $errors;
	}

	private function shouldBeIgnored(Use_|GroupUse|Node\UseItem $use): bool
	{
		return in_array($use->type, [Use_::TYPE_FUNCTION, Use_::TYPE_CONSTANT], true);
	}

}
