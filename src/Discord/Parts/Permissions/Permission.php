<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Permissions;

use Discord\Discord;
use Discord\Parts\Part;

/**
 * Permission represents a set of permissions for a given role or overwrite.
 *
 * @property int  $bitwise
 * @property bool $create_instant_invite
 * @property bool $manage_channels
 * @property bool $view_channel
 * @property bool $manage_roles
 * @property bool $manage_webhooks
 */
abstract class Permission extends Part
{
    /**
     * Array of permissions that only apply to voice channels.
     *
     * @var array
     */
    public const VOICE_PERMISSIONS = [
        'priority_speaker' => 0x100,
        'stream' => 0x200,
        'connect' => 0x100000,
        'speak' => 0x200000,
        'mute_members' => 0x400000,
        'deafen_members' => 0x800000,
        'move_members' => 0x1000000,
        'use_vad' => 0x2000000,
        'request_to_speak' => (1 << 32),
    ];

    /**
     * Array of permissions that only apply to text channels.
     *
     * @var array
     */
    public const TEXT_PERMISSIONS = [
        'add_reactions' => 0x40,
        'send_messages' => 0x800,
        'send_tts_messages' => 0x1000,
        'manage_messages' => 0x2000,
        'embed_links' => 0x4000,
        'attach_files' => 0x8000,
        'read_message_history' => 0x10000,
        'mention_everyone' => 0x20000,
        'use_external_emojis' => 0x40000,
        'use_slash_commands' => (1 << 31),
        'manage_threads' => (1 << 34),
        'use_public_threads' => (1 << 35),
        'use_private_threads' => (1 << 36),
    ];

    /**
     * Array of permissions that can only be applied to roles.
     *
     * @var array
     */
    public const ROLE_PERMISSIONS = [
        'kick_members' => 0x2,
        'ban_members' => 0x4,
        'administrator' => 0x8,
        'manage_guild' => 0x20,
        'view_audit_log' => 0x80,
        'view_guild_insights' => 0x80000,
        'change_nickname' => 0x4000000,
        'manage_nicknames' => 0x8000000,
        'manage_emojis' => 0x40000000,
    ];

    /**
     * Array of permissions for all roles.
     *
     * @var array
     */
    public const ALL_PERMISSIONS = [
        'create_instant_invite' => 0x1,
        'manage_channels' => 0x10,
        'view_channel' => 0x400,
        'manage_roles' => 0x10000000,
        'manage_webhooks' => 0x20000000,
    ];

    /**
     * Array of permissions.
     *
     * @var array
     */
    private $permissions = [];

    /**
     * @inheritdoc
     */
    public function __construct(Discord $discord, array $attributes = [], bool $created = false)
    {
        $this->permissions = $this->getPermissions();
        $this->fillable = array_keys($this->permissions);
        $this->fillable[] = 'bitwise';

        parent::__construct($discord, $attributes, $created);

        foreach ($this->fillable as $permission) {
            if (! isset($this->attributes[$permission])) {
                $this->attributes[$permission] = false;
            }
        }
    }

    /**
     * Returns an array of extra permissions.
     *
     * @return array
     */
    abstract public static function getPermissions(): array;

    /**
     * Gets the bitwise attribute of the permission.
     *
     * @return int
     */
    protected function getBitwiseAttribute(): int
    {
        $bitwise = 0;

        foreach ($this->permissions as $permission => $value) {
            if ($this->attributes[$permission]) {
                $bitwise |= $value;
            }
        }

        return $bitwise;
    }

    /**
     * Sets the bitwise attribute of the permission.
     *
     * @param int|string $bitwise
     */
    protected function setBitwiseAttribute($bitwise)
    {
        if (is_string($bitwise)) {
            $bitwise = (int) $bitwise;
        }

        foreach ($this->permissions as $permission => $value) {
            if (($bitwise & $value) == $value) {
                $this->attributes[$permission] = true;
            } else {
                $this->attributes[$permission] = false;
            }
        }
    }
}
