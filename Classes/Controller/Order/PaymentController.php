<?php

declare(strict_types=1);

namespace W4Services\W4Payrexx\Controller\Order;

/*
 * This file is part of the package W4Services/W4Payrexx.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\Cart\Domain\Model\Cart;
use Extcode\Cart\Domain\Repository\CartRepository;
use Extcode\Cart\Domain\Repository\Order\PaymentRepository;
use Extcode\Cart\Event\Order\FinishEvent;
use Extcode\Cart\Service\SessionHandler;
use W4Services\W4Payrexx\Event\Order\CancelEvent;
use W4Services\W4Payrexx\Event\Order\NotifyEvent;
use W4Services\W4Payrexx\Event\Order\SuccessEvent;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class PaymentController extends ActionController
{
    const PAYPAL_API_SANDBOX = 'https://www.sandbox.paypal.com/cgi-bin/webscr?';
    const PAYPAL_API_LIVE = 'https://www.paypal.com/cgi-bin/webscr?';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var SessionHandler
     */
    protected $sessionHandler;

    /**
     * @var CartRepository
     */
    protected $cartRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var array
     */
    protected $cartConf = [];

    /**
     * @var array
     */
    protected $cartPaypalConf = [];

    public function __construct(
        LogManagerInterface $logManager,
        PersistenceManager $persistenceManager,
        SessionHandler $sessionHandler,
        CartRepository $cartRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->logger = $logManager->getLogger();
        $this->persistenceManager = $persistenceManager;
        $this->sessionHandler = $sessionHandler;
        $this->cartRepository = $cartRepository;
        $this->paymentRepository = $paymentRepository;
    }

    protected function initializeAction(): void
    {
        $this->cartConf =
            $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
                'Cart'
            );

        $this->cartPaypalConf =
            $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
                'W4Payrexx'
            );
    }

    public function successAction(): void
    {
        if ($this->request->hasArgument('hash') && !empty($this->request->getArgument('hash'))) {
            $this->loadCartByHash($this->request->getArgument('hash'));

            if ($this->cart) {
                $orderItem = $this->cart->getOrderItem();

                $finishEvent = new FinishEvent($this->cart->getCart(), $orderItem, $this->cartConf);
                $this->eventDispatcher->dispatch($finishEvent);
                $this->redirect('show', 'Cart\Order', 'Cart', ['orderItem' => $orderItem]);
            } else {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'tx_w4payrexx.controller.order.payment.action.success.error_occured',
                        'w4_payrexx'
                    ),
                    '',
                    AbstractMessage::ERROR
                );
            }
        } else {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'tx_w4payrexx.controller.order.payment.action.success.access_denied',
                    'w4_payrexx'
                ),
                '',
                AbstractMessage::ERROR
            );
        }
    }

    public function failedAction(): void
    {
        if ($this->request->hasArgument('hash') && !empty($this->request->getArgument('hash'))) {
            $this->loadCartByHash($this->request->getArgument('hash'), 'FHash');

            if ($this->cart) {
                $orderItem = $this->cart->getOrderItem();
                $payment = $orderItem->getPayment();

                $this->restoreCartSession();

                $payment->setStatus('failed');

                $this->paymentRepository->update($payment);
                $this->persistenceManager->persistAll();

                $this->addFlashMessageByQueueIdentifier('extbase.flashmessages.tx_cart_cart',
                    LocalizationUtility::translate(
                        'tx_w4payrexx.controller.order.payment.action.failed.payment_failed',
                        'w4_payrexx'
                    ),
                    '',
                    AbstractMessage::ERROR
                );

                $cancelEvent = new CancelEvent($this->cart->getCart(), $orderItem, $this->cartConf);
                $this->eventDispatcher->dispatch($cancelEvent);
                $this->redirect('show', 'Cart\Cart', 'Cart');
            } else {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'tx_w4payrexx.controller.order.payment.action.cancel.error_occured',
                        'w4_payrexx'
                    ),
                    '',
                    AbstractMessage::ERROR
                );
            }
        } else {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'tx_w4payrexx.controller.order.payment.action.cancel.access_denied',
                    'w4_payrexx'
                ),
                '',
                AbstractMessage::ERROR
            );
        }
    }

    public function cancelAction(): void
    {
        if ($this->request->hasArgument('hash') && !empty($this->request->getArgument('hash'))) {
            $this->loadCartByHash($this->request->getArgument('hash'), 'FHash');

            if ($this->cart) {
                $orderItem = $this->cart->getOrderItem();
                $payment = $orderItem->getPayment();

                $this->restoreCartSession();

                $payment->setStatus('canceled');

                $this->paymentRepository->update($payment);
                $this->persistenceManager->persistAll();

                $this->addFlashMessageByQueueIdentifier('extbase.flashmessages.tx_cart_cart',
                    LocalizationUtility::translate(
                        'tx_w4payrexx.controller.order.payment.action.cancel.successfully_canceled',
                        'w4_payrexx'
                    )
                );

                $cancelEvent = new CancelEvent($this->cart->getCart(), $orderItem, $this->cartConf);
                $this->eventDispatcher->dispatch($cancelEvent);
                $this->redirect('show', 'Cart\Cart', 'Cart', ['billingAddress' => $orderItem->getBillingAddress(), 'shippingAddress' => $orderItem->getShippingAddress()]);
            } else {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'tx_w4payrexx.controller.order.payment.action.cancel.error_occured',
                        'w4_payrexx'
                    ),
                    '',
                    AbstractMessage::ERROR
                );
            }
        } else {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'tx_w4payrexx.controller.order.payment.action.cancel.access_denied',
                    'w4_payrexx'
                ),
                '',
                AbstractMessage::ERROR
            );
        }
    }

    public function notifyAction()
    {

        if ($this->request->getMethod() !== 'POST') {
            // exit with Status Code in TYPO3 v10.4
            if (isset($this->response)) {
                $this->response->setStatus(405);
                exit();
            }
            return $this->htmlResponse()->withStatus(405, 'Method not allowed.');
        }

        $postData = GeneralUtility::_POST();

        $curlRequest = $this->getCurlRequestFromPostData($postData);

        if ($this->cartPaypalConf['debug']) {
            $this->logger->debug(
                'Log Data',
                [
                    '$parsedPostData' => $postData,
                    '$curlRequest' => $curlRequest
                ]
            );
        }

        $this->execCurlRequest($curlRequest);

        $cartSHash = $postData['custom'];
        if (empty($cartSHash)) {
            // exit with Status Code in TYPO3 v10.4
            if (isset($this->response)) {
                $this->response->setStatus(403);
                exit();
            }
            return $this->htmlResponse()->withStatus(403, 'Not allowed.');
        }

        $this->loadCartByHash($this->request->getArgument('hash'));

        if ($this->cart === null) {
            // exit with Status Code in TYPO3 v10.4
            if (isset($this->response)) {
                $this->response->setStatus(404);
                exit();
            }
            return $this->htmlResponse()->withStatus(404, 'Page / Cart not found.');
        }

        $orderItem = $this->cart->getOrderItem();
        $payment = $orderItem->getPayment();

        if ($payment->getStatus() !== 'paid') {
            $payment->setStatus('paid');
            $this->paymentRepository->update($payment);
            $this->persistenceManager->persistAll();

            $notifyEvent = new NotifyEvent($this->cart->getCart(), $orderItem, $this->cartConf);
            $this->eventDispatcher->dispatch($notifyEvent);
        }

        // exit with Status Code in TYPO3 v10.4
        if (isset($this->response)) {
            $this->response->setStatus(200);
            exit();
        }
        return $this->htmlResponse()->withStatus(200);
    }

    protected function restoreCartSession(): void
    {
        $cart = $this->cart->getCart();
        $cart->resetOrderNumber();
        $cart->resetInvoiceNumber();
        $this->sessionHandler->write($cart, $this->cartConf['settings']['cart']['pid']);
    }

    protected function getCurlRequestFromPostData(array $parsePostData): string
    {
        $curlRequest = 'cmd=_notify-validate';
        foreach ($parsePostData as $key => $value) {
            $value = urlencode($value);
            $curlRequest .= "&$key=$value";
        }

        return $curlRequest;
    }

    protected function execCurlRequest(string $curlRequest): bool
    {
        $paypalUrl = $this->getPaypalUrl();

        $ch = curl_init($paypalUrl);
        if ($ch === false) {
            return false;
        }

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlRequest);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);

        if (is_array($this->cartPaypalConf) && intval($this->cartPaypalConf['curl_timeout'])) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, intval($this->cartPaypalConf['curl_timeout']));
        } else {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);

        $curlResult = strtolower(curl_exec($ch));
        $curlError = curl_errno($ch);

        if ($curlError !== 0) {
            $this->logger->warning(
                'paypal-payment-api',
                [
                    'ERROR' => 'Can\'t connect to PayPal to validate IPN message',
                    'curl_error' => curl_error($ch),
                    'curl_request' => $curlRequest,
                    'curl_result' => $curlResult,
                ]
            );

            curl_close($ch);
            exit;
        }

        if ($this->cartPaypalConf['debug']) {
            $this->logger->debug(
                'paypal-payment-api',
                [
                    'curl_info' => curl_getinfo($ch, CURLINFO_HEADER_OUT),
                    'curl_request' => $curlRequest,
                    'curl_result' => $curlResult,
                ]
            );
        }

        $curlResults = explode("\r\n\r\n", $curlResult);

        curl_close($ch);

        return true;
    }

    protected function getPaypalUrl(): string
    {
        if ($this->cartPaypalConf['sandbox']) {
            return self::PAYPAL_API_SANDBOX;
        }

        return self::PAYPAL_API_LIVE;
    }

    protected function loadCartByHash(string $hash, string $type = 'SHash'): void
    {
        $querySettings = GeneralUtility::makeInstance(
            Typo3QuerySettings::class
        );

        $querySettings->setStoragePageIds([$this->cartConf['settings']['order']['pid']]);
        $this->cartRepository->setDefaultQuerySettings($querySettings);

        $findOneByMethod = 'findOneBy' . $type;
        $this->cart = $this->cartRepository->$findOneByMethod($hash);
    }

    protected function addFlashMessageByQueueIdentifier($identifier, string $messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true)
    {
         /* @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
         $flashMessage = GeneralUtility::makeInstance(
             FlashMessage::class,
             $messageBody,
             (string)$messageTitle,
             $severity,
             $storeInSession
         );

        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $messageQueue = $flashMessageService->getMessageQueueByIdentifier($identifier);
        $messageQueue->addMessage($flashMessage);
    }
}
