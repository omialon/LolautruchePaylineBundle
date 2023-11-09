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

class ResultEvent extends Event
{

    public function __construct(
        private PaylineResult $result
    )
    {
    }

    /**
     * @return PaylineResult
     */
    public function getResult(): PaylineResult
    {
        return $this->result;
    }
}
