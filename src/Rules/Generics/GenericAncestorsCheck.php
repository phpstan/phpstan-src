<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TypeProjectionHelper;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_fill_keys;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function implode;
use function in_array;
use function sprintf;

class GenericAncestorsCheck
{

	/**
	 * @param string[] $skipCheckGenericClasses
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private GenericObjectTypeCheck $genericObjectTypeCheck,
		private VarianceCheck $varianceCheck,
		private bool $checkGenericClassInNonGenericObjectType,
		private array $skipCheckGenericClasses,
	)
	{
	}

	/**
	 * @param array<Node\Name> $nameNodes
	 * @param array<Type> $ancestorTypes
	 * @return RuleError[]
	 */
	public function check(
		array $nameNodes,
		array $ancestorTypes,
		string $incompatibleTypeMessage,
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
				$messages[] = RuleErrorBuilder::message(sprintf($incompatibleTypeMessage, $ancestorType->describe(VerbosityLevel::typeOnly())))->build();
				continue;
			}

			$ancestorTypeClassName = $ancestorType->getClassName();
			if (!isset($names[$ancestorTypeClassName])) {
				if (count($names) === 0) {
					$messages[] = RuleErrorBuilder::message($noNamesMessage)->build();
				} else {
					$messages[] = RuleErrorBuilder::message(sprintf($noRelatedNameMessage, $ancestorTypeClassName, implode(', ', array_keys($names))))->build();
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

			foreach ($ancestorType->getReferencedClasses() as $referencedClass) {
				if ($this->reflectionProvider->hasClass($referencedClass)) {
					continue;
				}

				$messages[] = RuleErrorBuilder::message(sprintf($invalidTypeMessage, $referencedClass))->build();
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
				))->identifier('generics.typeProjectionNotAllowed')->build();
			}
		}

		if ($this->checkGenericClassInNonGenericObjectType) {
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

				$messages[] = RuleErrorBuilder::message(sprintf(
					$genericClassInNonGenericObjectType,
					$unusedName,
					implode(', ', array_keys($unusedNameClassReflection->getTemplateTypeMap()->getTypes())),
				))->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)->build();
			}
		}

		return $messages;
	}

}
