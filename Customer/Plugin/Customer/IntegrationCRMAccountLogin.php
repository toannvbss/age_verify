<?php

namespace Smartosc\Customer\Plugin\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Smartosc\CRM\Model\Authentication;
use Smartosc\CRM\Model\Customer\CrmProfile;
use Smartosc\CRM\Model\DataProvider\RequestOptionsBuilder;
use Smartosc\Customer\Block\Widget\Telephone;
use Psr\Log\LoggerInterface;

class IntegrationCRMAccountLogin
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;
    /**
     * @var CrmProfile
     */
    protected $crmProfile;
    /**
     * @var RequestOptionsBuilder
     */
    protected $optionBuilder;
    /**
     * @var Authentication
     */
    protected $crmAuthentication;
    /**
     * @var Telephone
     */
    protected $telephoneBlock;
    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerInterfaceFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var DateTime
     */
    protected $date;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var AddressFactory
     */
    protected $addressFactory;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param CustomerRepository $customerRepository
     * @param CrmProfile $crmProfile
     * @param RequestOptionsBuilder $optionBuilder
     * @param Authentication $crmAuthentication
     * @param Telephone $telephoneBlock
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     * @param DateTime $date
     * @param ManagerInterface $messageManager
     * @param AddressFactory $addressFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerRepository $customerRepository,
        CrmProfile $crmProfile,
        RequestOptionsBuilder $optionBuilder,
        Authentication $crmAuthentication,
        Telephone $telephoneBlock,
        CustomerInterfaceFactory $customerInterfaceFactory,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        DateTime $date,
        ManagerInterface $messageManager,
        AddressFactory $addressFactory,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->crmProfile = $crmProfile;
        $this->optionBuilder = $optionBuilder;
        $this->crmAuthentication = $crmAuthentication;
        $this->telephoneBlock = $telephoneBlock;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->date = $date;
        $this->messageManager = $messageManager;
        $this->addressFactory = $addressFactory;
        $this->logger = $logger;
    }


    /**
     * @param AccountManagementInterface $subject
     * @param \Closure $proceed
     * @param $email
     * @param $password
     * @return mixed
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function aroundAuthenticate(AccountManagementInterface $subject, \Closure $proceed, $email , $password)
    {
        $associatedAccountInWebsite = $subject->isEmailAvailable($email);
        $token = $this->crmAuthentication->getTokenAuthenticate();
        if (!$associatedAccountInWebsite) {
            $customer = $this->customerRepository->get($email);
            $customerNumber = $customer->getCustomAttribute('customer_number');
            if ($customerNumber && $customerNumber->getValue()) {
                return $this->integrationCustomer(
                    $token,
                    $customerNumber->getValue(),
                    $proceed,
                    $email,
                    $password,
                    (int)$customer->getId()
                );
            } else {
                $searchRequest = $this->optionBuilder->buildSearchProfile($email);
                $searchData = $this->crmProfile->searchSimpleProfileByEmail($token, $searchRequest);
                if ($searchData) {
                    $customerNumber = $searchData[0]['CustomerNumber'];
                    return $this->integrationCustomer(
                        $token,
                        $customerNumber,
                        $proceed,
                        $email,
                        $password,
                        (int)$customer->getId()
                    );
                } else {
                    $requestCreate = $this->optionBuilder->buildDataRequestCRMCreate($customer);
                    $responseCreate = $this->crmProfile->createCRMProfile($token, $requestCreate);
                    if (isset($responseCreate['Profile'])) {
                        $profileCrm = $responseCreate['Profile'];
                        $this->createPassword($token, $profileCrm, $password);
                        return $this->integrationCustomer(
                            $token,
                            $customerNumber,
                            $proceed,
                            $email,
                            $password,
                            (int)$customer->getId()
                        );
                    }
                }
            }
        } else {
            $searchRequest = $this->optionBuilder->buildSearchProfile($email);
            $searchData = $this->crmProfile->searchSimpleProfileByEmail($token, $searchRequest);
            if ($searchData) {
                $customerNumber = $searchData[0]['CustomerNumber'];
                return $this->integrationCustomer(
                    $token,
                    $customerNumber,
                    $proceed,
                    $email,
                    $password
                );
            } else {
                throw new AuthenticationException(__("Email doesn't exist. Please try again!"));
            }
        }
        return $proceed($email , $password);
    }

    /**
     * @param $token
     * @param $customerNumber
     * @param $proceed
     * @param $email
     * @param $password
     * @param int|null $customerId
     *
     * @return mixed
     * @throws AuthenticationException|\Magento\Framework\Exception\LocalizedException
     */
    private function integrationCustomer($token, $customerNumber, $proceed, $email, $password, int $customerId = null)
    {
        $signInData = $this->signInCrm($token, $customerNumber, $password);
        if (!isset($signInData['ErrorCode'])) {
            $profileData = $this->crmProfile->getCRMProfileInformation(
                $token,
                $customerNumber,
                $signInData['Token']
            );
            if ($customerId) {
                if ($this->updateCustomerData($signInData, $profileData, $password, $customerId)) {
                    return $proceed($email , $password);
                }
            } else {
                try {
                    $this->connectProfileToAccount($signInData, $profileData, $password);
                    return $proceed($email , $password);
                } catch (LocalizedException $e) {
                    $this->logger->info('request Uri: ', [$e]);
                    throw new AuthenticationException(__('Login account failed. Please try again'));
                }
            }
        }
        $this->logger->info('request Uri: ', $signInData);
        throw new LocalizedException(__(json_encode($signInData)));
    }

    /**
     * @param $signInData
     * @param $profileData
     * @param $password
     * @param int|null $customerId
     * @throws LocalizedException
     */
    public function connectProfileToAccount($signInData, $profileData, $password, int $customerId = null)
    {
        if ($customerId) {
            $customerData = $this->customerRepository->getById($customerId);
        } else {
            $customerData = $this->customerInterfaceFactory->create();
        }
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
            $customerData->setWebsiteId($websiteId);
            $customerData->setEmail($profileData['Email']);
            $customerData->setFirstname($profileData['FirstName']);
            $customerData->setLastname($profileData['LastName']);
            $phone = $this->cleavePhones($profileData['Phones']);
            $customerData->setTelephone($phone['telephone']);
            $customerData->setCustomAttribute('crm_telephone', $phone['telephone']);
            $customerData->setCustomAttribute('customer_number', $profileData['CustomerNumber']);
            $customerData->setCustomAttribute('customer_telephone', $phone['telephone']);
            $customerData->setCustomAttribute('telephone_prefix', $phone['prefix']);
            $customerData->setCustomAttribute('beverages_preference', $this->combineBeverages($profileData['Interests']));
            $customerData->setCustomAttribute('temporaries_password', $password);
            $customerData->setCustomAttribute('form_code', 'customer_account_login');
            $customerData->setCustomAttribute('custom_gender', $profileData['GenderCode']);
            $customerData->setGender($profileData['GenderCode'] ?? 0);
            $customerData->setCustomAttribute('crm_token', $signInData['Token']);
            $customerData->setCustomAttribute('crm_token_expire', $signInData['ExpiryDate']);
            if (!empty($profileData['DOB']) && strtotime($profileData['DOB'])) {
                $customerData->setDob($this->date-> date('Y-m-d', $profileData['DOB']));
            } elseif (!$customerData->getDob()) {
                $customerData->setDob(date("Y-m-d"));
            }
            $hasPassword = $this->encryptor->hash($password);
            $customer = $this->customerRepository->save($customerData, $hasPassword);
            $this->updateShippingAddressAccount($profileData, $customer->getId());
        } catch (InputException | InputMismatchException | LocalizedException $e) {
            $this->logger->info('request Uri: ', [$e]);
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param $profileData
     * @param $customerId
     */
    private function updateShippingAddressAccount($profileData, $customerId)
    {
        $currentUrl = $this->storeManager->getStore()->getCurrentUrl();
        if (strrpos($currentUrl, 'loginPost') !== false) {
            return '';
        }
        $addressData = $this->addressFactory->create();
        $phone = $this->cleavePhones($profileData['Phones']);
        $addressData->setCustomerId((int)$customerId)
            ->setFirstname($profileData['FirstName'])
            ->setLastname($profileData['LastName'])
            ->setTelephone($phone['telephone']);
        $hasAddress = false;
        foreach ($profileData['Addresses'] as $address) {
            if ($address['Address1']) {
                $street = [$address['Address1'], $address['Address2']];
                $addressData->setCountryId($address['CountryCode'])
                    ->setPostcode($address['PostalCode'])
                    ->setCity($address['City'])
                    ->setStreet($street)
                    ->setIsDefaultBilling('1')
                    ->setIsDefaultShipping('1')
                    ->setSaveInAddressBook('1');
                $hasAddress = true;
            }
        }
        if ($hasAddress) {
            try {
                $addressData->save();
            } catch (\Exception $e) {
                $this->messageManager->addWarningMessage('Update address to order.');
            }
        }
    }

    /**
     * @param $profilePhones
     * @return array
     */
    public function cleavePhones($profilePhones): array
    {
        $result = [];
        foreach ($profilePhones as $phone) {
            if ($phone['Type'] == "MOBILE") {
                $telephone = $phone['Number'];
                $telephoneCountryCode = $phone['CountryCode'];
                $telephoneArr = $this->telephoneBlock->getPhoneCodeOptions();
                $telephonePrefix = array_search('+' . $telephoneCountryCode, $telephoneArr);
                $result['telephone'] = $telephone;
                $result['prefix'] = $telephonePrefix;
            }
        }
        return $result;
    }

    /**
     * @param $interests
     * @return mixed
     */
    public function combineBeverages($interests)
    {
        $beveragesPreference = '';
        foreach ($interests as $beverages) {
            if ($beverages['Value'] == 'Y' && $beveragesPreference == '') {
                $beveragesPreference = $beverages['Code'];
            } elseif ($beverages['Value'] == 'Y' && $beveragesPreference != '') {
                $beveragesPreference = $beveragesPreference . ','. $beverages['Code'];
            }
        }
        return $beveragesPreference;
    }

    /**
     * @param $token
     * @param $customerNumber
     * @param $password
     * @return array|mixed
     */
    protected function signInCrm($token, $customerNumber, $password)
    {
        $requestPassword = $this->optionBuilder->buildSignInData($password);
        return $this->crmProfile->signInCrmProfile(
            $token,
            $requestPassword,
            $customerNumber
        );
    }

    /**
     * @param $signInData
     * @param $profileData
     * @param $password
     * @param $customerId
     * @return bool
     */
    protected function updateCustomerData($signInData, $profileData, $password, $customerId): bool
    {
        if (isset($signInData['ErrorCode'])) {
            return false;
        } else {
            try {
                $this->connectProfileToAccount($signInData, $profileData, $password, $customerId);
                return true;
            } catch (LocalizedException $e) {
                $this->logger->info('request Uri: ', [$e]);
                return false;
            }
        }
    }

    /**
     * @param $customer
     * @param $customerData
     */
    protected function setCustomerAttributes($customer, $customerData)
    {
        foreach ($customerData as $key => $data) {
            $customer->setCustomAttribute($key, $data);
        }
    }

    /**
     * @param $token
     * @param $profileCrm
     * @param $password
     * @return array|mixed
     * @throws LocalizedException
     */
    protected function createPassword($token, $profileCrm, $password)
    {
        if ($password && isset($profileCrm['CustomerNumber'])) {
            $requestPassword = $this->prepareRequestPassword($password);
            return $this->crmProfile->createPasswordCrmProfile(
                $token,
                $requestPassword,
                $profileCrm['CustomerNumber']
            );
        } else {
            throw new LocalizedException(__('Can\'t create password crm profile.'));
        }
    }

    /**
     * @param $password
     * @return string[]
     */
    private function prepareRequestPassword($password): array
    {
        return [
            "PasswordQuestion" => '',
            "PasswordAnswer" => '',
            "Password" => $password
        ];
    }
}
