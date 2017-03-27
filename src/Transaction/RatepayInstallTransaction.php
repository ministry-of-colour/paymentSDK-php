<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

namespace Wirecard\PaymentSdk\Transaction;

use Wirecard\PaymentSdk\Entity\ItemCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Exception\UnsupportedOperationException;

/**
 * Class RatepayInstallTransaction
 * @package Wirecard\PaymentSdk\Transaction
 */
class RatepayInstallTransaction extends Transaction implements Reservable
{
    const NAME = 'ratepay-install';

    /**
     * @var string
     */
    private $orderNumber;

    /**
     * @var ItemCollection
     */
    private $itemCollection;

    /**
     * @var Redirect
     */
    private $redirect;

    /**
     * @param Redirect $redirect
     * @return RatepayInstallTransaction
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
        return $this;
    }

    /**
     * @param ItemCollection $itemCollection
     * @return RatepayInstallTransaction
     */
    public function setItemCollection($itemCollection)
    {
        $this->itemCollection = $itemCollection;
        return $this;
    }

    /**
     * @param string $orderNumber
     * @return RatepayInstallTransaction
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    /**
     * @throws MandatoryFieldMissingException|UnsupportedOperationException
     * @return array
     */
    protected function mappedSpecificProperties()
    {
        $transactionType = $this->retrieveTransactionType();

        $result = [
            'order-number' => $this->orderNumber,
            'order-items' => $this->itemCollection->mappedProperties()
        ];

        if ($transactionType === $this::TYPE_AUTHORIZATION) {
            $result['cancel-redirect-url'] = $this->redirect->getCancelUrl();
            $result['success-redirect-url'] =$this->redirect->getSuccessUrl();

            if ($this->redirect->getFailureUrl()) {
                $result['redirect-url'] = $this->redirect->getFailureUrl();
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function retrieveTransactionTypeForReserve()
    {
        return $this::TYPE_AUTHORIZATION;
    }

    /**
     * @throws MandatoryFieldMissingException
     * @return string
     */
    protected function retrieveTransactionTypeForCancel()
    {
        if ($this->parentTransactionType === $this::TYPE_AUTHORIZATION) {
            return $this::TYPE_VOID_AUTHORIZATION;
        }

        throw new MandatoryFieldMissingException('Parent transaction type is missing for cancel operation');
    }

    /**
     * return string
     */
    public function getEndpoint()
    {
        if ($this->operation === Operation::RESERVE) {
            return $this::ENDPOINT_PAYMENT_METHODS;
        } else {
            return $this::ENDPOINT_PAYMENTS;
        }
    }
}
