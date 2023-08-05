<?php

namespace Smartosc\Customer\Observer\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Registry;
use Magestore\Webpos\Model\Customer\Data\Customer;
use Smartosc\CRM\Model\Authentication;
use Smartosc\CRM\Model\Lookup\CRMData;
use Smartosc\Customer\Model\CRMProfileManagement;
use Magento\Framework\Math\Random;

/**
 * Class CreateCRMProfilePOS
 *
 * @package Smartosc\Customer\Observer\Customer
 */
class CreateCRMProfilePOS implements ObserverInterface
{
    /**
     * @var CRMProfileManagement
     */
    protected $CRMProfileManagement;

    /**
     * @var Random
     */
    protected $mathRandom;

    /**
     * @var CRMData
     */
    protected $crmData;
    /**
     * @var Authentication
     */
    protected $authentication;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * CreateCRMProfilePOS constructor.
     *
     * @param CRMProfileManagement        $CRMProfileManagement
     * @param Random                      $mathRandom
     * @param CRMData                     $crmData
     * @param Authentication              $authentication
     * @param CustomerRepositoryInterface $customerRepository
     * @param Registry                    $_coreRegistry
     */
    public function __construct(
        CRMProfileManagement $CRMProfileManagement,
        Random $mathRandom,
        CRMData $crmData,
        Authentication $authentication,
        CustomerRepositoryInterface $customerRepository,
        Registry $_coreRegistry
    ) {
        $this->CRMProfileManagement = $CRMProfileManagement;
        $this->mathRandom = $mathRandom;
        $this->crmData = $crmData;
        $this->authentication = $authentication;
        $this->customerRepository = $customerRepository;
        $this->_coreRegistry = $_coreRegistry;
    }

    /**
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomerRequest();
        $customerModel = $observer->getEvent()->getCustomerSaved();
        try {
            $genderString = $customerModel->getAttribute('gender')->getSource()->getOptionText($customerModel->getData('gender'));
            $genderCode = $this->mapGenderCode($genderString);
            $randomPassword = $this->generatePassword();
            $customer->setCustomAttribute('temporaries_password', $randomPassword);
            $customer->setCustomAttribute('telephone_prefix', '+65');
            $customer->setCustomAttribute('custom_gender', $genderCode);
            $locationId = $customerModel->getData('created_location_id') ? $customerModel->getData('created_location_id') : 0;
            $this->CRMProfileManagement->createCRMProfile($customer, $locationId);
            $currentCustomer = $this->customerRepository->get($customer->getEmail());
            $this->updateCrmDataToCustomer($currentCustomer, $customer);
            $this->customerRepository->save($currentCustomer);
        } catch (InputException $e) {
            throw new InputException(__($e->getMessage()), $e);
        }
    }

    /**
     * Retrieve random password
     *
     * @param int $length
     *
     * @return  string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generatePassword(int $length = 10): string
    {
        $chars = \Magento\Framework\Math\Random::CHARS_LOWERS
            . \Magento\Framework\Math\Random::CHARS_UPPERS
            . \Magento\Framework\Math\Random::CHARS_DIGITS;

        return $this->mathRandom->getRandomString($length, $chars);
    }

    /**
     * @param $genderString
     *
     * @return string
     */
    public function mapGenderCode($genderString): string
    {
        $token = $this->authentication->getTokenAuthenticate();
        $crmGender = $this->crmData->getListDataLookup($token, 'GENDER');
        if ($crmGender) {
            foreach ($crmGender as $gender) {
                if ($gender['TypeCodeValue'] == $genderString) {
                    return $gender['TypeCode'];
                }
            }
        }
        return $genderString;
    }

    /**
     * @param CustomerInterface|Customer $customer
     * @param CustomerInterface          $customerRequest
     */
    protected function updateCrmDataToCustomer(CustomerInterface $customer, CustomerInterface $customerRequest)
    {
        $customer->setCustomAttribute('crm_token', $customerRequest->getCustomAttribute('crm_token')->getValue());
        $customer->setCustomAttribute('customer_number',
            $customerRequest->getCustomAttribute('customer_number')->getValue());
        $customer->setCustomAttribute('crm_token_expire',
            $customerRequest->getCustomAttribute('crm_token_expire')->getValue());
    }
}
