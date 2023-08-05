<?php
namespace Miu\AgeVerification\Block;

use Magento\Framework\View\Element\Template;

class AgeVerification extends Template
{
    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('ageverification/index/index', ['_secure' => true]);
    }
}
