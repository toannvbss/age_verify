<?php

namespace Smartosc\Customer\Model;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magestore\Webpos\Model\Customer\Data\Customer;
use Smartosc\CRM\Model\Authentication;
use Smartosc\CRM\Model\Customer\CrmProfile;
use Smartosc\CRM\Model\DataProvider\RequestOptionsBuilder;

class CRMProfileManagement
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
     * @var Registry
     */
    protected $registry;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @param Authentication $crmAuthentication
     * @param CrmProfile $crmProfile
     * @param RequestOptionsBuilder $requestOptionsBuilder
     * @param Registry $registry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        Authentication $crmAuthentication,
        CrmProfile $crmProfile,
        RequestOptionsBuilder $requestOptionsBuilder,
        Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        $this->crmAuthentication = $crmAuthentication;
        $this->crmProfile = $crmProfile;
        $this->requestOptionsBuilder = $requestOptionsBuilder;
        $this->registry = $registry;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->date = $date;
    }

    /**
     * @param     $customer
     * @param int $location
     *
     * @throws \Magento\Framework\Exception\InputException
     */
    public function createCRMProfile($customer, int $location = 0)
    {
        $accountInfo = $this->searchProfile($customer->getEmail());
        $searchMobileRequest = $this->requestOptionsBuilder->buildSearchProfileByMobileNumber($this->requestOptionsBuilder->getMobileCountryCode($customer->getCustomAttributes()),
            $customer->getTelephone());
        $searchMobileResponse = $this->crmProfile->searchSimpleProfileByEmail($this->getCrmToken(), $searchMobileRequest);
        if ((!empty($accountInfo) && !$this->registry->registry('forget_password_flag')) || !empty($searchMobileResponse)) {
            throw new InputException(__('The phone number or email already existed.'));
        }
        try {
            $requestCreate = $this->requestOptionsBuilder->buildDataRequestCRMCreate($customer, null ,$location);
            $responseCreate = $this->crmProfile->createCRMProfile($this->getCrmToken(), $requestCreate);
            if (isset($responseCreate['Profile'])) {
                $profileCrm = $responseCreate['Profile'];
                $passwordInput = $this->getTemporariesPasswordAndSyncCRM($customer, $profileCrm);
                $responseLogin = $this->signInCrmProfileAfterUpdatePassword($passwordInput, $profileCrm);
                $this->updateCrmDataToCustomer($customer, $responseLogin);
            }
            if (isset($responseCreate['ErrorCode'])) {
                $this->messageManager->addErrorMessage(__('Create Profile failed'));
                if (isset($responseCreate['Message'])) {
                    $this->messageManager->addErrorMessage(__($responseCreate['Message']));
                    throw new InputException(__($responseCreate['Message']));
                }
            }

        } catch (NoSuchEntityException | LocalizedException $e) {
            throw new InputException(__('Can\'t create profile CRM.'));
        }
    }

    /**
     * @param $email
     * @return array
     */
    public function searchProfile($email): array
    {
        $paramsSearch = $this->requestOptionsBuilder->buildSearchProfile($email);
        return $this->crmProfile->searchSimpleProfileByEmail($this->getCrmToken(), $paramsSearch);
    }

    /**
     * @param $customer
     * @param AddressInterface|null $address
     * @throws InputException
     */
    public function updateCustomerCRMProfile($customer, AddressInterface $address = null)
    {
        if (isset($customer->getCustomAttributes()['customer_number'])) {
            $customerNumber = $customer->getCustomAttributes()['customer_number']->getValue();
            try {
                $this->updateProfileCrm($customer, $customerNumber, $address );
            } catch (NoSuchEntityException | LocalizedException $e) {
                throw new InputException(__($e->getMessage()));
            }
        } else {
            $paramsSearch = $this->requestOptionsBuilder->buildSearchProfile($customer->getEmail());
            $accountInfo = $this->crmProfile->searchSimpleProfileByEmail($this->getCrmToken(), $paramsSearch);
            if (empty($accountInfo)) {
                try {
                     $this->createCRMProfile($customer);
                } catch (NoSuchEntityException | LocalizedException $e) {
                    throw new InputException(__('Can\'t create profile CRM.'));
                }
            } else {
                try {
                    $this->processResponseAndUpdateProfile($customer, $accountInfo);
                } catch (InputException | NoSuchEntityException | LocalizedException $e) {
                    throw new InputException(__($e->getMessage()));
                }
            }
        }
    }

    /**
     * @param CustomerInterface $customer
     * @param array $profileCrm
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function processResponseAndUpdateProfile(CustomerInterface $customer, array $profileCrm)
    {
        if (isset($profileCrm[0]['CustomerNumber'])) {
            $customer->setCustomAttribute('customer_number', $profileCrm[0]['CustomerNumber']);
            $this->updateProfileCrm($customer, $profileCrm[0]['CustomerNumber']);
        }
    }

    /**
     * @param $customer
     * @param $customerNumber
     * @param AddressInterface|null $address
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function updateProfileCrm($customer, $customerNumber, AddressInterface $address = null)
    {
        $profileToken = $this->getProfileTokenFromCustomer($customer);
        if ($profileToken) {
            $currentCRMProfile = $this->crmProfile->getCRMProfileInformation(
                $this->getCrmToken(),
                $customerNumber,
                $profileToken
            );
            $requestCreate = $this->requestOptionsBuilder->buildDataPutCRMProfile(
                $customer,
                $currentCRMProfile,
                $address
            );
            $responseUpdate = $this->crmProfile->updateCustomerCRMProfile(
                $this->getCrmToken(),
                $requestCreate,
                $customerNumber,
                $profileToken
            );
            if ($responseUpdate->getStatusCode() !== 200) {
                throw new InputException(__($responseUpdate->getReasonPhrase()));
            }
        } else {
            $this->customerSession->logout()
                ->setBeforeAuthUrl('*/*/login')
                ->setLastCustomerId($customer->getId());
            $this->messageManager->addErrorMessage(__('Login to CRM happen error.'));
            throw new InputException(__('Login to CRM happen error. Please log in again.'));
        }
    }

    /**
     * @param $customer
     * @return mixed|null
     */
    public function getProfileTokenFromCustomer($customer)
    {
        if (isset($customer->getCustomAttributes()['crm_token'])) {
            return $customer->getCustomAttributes()['crm_token']->getValue();
        }
        return null;
    }

    /**
     * @param $customer
     * @return array|mixed
     */
    public function getCustomerProfileCrm($customer)
    {
        $customerNumber = $customer->getCustomAttributes()['customer_number']->getValue();
        $profileToken = $this->getProfileTokenFromCustomer($customer);
        return $this->crmProfile->getCRMProfileInformation(
            $this->crmProfile->getCrmToken(),
            $customerNumber,
            $profileToken
        );
    }

    /**
     * @return string
     */
    private function getCrmToken(): string
    {
        if (!$this->crmToken) {
            $this->crmToken = $this->crmAuthentication->getTokenAuthenticate();
        }
        return $this->crmToken;
    }

    /**
     * @param CustomerInterface|Customer $customer
     * @throws InputException
     */
    private function getTemporariesPasswordAndSyncCRM($customer, $profileCrm)
    {
        if (isset($customer->getCustomAttributes()['temporaries_password'])) {
            $temporary = $customer->getCustomAttributes()['temporaries_password'];
            $customer->setCustomAttribute('temporaries_password', '');
            $passwordInput = $temporary->getValue();
            $this->createPasswordByCustomerNumber($passwordInput, $profileCrm);
            return $passwordInput;
        }
        return '';
    }

    /**
     * @param CustomerInterface|Customer $customer
     * @param array $profileCrm
     * @throws InputException
     */
    protected function updateCrmDataToCustomer(CustomerInterface $customer, array $profileCrm)
    {
        if (isset($profileCrm['Token'])
            && isset($profileCrm['CustomerNumber'])
            && isset($profileCrm['ExpiryDate'])
        ) {
            $customer->setCustomAttribute('crm_token', $profileCrm['Token']);
            $customer->setCustomAttribute('customer_number', $profileCrm['CustomerNumber']);
            $customer->setCustomAttribute('crm_token_expire', $profileCrm['ExpiryDate']);
        } else {
            throw new InputException(__('Can\'t login this account to crm profile.'));
        }
    }

    /**
     * @param string $password
     * @param array $profileCrm
     * @return array|mixed
     * @throws InputException
     */
    protected function createPasswordByCustomerNumber(string $password, array $profileCrm)
    {
        if ($password && isset($profileCrm['CustomerNumber'])) {
            $requestPassword = $this->prepareRequestPassword($password);
            return $this->crmProfile->createPasswordCrmProfile(
                $this->getCrmToken(),
                $requestPassword,
                $profileCrm['CustomerNumber']
            );
        } else {
            throw new InputException(__('Can\'t create password crm profile.'));
        }
    }

    /**
     * @param $password
     * @param $profileCrm
     * @return array|mixed
     * @throws InputException
     */
    protected function signInCrmProfileAfterUpdatePassword($password, $profileCrm)
    {
        if ($password && isset($profileCrm['CustomerNumber'])) {
            $requestPassword = $this->prepareLoginPassword($password);
            return $this->crmProfile->signInCrmProfile(
                $this->getCrmToken(),
                $requestPassword,
                $profileCrm['CustomerNumber']
            );
        } else {
            throw new InputException(__('Can\'t login to crm profile.'));
        }
    }

    public function getCustomerNumberByCustomer($customer)
    {
        if ($customer->getCustomAttribute('customer_number')) {
            return $customer->getCustomAttribute('customer_number')->getValue();
        }
        return null;
    }

    public function getProfileTokenByCustomer($customer)
    {
        if ($customer->getCustomAttribute('crm_token')) {
            return $customer->getCustomAttribute('crm_token')->getValue();
        }
        return null;
    }

    /**
     * @param $customer
     * @param bool $isSubscribe
     * @return bool
     */
    public function updateSubscriptionCRMProfile($customer, bool $isSubscribe = false): bool
    {
        $customerNumber = $this->getCustomerNumberByCustomer($customer);
        $profileToken = $this->getProfileTokenFromCustomer($customer);
        if (!$customerNumber && !$profileToken) {
            return false;
        }
        $params = $this->buildParams($isSubscribe);
        $this->crmProfile->contactPreferenceManager(
            $this->getCrmToken(),
            $customerNumber,
            $profileToken,
            'PUT',
            $params
        );
        return true;
    }

    /**
     * @param      $customerNumber
     * @param      $profileToken
     * @param bool $isSubscribe
     *
     * @return bool
     */
    public function subscriptionCRMProfileNoneCustomer($customerNumber,$profileToken, bool $isSubscribe = false): bool
    {
        $params = $this->buildParams($isSubscribe);
        $this->crmProfile->updateContactPreferenceNoneAccount(
            $this->getCrmToken(),
            $params,
            $customerNumber,
            $profileToken
        );
        return true;
    }

    /**
     * @param $isSubscribe
     * @return string[]
     */
    protected function buildParams($isSubscribe): array
    {
        $currentSubscribe = 'N';
        if ($isSubscribe) {
            $currentSubscribe = 'Y';
        }
        return [
            'Code' => 'EMAIL',
            'Value' => $currentSubscribe
        ];
    }

    /**
     * @return string[]
     */
    protected function prepareRequestPassword($password, $question = '', $answer = ''): array
    {
        return [
            "PasswordQuestion" => $question,
            "PasswordAnswer" => $answer,
            "Password" => $password
        ];
    }

    /**
     * @return string[]
     */
    protected function prepareLoginPassword($password): array
    {
        return [
            "Password" => $password
        ];
    }
}
