<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Class_>
 */
class ClassAncestorsRule implements Rule
{

	/** @var \PHPStan\Type\FileTypeMapper */
	private $fileTypeMapper;

	/** @var \PHPStan\Rules\Generics\GenericAncestorsCheck */
	private $genericAncestorsCheck;

	public function __construct(
		FileTypeMapper $fileTypeMapper,
		GenericAncestorsCheck $genericAncestorsCheck
	)
	{
		$this->fileTypeMapper = $fileTypeMapper;
		$this->genericAncestorsCheck = $genericAncestorsCheck;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!isset($node->namespacedName)) {
			// anonymous class
			return [];
		}

		$className = (string) $node->namespacedName;

		$extendsTags = [];
		$implementsTags = [];
		$docComment = $node->getDocComment();
		if ($docComment !== null) {
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$scope->getFile(),
				$className,
				null,
				null,
				$docComment->getText()
			);
			$extendsTags = $resolvedPhpDoc->getExtendsTags();
			$implementsTags = $resolvedPhpDoc->getImplementsTags();
		}

		$extendsErrors = $this->genericAncestorsCheck->check(
			$node->extends !== null ? [$node->extends] : [],
			array_map(static function (ExtendsTag $tag): Type {
				return $tag->getType();
			}, $extendsTags),
			sprintf('Class %s @extends tag contains incompatible type %%s.', $className),
			sprintf('Class %s has @extends tag, but does not extend any class.', $className),
			sprintf('The @extends tag of class %s describes %%s but the class extends %%s.', $className),
			'PHPDoc tag @extends contains generic type %s but class %s is not generic.',
			'Generic type %s in PHPDoc tag @extends does not specify all template types of class %s: %s',
			'Generic type %s in PHPDoc tag @extends specifies %d template types, but class %s supports only %d: %s',
			'Type %s in generic type %s in PHPDoc tag @extends is not subtype of template type %s of class %s.',
			'PHPDoc tag @extends has invalid type %s.',
			sprintf('Class %s extends generic class %%s but does not specify its types: %%s', $className)
		);

		$implementsErrors = $this->genericAncestorsCheck->check(
			$node->implements,
			array_map(static function (ImplementsTag $tag): Type {
				return $tag->getType();
			}, $implementsTags),
			sprintf('Class %s @implements tag contains incompatible type %%s.', $className),
			sprintf('Class %s has @implements tag, but does not implement any interface.', $className),
			sprintf('The @implements tag of class %s describes %%s but the class implements: %%s', $className),
			'PHPDoc tag @implements contains generic type %s but interface %s is not generic.',
			'Generic type %s in PHPDoc tag @implements does not specify all template types of interface %s: %s',
			'Generic type %s in PHPDoc tag @implements specifies %d template types, but interface %s supports only %d: %s',
			'Type %s in generic type %s in PHPDoc tag @implements is not subtype of template type %s of interface %s.',
			'PHPDoc tag @implements has invalid type %s.',
			sprintf('Class %s implements generic interface %%s but does not specify its types: %%s', $className)
		);

		return array_merge($extendsErrors, $implementsErrors);
	}

}
