<?php

use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Event\ManifestTickeosConnectServerFilterEvent;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Exception\MobileServiceException;
use EosUptrade\TICKeos\Core\Domain\Customer\CustomerId;
use EosUptrade\TICKeos\Core\Repository\GenericRepository;

class MobileServiceApi38 extends MobileServiceApi
{
    public const string VERSION = '3.8';

}
