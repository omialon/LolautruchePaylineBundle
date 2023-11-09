<?php

/*
 * This file is part of the LolautruchePaylineBundle package.
 *
 * (c) Jérôme Vieilledent <jerome@vieilledent.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lolautruche\PaylineBundle\Event;

use Lolautruche\PaylineBundle\Payline\PaylineResult;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

class PaymentNotificationEvent extends Event
{

    /**
     * @var Response
     */
    private Response $response;

    public function __construct(
        private PaylineResult $paylineResult
    )
    {
    }

    /**
     * @return PaylineResult
     */
    public function getPaylineResult(): PaylineResult
    {
        return $this->paylineResult;
    }

    /**
     * Indicates if payment was successful or not.
     * Proxy to PaylineResult::isSuccessful().
     *
     * @return bool
     */
    public function isPaymentSuccessful(): bool
    {
        return $this->paylineResult->isSuccessful();
    }

    /**
     * Indicates if payment was canceled by user.
     * Proxy to PaylineResult::isCanceled().
     *
     * @return bool
     */
    public function isPaymentCanceledByUser(): bool
    {
        return $this->paylineResult->isCanceled();
    }

    /**
     * Indicates if transaction is a duplicate of an existing one.
     * Proxy to PaylineResult::isDuplicate().
     *
     * @return bool
     */
    public function isPaymentDuplicate(): bool
    {
        return $this->paylineResult->isDuplicate();
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    /**
     * @return bool
     */
    public function hasResponse(): bool
    {
        return isset($this->response);
    }
}
