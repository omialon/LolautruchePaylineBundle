<?php

/*
 * This file is part of the LolautruchePaylineBundle package.
 *
 * (c) Jérôme Vieilledent <jerome@vieilledent.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lolautruche\PaylineBundle\Controller;

use Lolautruche\PaylineBundle\Event\PaylineEvents;
use Lolautruche\PaylineBundle\Event\PaymentNotificationEvent;
use Lolautruche\PaylineBundle\Payline\WebGatewayInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaylineController
{
    /**
     * PaylineController constructor.
     * @param EventDispatcherInterface $eventDispatcher
     * @param WebGatewayInterface      $payline
     * @param string                   $defaultConfirmationUrl
     * @param string                   $defaultErrorUrl
     */
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private WebGatewayInterface $payline,
        private string $defaultConfirmationUrl,
        private string $defaultErrorUrl
    )
    {
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function paymentNotificationAction(Request $request): Response
    {
        $result = $this->payline->verifyWebTransaction($request->get('paylinetoken', $request->get('token')));
        $this->eventDispatcher->dispatch(new PaymentNotificationEvent($result), PaylineEvents::ON_NOTIFICATION);

        return new Response('OK');
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function backToShopAction(Request $request): RedirectResponse|Response
    {
        $result = $this->payline->verifyWebTransaction($request->get('paylinetoken', $request->get('token')));
        $event = new PaymentNotificationEvent($result);
        $this->eventDispatcher->dispatch($event, PaylineEvents::ON_BACK_TO_SHOP);

        if ($event->hasResponse()) {
            return $event->getResponse();
        }

        return new RedirectResponse($result->isSuccessful() ? $this->defaultConfirmationUrl : $this->defaultErrorUrl);
    }
}
