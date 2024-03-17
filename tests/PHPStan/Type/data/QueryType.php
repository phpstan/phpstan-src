<?php

namespace PHPStan\Type\data;

use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

class QueryType extends GenericObjectType
{
	/** @var Type */
	private $indexType;

	/** @var Type */
	private $resultType;

	/** @var string */
	private $dql;

	public function __construct(string $dql, ?Type $indexType = null, ?Type $resultType = null, ?Type $subtractedType = null)
	{
		$this->indexType = $indexType ?? new MixedType();
		$this->resultType = $resultType ?? new MixedType();

		parent::__construct('Doctrine\ORM\Query', [
			$this->indexType,
			$this->resultType,
		], $subtractedType);

		$this->dql = $dql;
	}

	public function getDql(): string {
		return $this->dql;
	}

	public function equals(Type $type): bool
	{
		if ($type instanceof self) {
			return $this->getDql() === $type->getDql();
		}

		return parent::equals($type);
	}

}
