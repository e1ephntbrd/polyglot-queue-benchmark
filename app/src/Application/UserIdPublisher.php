<?php

namespace app\src\Application;

use app\src\Infrastructure\Queue\MessagePublisher;
use app\src\Infrastructure\UserId\SourceInterface;
use app\src\Infrastructure\UserId\StorageInterface;
use app\src\Shared\Config\AppSettings;

/**
 * Publishes user IDs to the campaign queue.
 *
 * This class accumulates user IDs from multiple sources,
 * stores them temporarily, and then releases them in batches
 * to the message broker for further processing.
 */
class UserIdPublisher
{
    /**
     * @param StorageInterface   $storage        Storage for accumulating user IDs
     * @param MessagePublisher   $queuePublisher Publisher for sending messages to the queue
     * @param SourceInterface[]  $sources        List of user ID sources indexed by code
     */
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly MessagePublisher $queuePublisher,
        private readonly array $sources = []
    ) {
    }

    /**
     * Execute the publishing process.
     *
     * - Iterates through all configured sources.
     * - Streams user IDs into the storage.
     * - Releases accumulated IDs in batches to the queue.
     *
     * @return void
     */
    public function execute(): void
    {
        $storage   = $this->storage;
        $publisher = $this->queuePublisher;
        $queue     = AppSettings::get('campaign.queue');

        // Accumulate user IDs from each source
        foreach ($this->sources as $code => $source) {
            \Yii::info("Accumulating User IDs from {$code} source", __METHOD__);
            $source->stream(function (int $userId) use ($storage) {
                $storage->accumulate($userId);
            });
        }

        // Release accumulated IDs in batches to the queue
        $storage->release(function (array $messageBody) use ($publisher, $queue) {
            $publisher->publish($queue, $messageBody);
            \Yii::info("Sending UserIDs batch to the broker", __METHOD__);
        });
    }
}
