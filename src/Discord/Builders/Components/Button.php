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
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use InvalidArgumentException;

use function Discord\poly_strlen;

class Button extends Component
{
    const STYLE_PRIMARY = 1;
    const STYLE_SECONDARY = 2;
    const STYLE_SUCCESS = 3;
    const STYLE_DANGER = 4;
    const STYLE_LINK = 5;

    /**
     * Style of button.
     *
     * @var int
     */
    private $style = 1;

    /**
     * Label for the button.
     *
     * @var string|null
     */
    private $label;

    /**
     * Emoji to display on the button.
     *
     * @var array|null
     */
    private $emoji;

    /**
     * Custom ID to send with the button.
     *
     * @var string|null
     */
    private $custom_id;

    /**
     * URL to send as the button. Only for link buttons.
     *
     * @var string
     */
    private $url;
    
    /**
     * Whether the button is disabled.
     *
     * @var bool
     */
    private $disabled;

    /**
     * Listener for when the button is pressed.
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
     * Creates a new button.
     *
     * @param int $style Style of the button.
     */
    public function __construct(int $style)
    {
        if (! in_array($style, [
            self::STYLE_PRIMARY,
            self::STYLE_SECONDARY,
            self::STYLE_SUCCESS,
            self::STYLE_DANGER,
            self::STYLE_LINK,
        ])) {
            throw new InvalidArgumentException('Invalid style.');
        }

        $this->style = $style;
    }

    /**
     * Creates a new button.
     *
     * @param int $style Style of the button.
     *
     * @return static
     */
    public static function new(int $style): static
    {
        return new static($style);
    }

    /**
     * Sets the style of the button.
     * 
     * If the button is originally a link button, the link attribute will be cleared.
     * If the button was changed to a link button, the listener will be cleared.
     *
     * @param int $style
     * 
     * @return static
     */
    public function setStyle(int $style): static
    {
        if (! in_array($style, [
            self::STYLE_PRIMARY,
            self::STYLE_SECONDARY,
            self::STYLE_SUCCESS,
            self::STYLE_DANGER,
            self::STYLE_LINK,
        ])) {
            throw new InvalidArgumentException('Invalid style.');
        }

        if ($this->style == self::STYLE_LINK && $style != self::STYLE_LINK) {
            $this->url = null;
        } else if ($this->style != self::STYLE_LINK && $style == self::STYLE_LINK && $this->listener && $this->discord) {
            $this->setListener(null, $this->discord);
        }

        $this->style = $style;

        return $this;
    }

    /**
     * Sets the label of the button.
     *
     * @param string $label
     *
     * @return $this
     */
    public function setLabel(string $label): static
    {
        if (poly_strlen($label) > 80) {
            throw new InvalidArgumentException('Label must be maximum 80 characters.');
        }

        $this->label = $label;

        return $this;
    }

    /**
     * Sets the emoji of the button.
     *
     * @param Emoji $emoji
     *
     * @return $this
     */
    public function setEmoji(Emoji $emoji): static
    {
        $this->emoji = [
            'name' => $emoji->name,
            'id' => $emoji->id,
            'animated' => $emoji->animated,
        ];

        return $this;
    }

    /**
     * Sets the custom ID of the button.
     *
     * @param string $custom_id
     *
     * @return $this
     */
    public function setCustomId(string $custom_id): static
    {
        if ($this->style == Button::STYLE_LINK) {
            throw new InvalidArgumentException('You cannot set the custom ID of a link button.');
        }

        if (poly_strlen($custom_id) > 100) {
            throw new InvalidArgumentException('Custom ID must be maximum 100 characters.');
        }

        $this->custom_id = $custom_id;

        return $this;
    }

    /**
     * Sets the URL of the button. Only valid for link buttons.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setUrl(string $url): static
    {
        if ($this->style != Button::STYLE_LINK) {
            throw new InvalidArgumentException('You cannot set the URL of a non-link button.');
        }

        $this->url = $url;

        return $this;
    }

    /**
     * Sets the button as disabled/not disabled.
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled(bool $disabled): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Sets the callable listener for the button. The `$callback` will be called when the button
     * is pressed.
     *
     * The callback will only be called once with the `$oneOff` parameter set to true.
     * This can be changed to false, and the callback will be called each time the button is pressed.
     * To remove the listener, you can pass `$callback` as null.
     *
     * The button listener will not persist when the bot restarts.
     *
     * @param callable $callback Callback to call when the button is pressed. Will be called with the interaction object.
     * @param Discord  $discord  Discord client.
     * @param bool     $oneOff   Whether the listener should be removed after the button is pressed for the first time.
     *
     * @return $this
     */
    public function setListener(?callable $callback, Discord $discord, bool $oneOff = true): static
    {
        if ($this->style == Button::STYLE_LINK) {
            throw new InvalidArgumentException('You cannot add a listener to a link button.');
        }

        if (! $this->custom_id) {
            $this->custom_id = $this->generateUuid();
        }

        // Remove any existing listener
        if ($this->listener) {
            $discord->removeListener(Event::INTERACTION_CREATE, $this->listener);
        }

        $this->discord = $discord;

        if ($callback == null) {
            return $this;
        }

        $this->listener = function (Interaction $interaction) use ($discord, $callback, $oneOff) {
            if ($interaction->data->component_type == Component::TYPE_BUTTON && $interaction->data->custom_id == $this->custom_id) {
                $callback($interaction);

                if ($oneOff) {
                    $discord->removeListener(Event::INTERACTION_CREATE, $this->listener);
                }
            }
        };

        $discord->on(Event::INTERACTION_CREATE, $this->listener);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => Component::TYPE_BUTTON,
            'style' => $this->style,
        ];

        if ($this->label) {
            $content['label'] = $this->label;
        }

        if ($this->emoji) {
            $content['emoji'] = $this->emoji;
        }

        if ($this->custom_id) {
            $content['custom_id'] = $this->custom_id;
        } elseif ($this->style != Button::STYLE_LINK) {
            throw new InvalidArgumentException('Buttons must have a `custom_id` field set.');
        }

        if ($this->url) {
            $content['url'] = $this->url;
        } elseif ($this->style == Button::STYLE_LINK) {
            throw new InvalidArgumentException('Link buttons must have a `url` field set.');
        }

        if ($this->disabled) {
            $content['disabled'] = true;
        }

        return $content;
    }

    /**
     * Generates a UUID which can be used as the custom ID.
     *
     * @return string
     */
    public static function generateUuid(): string
    {
        return uniqid(time(), true);
    }
}
