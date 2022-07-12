<?php

namespace Bug6697;

$result = \is_subclass_of( '\\My\\Namespace\\MyClass', '\\My\\Namespace\\MyBaseClass', true);
