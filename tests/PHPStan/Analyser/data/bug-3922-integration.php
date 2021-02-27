<?php

namespace Bug3922Integration;

/**
 * @template TResult
 * @template TQuery of QueryInterface<TResult>
 */
interface QueryHandlerInterface
{
	/**
	 * @param TQuery $query
	 *
	 * @return TResult
	 */
	public function handle(QueryInterface $query);
}

/**
 * @template TResult
 */
interface QueryInterface
{
}

/**
 * @template-implements QueryInterface<FooQueryResult>
 */
final class FooQuery implements QueryInterface
{
}

final class FooQueryResult
{
}

/**
 * @template-implements QueryHandlerInterface<FooQueryResult, FooQuery>
 */
final class FooQueryHandler implements QueryHandlerInterface
{
	public function handle(QueryInterface $query): FooQueryResult
	{
		return new FooQueryResult();
	}
}

interface BasePackage {}

interface InnerPackage extends BasePackage {}

/**
 * @template TInnerPackage of InnerPackage
 */
interface GenericPackage extends BasePackage {
	/** @return TInnerPackage */
	public function unwrap() : InnerPackage;
}

interface SomeInnerPackage extends InnerPackage {}

/**
 * @extends GenericPackage<SomeInnerPackage>
 */
interface SomePackage extends GenericPackage {}

/**
 * @template TInnerPackage of InnerPackage
 * @template TGenericPackage of GenericPackage<TInnerPackage>
 * @param TGenericPackage $package
 * @return TInnerPackage
 */
function unwrapGeneric(GenericPackage $package) {
	return $package->unwrap();
}

/**
 * @template TInnerPackage of InnerPackage
 * @template TGenericPackage of GenericPackage<TInnerPackage>
 * @param  class-string<TGenericPackage> $class  FQCN to be instantiated
 * @return TInnerPackage
 */
function loadWithDirectUnwrap(string $class) {
	$package = new $class();
	return $package->unwrap();
}

/**
 * @template TInnerPackage of InnerPackage
 * @template TGenericPackage of GenericPackage<TInnerPackage>
 * @param  class-string<TGenericPackage> $class  FQCN to be instantiated
 * @return TInnerPackage
 */
function loadWithIndirectUnwrap(string $class) {
	$package = new $class();
	return unwrapGeneric($package);
}

function (): void {
	loadWithDirectUnwrap(SomePackage::class);
};
