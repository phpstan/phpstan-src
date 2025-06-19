<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TypeProjectionHelper;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_fill_keys;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function implode;
use function in_array;
use function sprintf;

#[AutowiredService]
final class GenericAncestorsCheck
{

	/**
	 * @param string[] $skipCheckGenericClasses
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private GenericObjectTypeCheck $genericObjectTypeCheck,
		private VarianceCheck $varianceCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		#[AutowiredParameter(ref: '%featureToggles.skipCheckGenericClasses%')]
		private array $skipCheckGenericClasses,
		#[AutowiredParameter]
		private bool $checkMissingTypehints,
	)
	{
	}

	/**
	 * @param array<Node\Name> $nameNodes
	 * @param array<Type> $ancestorTypes
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		array $nameNodes,
		array $ancestorTypes,
		string $incompatibleTypeMessage,
		string $unresolvableTypeMessage,
		string $noNamesMessage,
		string $noRelatedNameMessage,
		string $classNotGenericMessage,
		string $notEnoughTypesMessage,
		string $extraTypesMessage,
		string $typeIsNotSubtypeMessage,
		string $typeProjectionIsNotAllowedMessage,
		string $invalidTypeMessage,
		string $genericClassInNonGenericObjectType,
		string $invalidVarianceMessage,
	): array
	{
		$names = array_fill_keys(array_map(static fn (Name $nameNode): string => $nameNode->toString(), $nameNodes), true);

		$unusedNames = $names;

		$messages = [];
		foreach ($ancestorTypes as $ancestorType) {
			if (!$ancestorType instanceof GenericObjectType) {
				$messages[] = RuleErrorBuilder::message(sprintf($incompatibleTypeMessage, $ancestorType->describe(VerbosityLevel::typeOnly())))
					->identifier('generics.notCompatible')
					->build();
				continue;
			}

			$ancestorTypeClassName = $ancestorType->getClassName();
			if (!isset($names[$ancestorTypeClassName])) {
				if (count($names) === 0) {
					$messages[] = RuleErrorBuilder::message($noNamesMessage)
						->identifier('generics.noParent')
						->build();
				} else {
					$messages[] = RuleErrorBuilder::message(sprintf($noRelatedNameMessage, $ancestorTypeClassName, implode(', ', array_keys($names))))
						->identifier('generics.wrongParent')
						->build();
				}

				continue;
			}

			unset($unusedNames[$ancestorTypeClassName]);

			$genericObjectTypeCheckMessages = $this->genericObjectTypeCheck->check(
				$ancestorType,
				$classNotGenericMessage,
				$notEnoughTypesMessage,
				$extraTypesMessage,
				$typeIsNotSubtypeMessage,
				'',
				'',
			);
			$messages = array_merge($messages, $genericObjectTypeCheckMessages);

			if ($this->unresolvableTypeHelper->containsUnresolvableType($ancestorType)) {
				$messages[] = RuleErrorBuilder::message($unresolvableTypeMessage)
					->identifier('generics.unresolvable')
					->build();
			}

			foreach ($ancestorType->getReferencedClasses() as $referencedClass) {
				if (!$this->reflectionProvider->hasClass($referencedClass)) {
					$messages[] = RuleErrorBuilder::message(sprintf($invalidTypeMessage, $referencedClass))
						->identifier('class.notFound')
						->build();
					continue;
				}

				if ($referencedClass === $ancestorType->getClassName()) {
					continue;
				}

				$classReflection = $this->reflectionProvider->getClass($referencedClass);
				if (!$classReflection->isTrait()) {
					continue;
				}

				$messages[] = RuleErrorBuilder::message(sprintf($invalidTypeMessage, $referencedClass))
					->identifier('generics.trait')
					->build();
			}

			$variance = TemplateTypeVariance::createStatic();
			$messageContext = sprintf(
				$invalidVarianceMessage,
				$ancestorType->describe(VerbosityLevel::typeOnly()),
			);
			foreach ($this->varianceCheck->check($variance, $ancestorType, $messageContext) as $message) {
				$messages[] = $message;
			}

			foreach ($ancestorType->getVariances() as $index => $typeVariance) {
				if ($typeVariance->invariant()) {
					continue;
				}

				$messages[] = RuleErrorBuilder::message(sprintf(
					$typeProjectionIsNotAllowedMessage,
					TypeProjectionHelper::describe($ancestorType->getTypes()[$index], $typeVariance, VerbosityLevel::typeOnly()),
					$ancestorType->describe(VerbosityLevel::typeOnly()),
				))->identifier('generics.callSiteVarianceNotAllowed')->build();
			}
		}

		if ($this->checkMissingTypehints) {
			foreach (array_keys($unusedNames) as $unusedName) {
				if (!$this->reflectionProvider->hasClass($unusedName)) {
					continue;
				}

				$unusedNameClassReflection = $this->reflectionProvider->getClass($unusedName);
				if (in_array($unusedNameClassReflection->getName(), $this->skipCheckGenericClasses, true)) {
					continue;
				}
				if (!$unusedNameClassReflection->isGeneric()) {
					continue;
				}

				$templateTypes = $unusedNameClassReflection->getTemplateTypeMap()->getTypes();
				$templateTypesCount = count($templateTypes);
				$requiredTemplateTypesCount = count(array_filter($templateTypes, static fn (Type $type) => $type instanceof TemplateType && $type->getDefault() === null));
				if ($requiredTemplateTypesCount === 0) {
					continue;
				}

				$templateTypesList = implode(', ', array_keys($templateTypes));
				if ($requiredTemplateTypesCount !== $templateTypesCount) {
					$templateTypesList .= sprintf(' (%d-%d required)', $requiredTemplateTypesCount, $templateTypesCount);
				}

				$messages[] = RuleErrorBuilder::message(sprintf(
					$genericClassInNonGenericObjectType,
					$unusedName,
					$templateTypesList,
				))
					->identifier('missingType.generics')
					->build();
			}
		}

		return $messages;
	}

}
