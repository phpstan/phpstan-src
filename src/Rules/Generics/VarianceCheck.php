<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;

class VarianceCheck
{

	/** @return RuleError[] */
	public function checkParametersAcceptor(
		ParametersAcceptor $parametersAcceptor,
		string $parameterTypeMessage,
		string $returnTypeMessage
	): array
	{
		$errors = [];

		foreach ($parametersAcceptor->getParameters() as $parameterReflection) {
			$variance = TemplateTypeVariance::createContravariant();
			$type = $parameterReflection->getType();
			$message = sprintf($parameterTypeMessage, $parameterReflection->getName());
			foreach ($this->check($variance, $type, $message) as $error) {
				$errors[] = $error;
			}
		}

		$variance = TemplateTypeVariance::createCovariant();
		$type = $parametersAcceptor->getReturnType();
		foreach ($this->check($variance, $type, $returnTypeMessage) as $error) {
			$errors[] = $error;
		}

		return $errors;
	}

	/** @return RuleError[] */
	public function check(TemplateTypeVariance $positionVariance, Type $type, string $messageContext): array
	{
		$errors = [];

		foreach ($type->getReferencedTemplateTypes($positionVariance) as $reference) {
			$referredType = $reference->getType();
			if ($this->isTemplateTypeVarianceValid($reference->getPositionVariance(), $referredType)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Template type %s is declared as %s, but occurs in %s position %s.',
				$referredType->getName(),
				$referredType->getVariance()->describe(),
				$reference->getPositionVariance()->describe(),
				$messageContext
			))->build();
		}

		return $errors;
	}

	private function isTemplateTypeVarianceValid(TemplateTypeVariance $positionVariance, TemplateType $type): bool
	{
		if ($type->getVariance()->invariant()) {
			return true;
		}

		return $type->getVariance()->equals($positionVariance);
	}

}
