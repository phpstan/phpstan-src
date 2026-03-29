<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;
use function count;
use function in_array;
use function sprintf;

#[AutowiredService]
final class VarianceCheck
{

	/**
	 * @param 'function'|'method' $identifier
	 * @return list<IdentifierRuleError>
	 */
	public function checkParametersAcceptor(
		ExtendedParametersAcceptor $parametersAcceptor,
		string $parameterTypeMessage,
		string $parameterOutTypeMessage,
		string $returnTypeMessage,
		string $generalMessage,
		bool $isStatic,
		bool $isPrivate,
		string $identifier,
	): array
	{
		$errors = [];

		foreach ($parametersAcceptor->getTemplateTypeMap()->getTypes() as $templateType) {
			if (!$templateType instanceof TemplateType
				|| $templateType->getScope()->getFunctionName() === null
				|| $templateType->getVariance()->invariant()
			) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Variance annotation is only allowed for type parameters of classes and interfaces, but occurs in template type %s in %s.',
				$templateType->getName(),
				$generalMessage,
			))->identifier(sprintf('%s.variance', $identifier))->build();
		}

		if ($isPrivate) {
			return $errors;
		}

		$covariant = TemplateTypeVariance::createCovariant();
		$parameterVariance = TemplateTypeVariance::createContravariant();

		foreach ($parametersAcceptor->getParameters() as $parameterReflection) {
			$type = $parameterReflection->getType();
			$message = sprintf($parameterTypeMessage, $parameterReflection->getName());
			foreach ($this->check($parameterVariance, $type, $message) as $error) {
				$errors[] = $error;
			}

			$paramOutType = $parameterReflection->getOutType();
			if ($paramOutType === null) {
				continue;
			}

			$outMessage = sprintf($parameterOutTypeMessage, $parameterReflection->getName());
			foreach ($this->check($covariant, $paramOutType, $outMessage) as $error) {
				$errors[] = $error;
			}
		}

		$type = $parametersAcceptor->getReturnType();
		foreach ($this->check($covariant, $type, $returnTypeMessage) as $error) {
			$errors[] = $error;
		}

		return $errors;
	}

	/** @return list<IdentifierRuleError> */
	public function check(TemplateTypeVariance $positionVariance, Type $type, string $messageContext): array
	{
		$errors = [];

		$skipTemplates = $this->findCovariantTemplatesInCovariantGeneric($positionVariance, $type);

		foreach ($type->getReferencedTemplateTypes($positionVariance) as $reference) {
			$referredType = $reference->getType();
			if (($referredType->getScope()->getFunctionName() !== null && !$referredType->getVariance()->invariant())
				|| $this->isTemplateTypeVarianceValid($reference->getPositionVariance(), $referredType)) {
				continue;
			}

			if (in_array($referredType, $skipTemplates, true)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Template type %s is declared as %s, but occurs in %s position %s.',
				$referredType->getName(),
				$referredType->getVariance()->describe(),
				$reference->getPositionVariance()->describe(),
				$messageContext,
			))->identifier('generics.variance')->build();
		}

		return $errors;
	}

	/**
	 * When a covariant generic type (e.g. Element<T> where Element has @template-covariant)
	 * is used as a parameter type, covariant template type arguments should not be flagged.
	 * The covariant generic only produces values of type T, so using it in a parameter
	 * position does not create a true contravariant use of T.
	 *
	 * @return list<TemplateType>
	 */
	private function findCovariantTemplatesInCovariantGeneric(TemplateTypeVariance $positionVariance, Type $type): array
	{
		if (!$positionVariance->contravariant()) {
			return [];
		}

		$classReflections = $type->getObjectClassReflections();
		if (count($classReflections) !== 1) {
			return [];
		}

		$classReflection = $classReflections[0];
		$templateTypeMap = $classReflection->getTemplateTypeMap();
		$skipTemplates = [];

		foreach ($templateTypeMap->getTypes() as $templateName => $templateType) {
			if (!$templateType instanceof TemplateType || !$templateType->getVariance()->covariant()) {
				continue;
			}

			$resolvedType = $type->getTemplateType($classReflection->getName(), $templateName);
			if (!($resolvedType instanceof TemplateType) || !$resolvedType->getVariance()->covariant()) {
				continue;
			}

			$skipTemplates[] = $resolvedType;
		}

		return $skipTemplates;
	}

	private function isTemplateTypeVarianceValid(TemplateTypeVariance $positionVariance, TemplateType $type): bool
	{
		return $positionVariance->validPosition($type->getVariance());
	}

}
