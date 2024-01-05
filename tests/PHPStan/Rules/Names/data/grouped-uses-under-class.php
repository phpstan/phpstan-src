<?php

namespace SomeNamespace;

final class GroupedUsesUnderClass
{
}

final class FooBar
{
}

use SomeOtherNamespace\{
	FooBar,
	UsesUnderClass as GroupedUsesUnderClass,
};
