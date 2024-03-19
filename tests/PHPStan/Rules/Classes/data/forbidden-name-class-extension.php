<?php declare(strict_types = 1);

namespace App\GeneratedProxy\__CG__\App;

class TestDoctrineEntity
{
}

namespace _PHPStan_15755dag8c;

class TestPhpStanEntity
{
}

namespace ForbiddenNameClassExtension;

use App\GeneratedProxy\__CG__\App\TestEntity;

class CGForbiddenNameClassExtension implements \PHPStan\Classes\ForbiddenClassNameExtension
{

	public function getClassPrefixes(): array
	{
		return [
			'Doctrine' => 'App\GeneratedProxy\__CG__',
		];
	}

}

$doctrineEntity = new \App\GeneratedProxy\__CG__\App\TestDoctrineEntity();
$phpStanEntity = new \_PHPStan_15755dag8c\TestPhpStanEntity();
