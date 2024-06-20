<?php

namespace MethodTagReturnClassDoesNotExist;

use DateTimeImmutable;

/**
 * @method \PHPStan\Rules\PhpDoc\MethodAnnotationReturnTypeClassDoesNotExistRule foo()
 * @method IDoNotExist missing()
 * @method \DateTime getDate()
 * @method \DateTime|IDoNotExist baz()
 * @method IDoNotExist|\DateTime zab()
 * @method DateTimeImmutable getDateTwo()
 * @method datetimeimmutable getDateThree()
 * @method \PHPStan\Rules\PhpDoc\methodannotationreturntypeclassdoesnotexistrule lowerCased()
 */
class Bar {}
