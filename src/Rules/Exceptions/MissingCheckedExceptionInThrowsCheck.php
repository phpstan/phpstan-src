<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;
use Throwable;

class MissingCheckedExceptionInThrowsCheck
{

	public function __construct(private ExceptionTypeResolver $exceptionTypeResolver)
	{
	}

	/**
	 * @param ThrowPoint[] $throwPoints
	 * @return array<int, array{string, Node\Expr|Node\Stmt}>
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

			foreach ($throwPoint->getType()->getUnionedTypes() as $throwPointType) {
				if ($throwPointType->isSuperTypeOf(new ObjectType(Throwable::class))->yes()) {
					continue;
				}
				if ($throwType->isSuperTypeOf($throwPointType)->yes()) {
					continue;
				}

				if (
					$throwPointType instanceof TypeWithClassName
					&& !$this->exceptionTypeResolver->isCheckedException($throwPointType->getClassName(), $throwPoint->getScope())
				) {
					continue;
				}

				$classes[] = [$throwPointType->describe(VerbosityLevel::typeOnly()), $throwPoint->getNode()];
			}
		}

		return $classes;
	}

}
