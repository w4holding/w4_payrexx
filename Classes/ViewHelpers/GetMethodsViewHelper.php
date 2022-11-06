<?php

namespace W4Services\W4Payrexx\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Payrexx\Models\Request\PaymentMethod;
use Payrexx\Payrexx;
use Payrexx\PayrexxException;

class GetMethodsViewHelper extends AbstractViewHelper
{

   /**
    * @var mixed
    */
   private $extensionConfiguration;

   public function initializeArguments()
   {
   }
 
    /**
     * @return mixed
     */
      public function render()
      {
         $this->extensionConfiguration = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
         )->get('w4_payrexx');
         $instanceName = $this->extensionConfiguration['payrexx_api_instance'];
         $secret = $this->extensionConfiguration['payrexx_api_secret'];
         if (!$instanceName || !$secret) {
            return false;
         }
         spl_autoload_register(function($class) {
            $classFile = 'typo3conf/ext/w4_payrexx/Classes/lib/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($classFile)) {
               require_once $classFile;
            }
         });
         $payrexx = new Payrexx($instanceName, $secret);
         $paymentMethod = new PaymentMethod();
         try {
            $methods = $payrexx->getAll($paymentMethod);
         } catch (PayrexxException $e) {
            $methods = $e->getMessage();
         }
         return $methods;
      }
}
