<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * @api
 * @final
 */
class ScopeFactory
{

	public function __construct(private InternalScopeFactory $internalScopeFactory)
	{
	}

	public function create(ScopeContext $context): MutatingScope
	{
		return $this->internalScopeFactory->create($context);
	}

}
