<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use EosUptrade\TICKeos\Bundle\EtsPaymentFacadeClientBundle\Helper\BrowserType\BrowserTypeConfiguration;
use EosUptrade\TICKeos\Bundle\OrderProcessBundle\Domain\ProcessLockService;
use EosUptrade\TICKeos\Bundle\TICKeosCoreBundle\Domain\AnonymousCustomerSessionHandler;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Domain\Authentication\CreateAccessToken;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Domain\Certificate\LegacyCertificateProvider;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Event\V2019_06\FilterCartPriceRequestEvent;
use EosUptrade\TICKeos\Core\Domain\Customer\CustomerDeviceManager;
use EosUptrade\TICKeos\Core\Repository\CustomerDeviceRepository;
use EosUptrade\TICKeos\Core\Repository\GenericRepository;
use EosUptrade\TICKeos\Core\Repository\OrderProductRepository;
use EosUptrade\TICKeos\Core\UseCase\Order\ClaimOrderProduct\ClaimOrderProduct;
use EosUptrade\TICKeos\Library\TickeosContracts\Entity\Customer;
use EosUptrade\TICKeos\Library\TickeosContracts\Repository\CustomerRepository;
use EosUptrade\TICKeos\MajorCustomer\Domain\MandatoryFieldConfiguration\MandatoryFieldsChecker;
use EosUptrade\TICKeos\MajorCustomer\Entity\MajorCustomerEmployeeMandatoryFieldsConfiguration;
use EosUptrade\TICKeos\MajorCustomer\Repository\CustomerToMajorCustomerRepository;
use EosUptrade\TICKeos\Mobile\Domain\Layout\AccountError\AccountErrorBuilder;
use EosUptrade\TICKeos\Mobile\Domain\Layout\AccountField\AccountFieldBuilder;
use EosUptrade\TICKeos\Mobile\Domain\Layout\AccountField\AccountFieldContentBuilder;
use EosUptrade\TICKeos\Mobile\Domain\Layout\LayoutBlockBuilder;
use EosUptrade\TICKeos\Shop\Domain\Customer\CustomerDisabledByChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MobileServiceApi201906 extends MobileServiceApi201905
{
    public const string VERSION = '2019.06';

    private const string MAJOR_CUSTOMER_EMPLOYEE = 'major_customer_employee';

    public function __construct(
        sfUser $user,
        AuthorizationCheckerInterface $authorizationChecker,
        LegacyCertificateProvider $certificateProvider,
        MobileServiceClient $client,
        AnonymousCustomerSessionHandler $anonymousCustomerSessionHandler,
        CustomerRepository $customerRepository,
        CustomerDisabledByChecker $customerDisabledByChecker,
        LoggerInterface $logger,
        ?BrowserTypeConfiguration $browserTypeConfiguration,
        protected CustomerToMajorCustomerRepository $customerToMajorCustomerRepository,
        CustomerDeviceRepository $customerDeviceRepository,
        CustomerDeviceManager $customerDeviceManager,
        GenericRepository $genericRepository,
        CreateAccessToken $createAccessToken,
        protected TranslatorInterface $translator,
        ClaimOrderProduct $claimOrderProduct,
        OrderProductRepository $orderProductRepository,
        protected EntityManagerInterface $entityManager,
        protected MandatoryFieldsChecker $mandatoryFieldsChecker,
        ProcessLockService $processLockService,
    ) {
        parent::__construct(
            $user,
            $authorizationChecker,
            $certificateProvider,
            $client,
            $anonymousCustomerSessionHandler,
            $customerRepository,
            $customerDisabledByChecker,
            $logger,
            $browserTypeConfiguration,
            $customerToMajorCustomerRepository,
            $customerDeviceRepository,
            $customerDeviceManager,
            $genericRepository,
            $createAccessToken,
            $translator,
            $claimOrderProduct,
            $orderProductRepository,
        );
    }

}
