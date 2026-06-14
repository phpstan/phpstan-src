<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\ConditionalThrowTypeResolver;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\TrinaryLogic;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use Throwable;

#[AutowiredService]
final class MissingCheckedExceptionInThrowsCheck
{

	public function __construct(
		#[AutowiredParameter(ref: '@exceptionTypeResolver')]
		private ExceptionTypeResolver $exceptionTypeResolver,
	)
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

			// Conditional @throws types like ($x is 0 ? Exception : void) are resolved
			// against the parameter variables narrowed in the scope of the throw point.
			$resolvedThrowType = ConditionalThrowTypeResolver::resolveForScope($throwType, $throwPoint->getScope());

			foreach (TypeUtils::flattenTypes($throwPoint->getType()) as $throwPointType) {
				if ($throwPointType->isSuperTypeOf(new ObjectType(Throwable::class))->yes()) {
					continue;
				}
				if ($resolvedThrowType->isSuperTypeOf($throwPointType)->yes()) {
					continue;
				}

				$isCheckedException = TrinaryLogic::createNo()->lazyOr(
					$throwPointType->getObjectClassNames(),
					fn (string $objectClassName) => TrinaryLogic::createFromBoolean($this->exceptionTypeResolver->isCheckedException($objectClassName, $throwPoint->getScope())),
				);
				if ($isCheckedException->no()) {
					continue;
				}

				$classes[] = [$throwPointType->describe(VerbosityLevel::typeOnly()), $throwPoint->getNode()];
			}
		}

		return $classes;
	}

}
