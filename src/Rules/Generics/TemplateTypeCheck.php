<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
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
		private ClassNameCheck $classCheck,
		private GenericObjectTypeCheck $genericObjectTypeCheck,
		private TypeAliasResolver $typeAliasResolver,
		private bool $checkClassCaseSensitivity,
	)
	{
	}

	/**
	 * @param array<string, TemplateTag> $templateTags
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		Scope $scope,
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
			if ($templateTag->getName() === '') {
				throw new ShouldNotHappenException();
			}

			$templateTagName = $scope->resolveName(new Node\Name($templateTag->getName()));
			if ($this->reflectionProvider->hasClass($templateTagName)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					$sameTemplateTypeNameAsClassMessage,
					$templateTagName,
				))->identifier('generics.existingClass')->build();
			}
			if ($this->typeAliasResolver->hasTypeAlias($templateTagName, $templateTypeScope->getClassName())) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					$sameTemplateTypeNameAsTypeMessage,
					$templateTagName,
				))->identifier('generics.existingTypeAlias')->build();
			}
			$boundType = $templateTag->getBound();
			foreach ($boundType->getReferencedClasses() as $referencedClass) {
				if (!$this->reflectionProvider->hasClass($referencedClass)) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						$invalidBoundTypeMessage,
						$templateTagName,
						$referencedClass,
					))->identifier('class.notFound')->build();
					continue;
				}
				if (!$this->reflectionProvider->getClass($referencedClass)->isTrait()) {
					continue;
				}

				$messages[] = RuleErrorBuilder::message(sprintf(
					$invalidBoundTypeMessage,
					$templateTagName,
					$referencedClass,
				))->identifier('generics.traitBound')->build();
			}

			$classNameNodePairs = array_map(static fn (string $referencedClass): ClassNameNodePair => new ClassNameNodePair($referencedClass, $node), $boundType->getReferencedClasses());
			$messages = array_merge($messages, $this->classCheck->checkClassNames($classNameNodePairs, $this->checkClassCaseSensitivity));

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
				$messages[] = RuleErrorBuilder::message(sprintf($notSupportedBoundMessage, $templateTagName, $boundType->describe(VerbosityLevel::typeOnly())))
					->identifier('generics.notSupportedBound')
					->build();
			}

			$escapedTemplateTagName = SprintfHelper::escapeFormatString($templateTagName);
			$genericObjectErrors = $this->genericObjectTypeCheck->check(
				$boundType,
				sprintf('PHPDoc tag @template %s bound contains generic type %%s but %%s %%s is not generic.', $escapedTemplateTagName),
				sprintf('PHPDoc tag @template %s bound has type %%s which does not specify all template types of %%s %%s: %%s', $escapedTemplateTagName),
				sprintf('PHPDoc tag @template %s bound has type %%s which specifies %%d template types, but %%s %%s supports only %%d: %%s', $escapedTemplateTagName),
				sprintf('Type %%s in generic type %%s in PHPDoc tag @template %s is not subtype of template type %%s of %%s %%s.', $escapedTemplateTagName),
				sprintf('Call-site variance of %%s in generic type %%s in PHPDoc tag @template %s is in conflict with %%s template type %%s of %%s %%s.', $escapedTemplateTagName),
				sprintf('Call-site variance of %%s in generic type %%s in PHPDoc tag @template %s is redundant, template type %%s of %%s %%s has the same variance.', $escapedTemplateTagName),
			);
			foreach ($genericObjectErrors as $genericObjectError) {
				$messages[] = $genericObjectError;
			}
		}

		return $messages;
	}

}
