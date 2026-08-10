<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function preg_match;

/**
 * `$a['k']` and `$a["k"]` read the same offset, but MutatingScope tracks types
 * under the pretty-printed form of an expression, and the pretty printer
 * reproduces the *source spelling* of a literal - so narrowing established
 * through one spelling used to be invisible at the other, and reformatting a
 * file changed the analysis result.
 *
 * Rewriting the offset literal to a canonical spelling here rather than in the
 * printer keeps the printed form faithful everywhere else, so the expression
 * text quoted back in error messages is unaffected outside of array offsets.
 *
 * Only the spelling attributes are touched, never a subnode, so rules see the
 * same AST and format-preserving printing still reproduces the original source
 * for these nodes.
 */
#[AutowiredService]
final class ArrayOffsetNormalizingVisitor extends NodeVisitorAbstract
{

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (!$node instanceof ArrayDimFetch || $node->dim === null) {
			return null;
		}

		$dim = $node->dim;
		if ($dim instanceof String_) {
			// Single quotes normally, double quotes when the value holds control
			// characters - the same canonical form as ConstantStringType::export(),
			// and the one that keeps the expression key free of newlines.
			$dim->setAttribute(
				'kind',
				preg_match('/[\x00-\x1f]/', $dim->value) === 1
					? String_::KIND_DOUBLE_QUOTED
					: String_::KIND_SINGLE_QUOTED,
			);
		} elseif ($dim instanceof InterpolatedString) {
			$dim->setAttribute('kind', String_::KIND_DOUBLE_QUOTED);
		} elseif ($dim instanceof Int_) {
			$dim->setAttribute('kind', Int_::KIND_DEC);
		}

		return null;
	}

}
