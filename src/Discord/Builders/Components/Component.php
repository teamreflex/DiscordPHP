<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders\Components;

use JsonSerializable;

abstract class Component implements JsonSerializable
{
    const TYPE_ACTION_ROW = 1;
    const TYPE_BUTTON = 2;
    const TYPE_SELECT_MENU = 3;
}
