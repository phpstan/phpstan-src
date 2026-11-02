<?php declare(strict_types = 1);

// test data of a PHPStan rule - the namespace does not match the directory
namespace AttributeServicesFixtures\Psr4Mismatch\Elsewhere;

use Attribute;

#[Attribute]
final class MismatchedClass
{

}
