<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Traverser;

use PHPStan\Analyser\Scope;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Traverser\MemoizingTraverser;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverserCallable;

#[ReferencedByTurboExtension(key: 'transformStaticTypeTraverser')]
final class TransformStaticTypeTraverser implements TypeTraverserCallable
{

	private MemoizingTraverser $memoizingTraverser;

	public function __construct(
		private readonly Scope $scope,
	)
	{
		$this->memoizingTraverser = new MemoizingTraverser($this->doTraverse(...));
	}

	/**
	 * @param callable(Type): Type $traverse
	 */
	public function traverse(Type $type, callable $traverse): Type
	{
		if (!$this->scope->isInClass()) {
			return $type;
		}

		return $this->memoizingTraverser->traverse($type, $traverse);
	}

	/**
	 * @param callable(Type): Type $traverse
	 */
	private function doTraverse(Type $type, callable $traverse): Type
	{
		if ($type instanceof StaticType) {
			$classReflection = $this->scope->getClassReflection();
			$changedType = $type->changeBaseClass($classReflection);
			if ($classReflection->isFinal() && !$type instanceof ThisType) {
				$changedType = $changedType->getStaticObjectType();
			}
			return $traverse($changedType);
		}

		return $traverse($type);
	}

}
