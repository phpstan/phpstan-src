<?php // lint >= 8.1

declare(strict_types = 1);

namespace ArrayChunkPhp81;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param 1|2 $union
	 */
	public function enumTest($union) {
		$arr = [];
		$arr[] = Status::DRAFT;
		$arr[] = Status::PUBLISHED;
		if (rand(0,1)) {
			$arr[] = Status::ARCHIVED;
		}

		assertType('array{array{ArrayChunkPhp81\Status::DRAFT, ArrayChunkPhp81\Status::PUBLISHED}, array{0?: ArrayChunkPhp81\Status::ARCHIVED}}|array{array{ArrayChunkPhp81\Status::DRAFT}, array{ArrayChunkPhp81\Status::PUBLISHED}, array{0?: ArrayChunkPhp81\Status::ARCHIVED}}', array_chunk($arr, $union));
	}

}

enum Status
{
	case DRAFT;
	case PUBLISHED;
	case ARCHIVED;
}
