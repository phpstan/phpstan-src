<?php

namespace RepeatedNamespaces;

use SomeOtherNamespace\RepeatedUses;
use SomeOtherNamespace\{
	RepeatedGroupUses,
	RepeatedOther as RepeatedAliased,
};

final class RepeatedClass
{
}

namespace RepeatedNamespaces;

use SomeOtherNamespace\RepeatedUses;
use SomeOtherNamespace\{
	RepeatedGroupUses,
	RepeatedOther as RepeatedAliased,
};

final class RepeatedUses
{
}

namespace RepeatedNamespaces;

final class RepeatedClass
{
}

namespace RepeatedNamespaces;

use SomeOtherNamespace\RepeatedClass;
use SomeOtherNamespace\{
	RepeatedGroupUses as RepeatedAliased,
};
