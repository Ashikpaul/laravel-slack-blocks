<?php

namespace NathanHeffley\Tests\LaravelSlackBlocks;

use Mockery as m;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use NathanHeffley\LaravelSlackBlocks\Messages\SlackMessage;
use NathanHeffley\LaravelSlackBlocks\Channels\SlackWebhookChannel;

class NotificationSlackChannelTest extends TestCase
{
    /**
     * @var \NathanHeffley\LaravelSlackBlocks\Channels\SlackWebhookChannel
     */
    private $slackChannel;

    /**
     * @var \Mockery\MockInterface|\GuzzleHttp\Client
     */
    private $guzzleHttp;

    protected function setUp()
    {
        parent::setUp();

        $this->guzzleHttp = m::mock(Client::class);

        $this->slackChannel = new SlackWebhookChannel($this->guzzleHttp);
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @dataProvider payloadDataProvider
     * @param \Illuminate\Notifications\Notification $notification
     * @param array $payload
     */
    public function testCorrectPayloadIsSentToSlack(Notification $notification, array $payload)
    {
        $this->guzzleHttp->shouldReceive('post')->andReturnUsing(function ($argUrl, $argPayload) use ($payload) {
            $this->assertEquals($argUrl, 'url');
            $this->assertEquals($argPayload, $payload);
        });

        $this->slackChannel->send(new NotificationSlackChannelTestNotifiable, $notification);
    }

    public function payloadDataProvider()
    {
        return [
            'payloadWithAttachmentBlockBuilder' => $this->getPayloadWithAttachmentBlockBuilder(),
        ];
    }

    public function getPayloadWithAttachmentBlockBuilder()
    {
        return [
            new NotificationSlackChannelWithAttachmentBlockBuilderTestNotification,
            [
                'json' => [
                    'text' => 'Content',
                    'attachments' => [
                        [
                            'title' => 'Laravel',
                            'text' => 'Attachment Content',
                            'title_link' => 'https://laravel.com',
                            'blocks' => [
                                [
                                    'type' => 'section',
                                    'text' => [
                                        'type' => 'plain_text',
                                        'text' => 'Laravel',
                                    ],
                                    'block_id' => 'block-one',
                                    'fields' => [
                                        [
                                            'type' => 'mrkdwn',
                                            'text' => 'Block',
                                        ],
                                        [
                                            'type' => 'mrkdwn',
                                            'text' => 'Attachments',
                                        ],
                                    ],
                                    'accessory' => [
                                        'type' => 'datepicker',
                                    ],
                                ],
                                [
                                    'type' => 'divider',
                                ],
                                [
                                    'type' => 'image',
                                    'image_url' => 'https://placekitten.com/400/600',
                                    'alt_text' => 'A cute little kitten',
                                    'title' => [
                                        'type' => 'plain_text',
                                        'text' => 'Kitten picture',
                                    ],
                                ],
                                [
                                    'type' => 'actions',
                                    'elements' => [
                                        'type' => 'button',
                                        "text" => [
                                            'type' => 'plain_text',
                                            'text' => 'Cancel',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

class NotificationSlackChannelTestNotifiable
{
    use Notifiable;

    public function routeNotificationForSlack()
    {
        return 'url';
    }
}

class NotificationSlackChannelWithAttachmentBlockBuilderTestNotification extends Notification
{
    public function toSlack($notifiable)
    {
        return (new SlackMessage)
            ->content('Content')
            ->attachment(function ($attachment) {
                $attachment->title('Laravel', 'https://laravel.com')
                    ->content('Attachment Content')
                    ->block(function ($attachmentBlock) {
                        $attachmentBlock
                            ->type('section')
                            ->text([
                                'type' => 'plain_text',
                                'text' => 'Laravel',
                            ])
                            ->id('block-one')
                            ->fields([
                                [
                                    'type' => 'mrkdwn',
                                    'text' => 'Block',
                                ],
                                [
                                    'type' => 'mrkdwn',
                                    'text' => 'Attachments',
                                ],
                            ])
                            ->accessory([
                                'type' => 'datepicker',
                            ]);
                    })
                    ->block(function ($attachmentBlock) {
                        $attachmentBlock->type('divider');
                    })
                    ->block(function ($attachmentBlock) {
                        $attachmentBlock
                            ->type('image')
                            ->imageUrl('https://placekitten.com/400/600')
                            ->altText('A cute little kitten')
                            ->title([
                                'type' => 'plain_text',
                                'text' => 'Kitten picture'
                            ]);
                    })
                    ->block(function ($attachmentBlock) {
                        $attachmentBlock
                            ->type('actions')
                            ->elements([
                                'type' => 'button',
                                "text" => [
                                    'type' => 'plain_text',
                                    'text' => 'Cancel',
                                ],
                            ]);
                    });
            });
    }
}