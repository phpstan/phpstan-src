<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\Variable;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;

/**
 * @api
 */
final class ScopeFactory
{

	public function __construct(private InternalScopeFactory $internalScopeFactory)
	{
	}

	public function create(ScopeContext $context): MutatingScope
	{
		$superglobalType = new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType(true));
		$expressionTypes = [
			'$GLOBALS' => ExpressionTypeHolder::createYes(new Variable('GLOBALS'), $superglobalType),
			'$_SERVER' => ExpressionTypeHolder::createYes(new Variable('_SERVER'), $superglobalType),
			'$_GET' => ExpressionTypeHolder::createYes(new Variable('_GET'), $superglobalType),
			'$_POST' => ExpressionTypeHolder::createYes(new Variable('_POST'), $superglobalType),
			'$_FILES' => ExpressionTypeHolder::createYes(new Variable('_FILES'), $superglobalType),
			'$_COOKIE' => ExpressionTypeHolder::createYes(new Variable('_COOKIE'), $superglobalType),
			'$_SESSION' => ExpressionTypeHolder::createYes(new Variable('_SESSION'), $superglobalType),
			'$_REQUEST' => ExpressionTypeHolder::createYes(new Variable('_REQUEST'), $superglobalType),
			'$_ENV' => ExpressionTypeHolder::createYes(new Variable('_ENV'), $superglobalType),
		];

		return $this->internalScopeFactory->create($context, false, null, null, $expressionTypes, $expressionTypes);
	}

}
