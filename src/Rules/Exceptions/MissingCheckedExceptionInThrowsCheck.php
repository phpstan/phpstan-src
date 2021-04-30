<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Analyser\ThrowPoint;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;

class MissingCheckedExceptionInThrowsCheck
{

	private ExceptionTypeResolver $exceptionTypeResolver;

	public function __construct(ExceptionTypeResolver $exceptionTypeResolver)
	{
		$this->exceptionTypeResolver = $exceptionTypeResolver;
	}

	/**
	 * @param Type|null $throwType
	 * @param ThrowPoint[] $throwPoints
	 * @return array<int, array{string, int}>
	 */
	public function check(?Type $throwType, array $throwPoints): array
	{
		if ($throwType === null) {
			$throwType = new NeverType();
		}

		$classes = [];
		foreach ($throwPoints as $throwPoint) {
			if (!$throwPoint->isExplicit()) {
				continue;
			}

			foreach (TypeUtils::flattenTypes($throwPoint->getType()) as $throwPointType) {
				if ($throwType->isSuperTypeOf($throwPointType)->yes()) {
					continue;
				}

				if (
					$throwPointType instanceof TypeWithClassName
					&& !$this->exceptionTypeResolver->isCheckedException($throwPointType->getClassName())
				) {
					continue;
				}

				$classes[] = [$throwPointType->describe(VerbosityLevel::typeOnly()), $throwPoint->getNode()->getLine()];
			}
		}

		return $classes;
	}

}
