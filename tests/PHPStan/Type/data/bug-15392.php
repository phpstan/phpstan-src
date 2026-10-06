<?php

namespace Bug15392Types;

/** @phpstan-sealed SubA|SubB */
abstract class SealedBase
{

}

final class SubA extends SealedBase
{

}

final class SubB extends SealedBase
{

}

final class Other
{

}
