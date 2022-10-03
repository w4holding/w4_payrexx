<?php

namespace W4Services\W4Payrexx\Controller\Cart;

use \Extcode\Cart\Controller\Cart\CartController as BaseCartController;

class CartController extends BaseCartController
{
    use PayrexxTrait;

    protected function emitBeforeCallActionMethodSignal(array $preparedArguments) {
        parent::emitBeforeCallActionMethodSignal($preparedArguments);
        $this->addMessagesToDefaultQueue('extbase.flashmessages.tx_w4payrexx_cart');
    }

    protected function addMessagesToDefaultQueue($queueId) {
        $queue = $this->controllerContext->getFlashMessageQueue($queueId);
        $msg = $queue->getAllMessagesAndFlush();
        if ($msg) {
            $defaultQueue = $this->controllerContext->getFlashMessageQueue();
            foreach ($msg as $m) {
                $defaultQueue->enqueue($m);
            }
        }
    }

}
