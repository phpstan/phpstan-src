<?php // lint >= 8.4

namespace OverridingPropertySetVisibility;

class A
{

	public protected(set) string $protectedSetToPrivateSet;

	public string $publicToPrivateSet;

	public string $publicToProtectedSet;

	public protected(set) string $protectedSetToProtectedSet;

	protected string $protectedToProtectedPrivateSet;

	protected string $protectedToPublicProtectedSet;

	protected string $protectedToPublicPrivateSet;

	public readonly string $readonlyToReadonly;

	public readonly string $readonlyToPrivateSetReadonly;

	public function __construct()
	{
		$this->readonlyToReadonly = '';
		$this->readonlyToPrivateSetReadonly = '';
	}

}

class B extends A
{

	public private(set) string $protectedSetToPrivateSet;

	public private(set) string $publicToPrivateSet;

	public protected(set) string $publicToProtectedSet;

	public protected(set) string $protectedSetToProtectedSet;

	protected private(set) string $protectedToProtectedPrivateSet;

	public protected(set) string $protectedToPublicProtectedSet;

	public private(set) string $protectedToPublicPrivateSet;

	public readonly string $readonlyToReadonly;

	public private(set) readonly string $readonlyToPrivateSetReadonly;

	public function __construct()
	{
		$this->readonlyToReadonly = '';
		$this->readonlyToPrivateSetReadonly = '';
	}

}

interface GetOnly
{

	public string $getOnly { get; }

	public string $getAndSet { get; set; }

}

class ImplementsGetOnly implements GetOnly
{

	public private(set) string $getOnly;

	public private(set) string $getAndSet;

}
