<?php

namespace FirstNamespace
{
	use SomeOtherNamespace\SimpleUses;

	final class MultipleNamespaces
	{
	}
}

namespace SecondNamespace
{
	use SomeOtherNamespace\SimpleUses;

	final class MultipleNamespaces
	{
	}
}


namespace FirstNamespace
{
	trait MultipleNamespaces
	{
	}
}

namespace {
	final class MultipleNamespaces
	{
	}
}
