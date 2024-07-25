<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

final class ConstExprNodeResolver
{

	public function resolve(ConstExprNode $node): Type
	{
		if ($node instanceof ConstExprArrayNode) {
			return $this->resolveArrayNode($node);
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

		return new MixedType();
	}

	private function resolveArrayNode(ConstExprArrayNode $node): Type
	{
		$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($node->items as $item) {
			if ($item->key === null) {
				$key = null;
			} else {
				$key = $this->resolve($item->key);
			}
			$arrayBuilder->setOffsetValueType($key, $this->resolve($item->value));
		}

		return $arrayBuilder->getArray();
	}

}
