<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\Type;

/**
 * Implemented by the types whose parts stand in a variance of their own: the parameters of
 * a callable reverse the variance the callable stands in, the arguments of a generic object
 * take the variance declared for the class template type or projected onto them. Every
 * other type passes its own variance on to its parts, so the variance-aware traversal
 * (TypeTraverser::mapWithVariance()) needs nothing but Type::traverse() from it.
 *
 * The variances handed to the callback must be the ones getReferencedTemplateTypes()
 * reports for the same parts.
 */
interface TraversableWithVariance
{

	/**
	 * @param callable(Type, TemplateTypeVariance): Type $cb
	 */
	public function traverseWithVariance(TemplateTypeVariance $positionVariance, callable $cb): Type;

}
