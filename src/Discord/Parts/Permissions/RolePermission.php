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

/**
 * @inheritdoc
 *
 * @property bool $priority_speaker
 * @property bool $stream
 * @property bool $connect
 * @property bool $speak
 * @property bool $mute_members
 * @property bool $deafen_members
 * @property bool $move_members
 * @property bool $use_vad
 * @property bool $request_to_speak
 *
 * @property bool $add_reactions
 * @property bool $send_messages
 * @property bool $send_tts_messages
 * @property bool $manage_messages
 * @property bool $embed_links
 * @property bool $attach_files
 * @property bool $read_message_history
 * @property bool $mention_everyone
 * @property bool $use_external_emojis
 * @property bool $use_slash_commands
 * @property bool $manage_threads
 * @property bool $use_public_threads
 * @property bool $use_private_threads
 *
 * @property bool $kick_members
 * @property bool $ban_members
 * @property bool $administrator
 * @property bool $manage_guild
 * @property bool $view_audit_log
 * @property bool $view_guild_insights
 * @property bool $change_nickname
 * @property bool $manage_nicknames
 * @property bool $manage_emojis
 */
class RolePermission extends Permission
{
    /**
     * @inheritdoc
     */
    public static function getPermissions(): array
    {
        return array_merge(parent::ALL_PERMISSIONS, parent::TEXT_PERMISSIONS, parent::VOICE_PERMISSIONS, parent::ROLE_PERMISSIONS);
    }
}
