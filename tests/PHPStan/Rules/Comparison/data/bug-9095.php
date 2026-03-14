<?php declare(strict_types = 1);

namespace Bug9095;

trait EventTrait
{
	public function getCreatedAt(): ?\DateTimeInterface
	{
		if (
			property_exists(static::class, 'createdAt') &&
			isset($this->createdAt) &&
			$this->createdAt instanceof \DateTimeInterface
		) {
			return $this->createdAt;
		}
		return null;
	}
}

final class Event
{
	use EventTrait;
}
