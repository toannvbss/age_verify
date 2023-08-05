<?php

namespace Smartosc\Customer\Plugin\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Smartosc\CRM\Model\Authentication;
use Smartosc\CRM\Model\Customer\CrmProfile;
use Smartosc\CRM\Model\DataProvider\RequestOptionsBuilder;

class ChangePasswordCRMProfile
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var CrmProfile
     */
    protected $crmProfile;
    /**
     * @var RequestOptionsBuilder
     */
    protected $requestOptionsBuilder;
    /**
     * @var Authentication
     */
    protected $crmAuthentication;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CrmProfile $crmProfile
     * @param RequestOptionsBuilder $requestOptionsBuilder
     * @param Authentication $crmAuthentication
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CrmProfile $crmProfile,
        RequestOptionsBuilder $requestOptionsBuilder,
        Authentication $crmAuthentication,
        ManagerInterface $messageManager
    ) {
        $this->customerRepository = $customerRepository;
        $this->crmProfile = $crmProfile;
        $this->requestOptionsBuilder = $requestOptionsBuilder;
        $this->crmAuthentication = $crmAuthentication;
        $this->messageManager = $messageManager;
    }

    /**
     * @param AccountManagementInterface $subject
     * @param $email
     * @param $currentPassword
     * @param $newPassword
     * @throws LocalizedException
     */
    public function beforeChangePassword(AccountManagementInterface $subject, $email, $currentPassword, $newPassword)
    {
        try {
            $customer = $this->customerRepository->get($email);
            $crmToken = $this->crmProfile->getCrmToken();
            $paramsSearch = $this->requestOptionsBuilder->buildSearchProfile($customer->getEmail());
            $accountInfo = $this->crmProfile->searchSimpleProfileByEmail($crmToken, $paramsSearch);
            if (empty($accountInfo)) {
                $requestCreate = $this->requestOptionsBuilder->buildDataRequestCRMCreate($customer);
                $this->crmProfile->createCRMProfile($crmToken, $requestCreate);
            } else {
                $customerNumber = $accountInfo[0]['CustomerNumber'];
                $customer->setCustomAttribute('customer_number', $customerNumber);
                $dataChangePassword = $this->requestOptionsBuilder->prepareDataChangePassword($currentPassword, $newPassword);
                $crmCustomerToken = $customer->getCustomAttribute('crm_token')->getValue();
                $changePasswordResponse = $this->crmProfile->changePasswordCRM(
                    $crmToken,
                    $dataChangePassword,
                    $customerNumber,
                    $crmCustomerToken
                );

                if (isset($changePasswordResponse['ErrorCode'])) {
                    throw new LocalizedException(__('Can\'t update password CRM.'));
                }
            }
        } catch (NoSuchEntityException | LocalizedException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
