<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node\Expr;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_keys;
use function spl_object_id;
use function sprintf;

/**
 * Immutable template inference context carried by a scope. The observation walk
 * and the resolved walk use distinct instances, including in saved callbacks.
 */
final class TemplateArgumentFrame
{

	/**
	 * Set on the synthetic nodes handlers build for an on-demand pricing (the
	 * parent constructor's `new`, a fabricated offsetGet()/__toString() call):
	 * a fresh node every walk, so it can never be a site the second pass finds
	 * again. Markers keyed by such a node are re-keyed by the handler that
	 * built it or stay unobserved.
	 */
	public const SYNTHETIC_SITE_ATTRIBUTE = 'templateArgumentSyntheticSite';

	/**
	 * Set on a node a handler builds in place of the real one (the nullsafe
	 * call's plain twin) to the real node, which is the site.
	 */
	public const ORIGINAL_SITE_ATTRIBUTE = 'templateArgumentOriginalSite';

	/**
	 * The return type of a call the analyser walks: the variant's return type
	 * with the inferred template arguments unresolved/resolved under the
	 * scope's frame, the legacy (generalizing) one otherwise.
	 */
	public static function returnTypeOfCall(ParametersAcceptor $acceptor, MutatingScope $scope, Expr $site, ?bool $allowUnresolved = null): Type
	{
		$frame = $scope->getCurrentTemplateArgumentFrame();
		if ($frame === null || !$acceptor instanceof ResolvedFunctionVariant) {
			return $acceptor->getReturnType();
		}
		$originalSite = $site->getAttribute(self::ORIGINAL_SITE_ATTRIBUTE);

		return $acceptor->getReturnTypeWithUnresolvedTemplateArguments(
			$originalSite instanceof Expr ? $originalSite : $site,
			$frame,
			$allowUnresolved ?? !$scope->nativeTypesPromoted,
		);
	}

	/**
	 * @param array<string, Type>|null $resolutions null during collection
	 * @param array<int, true> $siteStatementIndexes
	 */
	public function __construct(
		private readonly ?self $parent,
		private readonly ?array $resolutions = null,
		private readonly array $siteStatementIndexes = [],
	)
	{
	}

	public function isObserving(): bool
	{
		return $this->resolutions === null;
	}

	public function firstSiteStatementIndex(): ?int
	{
		$first = null;
		foreach (array_keys($this->siteStatementIndexes) as $index) {
			if ($first !== null && $index >= $first) {
				continue;
			}

			$first = $index;
		}

		return $first;
	}

	public function ownsSiteInStatement(int $statementIndex): bool
	{
		return isset($this->siteStatementIndexes[$statementIndex]);
	}

	public function hasSiteAtOrAfter(int $statementIndex): bool
	{
		foreach (array_keys($this->siteStatementIndexes) as $index) {
			if ($index >= $statementIndex) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The resolution of the site's template argument, or - for a site this
	 * frame never observed (never asked during the observation pass, a native
	 * flavour) - what an unconstrained argument resolves to.
	 */
	public function resolveOrUnconstrained(Expr $site, TemplateType $template): Type
	{
		return $this->resolve($site, $template->getName()) ?? self::resolveUnconstrained($site, $template, fn (Expr $site, string $templateName): ?Type => $this->resolve($site, $templateName));
	}

	/**
	 * Nothing was inferred, sent or passed in: use the template's default or
	 * bound, resolving sibling arguments in dependent bounds (`U of T`).
	 * An unknown argument does not imply that the object holds nothing.
	 *
	 * @param callable(Expr, string): ?Type $resolve
	 */
	public static function resolveUnconstrained(Expr $site, TemplateType $template, callable $resolve): Type
	{
		$default = $template->getDefault();
		if ($default !== null) {
			return $default;
		}

		$bound = $template->getBound();
		if (!$bound->hasTemplateOrLateResolvableType()) {
			return $bound;
		}

		$scope = $template->getScope();

		return TypeTraverser::map($bound, static function (Type $type, callable $traverse) use ($site, $scope, $resolve): Type {
			if ($type instanceof TemplateType && $type->getScope()->equals($scope)) {
				return $resolve($site, $type->getName()) ?? $type->getDefault() ?? $traverse($type->getBound());
			}

			return $traverse($type);
		});
	}

	/**
	 * The resolved type of a template argument of the site, or null for a site
	 * this frame and its parents never observed.
	 */
	public function resolve(Expr $site, string $templateName): ?Type
	{
		$key = self::key($site, $templateName);
		if (isset($this->resolutions[$key])) {
			return $this->resolutions[$key];
		}

		if ($this->parent !== null) {
			return $this->parent->resolve($site, $templateName);
		}

		return null;
	}

	/**
	 * Distinguishes cache entries computed while observing from those computed
	 * with the resolutions installed (the closure type cache keys on scope state
	 * that does not change between the two passes).
	 */
	public function getResolutionCacheKeySuffix(): string
	{
		$frame = $this;
		while ($frame !== null) {
			if (!$frame->isObserving()) {
				return sprintf('|templateArguments:%d', spl_object_id($frame));
			}

			$frame = $frame->parent;
		}

		return '';
	}

	private static function key(Expr $site, string $templateName): string
	{
		return spl_object_id($site) . '#' . $templateName;
	}

}
