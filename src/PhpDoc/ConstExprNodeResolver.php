<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function strtolower;

#[AutowiredService]
final class ConstExprNodeResolver
{

	public function __construct(
		private ReflectionProvider\ReflectionProviderProvider $reflectionProviderProvider,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function resolve(ConstExprNode $node, NameScope $nameScope): Type
	{
		if ($node instanceof ConstExprArrayNode) {
			return $this->resolveArrayNode($node, $nameScope);
		}

		if ($node instanceof ConstExprFalseNode) {
			return new ConstantBooleanType(false);
		}

		if ($node instanceof ConstExprTrueNode) {
			return new ConstantBooleanType(true);
		}

		if ($node instanceof ConstExprFloatNode) {
			return new ConstantFloatType((float) $node->value);
		}

		if ($node instanceof ConstExprIntegerNode) {
			return new ConstantIntegerType((int) $node->value);
		}

		if ($node instanceof ConstExprNullNode) {
			return new NullType();
		}

		if ($node instanceof ConstExprStringNode) {
			return new ConstantStringType($node->value);
		}

		if ($node instanceof ConstFetchNode) {
			if ($nameScope->getClassName() !== null) {
				switch (strtolower($node->className)) {
					case 'static':
					case 'self':
						$className = $nameScope->getClassName();
						break;

					case 'parent':
						if ($this->getReflectionProvider()->hasClass($nameScope->getClassName())) {
							$classReflection = $this->getReflectionProvider()->getClass($nameScope->getClassName());
							if ($classReflection->getParentClass() === null) {
								return new ErrorType();

							}

							$className = $classReflection->getParentClass()->getName();
						}
						break;
				}
			}
			if (!isset($className)) {
				$className = $nameScope->resolveStringName($node->className);
			}
			if (!$this->getReflectionProvider()->hasClass($className)) {
				return new ErrorType();
			}
			$classReflection = $this->getReflectionProvider()->getClass($className);
			if (!$classReflection->hasConstant($node->name)) {
				return new ErrorType();
			}
			if ($classReflection->isEnum() && $classReflection->hasEnumCase($node->name)) {
				return new EnumCaseObjectType($classReflection->getName(), $node->name);
			}

			$reflectionConstant = $classReflection->getNativeReflection()->getReflectionConstant($node->name);
			if ($reflectionConstant === false) {
				return new ErrorType();
			}
			$declaringClass = $reflectionConstant->getDeclaringClass();

			return $this->initializerExprTypeResolver->getType(
				$reflectionConstant->getValueExpression(),
				InitializerExprContext::fromClass($declaringClass->getName(), $declaringClass->getFileName() ?: null),
			);
		}

		return new ErrorType();
	}

	private function resolveArrayNode(ConstExprArrayNode $node, NameScope $nameScope): Type
	{
		$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($node->items as $item) {
			if ($item->key === null) {
				$key = null;
			} else {
				$key = $this->resolve($item->key, $nameScope);
			}
			$arrayBuilder->setOffsetValueType($key, $this->resolve($item->value, $nameScope));
		}

		return $arrayBuilder->getArray();
	}

	private function getReflectionProvider(): ReflectionProvider
	{
		return $this->reflectionProviderProvider->getReflectionProvider();
	}

}
