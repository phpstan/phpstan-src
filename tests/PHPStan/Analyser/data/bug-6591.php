<?php declare(strict_types=1); // onlyif PHP_VERSION_ID >= 70400

namespace Bug6591;

use function PHPStan\Testing\assertType;

interface HydratorInterface {
	/**
	 * @return array<string, mixed>
	 */
	public function extract(object $object): array;
}

interface EntityInterface {
	public const IDENTITY = 'identity';
	public const CREATED  = 'created';
	public function getIdentity(): string;
	public function getCreated(): \DateTimeImmutable;
}
interface UpdatableInterface extends EntityInterface {
	public const UPDATED = 'updated';
	public function getUpdated(): \DateTimeImmutable;
	public function setUpdated(\DateTimeImmutable $updated): void;
}
interface EnableableInterface extends UpdatableInterface {
	public const ENABLED = 'enabled';
	public function isEnabled(): bool;
	public function setEnabled(bool $enabled): void;
}


/**
 * @template T of EntityInterface
 */
class DoctrineEntityHydrator implements HydratorInterface
{
	/** @param T $object */
	public function extract(object $object): array
	{
		$data = [
			EntityInterface::IDENTITY => $object->getIdentity(),
			EntityInterface::CREATED  => $object->getCreated()->format('c'),
		];
		assertType('T of Bug6591\EntityInterface (class Bug6591\DoctrineEntityHydrator, argument)', $object);
		if ($object instanceof UpdatableInterface) {
			assertType('Bug6591\UpdatableInterface&T of Bug6591\EntityInterface (class Bug6591\DoctrineEntityHydrator, argument)', $object);
			$data[UpdatableInterface::UPDATED] = $object->getUpdated()->format('c');
		} else {
			assertType('T of Bug6591\EntityInterface~Bug6591\UpdatableInterface (class Bug6591\DoctrineEntityHydrator, argument)', $object);
		}

		assertType('T of Bug6591\EntityInterface (class Bug6591\DoctrineEntityHydrator, argument)', $object);

		if ($object instanceof EnableableInterface) {
			assertType('Bug6591\EnableableInterface&T of Bug6591\EntityInterface (class Bug6591\DoctrineEntityHydrator, argument)', $object);
			$data[EnableableInterface::ENABLED] = $object->isEnabled();
		} else {
			assertType('T of Bug6591\EntityInterface~Bug6591\EnableableInterface (class Bug6591\DoctrineEntityHydrator, argument)', $object);
		}

		assertType('T of Bug6591\EntityInterface (class Bug6591\DoctrineEntityHydrator, argument)', $object);

		return [...$data, ...$this->performExtraction($object)];
	}

	/**
	 * @param T $entity
	 * @return array<string, mixed>
	 */
	public function performExtraction(EntityInterface $entity): array
	{
		return [];
	}
}
