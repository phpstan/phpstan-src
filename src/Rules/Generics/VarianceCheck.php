<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;
use function sprintf;

class VarianceCheck
{

	public function __construct(
		private bool $checkParamOutVariance,
		private bool $strictStaticVariance,
	)
	{
	}

	/** @return RuleError[] */
	public function checkParametersAcceptor(
		ParametersAcceptorWithPhpDocs $parametersAcceptor,
		string $parameterTypeMessage,
		string $parameterOutTypeMessage,
		string $returnTypeMessage,
		string $generalMessage,
		bool $isStatic,
		bool $isPrivate,
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
			))->build();
		}

		if ($isPrivate) {
			return $errors;
		}

		$covariant = TemplateTypeVariance::createCovariant();
		$parameterVariance = $isStatic && !$this->strictStaticVariance
			? TemplateTypeVariance::createStatic()
			: TemplateTypeVariance::createContravariant();

		foreach ($parametersAcceptor->getParameters() as $parameterReflection) {
			$type = $parameterReflection->getType();
			$message = sprintf($parameterTypeMessage, $parameterReflection->getName());
			foreach ($this->check($parameterVariance, $type, $message) as $error) {
				$errors[] = $error;
			}

			if (!$this->checkParamOutVariance) {
				continue;
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

	public function checkProperty(
		PropertyReflection $propertyReflection,
		string $message,
		bool $isReadOnly,
	): array
	{
		$readableType = $propertyReflection->getReadableType();
		$writableType = $propertyReflection->getWritableType();

		if ($readableType->equals($writableType)) {
			$variance = $isReadOnly
				? TemplateTypeVariance::createCovariant()
				: TemplateTypeVariance::createInvariant();

			return $this->check($variance, $readableType, $message);
		}

		$errors = [];

		if ($propertyReflection->isReadable()) {
			foreach ($this->check(TemplateTypeVariance::createCovariant(), $readableType, $message) as $error) {
				$errors[] = $error;
			}
		}

		if ($propertyReflection->isWritable()) {
			foreach ($this->check(TemplateTypeVariance::createContravariant(), $writableType, $message) as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

	/** @return RuleError[] */
	public function check(TemplateTypeVariance $positionVariance, Type $type, string $messageContext): array
	{
		$errors = [];

		foreach ($type->getReferencedTemplateTypes($positionVariance) as $reference) {
			$referredType = $reference->getType();
			if (($referredType->getScope()->getFunctionName() !== null && !$referredType->getVariance()->invariant())
				|| $this->isTemplateTypeVarianceValid($reference->getPositionVariance(), $referredType)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Template type %s is declared as %s, but occurs in %s position %s.',
				$referredType->getName(),
				$referredType->getVariance()->describe(),
				$reference->getPositionVariance()->describe(),
				$messageContext,
			))->build();
		}

		return $errors;
	}

	private function isTemplateTypeVarianceValid(TemplateTypeVariance $positionVariance, TemplateType $type): bool
	{
		return $positionVariance->validPosition($type->getVariance());
	}

}
