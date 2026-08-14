<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Type\Type;

/**
 * The once-walked, fully-resolved view of an isset/empty/?? chain: a resolved
 * link (IssetabilityLinkInfo) plus the resolution of the chain inward of it.
 * IssetabilityDescriptor::resolve() builds it on a given scope; the engine reads
 * the fold via isSet() and the rule (PHPStan\Rules\IssetCheck) renders messages
 * from the same links - neither re-walks the AST nor re-resolves types.
 */
final class IssetabilityResolution
{

	public function __construct(
		private IssetabilityLinkInfo $link,
		private ?IssetabilityResolution $inner,
	)
	{
	}

	public function getLink(): IssetabilityLinkInfo
	{
		return $this->link;
	}

	public function getInner(): ?IssetabilityResolution
	{
		return $this->inner;
	}

	/**
	 * Whether isset() of the whole chain holds: null = maybe (resolves to bool),
	 * true/false = the typeCallback's verdict on the leaf type threaded outward
	 * over the chain's set-ness. Mirrors the former MutatingScope::issetCheck().
	 *
	 * @param callable(Type): ?bool $typeCallback
	 */
	public function isSet(callable $typeCallback, ?bool $result = null): ?bool
	{
		$link = $this->link;

		if ($link->isVariable()) {
			$hasVariable = $link->getHasVariable();
			if ($hasVariable->maybe()) {
				return null;
			}

			if ($result === null) {
				if ($hasVariable->yes()) {
					if ($link->getVariableName() === '_SESSION') {
						return null;
					}

					return $typeCallback($link->getValueType());
				}

				return false;
			}

			return $result;
		}

		if ($link->isOffset()) {
			if (!$link->getIsOffsetAccessible()->yes()) {
				return $result ?? ($this->inner !== null ? $this->inner->isSetUndefined() : null);
			}

			$hasOffsetValue = $link->getHasOffsetValue();
			if ($hasOffsetValue->no()) {
				return false;
			}

			// If offset cannot be null, store this verdict and see if one of the earlier
			// offsets is. E.g. $array['a']['b']['c'] ?? null; is a valid coalesce if a OR
			// b OR c might be null.
			if ($hasOffsetValue->yes()) {
				$result = $typeCallback($link->getValueType());

				if ($result !== null) {
					return $this->inner !== null ? $this->inner->isSet($typeCallback, $result) : $result;
				}
			}

			// Has offset, it is nullable
			return null;
		}

		if ($link->isProperty()) {
			if ($link->getPropertyReflection() === null || !$link->isReflectionNative()) {
				return $this->inner !== null ? $this->inner->isSetUndefined() : null;
			}

			if (
				$link->hasNativeType()
				&& !$link->isVirtual()->yes()
				&& !$link->hasExpressionTypeOfFetch()
				&& !$link->nativeHasDefaultValue()
				&& (!$link->nativeReflectionExists() || !$link->nativeIsPromoted() || (!$link->nativeIsReadOnly() && !$link->nativeIsHooked()))
			) {
				return $this->inner !== null ? $this->inner->isSetUndefined() : null;
			}

			if ($result !== null) {
				return $this->inner !== null ? $this->inner->isSet($typeCallback, $result) : $result;
			}

			$result = $typeCallback($link->getValueType());
			if ($result !== null && $this->inner !== null) {
				return $this->inner->isSet($typeCallback, $result);
			}

			return $result;
		}

		// leaf
		return $result ?? $typeCallback($link->getValueType());
	}

	private function isSetUndefined(): ?bool
	{
		$link = $this->link;

		if ($link->isVariable()) {
			if (!$link->getHasVariable()->no()) {
				return null;
			}

			return false;
		}

		if ($link->isOffset()) {
			if (!$link->getIsOffsetAccessible()->yes()) {
				return $this->inner !== null ? $this->inner->isSetUndefined() : null;
			}

			if (!$link->getHasOffsetValue()->no()) {
				return $this->inner !== null ? $this->inner->isSetUndefined() : null;
			}

			return false;
		}

		if ($link->isProperty()) {
			return $this->inner !== null ? $this->inner->isSetUndefined() : null;
		}

		return null;
	}

	/**
	 * Whether empty() of the whole chain is surely false (i.e. set and not falsy);
	 * null = maybe. EmptyHandler negates the result.
	 */
	public function notEmpty(): ?bool
	{
		return $this->isSet(static function (Type $type): ?bool {
			$isNull = $type->isNull();
			$isFalsey = $type->toBoolean()->isFalse();
			if ($isNull->maybe()) {
				return null;
			}
			if ($isFalsey->maybe()) {
				return null;
			}

			if ($isNull->yes()) {
				return $isFalsey->no();
			}

			return !$isFalsey->yes();
		});
	}

}
