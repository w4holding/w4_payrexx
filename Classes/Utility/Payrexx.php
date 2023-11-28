<?php

namespace W4Services\W4Payrexx\Utility;

use \Extcode\Cart\Domain\Model\Cart;
use Extcode\Cart\Domain\Model\Order\Item;
//use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use W4Services\W4Payrexx\Models\Gateway;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class Payrexx
{
    /**
     * Prefix to referenceId in Gateway
     */
    const REFERENCE_PREFIX = 'web_';

    /**
     * @var Gateway
     */
    private $gateway;

    /**
     * @var mixed
     */
    private $extensionConfiguration;

    /**
     * @var
     */
    private $cartSHash;

    /**
     * @var
     */
    private $cartFHash;

    /**
     * @var array
     */
    private $cartConf = [];

    /**
     * @var object
     */
    private $objectManager;

    /**
     * @var
     */
    private $persistenceManager;

    /**
     * @var
     */
    private $configurationManager;

    /**
     * @var
     */
    private $orderItem;

    private $conf;

    /**
     * Payrexx constructor.
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(
            ObjectManager::class
        );
        $this->persistenceManager = $this->objectManager->get(
            PersistenceManager::class
        );
        $this->configurationManager = $this->objectManager->get(
            ConfigurationManager::class
        );

        $this->cartConf = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Cart'
        );

        $this->conf = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'W4Payrexx'
        );

        $this->extensionConfiguration = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get('w4_payrexx');
    }

    /**
     * @param Item $orderItem
     * @return mixed
     */
    public function processPayment(Item $orderItem, Cart $cart): array
    {

        $this->orderItem = $orderItem;

        $this->cartSHash = $cart->getSHash();
        $this->cartFHash = $cart->getFHash();

        $content = [];

        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);

        $this->gateway = $this->prepareData($orderItem);

        $apiSignature = $this->createApiSignature();

        $formParams = $this->gateway->toArray();
        $formParams['ApiSignature'] = $apiSignature;

        $response = $requestFactory->request($this->getApiUrl() . $this->getApiInstance(), 'POST', ['form_params' => $formParams]);

        if ($response->getStatusCode() === 200) $content = $response->getBody()->getContents();

        $content = json_decode($content, TRUE);

        return $content['data'][0];
    }
    /**
     * @param Item $orderItem
     * @return Gateway
     */
    private function prepareData(Item $orderItem): Gateway
    {
        $gateway = GeneralUtility::makeInstance(
            Gateway::class
        );

        $gateway->addField('forename', $orderItem->getBillingAddress()->getFirstName());
        $gateway->addField('surname', $orderItem->getBillingAddress()->getLastName());
        $gateway->addField('email', $orderItem->getBillingAddress()->getEmail());
        $gateway->addField('custom_field_1', (string)$orderItem->getOrderNumber(), LocalizationUtility::translate('tx_cart_domain_model_order_item.order_number', 'cart'));
        $gateway->setAmount($orderItem->getTotalGross() * 100);
        $gateway->setCurrency($this->getCurrency());
        $gateway->setVatRate($orderItem->getTax()->toArray()[0]->getTaxClass()->getValue());
        $gateway->setReferenceId(self::REFERENCE_PREFIX . (string)$orderItem->getOrderNumber());
        $gateway->setSuccessRedirectUrl($this->getUrl('success', $this->cartSHash));
        $gateway->setFailedRedirectUrl($this->getUrl('failed', $this->cartFHash));
        $gateway->setCancelRedirectUrl($this->getUrl('cancel', $this->cartFHash));
 
        return $gateway;
    }

    /**
     * @param string $action
     * @param string $hash
     * @return string
     */
    private function getUrl(string $action, string $hash): string
    {
        $pid = $this->cartConf['settings']['cart']['pid'];

        $arguments = [
            'tx_w4payrexx_cart' => [
                'hash' => $hash,
                'controller' => 'Order\Payment',
                'order' => $this->orderItem->getUid(),
                'action' => $action
            ]
        ];

        $uriBuilder = $this->getUriBuilder();
        return $uriBuilder->reset()
            ->setTargetPageUid($pid)
            ->setTargetPageType($this->conf['redirectTypeNum'])
            ->setCreateAbsoluteUri(true)
            ->setArguments($arguments)
            ->build();
    }

    /**
     * @return string
     */
    private function createApiSignature(): string
    {
        return base64_encode(hash_hmac('sha256', http_build_query($this->gateway->toArray(), null, '&'), $this->getApiSecret(), true));
    }

    /**
     * @return string
     */
    private function getApiUrl(): string
    {
        return $this->extensionConfiguration['payrexx_api_url'];
    }

    /**
     * @return string
     */
    private function getApiSecret(): string
    {
        return $this->extensionConfiguration['payrexx_api_secret'];
    }

    /**
     * @return string
     */
    private function getApiInstance(): string
    {
        return $this->extensionConfiguration['payrexx_api_instance'];
    }

    /**
     * @return string
     */
    private function getCurrency(): string
    {
        return $this->extensionConfiguration['currency'];
    }

    /**
     * @return UriBuilder
     */
    private function getUriBuilder(): UriBuilder
    {
        $request = $this->objectManager->get(Request::class);
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($request);
        return $uriBuilder;
    }
}
