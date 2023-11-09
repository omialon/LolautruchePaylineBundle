<?php

/*
 * This file is part of the LolautruchePaylineBundle package.
 *
 * (c) Jérôme Vieilledent <jerome@vieilledent.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lolautruche\PaylineBundle\Payline;

use Lolautruche\PaylineBundle\Event\PaylineEvents;
use Lolautruche\PaylineBundle\Event\ResultEvent;
use Lolautruche\PaylineBundle\Event\WebTransactionEvent;
use Payline\PaylineSDK;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Main Payline implementation, abstracting PaylineSDK.
 */
class Payline implements WebGatewayInterface
{
    public function __construct(
        private PaylineSDK $paylineSDK,
        private EventDispatcherInterface $eventDispatcher,
        private int $defaultCurrency,
        private string $defaultReturnUrl,
        private string $defaultCancelUrl,
        private string $defaultNotificationUrl,
        private ?string $defaultContractNumber = null
    ) {
        $this->defaultContractNumber = (string) $defaultContractNumber;
    }

    /**
     * @return PaylineSDK
     */
    public function getPaylineSDK(): PaylineSDK
    {
        return $this->paylineSDK;
    }

    /**
     * {@inheritdoc}
     */
    public function initiateWebTransaction(WebTransaction $transaction): PaylineResult
    {
        $this->eventDispatcher->dispatch(
            new WebTransactionEvent($transaction),
            PaylineEvents::PRE_WEB_TRANSACTION_INITIATE
        );

        $payment = [
            'amount' => $transaction->getAmount(),
            'currency' => $transaction->getCurrency() ?: $this->defaultCurrency,
            'action' => $transaction->getAction(),
            'mode' => $transaction->getMode(),
            'contractNumber' => (string) $transaction->getContractNumber() ?: $this->defaultContractNumber,
        ];

        $order = [
            'ref' => $transaction->getOrderRef(),
            'amount' => $transaction->getOrderAmount() ?: $transaction->getAmount(),
            'currency' => $transaction->getOrderCurrency() ?: $transaction->getCurrency() ?: $this->defaultCurrency,
            'date' => $transaction->getOrderDate()->format('d/m/Y H:i'),
        ];
        if ($orderTaxes = $transaction->getOrderTaxes()) {
            $order['taxes'] = $orderTaxes;
        }
        if ($orderCountry = $transaction->getOrderCountry()) {
            $order['country'] = $orderCountry;
        }

        // Merge options with extra options provided in the transaction.
        $params = array_merge_recursive([
            'payment' => $payment,
            'order' => $order,
            'returnURL' => $this->defaultReturnUrl,
            'cancelURL' => $this->defaultCancelUrl,
            'notificationURL' => $this->defaultNotificationUrl,
        ], $transaction->getExtraOptions());

        // Add private data.
        foreach ($transaction->getPrivateData() as $key => $value) {
            $this->paylineSDK->addPrivateData(['key' => $key, 'value' => $value]);
        }

        $paylineResult = new PaylineResult($this->paylineSDK->doWebPayment($params));
        $this->eventDispatcher->dispatch(
            new ResultEvent($paylineResult),
            PaylineEvents::POST_WEB_TRANSACTION_INITIATE
        );

        return $paylineResult;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyWebTransaction($paymentToken): PaylineResult
    {
        $response = $this->paylineSDK->getWebPaymentDetails(['token' => $paymentToken]);

        $paylineResult = new PaylineResult($response);
        $this->eventDispatcher->dispatch(
            new ResultEvent($paylineResult),
            PaylineEvents::WEB_TRANSACTION_VERIFY
        );

        return $paylineResult;
    }
    
    public function doRefund($paymentToken, $comment = '', $sequenceNumber = 0, $amount = null): PaylineResult
    {
        // first, we get the payment details
        $paymentDetails = $this->paylineSDK->getWebPaymentDetails(['token' => $paymentToken]);

        // add the private datas
        foreach ($paymentDetails['privateDataList']['privateData'] as $key => $value) {
            $this->paylineSDK->addPrivateData(['key' => $value['key'], 'value' => $value['value']]);
        }

        $paymentDetails['payment']['action'] = WebGatewayInterface::CODE_ACTION_DOREFUND;

        if($amount) {
            $paymentDetails['payment']['amount'] = $amount;
        }

        $params = [
            'transactionID' => $paymentDetails['transaction']['id'],
            'payment' => $paymentDetails['payment'],
            'comment' => $comment,
            'sequenceNumber' => $sequenceNumber
        ];

        // do refund
        $paylineResult = new PaylineResult($this->paylineSDK->doRefund($params));

        return $paylineResult;
    }
}
