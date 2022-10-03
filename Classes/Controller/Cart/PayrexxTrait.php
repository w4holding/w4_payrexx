<?php

namespace W4Services\W4Payrexx\Controller\Cart;

trait PayrexxTrait {

    /**
     * Parse Data
     */
    protected function parseData()
    {
        // parse all shippings
        $this->shippings = $this->parserUtility->parseServices('Shipping', $this->pluginSettings, $this->cart);

        // parse all payments
        $payments = $this->parserUtility->parseServices('Payment', $this->pluginSettings, $this->cart);
        $this->payments = $this->validatePayrexxPayment($payments);

        // parse all specials
        $this->specials = $this->parserUtility->parseServices('Special', $this->pluginSettings, $this->cart);
    }

    protected function validatePayrexxPayment($payments)
    {
        $validatedPayments = [];

        foreach ($payments as $payment) {
            if ($payment->getProvider() == 'payrexx' && $this->cart->getTotalGross() === 0.0) {
                continue;
            }

            array_push($validatedPayments, $payment);
        }

        return $validatedPayments;
    }

}
