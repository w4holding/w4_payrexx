.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

The extension is configured in :guilabel:`Settings > Extension Configuration > Configure extensions`:

.. image:: ../Images/1.png
   :class: with-shadow

|
.. list-table:: Explanation
   :header-rows: 1

   * - Field
     - Description
   * - Payrexx api key
     - Your Payrexx api key
   * - Payrexx instance
     - Payrexx instance name
   * - Currency
     - Currency to be used for the payment

Setup
=====

A new payment option has to be added to the :guilabel:`cart` extension. It's done in the setup of the extension :guilabel:`w4_payrexx`:

.. code-block:: typoscript

  plugin.tx_cart {
      payments {
          options {
              10 {
                  title = Online payment
                  provider = payrexx
                  extra = 0.00
                  taxClassId = 2
                  status = open
                  type = payrexx
              }
          }
      }
  }

But the values can be overwritten in your own setup. More info at https://docs.typo3.org/p/extcode/cart/8.2/en-us/AdministratorManual/Configuration/PaymentMethods/Index.html

Getting the payment methods
===========================

The extension comes with a viewhelper, :guilabel:`GetMethods`, to retrieve the available payment methods directly from Payrexx. For instance:

.. code-block:: html

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
        xmlns:cart="http://typo3.org/ns/Extcode/Cart/ViewHelpers"
        data-namespace-typo3-fluid="true">

    {namespace w4payrexx=W4Services\W4Payrexx\ViewHelpers}

    <div id="checkout-step-payment-method" class="checkout-step bg-light-grey bottom-buffer">
        <h5 class="checkout-step-title underline-header">
            <span class="checkout-step-number"></span>
            <span><f:translate key="tx_cart.controller.order.action.show_cart.block-header.payment_method"/></span>
        </h5>
        <div class="checkout-step-content">
            <fieldset>
                <div class="method-list checkout-step-content-list">
                    <f:for each="{payments}" as="payment">
                        <f:if condition="{payment.available}">
                            <div class="method-item-wrap">
                                <div class="method-item-name">
                                    <f:if condition="{0: payment.id} == {0: cart.payment.id}">
                                        <f:then>               
                                            <a><span>{payment.name} <f:render section="Payrexx" arguments="{_all}" /></span></a>
                                        </f:then>
                                        <f:else>                                                                    
                                            <f:link.action controller="Cart\Payment"
                                                action="update"
                                                arguments="{paymentId:payment.id}"
                                                pageType="2278001" 
                                                class="set-payment">                                   
                                                    <span>{payment.name} <f:render section="Payrexx" arguments="{_all}" />
                                            </f:link.action>
                                        </f:else>
                                    </f:if>
                                </div>
                            </div>
                        </f:if>
                    </f:for>
                </div>
            </fieldset>
        </div>
    </div>

    <f:section name="Payrexx">    
        <f:variable name="names" value="" />
        <f:variable name="cards" value="" />
        <f:if condition="{payment.provider} == 'payrexx'">
            <f:variable name="methods" value="{w4payrexx:GetMethods()}" />
            <f:if condition="{methods.0.id}">
                <f:for each="{methods}" as="method" iteration="iteration">
                    <f:variable name="names" value="{names}{method.name}{f:if(condition: '!{iteration.isLast}', then: ', ')}" />
                    <f:variable name="cards" value="{cards}<img src=\"{method.logo.en}\" alt=\"{method.name}\" title=\"{method.name}\" /> " />
                </f:for>
            </f:if>
            <f:variable name="cards" value="<div class=\"payment-cards\">{cards}</div>" />
        </f:if>
        ({names}) - <cart:format.currency currencySign="{cart.currencySign}">{payment.gross}</cart:format.currency>
        <f:format.html>{cards}</f:format.html>
    </f:section>

  </html>

Will prompt something like:

.. image:: ../Images/2.png
   :class: with-shadow

|
