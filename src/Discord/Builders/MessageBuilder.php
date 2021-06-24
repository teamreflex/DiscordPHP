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

use Discord\Exceptions\FileNotFoundException;
use Discord\Helpers\Multipart;
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
     * @return Multipart
     */
    public function toMultipart(): Multipart
    {
        $fields = [
            [
                'name' => 'payload_json',
                'content' => json_encode($this),
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ],
        ];

        foreach ($this->files as $idx => [$filename, $content]) {
            $fields[] = [
                'name' => 'file'.$idx,
                'content' => $content,
                'filename' => $filename,
            ];
        }

        return new Multipart($fields);
    }

    public function jsonSerialize(): array
    {
        $content = [];

        if ($this->content) {
            $content['content'] = $this->content;
        }

        if ($this->tts) {
            $content['tts'] = true;
        }

        if (count($this->embeds) > 0) {
            $content['embeds'] = $this->embeds;
        }

        if ($this->replyTo) {
            $content['message_reference'] = [
                'message_id' => $this->replyTo->id,
                'channel_id' => $this->replyTo->channel_id,
            ];
        }

        return $content;
    }
}
