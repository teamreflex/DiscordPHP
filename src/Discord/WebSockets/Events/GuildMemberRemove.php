<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildMemberRemove extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $memberPart = $this->factory->create(Member::class, $data, true);
		
		if ($this->discord->options['storeUsers']) {
			$this->discord->users->offsetSet($memberPart->id, $memberPart->user);
		}

        if ($this->discord->guilds->has($memberPart->guild_id)) {
            $guild = $this->discord->guilds->offsetGet($memberPart->guild_id);
            --$guild->member_count;
			
			if ($this->discord->options['storeMembers']) {
				$guild->members->pull($memberPart->id);
			}

            $this->discord->guilds->offsetSet($guild->id, $guild);
        }

        $deferred->resolve($memberPart);
    }
}
