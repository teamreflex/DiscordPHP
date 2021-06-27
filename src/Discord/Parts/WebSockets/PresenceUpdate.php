<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\WebSockets;

use Discord\Helpers\Collection;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\Activity;
use Discord\Parts\User\User;

/**
 * A PresenceUpdate part is used when the `PRESENCE_UPDATE` event is fired on the WebSocket. It contains
 * information about the users presence such as their status (online/away) and their current game.
 *
 * @property Member                $member         The member that the presence update affects.
 * @property User                  $user           The user that the presence update affects.
 * @property Guild                 $guild          The guild that the presence update affects.
 * @property string                $guild_id       The unique identifier of the guild that the presence update affects.
 * @property string                $status         The updated status of the user.
 * @property Activity              $game           The updated game of the user.
 * @property Collection|Activity[] $activities     The activities of the user.
 * @property Collection|Role[]     $roles          Roles that the user has in the guild.
 * @property object                $client_status  Status of the client.
 * @property string|null           $desktop_status Status of the user on their desktop client. Null if they are not active on desktop.
 * @property string|null           $mobile_status  Status of the user on their mobile client. Null if they are not active on mobile.
 * @property string|null           $web_status     Status of the user on their web client. Null if they are not active on web.
 */
class PresenceUpdate extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['user', 'guild_id', 'status', 'activities', 'client_status'];

    /**
     * @inheritDoc
     */
    protected $visible = ['member', 'roles', 'guild', 'game', 'desktop_status', 'mobile_status', 'web_status'];

    /**
     * Gets the member attribute.
     *
     * @return Member|null
     */
    protected function getMemberAttribute(): ?Member
    {
        if (isset($this->attributes['user']) && $this->guild) {
            return $this->guild->members->get('id', $this->attributes['user']->id);
        }

        return null;
    }

    /**
     * Gets the user attribute.
     *
     * @return User The user that had their presence updated.
     */
    protected function getUserAttribute(): User
    {
        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->factory->create(User::class, $this->attributes['user'], true);
    }

    /**
     * Returns the users roles.
     *
     * @return Collection|Role[]
     */
    protected function getRolesAttribute(): Collection
    {
        return $this->member->roles ?? new Collection();
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild The guild that the user was in.
     */
    protected function getGuildAttribute(): Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the game attribute.
     *
     * @return Activity|null The game attribute.
     */
    protected function getGameAttribute(): ?Part
    {
        return $this->activities->first();
    }

    /**
     * Gets the activities attribute.
     *
     * @return Collection|Activity[]
     */
    protected function getActivitiesAttribute()
    {
        $collection = Collection::for(Activity::class, null);

        foreach ($this->attributes['activities'] ?? [] as $activity) {
            $collection->push($this->factory->create(Activity::class, $activity, true));
        }

        return $collection;
    }

    /**
     * Gets the status of the user on their desktop client.
     *
     * @return string|null
     */
    protected function getDesktopStatusAttribute(): ?string
    {
        return $this->client_status->desktop ?? null;
    }

    /**
     * Gets the status of the user on their mobile client.
     *
     * @return string|null
     */
    protected function getMobileStatusAttribute(): ?string
    {
        return $this->client_status->mobile ?? null;
    }

    /**
     * Gets the status of the user on their web client.
     *
     * @return string|null
     */
    protected function getWebStatusAttribute(): ?string
    {
        return $this->client_status->web ?? null;
    }
}
