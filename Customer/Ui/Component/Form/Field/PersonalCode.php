<?php

namespace Smartosc\Customer\Ui\Component\Form\Field;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Smartosc\CRM\Model\Customer\CrmProfile;
use Smartosc\CRM\Model\Authentication;

class PersonalCode extends \Magento\Ui\Component\Form\Field
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CrmProfile
     */
    protected $crmProfile;

    /**
     * @var Authentication
     */
    protected $authentication;

    /**
     * PersonalCode constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param RequestInterface $request
     * @param CustomerRepositoryInterface $customerRepository
     * @param CrmProfile $crmProfile
     * @param Authentication $authentication
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository,
        CrmProfile $crmProfile,
        Authentication $authentication,
        array $components = [],
        array $data = []
    )
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->crmProfile = $crmProfile;
        $this->authentication = $authentication;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepare()
    {
        parent::prepare();
        $personalCode = $this->getPersonalCodeFromCrmApi();
        $this->_data['config']['default'] = $personalCode;
    }

    /**
     * @return mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getPersonalCodeFromCrmApi() {
        $customerEmail = $this->getCustomerEmail();
        if ($customerEmail) {
            $token = $this->authentication->getTokenAuthenticate();
            $params = ["EmailAddress" => $customerEmail];
            $result = $this->crmProfile->searchSimpleProfileByEmail($token, $params);
            if (isset($result[0]['PersonalCode'])) {
                return $result[0]['PersonalCode'];
            }
        }
        return null;
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCustomerEmail() {
        $id = $this->request->getParam('id', null);
        if ($id) {
            $customer   = $this->customerRepository->getById($id);
            if ($customer) {
                return $customer->getEmail();
            }
        }
        return null;
    }
}
