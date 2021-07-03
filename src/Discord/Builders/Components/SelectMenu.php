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

use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use Exception;
use InvalidArgumentException;
use React\Promise\PromiseInterface;

class SelectMenu extends Component
{
    /**
     * Custom ID to identify the select menu.
     *
     * @var string
     */
    private $custom_id;

    /**
     * Array of options that the select menu has.
     *
     * @var Option[]
     */
    private $options = [];

    /**
     * Placeholder string to display if nothing is selected. Maximum 100 characters.
     *
     * @var string|null
     */
    private $placeholder;

    /**
     * Minimum number of options that must be selected.
     * Default 1, minimum 0, maximum 25.
     *
     * @var int|null
     */
    private $min_values;

    /**
     * Maximum number of options that must be selected.
     * Default 1, maximum 25.
     *
     * @var int|null
     */
    private $max_values;

    /**
     * Whether the select menu should be disabled.
     *
     * @var bool|null
     */
    private $disabled;

    /**
     * Callback used to listen for `INTERACTION_CREATE` events.
     *
     * @var callable|null
     */
    private $listener;

    /**
     * Discord instance when the listener is set.
     *
     * @var Discord|null
     */
    private $discord;

    /**
     * Creates a new select menu.
     */
    public function __construct()
    {
        $this->custom_id = $this->generateUuid();
    }

    /**
     * Creates a new select menu.
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Adds an option to the select menu. Maximum 25 options.
     *
     * @param Option $option Option to add.
     *
     * @return $this
     */
    public function addOption(Option $option): self
    {
        if (count($this->options) >= 25) {
            throw new InvalidArgumentException('You can only have 25 options per select menu.');
        }

        $value = $option->getValue();

        // didn't wanna use a hashtable here so that we can keep the order of options
        foreach ($this->options as $other) {
            if ($other->getValue() == $value) {
                throw new InvalidArgumentException('Another value already has the same value. These must not be the same.');
            }
        }

        $this->options[] = $option;

        return $this;
    }

    /**
     * Removes an option from the select menu.
     *
     * @param Option $option Option to remove.
     *
     * @return $this
     */
    public function removeOption(Option $option): self
    {
        if (($idx = array_search($option, $this->options)) !== null) {
            array_splice($this->options, $idx, 1);
        }

        return $this;
    }

    /**
     * Sets the placeholder string to display if nothing is selected.
     * Maximum 100 characters. Null to clear placeholder.
     *
     * @param string|null $placeholder
     *
     * @return $this
     */
    public function setPlaceholder(?string $placeholder): self
    {
        if ($placeholder && strlen($placeholder) > 100) {
            throw new InvalidArgumentException('Placeholder string must be less than or equal to 100 characters.');
        }

        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Sets the minimum number of options which must be chosen.
     * Default 1, minimum 0 and maximum 25. Null to set as default.
     *
     * @param int|null $min_values
     *
     * @return $this
     */
    public function setMinValues(?int $min_values): self
    {
        if ($min_values && ($min_values < 0 || $min_values > 25)) {
            throw new InvalidArgumentException('Number must be between 0 and 25 inclusive.');
        }

        $this->min_values = $min_values;

        return $this;
    }

    /**
     * Sets the maximum number of options which must be chosen.
     * Default 1 and maximum 25. Null to set as default.
     *
     * @param int|null $max_values
     *
     * @return $this
     */
    public function setMaxValues(?int $max_values): self
    {
        if ($max_values && $max_values > 25) {
            throw new InvalidArgumentException('Number must be less than or equal to 25.');
        }

        $this->max_values = $max_values;

        return $this;
    }

    /**
     * Sets the select menus disabled state.
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled(bool $disabled = true): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Sets the callable listener for the select menu. The `$callback` function will be called when
     * the selection of the menu is changed.
     *
     * The callback function is called with the `Interaction` object as well as a `Collection` of
     * selected options.
     *
     * If you do not respond to or acknowledge the `Interaction`, it will be acknowledged for you.
     * Note that if you intend to respond to or acknowledge the interaction inside a promise, you should
     * return a promise that resolves *after* you respond or acknowledge.
     *
     * The callback will only be called once with the `$oneOff` parameter set to true.
     * This can be changed to false, and the callback will be called each time the selection is changed.
     * To remove the listener, you can pass `$callback` as null.
     *
     * The select menu listener will not persist when the bot restarts.
     *
     * @param callable $callback Callback to call when the selection is changed. Will be called with the interaction object and collection of options.
     * @param Discord  $discord  Discord client.
     * @param bool     $oneOff   Whether the listener should be removed after the selection is changed for the first time.
     *
     * @return $this
     */
    public function setListener(?callable $callback, Discord $discord, bool $oneOff = false): self
    {
        if ($this->listener) {
            $this->discord->removeListener(Event::INTERACTION_CREATE, $this->listener);
        }

        $this->discord = $discord;

        if ($callback == null) {
            return $this;
        }

        $this->listener = function (Interaction $interaction) use ($callback, $oneOff) {
            if ($interaction->data->component_type == Component::TYPE_SELECT_MENU &&
                $interaction->data->custom_id == $this->custom_id) {
                $options = Collection::for(Option::class, null);
                
                foreach ($this->options as $option) {
                    if (in_array($option->getValue(), $interaction->data->values)) {
                        $options->push($option);
                    }
                }

                $response = $callback($interaction, $options);
                $ack = function () use ($interaction) {
                    // attempt to acknowledge interaction if it has not already been responded to.
                    try {
                        $interaction->acknowledge();
                    } catch (exception $e) {
                    }
                };

                if ($response instanceof PromiseInterface) {
                    $response->then($ack);
                } else {
                    $ack();
                }

                if ($oneOff) {
                    $this->removeListener();
                }
            }
        };

        $discord->on(Event::INTERACTION_CREATE, $this->listener);

        return $this;
    }

    /**
     * Removes the listener from the button.
     *
     * @return $this
     */
    public function removeListener(): self
    {
        return $this->setListener(null, $this->discord);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => Component::TYPE_SELECT_MENU,
            'custom_id' => $this->custom_id,
            'options' => $this->options,
        ];

        if ($this->placeholder) {
            $content['placeholder'] = $this->placeholder;
        }

        if ($this->min_values) {
            if ($this->min_values > count($this->options)) {
                throw new InvalidArgumentException('There are less options than the minimum number of options to be selected.');
            }

            $content['min_values'] = $this->min_values;
        }

        if ($this->max_values) {
            if ($this->max_values > count($this->options)) {
                throw new InvalidArgumentException('There are less options than the maximum number of options to be selected.');
            }

            $content['max_values'] = $this->max_values;
        }

        if ($this->disabled) {
            $content['disabled'] = true;
        }

        return [
            'type' => Component::TYPE_ACTION_ROW,
            'components' => [$content],
        ];
    }
}
