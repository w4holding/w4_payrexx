<?php

namespace W4Services\W4Payrexx\Utility;

use Extcode\Cart\Domain\Model\Order\Item;
use Extcode\Cart\Domain\Repository\CartRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use W4Services\W4Payrexx\Models\Gateway;

class PaymentUtility
{
    /**
     * @var Item
     */
    private $orderItem;
    
    /**
     * @var array
     */
    private $cartPluginSettings;

    /**
     * Intitialize
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
        $this->cartPluginSettings =
            $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Cart'
        );

    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function handlePayment(array $params): array
    {
        $this->orderItem = $params['orderItem'];

        if ($this->orderItem->getPayment()->getProvider() === 'payrexx' && $this->orderItem->getTotalGross() !== 0.0) {
            $params['providerUsed'] = true;

            $this->cart = $params['cart'];

            $cart = $this->objectManager->get(
                \Extcode\Cart\Domain\Model\Cart::class
            );
            $cart->setOrderItem($this->orderItem);
            $cart->setCart($this->cart);
            $cart->setPid($this->cartPluginSettings['settings']['order']['pid']);

            $cartRepository = $this->objectManager->get(
                CartRepository::class
            );
            $cartRepository->add($cart);

            $this->persistenceManager->persistAll();

            $payrexx = GeneralUtility::makeInstance(
                Payrexx::class
            );

            $this->storeAddressToSession($this->orderItem);

            $data = $payrexx->processPayment($this->orderItem, $cart);

            header('Location: ' . $data['link']);
        }

        return [$params];
    }

    /**
     * @param Item $data
     */
    private function storeAddressToSession(Item $order): void
    {
        $GLOBALS['TSFE']->fe_user->setKey("ses", "user_address", [
           'billing_address' => $order->getBillingAddress(),
           'shipping_address' => $order->getShippingAddress(),
        ]);
    }

}
