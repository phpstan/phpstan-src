<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Internal\SprintfHelper;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\KeyOfType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeAliasResolver;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function array_merge;
use function get_class;
use function sprintf;

class TemplateTypeCheck
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private GenericObjectTypeCheck $genericObjectTypeCheck,
		private TypeAliasResolver $typeAliasResolver,
		private bool $checkClassCaseSensitivity,
	)
	{
	}

	/**
	 * @param array<string, TemplateTag> $templateTags
	 * @return RuleError[]
	 */
	public function check(
		Node $node,
		TemplateTypeScope $templateTypeScope,
		array $templateTags,
		string $sameTemplateTypeNameAsClassMessage,
		string $sameTemplateTypeNameAsTypeMessage,
		string $invalidBoundTypeMessage,
		string $notSupportedBoundMessage,
	): array
	{
		$messages = [];
		foreach ($templateTags as $templateTag) {
			$templateTagName = $templateTag->getName();
			if ($this->reflectionProvider->hasClass($templateTagName)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					$sameTemplateTypeNameAsClassMessage,
					$templateTagName,
				))->build();
			}
			if ($this->typeAliasResolver->hasTypeAlias($templateTagName, $templateTypeScope->getClassName())) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					$sameTemplateTypeNameAsTypeMessage,
					$templateTagName,
				))->build();
			}
			$boundType = $templateTag->getBound();
			foreach ($boundType->getReferencedClasses() as $referencedClass) {
				if (
					$this->reflectionProvider->hasClass($referencedClass)
					&& !$this->reflectionProvider->getClass($referencedClass)->isTrait()
				) {
					continue;
				}

				$messages[] = RuleErrorBuilder::message(sprintf(
					$invalidBoundTypeMessage,
					$templateTagName,
					$referencedClass,
				))->build();
			}

			if ($this->checkClassCaseSensitivity) {
				$classNameNodePairs = array_map(static fn (string $referencedClass): ClassNameNodePair => new ClassNameNodePair($referencedClass, $node), $boundType->getReferencedClasses());
				$messages = array_merge($messages, $this->classCaseSensitivityCheck->checkClassNames($classNameNodePairs));
			}

			$boundType = $templateTag->getBound();
			$boundTypeClass = get_class($boundType);
			if (
				$boundTypeClass !== MixedType::class
				&& $boundTypeClass !== ConstantArrayType::class
				&& $boundTypeClass !== ArrayType::class
				&& $boundTypeClass !== ConstantStringType::class
				&& $boundTypeClass !== StringType::class
				&& $boundTypeClass !== ConstantIntegerType::class
				&& $boundTypeClass !== IntegerType::class
				&& $boundTypeClass !== FloatType::class
				&& $boundTypeClass !== BooleanType::class
				&& $boundTypeClass !== ObjectWithoutClassType::class
				&& $boundTypeClass !== ObjectType::class
				&& $boundTypeClass !== ObjectShapeType::class
				&& $boundTypeClass !== GenericObjectType::class
				&& $boundTypeClass !== KeyOfType::class
				&& !$boundType instanceof UnionType
				&& !$boundType instanceof IntersectionType
				&& !$boundType instanceof TemplateType
			) {
				$messages[] = RuleErrorBuilder::message(sprintf($notSupportedBoundMessage, $templateTagName, $boundType->describe(VerbosityLevel::typeOnly())))->build();
			}

			$escapedTemplateTagName = SprintfHelper::escapeFormatString($templateTagName);
			$genericObjectErrors = $this->genericObjectTypeCheck->check(
				$boundType,
				sprintf('PHPDoc tag @template %s bound contains generic type %%s but %%s %%s is not generic.', $escapedTemplateTagName),
				sprintf('PHPDoc tag @template %s bound has type %%s which does not specify all template types of %%s %%s: %%s', $escapedTemplateTagName),
				sprintf('PHPDoc tag @template %s bound has type %%s which specifies %%d template types, but %%s %%s supports only %%d: %%s', $escapedTemplateTagName),
				sprintf('Type %%s in generic type %%s in PHPDoc tag @template %s is not subtype of template type %%s of %%s %%s.', $escapedTemplateTagName),
				sprintf('Type projection %%s in generic type %%s in PHPDoc tag @template %s is conflicting with variance of template type %%s of %%s %%s.', $escapedTemplateTagName),
				sprintf('Type projection %%s in generic type %%s in PHPDoc tag @template %s is redundant, template type %%s of %%s %%s has the same variance.', $escapedTemplateTagName),
			);
			foreach ($genericObjectErrors as $genericObjectError) {
				$messages[] = $genericObjectError;
			}
		}

		return $messages;
	}

}
