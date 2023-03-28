<?php declare(strict_types = 1);

namespace PHPStan\PhpStormMeta;

use PhpParser\Node as ParserNode;
use PHPStan\PhpStormMeta\TypeMapping\FunctionCallTypeOverride;
use PHPStan\PhpStormMeta\TypeMapping\MethodCallTypeOverride;
use PHPStan\PhpStormMeta\TypeMapping\PassedArgumentType;
use PHPStan\PhpStormMeta\TypeMapping\PassedArrayElementType;
use PHPStan\PhpStormMeta\TypeMapping\ReturnTypeMap;
use function count;
use function strtolower;
use function trim;

class OverrideParser
{

	public function parseOverride(
		ParserNode\Arg $callableArg,
		ParserNode\Arg $overrideArg,
	): MethodCallTypeOverride|FunctionCallTypeOverride|null
	{
		$identifier = $callableArg->value;

		$override = $overrideArg->value;
		if (!$override instanceof ParserNode\Expr\FuncCall
			|| !$override->name instanceof ParserNode\Name
		) {
			return null;
		}

		$map = null;
		$typeOffset = null;
		$elementTypeOffset = null;

		if (count($override->getArgs()) > 0) {
			$overrideName = $override->name->toString();
			$overrideArg0 = $override->getArgs()[0]->value;
			if ($overrideName === 'map') {
				if ($overrideArg0 instanceof ParserNode\Expr\Array_) {
					$map = new ReturnTypeMap();
					foreach ($overrideArg0->items as $arrayItem) {
						if (!($arrayItem instanceof ParserNode\Expr\ArrayItem)
							|| !($arrayItem->key instanceof ParserNode\Scalar\String_)
						) {
							continue;
						}

						$arrayKey = $arrayItem->key->value;
						$arrayValue = $arrayItem->value;
						if ($arrayValue instanceof ParserNode\Expr\ClassConstFetch
							&& $arrayValue->class instanceof ParserNode\Name\FullyQualified
							&& $arrayValue->name instanceof ParserNode\Identifier
							&& strtolower(trim($arrayValue->name->name)) !== ''
						) {
							$map->addMapping($arrayKey, clone $arrayValue->class);
						} elseif ($arrayValue instanceof ParserNode\Scalar\String_) {
							$map->addMapping($arrayKey, $arrayValue->value);
						}
					}
				}
			} elseif ($overrideName === 'type') {
				if ($overrideArg0 instanceof ParserNode\Scalar\LNumber) {
					$typeOffset = new PassedArgumentType($overrideArg0->value);
				}
			} elseif ($overrideName === 'elementType') {
				if ($overrideArg0 instanceof ParserNode\Scalar\LNumber) {
					$elementTypeOffset = new PassedArrayElementType($overrideArg0->value);
				}
			}
		}

		$returnType = $map ?? $typeOffset ?? $elementTypeOffset;

		if ($returnType === null) {
			return null;
		}

		if ($identifier instanceof ParserNode\Expr\StaticCall) {
			if ($identifier->class instanceof ParserNode\Name\FullyQualified &&
				$identifier->name instanceof ParserNode\Identifier &&
				count($identifier->getArgs()) > 0
			) {
				$identifierArg0 = $identifier->getArgs()[0]->value;
				if ($identifierArg0 instanceof ParserNode\Scalar\LNumber) {
					return new MethodCallTypeOverride(
						$identifier->class->toString(),
						$identifier->name->toString(),
						$identifierArg0->value,
						$returnType,
					);
				}
			}
		}

		if ($identifier instanceof ParserNode\Expr\FuncCall) {
			if ($identifier->name instanceof ParserNode\Name\FullyQualified &&
				count($identifier->getArgs()) > 0
			) {
				$identifierArg0 = $identifier->getArgs()[0]->value;
				if ($identifierArg0 instanceof ParserNode\Scalar\LNumber) {
					return new FunctionCallTypeOverride(
						$identifier->name->toString(),
						$identifierArg0->value,
						$returnType,
					);
				}
			}
		}

		return null;
	}

}
