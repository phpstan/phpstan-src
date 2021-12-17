<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;
use Throwable;
use function array_map;

class MissingCheckedExceptionInThrowsCheck
{

	private ExceptionTypeResolver $exceptionTypeResolver;

	public function __construct(ExceptionTypeResolver $exceptionTypeResolver)
	{
		$this->exceptionTypeResolver = $exceptionTypeResolver;
	}

	/**
	 * @param ThrowPoint[] $throwPoints
	 * @return array<int, array{string, Node\Expr|Node\Stmt, int|null}>
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

				$classes[] = [$throwPointType->describe(VerbosityLevel::typeOnly()), $throwPoint->getNode(), $this->getNewCatchPosition($throwPointType, $throwPoint->getNode())];
			}
		}

		return $classes;
	}

	private function getNewCatchPosition(Type $throwPointType, Node $throwPointNode): ?int
	{
		if ($throwPointType instanceof TypeWithClassName) {
			// to get rid of type subtraction
			$throwPointType = new ObjectType($throwPointType->getClassName());
		}
		$tryCatch = $this->findTryCatch($throwPointNode);
		if ($tryCatch === null) {
			return null;
		}

		$position = 0;
		foreach ($tryCatch->catches as $catch) {
			$type = TypeCombinator::union(...array_map(static fn (Node\Name $class): ObjectType => new ObjectType($class->toString()), $catch->types));
			if (!$throwPointType->isSuperTypeOf($type)->yes()) {
				continue;
			}

			$position++;
		}

		return $position;
	}

	private function findTryCatch(Node $node): ?Node\Stmt\TryCatch
	{
		if ($node instanceof Node\FunctionLike) {
			return null;
		}

		if ($node instanceof Node\Stmt\TryCatch) {
			return $node;
		}

		$parent = $node->getAttribute('parent');
		if ($parent === null) {
			return null;
		}

		return $this->findTryCatch($parent);
	}

}
