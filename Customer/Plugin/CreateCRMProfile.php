<?php

namespace Smartosc\Customer\Plugin;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magestore\Webpos\Model\Customer\Data\Customer;
use Smartosc\CRM\Model\Authentication;
use Smartosc\CRM\Model\Customer\CrmProfile;
use Smartosc\CRM\Model\DataProvider\RequestOptionsBuilder;
use Smartosc\Customer\Model\CRMProfileManagement;

class CreateCRMProfile
{
    /**
     * @var Authentication
     */
    protected $crmAuthentication;
    /**
     * @var CrmProfile
     */
    protected $crmProfile;

    /**
     * @var string
     */
    protected $crmToken = '';
    /**
     * @var RequestOptionsBuilder
     */
    protected $requestOptionsBuilder;
    /**
     * @var CRMProfileManagement
     */
    protected $CRMProfileManagement;

    /**
     * @param Authentication $crmAuthentication
     * @param CrmProfile $crmProfile
     * @param RequestOptionsBuilder $requestOptionsBuilder
     * @param CRMProfileManagement $CRMProfileManagement
     */
    public function __construct(
        Authentication $crmAuthentication,
        CrmProfile $crmProfile,
        RequestOptionsBuilder $requestOptionsBuilder,
        CRMProfileManagement $CRMProfileManagement
    ) {
        $this->crmAuthentication = $crmAuthentication;
        $this->crmProfile = $crmProfile;
        $this->requestOptionsBuilder = $requestOptionsBuilder;
        $this->CRMProfileManagement = $CRMProfileManagement;
    }

    /**
     * @param AccountManagementInterface $subject
     * @param CustomerInterface $customer
     * @param string $hash
     * @param $redirectUrl
     * @return array
     * @throws InputException
     */
    public function beforeCreateAccountWithPasswordHash(
        AccountManagementInterface $subject,
        CustomerInterface $customer,
        string $hash,
        $redirectUrl
    ): array
    {
        try {
            $this->CRMProfileManagement->createCRMProfile($customer);
        } catch (InputException $e) {
            throw new InputException(__($e->getMessage()), $e);
        }
        return [$customer, $hash, $redirectUrl];
    }
}
