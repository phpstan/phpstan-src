<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use SplObjectStorage;
use function sprintf;

/**
 * The compiler's front end: PHPStan analyses the twin file and every
 * expression's native and PHPDoc type is recorded on the parsed nodes the
 * compiler then walks — the way scriptc runs tsc before lowering.
 *
 * Native types are the trusted facts (strict_types + declared parameter,
 * property and return types are enforced by the engine). PHPDoc types are
 * only speculation hints: code specialised on them is guarded at run time.
 */
final class TypeOracle
{

	/** @var SplObjectStorage<Node\Expr, array{Type, Type}> */
	private SplObjectStorage $types;

	/** @var array<string, Node\Stmt\Class_> */
	private array $classes = [];

	/** @var array<string, array<string, array{Node\Stmt\ClassMethod, string}>> class => lowercase method => [the trait method as analysed in the class, the trait] */
	private array $traitMethods = [];

	public function __construct(string $file)
	{
		$this->types = new SplObjectStorage();
		TypeInferenceTestCase::processFile($file, function (Node $node, Scope $scope): void {
			if ($node instanceof Node\Stmt\Class_ && $node->namespacedName !== null) {
				$this->classes[$node->namespacedName->toString()] = $node;
			}
			if ($node instanceof Node\Stmt\ClassMethod && $scope->isInTrait() && $scope->isInClass()) {
				$this->traitMethods[$scope->getClassReflection()->getName()][$node->name->toLowerString()] = [$node, $scope->getTraitReflection()->getName()];
			}
			if (!($node instanceof Node\Expr)) {
				return;
			}
			$this->types[$node] = [$scope->getNativeType($node), $scope->getType($node)];
		});
	}

	public function getClass(string $name): Node\Stmt\Class_
	{
		if (!isset($this->classes[$name])) {
			throw new CompileError(sprintf('class %s not found in the analysed file', $name));
		}
		return $this->classes[$name];
	}

	/** @return array<string, array{Node\Stmt\ClassMethod, string}> */
	public function getTraitMethods(string $class): array
	{
		return $this->traitMethods[$class] ?? [];
	}

	public function nativeType(Node\Expr $expr): Type
	{
		if (!$this->types->offsetExists($expr)) {
			return new MixedType();
		}
		if ($expr instanceof Node\Expr\ArrayDimFetch) {
			// an array's native type can carry PHPDoc element types in through
			// narrowing (count() on a `@return list<X>` array), so what an
			// offset read yields is never treated as proven
			return new MixedType();
		}
		return $this->types[$expr][0];
	}

	public function phpDocType(Node\Expr $expr): Type
	{
		if (!$this->types->offsetExists($expr)) {
			return new MixedType();
		}
		return $this->types[$expr][1];
	}

}
