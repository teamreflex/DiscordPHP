<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Request\InteractionData;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * Represents an interaction from Discord.
 *
 * @property string               $id             ID of the interaction.
 * @property string               $application_id ID of the application the interaction is for.
 * @property int                  $type           Type of interaction.
 * @property InteractionData|null $data           Data associated with the interaction.
 * @property string|null          $guild_id       ID of the guild the interaction was sent from.
 * @property Guild|null           $guild          Guild the interaction was sent from.
 * @property string|null          $channel_id     ID of the channel the interaction was sent from.
 * @property Channel|null         $channel        Channel the interaction was sent from.
 * @property Member|null          $member         Member who invoked the interaction.
 * @property User                 $user           User who invoked the interaction.
 * @property string               $token          Continuation token for responding to the interaction.
 * @property int                  $version        Version of interaction.
 * @property Message|null         $message        Message that triggered the interactions, when triggered from message components.
 */
class Interaction extends Part
{
    const TYPE_PING = 1;
    const TYPE_APPLICATION_COMMAND = 2;
    const TYPE_MESSAGE_COMPONENT = 3;

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'application_id', 'type', 'data', 'guild_id', 'channel_id', 'member', 'user', 'token', 'version', 'message'];

    /**
     * {@inheritdoc}
     */
    protected $visible = ['guild', 'channel'];

    /**
     * Returns the data associated with the interaction.
     *
     * @return InteractionData|null
     */
    protected function getDataAttribute(): ?InteractionData
    {
        if (! isset($this->attributes['data'])) {
            return null;
        }

        return $this->factory->create(InteractionData::class, $this->attributes['data'], true);
    }

    /**
     * Returns the guild the interaction was invoked from. Null when invoked via DM.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the channel the interaction was invoked from.
     *
     * @return Channel|null
     */
    protected function getChannelAttribute(): ?Channel
    {
        if ($this->guild && $channel = $this->guild->channels->get('id', $this->channel_id)) {
            return $channel;
        }

        return $this->discord->getChannel($this->channel_id);
    }

    /**
     * Returns the member who invoked the interaction. Null when invoked via DM.
     *
     * @return Member|null
     */
    protected function getMemberAttribute(): ?Member
    {
        if (isset($this->attributes['member'])) {
            if ($this->guild && $member = $this->guild->members->get('id', $this->attributes['member']->user->id)) {
                return $member;
            }

            return $this->factory->create(Member::class, (array) $this->attributes['member'] + ['guild_id' => $this->guild_id], true);
        }

        return null;
    }

    /**
     * Returns the user who invoked the interaction.
     *
     * @return User
     */
    protected function getUserAttribute(): User
    {
        if ($this->member) {
            return $this->member->user;
        }

        return $this->factory->create(User::class, $this->attributes['user'], true);
    }

    /**
     * Returns the message that triggered the interaction, when triggered via message components.
     *
     * @return Message|null
     */
    protected function getMessageAttribute(): ?Message
    {
        if (isset($this->attributes['message'])) {
            return $this->factory->create(Message::class, $this->attributes['message'], true);
        }

        return null;
    }
}
