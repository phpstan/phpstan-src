<?php

vsprintf($message, 'foo'); // skip - format not a literal string
vsprintf('%s', ['foo']); // ok
vsprintf('%s %% %% %s', ['foo', 'bar']); // ok
vsprintf('%s %s', ['foo']); // one parameter missing
vsprintf('foo', ['foo']); // one parameter over
vsprintf('foo %s', ['foo', 'bar']); // one parameter over
vsprintf('%2$s %1$s %% %1$s %%%', ['one']); // one parameter missing
vsprintf('%2$s %%'); // two parameters required
vsprintf('%2$s %%', []); // two parameters required
vsprintf('%2$s %1$s %1$s %s %s %s %s'); // four parameters required
vsprintf('%2$s %1$s %% %s %s %s %s %%% %%%%', ['one', 'two', 'three', 'four']); // ok
vsprintf("%'.9d %1$'.9d %0.3f %d %d %d", [123, 456]); // five parameters required

vsprintf('%-4s', ['foo']); // ok
vsprintf('%%s %s', ['foo', 'bar']); // one parameter over
