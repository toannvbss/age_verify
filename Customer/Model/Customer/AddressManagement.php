<?php

namespace Smartosc\Customer\Model\Customer;

class AddressManagement implements \Smartosc\Customer\Api\Customer\AddressManagementInterface
{
    /**
     * @var \Magento\Customer\Model\AddressRegistry
     */
    protected $addressRegistry;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Address
     */
    protected $addressResourceModel;

    /**
     * @param \Magento\Customer\Model\AddressRegistry       $addressRegistry
     * @param \Magento\Customer\Model\CustomerRegistry      $customerRegistry
     * @param \Magento\Customer\Model\ResourceModel\Address $addressResourceModel
     */
    public function __construct(
        \Magento\Customer\Model\AddressRegistry $addressRegistry,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Customer\Model\ResourceModel\Address $addressResourceModel
    ) {
        $this->addressRegistry = $addressRegistry;
        $this->customerRegistry = $customerRegistry;
        $this->addressResourceModel = $addressResourceModel;
    }

    /**
     * Delete customer address by ID.
     *
     * @param int $addressId
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($addressId)
    {
        $address = $this->addressRegistry->retrieve($addressId);
        $customerModel = $this->customerRegistry->retrieve($address->getCustomerId());
        $customerModel->getAddressesCollection()->removeItemByKey($addressId);
        $this->addressResourceModel->delete($address);
        $this->addressRegistry->remove($addressId);
        return true;
    }
}
