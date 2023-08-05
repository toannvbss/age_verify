<?php

namespace Smartosc\Customer\Observer\Customer;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\CatalogInventory\Observer\ProductQty;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use phpDocumentor\Reflection\Types\This;
use Smartosc\Customer\Block\Widget\Telephone;

class UpdateAdditionalField implements ObserverInterface
{
    const FORM_DATA_EXTRACTOR_CODE = 'customer_account_create';

    /**
     * @var \Magento\Customer\Model\Customer\Mapper
     */
    protected $customerMapper;
    /**
     * @var CustomerExtractor
     */
    protected $customerExtractor;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var Telephone
     */
    protected $telephoneBlock;

    /**
     * @param \Magento\Customer\Model\Customer\Mapper $customerMapper
     * @param CustomerExtractor $customerExtractor
     * @param CustomerRepositoryInterface $customerRepository
     * @param Telephone $telephoneBlock
     */
    public function __construct(
        \Magento\Customer\Model\Customer\Mapper $customerMapper,
        CustomerExtractor $customerExtractor,
        CustomerRepositoryInterface $customerRepository,
        Telephone $telephoneBlock
    ) {
        $this->customerMapper = $customerMapper;
        $this->customerExtractor = $customerExtractor;
        $this->customerRepository = $customerRepository;
        $this->telephoneBlock = $telephoneBlock;
    }

    /**
     * @param EventObserver $observer
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function execute(EventObserver $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $controller = $observer->getEvent()->getAccountController();
        foreach ($this->customAttributeCode() as $customAttribute) {
            if ($attributeValue = $controller->getRequest()->getParam($customAttribute)) {
                $customer->setCustomAttribute($customAttribute, $attributeValue);
            }
        }
        if ($prefix = $controller->getRequest()->getParam('telephone_prefix')
            && $tel = $controller->getRequest()->getParam('telephone')) {
            $telephone = $this->telephoneBlock->searchPhoneCode($prefix) . $tel;
            $customer->setTelephone($telephone);
        }
        if ($beverages = $controller->getRequest()->getParam('beverages_preference')) {
            $beverageValue = '';
            foreach ($beverages as $beverage) {
                if ($beverageValue) {
                    $beverageValue = $beverageValue . $beverage;
                } else {
                    $beverageValue = $beverage;
                }
            }
            $customer->setCustomAttribute('beverages_preference', $beverageValue);
        }
        $this->customerRepository->save($customer);
    }

    /**
     * @return string[]
     */
    private function customAttributeCode(): array
    {
        return [
            'salutation',
            'telephone_prefix'
        ];
    }

    /**
     * @param $inputData
     * @param $currentCustomerData
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    private function populateCustomerDataObject($inputData, $currentCustomerData)
    {
        $attributeValues = $this->customerMapper->toFlatArray($currentCustomerData);
        return $this->customerExtractor->extract(
            self::FORM_DATA_EXTRACTOR_CODE,
            $inputData,
            $attributeValues
        );
    }
}
