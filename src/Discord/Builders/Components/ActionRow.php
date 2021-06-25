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

use InvalidArgumentException;

class ActionRow extends Component
{
    /**
     * Components contained by the action row.
     *
     * @var Component[]
     */
    private $components = [];

    /**
     * Creates a new action row.
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }

    /**
     * Adds a component to the action row.
     *
     * @param Component $component
     *
     * @return $this
     */
    public function addComponent(Component $component)
    {
        if ($component instanceof ActionRow) {
            throw new InvalidArgumentException('You cannot add another `ActionRow` to this action row.');
        }

        if (count($this->components) >= 5) {
            throw new InvalidArgumentException('You can only have 5 components per action row.');
        }

        $this->components[] = $component;

        return $this;
    }

    /**
     * Returns all the components in the action row.
     *
     * @return Component[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => Component::TYPE_ACTION_ROW,
            'components' => $this->components,
        ];
    }
}
