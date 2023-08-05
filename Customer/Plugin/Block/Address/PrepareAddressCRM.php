<?php

namespace Smartosc\Customer\Plugin\Block\Address;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Block\Address\Edit;
use Smartosc\CRM\Model\Customer\CrmProfile;
use Smartosc\Customer\Model\CRMProfileManagement;

class PrepareAddressCRM
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
     * @param CrmProfile $crmProfile
     * @param CRMProfileManagement $CRMProfileManagement
     */
    public function __construct(
        CrmProfile $crmProfile,
        CRMProfileManagement $CRMProfileManagement
    ) {
        $this->crmProfile = $crmProfile;
        $this->CRMProfileManagement = $CRMProfileManagement;
    }

    /**
     * @param Edit $edit
     * @param AddressInterface $address
     * @return AddressInterface
     */
    public function afterGetAddress(Edit $edit, AddressInterface $address): AddressInterface
    {
        $customer = $edit->getCustomer();
        if ($customer->getId() && isset($customer->getCustomAttributes()['customer_number'])) {
            $crmProfile = $this->CRMProfileManagement->getCustomerProfileCrm($customer);
            $address = $this->prepareAddressProfileCrm($address, $crmProfile['Addresses'][0]);
        }
        return $address;
    }

    /**
     * @param $address
     * @param $crmAddress
     * @return mixed
     */
    protected function prepareAddressProfileCrm($address, $crmAddress)
    {
        $address->setCity($crmAddress['City']);
        $address->setPostcode($crmAddress['PostalCode']);
        $address->setStreet([$crmAddress['Address1'], $crmAddress['Address2']]);
        return $address;
    }

    /**
     * @param $phones
     * @return mixed|string
     */
    public function getMobilePhoneCrm($phones)
    {
        foreach ($phones as $phone) {
            if ($phone['Type'] == 'MOBILE' && $phone['Number']) {
                return $phone['Number'];
            }
            if ($phone['Type'] == 'HOME' && $phone['Number']) {
                return $phone['Number'];
            }
            if ($phone['Type'] == 'OFFICE' && $phone['Number']) {
                return $phone['Number'];
            }
        }
        return '';
    }
}
