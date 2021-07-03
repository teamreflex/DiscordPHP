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

use Discord\Parts\Guild\Emoji;
use InvalidArgumentException;

use function Discord\poly_strlen;

class Option extends Component
{
    /**
     * User-visible label of the option. Maximum 25 characters.
     *
     * @var string
     */
    private $label;

    /**
     * Developer value for the option. Maximum 100 characters.
     *
     * @var string
     */
    private $value;

    /**
     * Description for the option. Maximum 50 characters.
     *
     * @var string|null
     */
    private $description;

    /**
     * Emoji to display alongside the option.
     *
     * @var Emoji|null
     */
    private $emoji;

    /**
     * Whether the option should be enabled as default.
     *
     * @var bool
     */
    private $default = false;

    /**
     * Creates a new select menu option.
     *
     * @param string      $label User-visible label for the option. Maximum 25 characters.
     * @param string|null $value Developer value for the option. Maximum 100 characters. Leave as null to automatically generate a UUID.
     */
    public function __construct(string $label, ?string $value)
    {
        if (poly_strlen($label) > 25) {
            throw new InvalidArgumentException('Label must be less than or equal to 25 characters.');
        }

        if ($value && poly_strlen($value) > 100) {
            throw new InvalidArgumentException('Value must be less than or equal to 100 characters.');
        }

        $this->label = $label;
        $this->value = $value ?? $this->generateUuid();
    }

    /**
     * Creates a new select menu option.
     *
     * @param string      $label User-visible label for the option. Maximum 25 characters.
     * @param string|null $value Developer value for the option. Maximum 100 characters. Leave as null to automatically generate a UUID.
     *
     * @return self
     */
    public static function new(string $label, ?string $value = null): self
    {
        return new self($label, $value);
    }

    /**
     * Sets the description of the option. Null to clear.
     *
     * @param string|null $description Description of the option. Maximum 50 characters.
     *
     * @return $this
     */
    public function setDescription(?string $description): self
    {
        if ($description && poly_strlen($description) > 50) {
            throw new InvalidArgumentException('Description must be less than or equal to 50 characters.');
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Sets the emoji of the option. Null to clear.
     *
     * @param Emoji|null $emoji Emoji to set.
     *
     * @return $this
     */
    public function setEmoji(?Emoji $emoji): self
    {
        $this->emoji = $emoji;

        return $this;
    }

    /**
     * Sets the option as default. Pass false to set as non-default.
     *
     * @param bool $default
     *
     * @return $this
     */
    public function setDefault(bool $default = true): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Returns the developer value for the option.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'label' => $this->label,
            'value' => $this->value,
        ];

        if ($this->description) {
            $content['description'] = $this->description;
        }
        
        if ($this->emoji) {
            $content['emoji'] = [
                'id' => $this->emoji->id,
                'name' => $this->emoji->name,
                'animated' => $this->emoji->animated,
            ];
        }

        if ($this->default) {
            $content['default'] = true;
        }

        return $content;
    }
}
