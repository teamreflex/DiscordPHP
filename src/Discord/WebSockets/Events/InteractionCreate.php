<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Helpers\Deferred;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;

class InteractionCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        // do nothing with interactions - pass on to DiscordPHP-Slash
        // $deferred->resolve($data);
        $deferred->resolve($this->factory->create(Interaction::class, $data, true));
    }
}
