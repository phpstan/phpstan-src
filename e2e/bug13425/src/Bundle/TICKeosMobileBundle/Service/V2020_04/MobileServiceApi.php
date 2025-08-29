<?php

declare(strict_types=1);

namespace EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Service\V2020_04;

use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Domain\Builder\Layout\CustomerFieldLayoutService;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Exception\MobileServiceException;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Service\V2019_12\MobileServiceApi as MobileServiceApi201912;
use sfContext;
use stdClass;

class MobileServiceApi extends MobileServiceApi201912
{
    public const string VERSION = '2020.04';


}
