<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Component;
use Discord\Builders\Components\SelectMenu;
use Discord\Exceptions\FileNotFoundException;
use Discord\Helpers\Multipart;
use Discord\Http\Exceptions\RequestFailedException;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Helper class used to build messages.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class MessageBuilder implements JsonSerializable
{
    /**
     * Content of the message.
     *
     * @var string|null
     */
    private $content;
    
    /**
     * Whether the message is text-to-speech.
     *
     * @var bool
     */
    private $tts = false;

    /**
     * Array of embeds to send with the message.
     *
     * @var array[]
     */
    private $embeds = [];

    /**
     * Message to reply to with this message.
     *
     * @var Message|null
     */
    private $replyTo;

    /**
     * Files to send with this message.
     *
     * @var array[]
     */
    private $files = [];

    /**
     * Components to send with this message.
     *
     * @var Component[]
     */
    private $components = [];

    /**
     * Flags to send with this message.
     *
     * @var int|null
     */
    private $flags;

    /**
     * Creates a new message builder.
     *
     * @return $this
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * Sets the content of the message.
     *
     * @param string $content
     *
     * @return $this
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Sets the TTS status of the message.
     *
     * @param bool $tts
     *
     * @return $this
     */
    public function setTts(bool $tts): self
    {
        $this->tts = $tts;

        return $this;
    }

    /**
     * Returns the value of TTS of the message.
     *
     * @return bool
     */
    public function getTts(): bool
    {
        return $this->tts ?? false;
    }

    /**
     * Adds an embed to the message.
     *
     * @param Embed|array $embeds,...
     *
     * @return $this
     */
    public function addEmbed(...$embeds): self
    {
        foreach ($embeds as $embed) {
            if ($embed instanceof Embed) {
                $embed = $embed->getRawAttributes();
            }

            if (count($this->embeds) >= 10) {
                throw new InvalidArgumentException('You can only have 10 embeds per message.');
            }

            $this->embeds[] = $embed;
        }

        return $this;
    }

    /**
     * Sets the embeds for the message. Clears the existing embeds in the process.
     *
     * @param array $embeds
     *
     * @return $this
     */
    public function setEmbeds(array $embeds): self
    {
        $this->embeds = [];

        return $this->addEmbed(...$embeds);
    }

    /**
     * Sets this message as a reply to another message.
     *
     * @param Message|null $message
     *
     * @return $this
     */
    public function setReplyTo(?Message $message): self
    {
        $this->replyTo = $message;

        return $this;
    }

    /**
     * Adds a file attachment to the message.
     *
     * Note this is a synchronous function which uses `file_get_contents` and therefore
     * should not be used when requesting files from an online resource. Fetch the content
     * asynchronously and use the `addFileFromContent` function for tasks like these.
     *
     * @param string      $filepath Path to the file to send.
     * @param string|null $filename Name to send the file as. Null for the base name of `$filepath`.
     *
     * @return $this
     */
    public function addFile(string $filepath, ?string $filename = null): self
    {
        if (! file_exists($filepath)) {
            throw new FileNotFoundException("File does not exist at path {$filepath}.");
        }

        if ($filename == null) {
            $filename = basename($filepath);
        }

        return $this->addFileFromContent($filename, file_get_contents($filepath));
    }

    /**
     * Adds a file attachment to the message with a given filename and content.
     *
     * @param string $filename Name to send the file as.
     * @param string $content  Content of the file.
     *
     * @return $this
     */
    public function addFileFromContent(string $filename, string $content): self
    {
        $this->files[] = [$filename, $content];

        return $this;
    }

    /**
     * Returns the number of files attached to the message.
     *
     * @return int
     */
    public function numFiles(): int
    {
        return count($this->files);
    }

    /**
     * Removes all files from the message.
     *
     * @return $this
     */
    public function clearFiles(): self
    {
        $this->files = [];

        return $this;
    }

    /**
     * Adds a component to the message.
     *
     * @param Component $component Component to add.
     *
     * @return $this
     */
    public function addComponent(Component $component): self
    {
        if (! ($component instanceof ActionRow || $component instanceof SelectMenu)) {
            throw new InvalidArgumentException('You can only add action rows and select menus as components to messages. Put your other components inside an action row.');
        }

        if (count($this->components) >= 5) {
            throw new InvalidArgumentException('You can only add 5 components to a message');
        }

        $this->components[] = $component;

        return $this;
    }

    /**
     * Removes a component from the message.
     *
     * @param Component $component Component to remove.
     *
     * @return $this
     */
    public function removeComponent(Component $component): self
    {
        if (($idx = array_search($component, $this->components)) !== null) {
            array_splice($this->components, $idx, 1);
        }

        return $this;
    }

    /**
     * Sets the components of the message. Removes the existing components in the process.
     *
     * @param array $components New message components.
     *
     * @return $this
     */
    public function setComponents(array $components): self
    {
        $this->components = [];

        foreach ($components as $component) {
            $this->addComponent($component);
        }

        return $this;
    }

    /**
     * Returns all the components in the message.
     *
     * @return Component[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Sets the flags of the message.
     *
     * @internal You cannot set flags except for when sending webhooks. Use the APIs given.
     *
     * @param int $flags
     *
     * @return $this
     */
    public function _setFlags(int $flags): self
    {
        $this->flags = $flags;

        return $this;
    }

    /**
     * Returns a boolean that determines whether the message needs to
     * be sent via multipart request, i.e. contains files.
     *
     * @return bool
     */
    public function requiresMultipart(): bool
    {
        return count($this->files) > 0;
    }

    /**
     * Converts the request to a multipart request.
     *
     * @internal
     *
     * @param bool $payload Whether to include the JSON payload in the response.
     *
     * @return Multipart
     */
    public function toMultipart(bool $payload = true): Multipart
    {
        if ($payload) {
            $fields = [
                [
                    'name' => 'payload_json',
                    'content' => json_encode($this),
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ],
            ];
        }

        foreach ($this->files as $idx => [$filename, $content]) {
            $fields[] = [
                'name' => 'file'.$idx,
                'content' => $content,
                'filename' => $filename,
            ];
        }

        return new Multipart($fields);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        $empty = count($this->files) < 1;
        $content = [
            'components' => $this->components,
        ];

        if ($this->content) {
            $content['content'] = $this->content;
            $empty = false;
        }

        if ($this->tts) {
            $content['tts'] = true;
        }

        if ($this->flags) {
            $content['flags'] = $this->flags;
        }

        if (count($this->embeds) > 0) {
            $content['embeds'] = $this->embeds;
            $empty = false;
        }

        if ($this->replyTo) {
            $content['message_reference'] = [
                'message_id' => $this->replyTo->id,
                'channel_id' => $this->replyTo->channel_id,
            ];
        }

        if ($empty) {
            throw new RequestFailedException('You cannot send an empty message. Set the content or add an embed or file.');
        }

        return $content;
    }
}
