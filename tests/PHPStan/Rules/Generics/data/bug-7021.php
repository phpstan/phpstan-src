<?php declare(strict_types=1);

namespace Bug7021;

interface DataObjectInterface {}

interface RepositoryInterface {}

interface QueryBuilderInterface {}

/**
 * @template DO of DataObjectInterface
 * @template RQB of RepositoryQueryBuilderInterface
 */
interface CachingReadOnlyQueryBuilderRepositoryInterface {}

/**
 * @template DO of DataObjectInterface
 */
interface RepositoryQueryBuilderInterface {}

/**
 * @template DO of DataObjectInterface
 * @template RQB of RepositoryQueryBuilderInterface
 */
interface ReadOnlyQueryBuilderRepositoryInterface extends RepositoryInterface {}

/**
 * @template DO of DataObjectInterface
 * @template R of RepositoryInterface
 */
class CachingReadOnlyRepositoryDecorator {}

/**
 * @template DO of DataObjectInterface
 * @template QB of QueryBuilderInterface
 * @template RQB of RepositoryQueryBuilderInterface<DO>
 * @template R of ReadOnlyQueryBuilderRepositoryInterface<DO, RQB>
 * @extends CachingReadOnlyRepositoryDecorator<DO, R>
 * @implements CachingReadOnlyQueryBuilderRepositoryInterface<DO, RQB>
 */
class CachingReadOnlyQueryBuilderRepositoryDecorator extends CachingReadOnlyRepositoryDecorator implements
	CachingReadOnlyQueryBuilderRepositoryInterface
{
}
