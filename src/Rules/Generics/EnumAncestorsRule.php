<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InClassNode;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 2)]
#[ValidatesStubFiles]
final class EnumAncestorsRule implements Rule
{

	public function __construct(
		private GenericAncestorsCheck $genericAncestorsCheck,
		private CrossCheckInterfacesHelper $crossCheckInterfacesHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$originalNode = $node->getOriginalNode();
		if (!$originalNode instanceof Node\Stmt\Enum_) {
			return [];
		}
		$classReflection = $node->getClassReflection();

		$enumName = $classReflection->getName();
		$escapedEnumName = SprintfHelper::escapeFormatString($enumName);

		$extendsErrors = $this->genericAncestorsCheck->check(
			[],
			array_map(static fn (ExtendsTag $tag): Type => $tag->getType(), $classReflection->getExtendsTags()),
			sprintf('Enum %s @extends tag contains incompatible type %%s.', $escapedEnumName),
			sprintf('Enum %s @extends tag contains unresolvable type.', $enumName),
			sprintf('Enum %s has @extends tag, but cannot extend anything.', $escapedEnumName),
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
		);

		$implementsNames = $originalNode->implements;
		if ($classReflection->isBackedEnum()) {
			// PHP implicitly implements BackedEnum on backed enums, so an
			// @implements tag for it has no counterpart in the declared list.
			$implementsNames[] = new Node\Name\FullyQualified('BackedEnum');
		}

		$implementsErrors = $this->genericAncestorsCheck->check(
			$implementsNames,
			array_map(static fn (ImplementsTag $tag): Type => $tag->getType(), $classReflection->getImplementsTags()),
			sprintf('Enum %s @implements tag contains incompatible type %%s.', $escapedEnumName),
			sprintf('Enum %s @implements tag contains unresolvable type.', $enumName),
			sprintf('Enum %s has @implements tag, but does not implement any interface.', $escapedEnumName),
			sprintf('The @implements tag of enum %s describes %%s but the enum implements: %%s', $escapedEnumName),
			'PHPDoc tag @implements contains generic type %s but %s %s is not generic.',
			'Generic type %s in PHPDoc tag @implements does not specify all template types of %s %s: %s',
			'Generic type %s in PHPDoc tag @implements specifies %d template types, but %s %s supports only %d: %s',
			'Type %s in generic type %s in PHPDoc tag @implements is not subtype of template type %s of %s %s.',
			'Call-site variance annotation of %s in generic type %s in PHPDoc tag @implements is not allowed.',
			'PHPDoc tag @implements has invalid type %s.',
			sprintf('Enum %s implements generic interface %%s but does not specify its types: %%s', $escapedEnumName),
			sprintf('in implemented type %%s of enum %s', $escapedEnumName),
		);

		$backedEnumType = $classReflection->getBackedEnumType();
		if ($backedEnumType !== null) {
			$expectedTagType = new GenericObjectType('BackedEnum', [$backedEnumType]);
			$bareTagType = new ObjectType('BackedEnum');
			foreach ($classReflection->getImplementsTags() as $implementsTag) {
				$implementsTagType = $implementsTag->getType();
				if ($implementsTagType->getObjectClassNames() !== ['BackedEnum']) {
					continue;
				}
				if ($implementsTagType->equals($bareTagType) || $implementsTagType->equals($expectedTagType)) {
					continue;
				}

				$implementsErrors[] = RuleErrorBuilder::message(sprintf(
					'The @implements tag of enum %s specifies %s but the enum is backed by %s.',
					$enumName,
					$implementsTagType->describe(VerbosityLevel::typeOnly()),
					$backedEnumType->describe(VerbosityLevel::typeOnly()),
				))
					->identifier('enum.implementsBackingType')
					->build();
			}
		}

		foreach ($this->crossCheckInterfacesHelper->check($classReflection) as $error) {
			$implementsErrors[] = $error;
		}

		return array_merge($extendsErrors, $implementsErrors);
	}

}
