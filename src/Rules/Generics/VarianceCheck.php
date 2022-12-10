<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;
use function sprintf;

class VarianceCheck
{

	/** @return RuleError[] */
	public function checkParametersAcceptor(
		ParametersAcceptor $parametersAcceptor,
		string $parameterTypeMessage,
		string $returnTypeMessage,
		string $generalMessage,
		bool $isConstructor,
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
				'Variance annotation is only allowed for type parameters of classes and interfaces, but occurs in template type %s %s.',
				$templateType->getName(),
				$generalMessage,
			))->build();
		}

		if ($isConstructor) {
			return $errors;
		}

		foreach ($parametersAcceptor->getParameters() as $parameterReflection) {
			$variance = TemplateTypeVariance::createContravariant();
			$type = $parameterReflection->getType();
			$message = sprintf($parameterTypeMessage, $parameterReflection->getName());
			foreach ($this->check($variance, $type, $message, $isStatic, $isPrivate) as $error) {
				$errors[] = $error;
			}
		}

		$variance = TemplateTypeVariance::createCovariant();
		$type = $parametersAcceptor->getReturnType();
		foreach ($this->check($variance, $type, $returnTypeMessage, $isStatic, $isPrivate) as $error) {
			$errors[] = $error;
		}

		return $errors;
	}

	/** @return RuleError[] */
	public function check(
		TemplateTypeVariance $positionVariance,
		Type $type,
		string $messageContext,
		bool $isStatic,
		bool $isPrivate,
	): array
	{
		$errors = [];

		foreach ($type->getReferencedTemplateTypes($positionVariance) as $reference) {
			$referredType = $reference->getType();

			if ($isStatic && $referredType->getScope()->getFunctionName() === null) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Class template type %s cannot be referenced in a static member %s.',
					$referredType->getName(),
					$messageContext,
				))->build();
				continue;
			}

			if ($isPrivate) {
				continue;
			}

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
