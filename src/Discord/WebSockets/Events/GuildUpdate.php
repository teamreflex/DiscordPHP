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

use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Repository\Guild\RoleRepository;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        if (isset($data->unavailable) && $data->unavailable) {
            $deferred->notify('Guild is unavailable.');

            return;
        }

		$old = null;
		if ($this->discord->guilds->has($data->id)) {
			$old = $this->discord->guilds->offsetGet($data->id);
			$guildPart = $this->discord->guilds->offsetGet($data->id);
			$guildPart->fill($data);
		} else {
			$guildPart = $this->factory->create(Guild::class, $data, true);

			$roles = new RoleRepository(
				$this->http,
				$this->cache,
				$this->factory
			);

			foreach ($data->roles as $role) {
				$role             = (array) $role;
				$role['guild_id'] = $guildPart->id;
				$rolePart         = $this->factory->create(Role::class, $role, true);

				$roles->offsetSet($rolePart->id, $rolePart);
			}

			$guildPart->roles = $roles;
			
			$this->discord->guilds->offsetSet($guildPart->id, $guildPart);
		}

        $deferred->resolve([$guildPart, $old]);
    }
}
