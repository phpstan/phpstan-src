<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Internal\SprintfHelper;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\Tag\UsesTag;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use function array_map;
use function count;
use function sprintf;
use function ucfirst;

/**
 * Validates `@use` tags in class-level PHPDocs, the counterpart of UsedTraitsRule
 * which validates `@use` tags above individual trait use statements.
 *
 * @implements Rule<Node\Stmt\ClassLike>
 */
#[RegisteredRule(level: 2)]
#[ValidatesStubFiles]
final class ClassLevelUsedTraitsRule implements Rule
{

	public function __construct(
		private PhpDocStringResolver $phpDocStringResolver,
		private FileTypeMapper $fileTypeMapper,
		private GenericAncestorsCheck $genericAncestorsCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		if (!isset($node->namespacedName)) {
			// anonymous class
			return [];
		}

		if ($node instanceof Node\Stmt\Class_) {
			$typeDescription = 'class';
		} elseif ($node instanceof Node\Stmt\Interface_) {
			$typeDescription = 'interface';
		} elseif ($node instanceof Node\Stmt\Trait_) {
			$typeDescription = 'trait';
		} elseif ($node instanceof Node\Stmt\Enum_) {
			$typeDescription = 'enum';
		} else {
			return [];
		}

		// resolving the PHPDoc types of every class-like is not for free and can
		// change the outcome of recursion detection in phpdoc type resolution,
		// so bail out early unless the doc comment really contains @use tags
		$phpDocNode = $this->phpDocStringResolver->resolve($docComment->getText());
		$hasUsesTagValues = false;
		foreach (['@use', '@template-use', '@phpstan-use'] as $tagName) {
			if (count($phpDocNode->getUsesTagValues($tagName)) > 0) {
				$hasUsesTagValues = true;
				break;
			}
		}
		if (!$hasUsesTagValues) {
			return [];
		}

		$className = (string) $node->namespacedName;
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$className,
			null,
			null,
			$docComment->getText(),
		);
		$useTags = $resolvedPhpDoc->getUsesTags();
		if (count($useTags) === 0) {
			return [];
		}

		$traitNames = [];
		foreach ($node->getTraitUses() as $traitUse) {
			foreach ($traitUse->traits as $traitNameNode) {
				$traitNames[] = $traitNameNode;
			}
		}

		$description = sprintf('%s %s', $typeDescription, SprintfHelper::escapeFormatString($className));
		$escapedDescription = SprintfHelper::escapeFormatString($description);
		$upperCaseDescription = ucfirst($description);
		$escapedUpperCaseDescription = SprintfHelper::escapeFormatString($upperCaseDescription);

		return $this->genericAncestorsCheck->check(
			$traitNames,
			array_map(static fn (UsesTag $tag): Type => $tag->getType(), $useTags),
			sprintf('%s @use tag contains incompatible type %%s.', $escapedUpperCaseDescription),
			sprintf('%s @use tag contains unresolvable type.', $upperCaseDescription),
			sprintf('%s has @use tag, but does not use any trait.', $upperCaseDescription),
			sprintf('The @use tag of %s describes %%s but the %s uses %%s.', $escapedDescription, $typeDescription),
			'PHPDoc tag @use contains generic type %s but %s %s is not generic.',
			'Generic type %s in PHPDoc tag @use does not specify all template types of %s %s: %s',
			'Generic type %s in PHPDoc tag @use specifies %d template types, but %s %s supports only %d: %s',
			'Type %s in generic type %s in PHPDoc tag @use is not subtype of template type %s of %s %s.',
			'Call-site variance annotation of %s in generic type %s in PHPDoc tag @use is not allowed.',
			'PHPDoc tag @use has invalid type %s.',
			sprintf('%s uses generic trait %%s but does not specify its types: %%s', $escapedUpperCaseDescription),
			sprintf('in used type %%s of %s', $escapedDescription),
			// the missing-generics check is left entirely to UsedTraitsRule, which runs
			// for every trait use statement and takes class-level @use tags into account
			array_map(static fn (Node\Name $traitNameNode): string => $traitNameNode->toString(), $traitNames),
		);
	}

}
