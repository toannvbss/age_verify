<?php

namespace Smartosc\Customer\Plugin\Block\Account;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Block\Account\Dashboard;
use Smartosc\CRM\Model\Customer\CrmProfile;
use Smartosc\Customer\Model\CRMProfileManagement;

class PrepareCustomerCRM
{
    /**
     * @var CrmProfile
     */
    protected $crmProfile;
    /**
     * @var CRMProfileManagement
     */
    protected $CRMProfileManagement;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var bool
     */
    private $profileCrm = [];

    /**
     * @param CrmProfile $crmProfile
     * @param CRMProfileManagement $CRMProfileManagement
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        CrmProfile $crmProfile,
        CRMProfileManagement $CRMProfileManagement,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        $this->crmProfile = $crmProfile;
        $this->CRMProfileManagement = $CRMProfileManagement;
        $this->date = $date;
    }

    /**
     * @param Dashboard $dashboard
     * @param CustomerInterface $customer
     * @return mixed
     */
    public function afterGetCustomer(Dashboard $dashboard, CustomerInterface $customer)
    {
        if ($customer->getId() && isset($customer->getCustomAttributes()['customer_number'])) {
            $crmProfile = $this->prepareCustomerProfile($customer);
            if (!empty($crmProfile)) {
                $customer->setEmail($crmProfile['Email']);
                $customer->setFirstname($crmProfile['FirstName']);
                $customer->setLastname($crmProfile['LastName']);
                $customer->setGender($crmProfile['GenderCode']);
                $customer->setCustomAttribute('custom_gender', $crmProfile['GenderCode']);
                $customer->setCustomAttribute('salutation', $crmProfile['Title']);
                if (!empty($crmProfile['DOB'])) {
                    $dobValue = str_replace('T00:00:00+08:00', '', $crmProfile['DOB']);
                    if (strtotime($dobValue)) {
                        $customer->setDob($this->date->date('Y-m-d', $dobValue));
                    }
                } elseif (!$customer->getDob()) {
                    $customer->setDob(date("Y-m-d"));      
                }
                $customer->setCustomAttribute('beverages_preference', $this->prepareInterests($crmProfile['Interests']));
            }
        }
        return $customer;
    }

    /**
     * @param $interests
     * @return mixed|string
     */
    protected function prepareInterests($interests)
    {
        $beverages = '';
        foreach ($interests as $interest) {
            if ($interest['Value'] == 'Y') {
                if (!$beverages) {
                    $beverages = $interest['Code'];
                    continue;
                }
                $beverages = $beverages . ',' . $interest['Code'];
            }
        }
        return $beverages;
    }

    /**
     * @param $customer
     * @return array|mixed
     */
    protected function prepareCustomerProfile($customer)
    {
        if (!$this->profileCrm) {
            $this->profileCrm = $this->CRMProfileManagement->getCustomerProfileCrm($customer);
        }
        return $this->profileCrm;
    }
}
