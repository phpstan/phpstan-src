<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function array_key_last;
use function count;
use function preg_match;

/**
 * `$a['k']` and `$a["k"]` read the same offset, but MutatingScope tracks types
 * under the pretty-printed form of an expression, and the pretty printer
 * reproduces the *source spelling* of a literal - so narrowing established
 * through one spelling used to be invisible at the other, and reformatting a
 * file changed the analysis result.
 *
 * The same is true of the two ways of building an offset out of parts:
 * `$a["$k.value"]` and `$a[$k . '.value']` read the same offset through
 * different AST shapes. Interpolation is sugar for concatenation - each part is
 * cast to string and the results are joined - so an offset built either way is
 * rewritten into a single canonical concatenation of its parts.
 *
 * Normalizing here rather than in the printer keeps the printed form faithful
 * everywhere else, so the expression text quoted back in error messages is
 * unaffected outside of array offsets.
 *
 * Rewriting an interpolation into a concatenation is the one change rules can
 * see - inside an offset, a part that cannot be cast to string is reported by
 * InvalidBinaryOperationRule instead of InvalidPartOfEncapsedStringRule. The
 * synthesized nodes carry the token positions of the source they stand for, so
 * error lines stay put and format-preserving printing keeps reproducing the
 * original spelling.
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

		$node->dim = $this->normalize($node->dim);

		return null;
	}

	private function normalize(Expr $dim): Expr
	{
		if ($dim instanceof String_) {
			$this->canonicalizeStringKind($dim);

			return $dim;
		}

		if ($dim instanceof Int_) {
			$dim->setAttribute('kind', Int_::KIND_DEC);

			return $dim;
		}

		if (!$dim instanceof InterpolatedString && !$dim instanceof Concat) {
			return $dim;
		}

		$operands = $this->mergeAdjacentStrings($this->flatten($dim));
		if (count($operands) === 1) {
			$operand = $operands[0];
			if ($operand instanceof String_) {
				// The whole offset is a constant string built out of pieces,
				// e.g. `'a' . 'b'` - spell it the way `$a['ab']` is spelled.
				return $operand;
			}

			// A one-part interpolation is a string cast, which the part alone is
			// not: `"$k"` and `$k` are the same offset only when `$k` is already
			// a string. Leave the cast in place and only pin its spelling.
			$dim->setAttribute('kind', String_::KIND_DOUBLE_QUOTED);

			return $dim;
		}

		$concat = $operands[0];
		for ($i = 1; $i < count($operands); $i++) {
			$concat = new Concat($concat, $operands[$i], $this->spanAttributes($concat, $operands[$i]));
		}

		return $concat;
	}

	/**
	 * Collects the operands of a concatenation, no matter which of the two
	 * syntaxes - or which nesting of them - built it. String concatenation is
	 * associative, so the flat list describes the same value as the tree.
	 *
	 * @return list<Expr>
	 */
	private function flatten(Expr $expr): array
	{
		if ($expr instanceof Concat) {
			$operands = [];
			foreach ([$expr->left, $expr->right] as $side) {
				foreach ($this->flatten($side) as $operand) {
					$operands[] = $operand;
				}
			}

			return $operands;
		}

		if ($expr instanceof InterpolatedString) {
			$operands = [];
			foreach ($expr->parts as $part) {
				if ($part instanceof InterpolatedStringPart) {
					$operands[] = $this->createString($part->value, $part->getAttributes());
					continue;
				}

				foreach ($this->flatten($part) as $operand) {
					$operands[] = $operand;
				}
			}

			return $operands;
		}

		if ($expr instanceof Int_) {
			$expr->setAttribute('kind', Int_::KIND_DEC);
		} elseif ($expr instanceof String_) {
			$this->canonicalizeStringKind($expr);
		}

		return [$expr];
	}

	/**
	 * @param list<Expr> $operands
	 * @return non-empty-list<Expr>
	 */
	private function mergeAdjacentStrings(array $operands): array
	{
		$merged = [];
		foreach ($operands as $operand) {
			$last = $merged === [] ? null : $merged[array_key_last($merged)];
			if (!$operand instanceof String_ || !$last instanceof String_) {
				$merged[] = $operand;
				continue;
			}

			$merged[array_key_last($merged)] = $this->createString(
				$last->value . $operand->value,
				$this->spanAttributes($last, $operand),
			);
		}

		if ($merged === []) {
			// An interpolation always has at least one part, but an empty one
			// would still describe the empty string.
			return [$this->createString('', [])];
		}

		return $merged;
	}

	/**
	 * @param array<string, mixed> $attributes
	 */
	private function createString(string $value, array $attributes): String_
	{
		$string = new String_($value, $attributes);
		$this->canonicalizeStringKind($string);

		return $string;
	}

	/**
	 * Single quotes normally, double quotes when the value holds control
	 * characters - the same canonical form as ConstantStringType::export(), and
	 * the one that keeps the expression key free of newlines.
	 */
	private function canonicalizeStringKind(String_ $string): void
	{
		$string->setAttribute(
			'kind',
			preg_match('/[\x00-\x1f]/', $string->value) === 1
				? String_::KIND_DOUBLE_QUOTED
				: String_::KIND_SINGLE_QUOTED,
		);
	}

	/**
	 * Keeps a synthesized node pointing at the source it stands for, so that
	 * error lines stay put and format-preserving printing reproduces the
	 * original spelling instead of the canonical one.
	 *
	 * @return array<string, mixed>
	 */
	private function spanAttributes(Node $start, Node $end): array
	{
		$attributes = [];
		foreach (['startLine', 'startTokenPos', 'startFilePos'] as $attribute) {
			if (!$start->hasAttribute($attribute)) {
				continue;
			}

			$attributes[$attribute] = $start->getAttribute($attribute);
		}

		foreach (['endLine', 'endTokenPos', 'endFilePos'] as $attribute) {
			if (!$end->hasAttribute($attribute)) {
				continue;
			}

			$attributes[$attribute] = $end->getAttribute($attribute);
		}

		return $attributes;
	}

}
