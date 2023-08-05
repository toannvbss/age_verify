<?php

namespace Smartosc\Customer\Plugin\Ui\Component;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\ResourceConnection;
use Smartosc\Customer\Block\Widget\Telephone;

class DataProvider
{
    /**
     * @var Config
     */
    protected $eavConfig;
    /**
     * @var EavSetup
     */
    protected $eavSetup;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var Telephone
     */
    protected $telephoneBlock;

    /**
     * @param Config $eavConfig
     * @param EavSetup $eavSetup
     * @param ResourceConnection $resourceConnection
     * @param Telephone $telephoneBlock
     */
    public function __construct(
        Config $eavConfig,
        EavSetup $eavSetup,
        ResourceConnection $resourceConnection,
        Telephone $telephoneBlock
    ) {
        $this->eavConfig = $eavConfig;
        $this->eavSetup = $eavSetup;
        $this->resourceConnection = $resourceConnection;
        $this->telephoneBlock = $telephoneBlock;
    }

    /**
     * @param \Magento\Customer\Ui\Component\DataProvider $subject
     * @param $result
     * @return mixed
     */
    public function afterGetData(\Magento\Customer\Ui\Component\DataProvider $subject, $result)
    {
        foreach ($result['items'] as &$item) {
            $item['billing_telephone'] = $this->getCustomAttribute($item['entity_id'], 'telephone_prefix') . $item['billing_telephone'];
            $item['custom_gender'] = $this->getCustomAttribute($item['entity_id'], 'custom_gender');
            $item['gender'] = $this->getCustomAttribute($item['entity_id'], 'custom_gender');
        }
        return $result;
    }

    /**
     * @param $customerId
     * @param $attributeCode
     * @return mixed|string
     */
    protected function getCustomAttribute($customerId, $attributeCode)
    {
        $connection = $this->resourceConnection->getConnection();
        $valueAttrPrTable = $this->resourceConnection->getTableName('customer_entity_varchar');
        return $this->getValueAttribute(
            $connection,
            $attributeCode,
            $valueAttrPrTable,
            $customerId
        );
    }

    /**
     * @param $connection
     * @param $attributeCode
     * @param $valueAttrPrTable
     * @param $customerId
     * @return mixed|string
     */
    protected function getValueAttribute($connection, $attributeCode, $valueAttrPrTable, $customerId)
    {
        $eaTable = $this->resourceConnection->getTableName('eav_attribute');
        $selectAttributeId = $connection->select()
            ->from(
                ['vpa' => $eaTable],
                'vpa.attribute_id'
            )->where('attribute_code = ?', $attributeCode);
        $attributeRows = $connection->fetchAll($selectAttributeId);
        foreach ($attributeRows as $row) {
            $selectAttributeValue = $connection->select()
                ->from(
                    ['r' => $valueAttrPrTable],
                    'r.value'
                )->where(
                    'attribute_id = ?', $row['attribute_id']
                )->where(
                    'entity_id = ?', $customerId
                );
            $attributeValueProduct = $connection->fetchAll($selectAttributeValue);
            foreach ($attributeValueProduct as $attrValueSelect) {
                return $this->telephoneBlock->searchPhoneCode($attrValueSelect['value']);
            }
        }
        return '';
    }
}
