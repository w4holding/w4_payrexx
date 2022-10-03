<?php

namespace W4Services\W4Payrexx\Utility;

use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Utility;
use Extcode\Cart\Domain\Model\Order\Item;
use Extcode\Cart\Domain\Model\Order\Payment;
use Extcode\Cart\Domain\Repository\Order\ItemRepository;
use Extcode\Cart\Domain\Repository\Order\PaymentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Utility\EidUtility;

class PayrexxWebhook
{

    /**
     * @var string
     */
    private $orderNumber;

    /**
     * @var ItemRepository
     */
    private $orderItemRepository;

    /**
     * @var PaymentRepository
     */
    private $orderPaymentRepository;

    /**
     * @var Item
     */
    private $orderItem;

    /**
     * @var Payment
     */
    private $orderPayment;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var PersistenceManager
     */
    private $persistenceManager;

    /**
     * PayrexxWebhook constructor.
     */
    public function __construct()
    {

        $this->objectManager = GeneralUtility::makeInstance(
            ObjectManager::class
        );

        $this->persistenceManager = $this->objectManager->get(
            PersistenceManager::class
        );

        $this->orderItemRepository = $this->objectManager->get(
            ItemRepository::class
        );

        $this->orderPaymentRepository = $this->objectManager->get(
            PaymentRepository::class
        );

        $this->loadConfiguration();

    }

    /**
     * Setting configuration and loading TCA
     */
    public function loadConfiguration(): void
    {
        $pageId = (int)GeneralUtility::_GP('pageid');
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
            \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class,
            $GLOBALS['TYPO3_CONF_VARS'],
            $pageId,
            0,
            true
        );
        \TYPO3\CMS\Frontend\Utility\EidUtility::initLanguage();

        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->initFEuser();
        \TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();

        $GLOBALS['TSFE']->initUserGroups();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(
            \TYPO3\CMS\Frontend\Page\PageRepository::class
        );
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->getConfigArray();

    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response): Response
    {
        $transaction = json_decode(file_get_contents('php://input'), TRUE)['transaction'];
        $referenceId = $transaction['invoice']['referenceId'];

        if ($transaction['status'] == 'confirmed') {
            if (substr($referenceId, 0, 4) != Payrexx::REFERENCE_PREFIX) {
                return $response->withStatus(400);
            }

            $this->orderNumber = ltrim($referenceId, Payrexx::REFERENCE_PREFIX);

            try {
                $this->getOrderItem();
                $this->validateOrder($transaction);
            } catch (\Exception $exception) {
               return $response->withStatus(400);
            }

            $this->getOrderPayment();

            $this->orderPayment->setStatus('paid');
            $this->orderPaymentRepository->update($this->orderPayment);
            $this->persistenceManager->persistAll();

            return $response;

        } else {
            return $response->withStatus(400);
        }

    }

    /**
     * @param $transaction
     * @throws \Exception
     */
    private function validateOrder($transaction): void
    {
        if ($transaction['invoice']['amount'] != $this->orderItem->getTotalGross())
            throw new \Exception('Order amount does not match');

        if ($transaction['invoice']['currency'] != $this->orderItem->getCurrency())
            throw new \Exception('Currency does not match');

    }

    private function getOrderPayment(): void
    {
        if ($this->orderItem) {
            $this->orderPayment = $this->orderItem->getPayment();
        }
    }

    /**
     * @throws \Exception
     */
    private function getOrderItem(): void
    {

        $orderItem = $this->orderItemRepository->findOneByOrderNumber($this->orderNumber);

        if (!$orderItem) {
            throw new \Exception('Order number does not exists');
        } else {
            $this->orderItem = $orderItem;
        }
    }
}
