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
use PhpParser\Node\Stmt\UseUse;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<FileNode>
 */
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
	 * @param array<string, string[]> $usedNames
	 * @return RuleError[]
	 */
	private function findErrorsForNode(Node $node, string $namespace, array &$usedNames): array
	{
		$lowerNamespace = strtolower($namespace);
		if ($node instanceof Use_) {
			if ($this->shouldBeIgnored($node)) {
				return [];
			}
			return $this->findErrorsInUses($node->uses, '', $lowerNamespace, $usedNames);
		}

		if ($node instanceof GroupUse) {
			if ($this->shouldBeIgnored($node)) {
				return [];
			}
			$useGroupPrefix = $node->prefix->toString();
			return $this->findErrorsInUses($node->uses, $useGroupPrefix, $lowerNamespace, $usedNames);
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
			if (in_array($name, $usedNames[$lowerNamespace] ?? [], true)) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Cannot declare %s %s because the name is already in use.',
						$type,
						$namespace !== '' ? $namespace . '\\' . $node->name->toString() : $node->name->toString(),
					))
						->line($node->getLine())
						->nonIgnorable()
						->build(),
				];
			}
			$usedNames[$lowerNamespace][] = $name;
			return [];
		}

		return [];
	}

	/**
	 * @param UseUse[] $uses
	 * @param array<string, string[]> $usedNames
	 * @return RuleError[]
	 */
	private function findErrorsInUses(array $uses, string $useGroupPrefix, string $lowerNamespace, array &$usedNames): array
	{
		$errors = [];
		foreach ($uses as $use) {
			if ($this->shouldBeIgnored($use)) {
				continue;
			}
			$useAlias = $use->getAlias()->toLowerString();
			if (in_array($useAlias, $usedNames[$lowerNamespace] ?? [], true)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Cannot use %s as %s because the name is already in use.',
					$useGroupPrefix !== '' ? $useGroupPrefix . '\\' . $use->name->toString() : $use->name->toString(),
					$use->getAlias()->toString(),
				))
					->line($use->getLine())
					->nonIgnorable()
					->build();
				continue;
			}
			$usedNames[$lowerNamespace][] = $useAlias;
		}
		return $errors;
	}

	private function shouldBeIgnored(Use_|GroupUse|UseUse $use): bool
	{
		return in_array($use->type, [Use_::TYPE_FUNCTION, Use_::TYPE_CONSTANT], true);
	}

}
