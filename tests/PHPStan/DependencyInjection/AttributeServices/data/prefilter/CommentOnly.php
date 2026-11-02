<?php declare(strict_types = 1);

namespace AttributeServicesFixtures\PHPStan\Prefilter;

// a rule test data file can mention #[Foo] in a comment and declare classes that fail to load
define('ATTRIBUTE_SERVICES_FIXTURE_COMMENT_ONLY_LOADED', true);

final class CommentOnly
{

}
