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
use Exception;
use InvalidArgumentException;
use React\Promise\PromiseInterface;

use function Discord\poly_strlen;

class Button extends Component
{
    public const STYLE_PRIMARY = 1;
    public const STYLE_SECONDARY = 2;
    public const STYLE_SUCCESS = 3;
    public const STYLE_DANGER = 4;
    public const STYLE_LINK = 5;

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
     * @return self
     */
    public static function new(int $style): self
    {
        return new self($style);
    }

    /**
     * Sets the style of the button.
     *
     * If the button is originally a link button, the link attribute will be cleared.
     * If the button was changed to a link button, the listener will be cleared.
     *
     * @param int $style
     *
     * @return $this
     */
    public function setStyle(int $style): self
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
        } elseif ($this->style != self::STYLE_LINK && $style == self::STYLE_LINK && $this->listener && $this->discord) {
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
    public function setLabel(string $label): self
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
    public function setEmoji(Emoji $emoji): self
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
    public function setCustomId(string $custom_id): self
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
     * Sets the URL of the button. Only valid for link buttons.tatic.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setUrl(string $url): self
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
    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Sets the callable listener for the button. The `$callback` will be called when the button
     * is pressed.
     *
     * If you do not respond to or acknowledge the `Interaction`, it will be acknowledged for you.
     * Note that if you intend to respond to or acknowledge the interaction inside a promise, you should
     * return a promise that resolves *after* you respond or acknowledge.
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
    public function setListener(?callable $callback, Discord $discord, bool $oneOff = false): self
    {
        if ($this->style == Button::STYLE_LINK) {
            throw new InvalidArgumentException('You cannot add a listener to a link button.');
        }

        if (! $this->custom_id) {
            $this->custom_id = $this->generateUuid();
        }

        // Remove any existing listener
        if ($this->listener) {
            $this->discord->removeListener(Event::INTERACTION_CREATE, $this->listener);
        }

        $this->discord = $discord;

        if ($callback == null) {
            return $this;
        }

        $this->listener = function (Interaction $interaction) use ($callback, $oneOff) {
            if ($interaction->data->component_type == Component::TYPE_BUTTON && $interaction->data->custom_id == $this->custom_id) {
                $response = $callback($interaction);
                $ack = function () use ($interaction) {
                    // attempt to acknowledge interaction if it has not already been responded to.
                    try {
                        $interaction->acknowledge();
                    } catch (Exception $e) {
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
     * @inheritdoc
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
}
