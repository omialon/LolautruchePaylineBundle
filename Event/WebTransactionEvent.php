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

use Lolautruche\PaylineBundle\Payline\WebTransaction;
use Symfony\Contracts\EventDispatcher\Event;

class WebTransactionEvent extends Event
{
    public function __construct(
        private WebTransaction $transaction
    )
    {
    }

    /**
     * @return WebTransaction
     */
    public function getTransaction(): WebTransaction
    {
        return $this->transaction;
    }
}
