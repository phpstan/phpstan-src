<?php

namespace Bug3558;

use function PHPStan\Testing\assertType;

function (): void {
	$idGroups = [];

	if(time() > 3){
		$idGroups[] = [1,2];
		$idGroups[] = [1,2];
		$idGroups[] = [1,2];
	}

	if(count($idGroups) > 0){
		assertType('array{array{1, 2}, array{1, 2}, array{1, 2}}', $idGroups);
	}
};

function (): void {
	$idGroups = [1];

	if(time() > 3){
		$idGroups[] = [1,2];
		$idGroups[] = [1,2];
		$idGroups[] = [1,2];
	}

	if(count($idGroups) > 1){
		assertType('array{1, array{1, 2}, array{1, 2}, array{1, 2}}|array{1}', $idGroups);
	}
};
