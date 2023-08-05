<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Smartosc\Customer\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;

/**
* Patch is mechanism, that allows to do atomic upgrade data changes
*/
class UpdateRequireTelephoneAndGender implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    protected $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        foreach ($this->attributesCustomer() as $attributeCode) {
            $this->updateCustomerVisibleAttribute($eavSetup, $attributeCode);
        }
    }

    /**
     * @param $eavSetup
     * @param $code
     */
    protected function updateCustomerVisibleAttribute($eavSetup, $code)
    {
        $eavSetup->updateAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            $code,
            'is_required',
            1
        );
    }

    /**
     * @return string[]
     */
    protected function attributesCustomer()
    {
        return [
            'gender',
            'dob'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [

        ];
    }
}
