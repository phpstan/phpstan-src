<?php declare(strict_types = 1);

namespace TemplateArgumentRawReturn;

use function PHPStan\Testing\assertType;

class Entity
{
}

/** @template E of Entity */
class EntityCollection
{
}

/** @template C of EntityCollection */
class Repository
{
}

/** @template C of EntityCollection */
class RepositoryIterator
{

	/** @param Repository<covariant C> $repository */
	public function __construct(Repository $repository)
	{
	}

}

/**
 * @template R
 * @param \Closure(): R $callback
 * @return R
 */
function run(\Closure $callback)
{
	return $callback();
}

/** @param Repository<covariant EntityCollection<covariant Entity>> $repository */
function test(Repository $repository): void
{
	$iterator = run(static fn (): RepositoryIterator => new RepositoryIterator($repository));
	assertType('TemplateArgumentRawReturn\RepositoryIterator<TemplateArgumentRawReturn\EntityCollection<covariant TemplateArgumentRawReturn\Entity>>', $iterator);

	$closureIterator = run(static function () use ($repository): RepositoryIterator {
		return new RepositoryIterator($repository);
	});
	assertType('TemplateArgumentRawReturn\RepositoryIterator<TemplateArgumentRawReturn\EntityCollection<covariant TemplateArgumentRawReturn\Entity>>', $closureIterator);

	/** @var RepositoryIterator<EntityCollection<covariant Entity>> $documentedIterator */
	$documentedIterator = run(static fn (): RepositoryIterator => new RepositoryIterator($repository));
}
