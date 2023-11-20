<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\PhpDoc\Tag\UsesTag;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use function array_map;
use function sprintf;
use function strtolower;
use function ucfirst;

/**
 * @implements Rule<Node\Stmt\TraitUse>
 */
class UsedTraitsRule implements Rule
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
		private GenericAncestorsCheck $genericAncestorsCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\TraitUse::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$className = $scope->getClassReflection()->getName();
		$traitName = null;
		if ($scope->isInTrait()) {
			$traitName = $scope->getTraitReflection()->getName();
		}
		$useTags = [];
		$docComment = $node->getDocComment();
		if ($docComment !== null) {
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$scope->getFile(),
				$className,
				$traitName,
				null,
				$docComment->getText(),
			);
			$useTags = $resolvedPhpDoc->getUsesTags();
		}

		$typeDescription = strtolower($scope->getClassReflection()->getClassTypeDescription());
		$description = sprintf('%s %s', $typeDescription, SprintfHelper::escapeFormatString($className));
		if ($traitName !== null) {
			$typeDescription = 'trait';
			$description = sprintf('%s %s', $typeDescription, SprintfHelper::escapeFormatString($traitName));
		}

		return $this->genericAncestorsCheck->check(
			$node->traits,
			array_map(static fn (UsesTag $tag): Type => $tag->getType(), $useTags),
			sprintf('%s @use tag contains incompatible type %%s.', ucfirst($description)),
			sprintf('%s has @use tag, but does not use any trait.', ucfirst($description)),
			sprintf('The @use tag of %s describes %%s but the %s uses %%s.', $description, $typeDescription),
			'PHPDoc tag @use contains generic type %s but %s %s is not generic.',
			'Generic type %s in PHPDoc tag @use does not specify all template types of %s %s: %s',
			'Generic type %s in PHPDoc tag @use specifies %d template types, but %s %s supports only %d: %s',
			'Type %s in generic type %s in PHPDoc tag @use is not subtype of template type %s of %s %s.',
			'Call-site variance annotation of %s in generic type %s in PHPDoc tag @use is not allowed.',
			'PHPDoc tag @use has invalid type %s.',
			sprintf('%s uses generic trait %%s but does not specify its types: %%s', ucfirst($description)),
			sprintf('in used type %%s of %s', $description),
		);
	}

}
