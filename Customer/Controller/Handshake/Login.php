<?php

namespace Smartosc\Customer\Controller\Handshake;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smartosc\CRM\Model\Authentication;
use Smartosc\CRM\Model\Customer\CrmProfile;
use Smartosc\Customer\Plugin\Customer\IntegrationCRMAccountLogin;
use Magento\Customer\Model\CustomerFactory;
use Smartosc\Customer\Helper\Data as CustomerHelper;

class Login extends Action
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var IntegrationCRMAccountLogin
     */
    protected $integrationAccount;

    /**
     * @var Authentication
     */
    protected $crmAuthentication;

    /**
     * @var CrmProfile
     */
    protected $crmProfile;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * Login constructor.
     *
     * @param Context                     $context
     * @param Session                     $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param SearchCriteriaBuilder       $searchCriteriaBuilder
     * @param TimezoneInterface           $timezone
     * @param IntegrationCRMAccountLogin  $integrationAccount
     * @param Authentication              $crmAuthentication
     * @param CrmProfile                  $crmProfile
     * @param CustomerFactory             $customerFactory
     * @param StoreManagerInterface       $storeManager
     * @param CustomerHelper              $customerHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TimezoneInterface $timezone,
        IntegrationCRMAccountLogin $integrationAccount,
        Authentication $crmAuthentication,
        CrmProfile $crmProfile,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        CustomerHelper $customerHelper
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->timezone = $timezone;
        $this->integrationAccount = $integrationAccount;
        $this->crmAuthentication = $crmAuthentication;
        $this->crmProfile = $crmProfile;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->customerHelper = $customerHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $params = $this->getRequest()->getParams();
            if (isset($params['token'])) {
                $payload = json_decode(base64_decode(str_replace('_', '/',
                    str_replace('-', '+', explode('.', $params['token'])[1]))), true);
                if (isset($payload['CustomerNumber']) && isset($payload['exp']) && $payload['ProfileToken']) {
                    $searchCriteria = $this->searchCriteriaBuilder->addFilter('customer_number',
                        $payload['CustomerNumber'])->create();
                    $customers = $this->customerRepository->getList($searchCriteria);

                    $currentTime = $this->timezone->date()->format(\DateTime::ATOM);
                    $expiredTime = $this->timezone->date($payload['exp'])->format(\DateTime::ATOM);
                    if ($customers->getTotalCount() > 0) {
                        foreach ($customers->getItems() as $customer) {
                            if ($expiredTime < $currentTime) {
                                $this->messageManager->addErrorMessage(__('Expired time is not valid'));
                                return $this->resultRedirectFactory->create()->setPath("customer/account/login");
                            }
                            $customer->setCustomAttribute('crm_token_expire', $expiredTime);
                            $customer->setCustomAttribute('crm_token', $payload['ProfileToken']);
                            $this->customerRepository->save($customer);
                            $this->customerSession->loginById($customer->getId());
                            $this->customerHelper->setAgeCookie(1);
                            break;
                        }
                    } else {
                        if ($expiredTime < $currentTime) {
                            $this->messageManager->addErrorMessage(__('Expired time is not valid'));
                            return $this->resultRedirectFactory->create()->setPath("customer/account/login");
                        }

                        $signInData = [
                            'Token' => $payload['ProfileToken'],
                            'ExpiryDate' => $expiredTime
                        ];
                        $token = $this->crmAuthentication->getTokenAuthenticate();
                        $profileData = $this->crmProfile->getCRMProfileInformation(
                            $token,
                            $payload['CustomerNumber'],
                            $signInData['Token']
                        );

                        try {
                            $this->integrationAccount->connectProfileToAccount($signInData, $profileData,
                                "Password@123");
                            $storeId = $this->storeManager->getStore()->getId();
                            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
                            $newCustomer = $this->customerFactory->create()
                                ->setWebsiteId($websiteId)
                                ->loadByEmail($profileData['Email']);
                            if ($newCustomer->getId()) {
                                $this->customerSession->loginById($newCustomer->getId());
                                $this->customerHelper->setAgeCookie(1);
                            } else {
                                $this->messageManager->addErrorMessage(__('Error happen when create new Account!!!'));
                                return $this->resultRedirectFactory->create()->setPath("customer/account/login");
                            }
                        } catch (\Exception $exception) {
                            $this->messageManager->addErrorMessage(__('Error happen when create new Account!!!'));
                            return $this->resultRedirectFactory->create()->setPath("customer/account/login");
                        }
                    }
                } else {
                    throw new \Exception(__('Missing payload field CustomerNumber or exp or ProfileToken'));
                }
            }

            if (isset($params['redirect_url'])) {
                return $this->resultRedirectFactory->create()->setUrl($params['redirect_url']);
            } else {
                throw new \Exception(__('Missing redirect_url param'));
            }
        } catch (NoSuchEntityException | LocalizedException | \Exception $noSuchEntityException) {
            echo $noSuchEntityException->getMessage();
        }
    }
}
