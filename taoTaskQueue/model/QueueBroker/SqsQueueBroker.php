<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoTaskQueue\model\QueueBroker;

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use oat\awsTools\AwsClient;
use oat\tao\model\taskQueue\Queue\Broker\AbstractQueueBroker;
use oat\tao\model\taskQueue\Task\TaskInterface;

/**
 * Storing messages/tasks on AWS SQS.
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */
class SqsQueueBroker extends AbstractQueueBroker
{
    const DEFAULT_AWS_CLIENT_KEY = 'generis/awsClient';

    private $cacheId;

    /**
     * @var SqsClient
     */
    private $client;
    private $queueUrl;

    /**
     * @var \common_cache_Cache
     */
    private $cache;

    /**
     * SqsQueueBroker constructor.
     *
     * @param string $cacheServiceId
     * @param int $receiveTasks
     */
    public function __construct($cacheServiceId, $receiveTasks = 1)
    {
        parent::__construct($receiveTasks);

        if (empty($cacheServiceId)) {
            throw new \InvalidArgumentException("Cache Service needs to be set for ". __CLASS__);
        }

        $this->cacheId = $cacheServiceId;
    }

    public function __toPhpCode()
    {
        return 'new '. get_called_class() .'('
            . \common_Utils::toHumanReadablePhpString($this->cacheId)
            . ', '
            . \common_Utils::toHumanReadablePhpString($this->getNumberOfTasksToReceive())
            .')';
    }

    /**
     * @return SqsClient
     */
    protected function getClient()
    {
        if (is_null($this->client)) {
            if (!$this->getServiceLocator()->has(self::DEFAULT_AWS_CLIENT_KEY)) {
                throw new \RuntimeException('Unable to load driver for '. __CLASS__ .', most likely generis/awsClient.conf.php does not exist.');
            }

            /** @var AwsClient $awsClient */
            $awsClient = $this->getServiceLocator()->get(self::DEFAULT_AWS_CLIENT_KEY);

            $this->client = $awsClient->getSqsClient();
        }

        return $this->client;
    }

    /**
     * @return \common_cache_Cache
     */
    protected function getCache()
    {
        if (is_null($this->cache)) {
            $this->cache = $this->getServiceLocator()->get($this->cacheId);
        }

        return $this->cache;
    }

    /**
     * Creates queue.
     */
    public function createQueue()
    {
        try {
            // Note: we are creating a Standard Queue for the time being.
            // More development needed to be able to customize it, for example creating FIFO Queue or setting attributes from outside.
            /** @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#createqueue */
            $result = $this->getClient()->createQueue([
                'QueueName' => $this->getQueueNameWithPrefix(),
                'Attributes' => [
                    'DelaySeconds' => 0,
                    'VisibilityTimeout' => 600
                ]
            ]);

            if ($result->hasKey('QueueUrl')) {
                $this->queueUrl = $result->get('QueueUrl');

                $this->getCache()->put($this->getUrlCacheKey(), $this->queueUrl);

                $this->logDebug('Queue '. $this->queueUrl .' created and cached');
            } else {
                $this->logError('Queue '. $this->getQueueNameWithPrefix() .' not created');
            }
        } catch (AwsException $e) {
            $this->logError('Creating queue '. $this->getQueueNameWithPrefix() .' failed with MSG: '. $e->getMessage());

            if(PHP_SAPI == 'cli'){
                throw $e;
            }
        }
    }

    /**
     * @param TaskInterface $task
     * @return bool
     */
    public function push(TaskInterface $task)
    {
        // ensures that the SQS Queue exist
        if (!$this->queueExists()) {
            $this->createQueue();
        }

        $logContext = [
            'QueueUrl' => $this->queueUrl,
            'InternalMessageId' => $task->getId()
        ];

        try {
            $result = $this->getClient()->sendMessage([
                'MessageAttributes' => [],
                'MessageBody' => $this->serializeTask($task),
                'QueueUrl' => $this->queueUrl
            ]);

            if ($result->hasKey('MessageId')) {
                $this->logDebug('Message pushed to SQS', array_merge($logContext, [
                    'SqsMessageId' => $result->get('MessageId')
                ]));
                return true;
            } else {
                $this->logError('Message seems not received by SQS.', $logContext);
            }
        } catch (AwsException $e) {
            $this->logError('Pushing message failed with MSG: '. $e->getAwsErrorMessage(), $logContext);
        }

        return false;
    }

    /**
     * Does the SQS specific pop mechanism.
     *
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#shape-message
     */
    protected function doPop()
    {
        // ensures that the SQS Queue exist
        if (!$this->queueExists()) {
            $this->createQueue();
        }

        $logContext = [
            'QueueUrl' => $this->queueUrl
        ];

        try {
            $result = $this->getClient()->receiveMessage([
                'AttributeNames' => [], //nothing
                'MaxNumberOfMessages' => $this->getNumberOfTasksToReceive(),
                'MessageAttributeNames' => [], //nothing
                'QueueUrl' => $this->queueUrl,
                'WaitTimeSeconds' => 20 //retrieving messages with Long Polling
            ]);

            if (count($result->get('Messages')) > 0) {
                $this->logDebug('Received '. count($result->get('Messages')) .' messages.', $logContext);

                foreach ($result->get('Messages') as $message) {
                    $task = $this->unserializeTask($message['Body'], $message['ReceiptHandle'], [
                        'SqsMessageId' => $message['MessageId']
                    ]);

                    if ($task) {
                        $task->setMetadata('SqsMessageId', $message['MessageId']);
                        $task->setMetadata('ReceiptHandle', $message['ReceiptHandle']);
                        $this->pushPreFetchedMessage($task);
                    }
                }
            } else {
                $this->logDebug('No messages in queue.', $logContext);
            }
        } catch (AwsException $e) {
            $this->logError('Popping tasks failed with MSG: '. $e->getAwsErrorMessage(), $logContext);
        }
    }

    /**
     * @param TaskInterface $task
     */
    public function delete(TaskInterface $task)
    {
        $this->doDelete($task->getMetadata('ReceiptHandle'), [
            'InternalMessageId' => $task->getId(),
            'SqsMessageId' => $task->getMetadata('SqsMessageId')
        ]);
    }

    /**
     * Delete a task by its receipt.
     *
     * @param string $receipt
     * @param array $logContext
     */
    protected function doDelete($receipt, array $logContext = [])
    {
        // ensures that the SQS Queue exist
        if (!$this->queueExists()) {
            $this->createQueue();
        }

        $logContext = array_merge([
            'QueueUrl' => $this->queueUrl
        ], $logContext);

        try {
            $this->getClient()->deleteMessage([
                'QueueUrl' => $this->queueUrl,
                'ReceiptHandle' => $receipt
            ]);

            $this->logDebug('Task deleted from queue.', $logContext);
        } catch (AwsException $e) {
            $this->logError('Deleting task failed with MSG: '. $e->getAwsErrorMessage(), $logContext);
        }
    }

    /**
     * @return int
     */
    public function count()
    {
        // ensures that the SQS Queue exist
        if (!$this->queueExists()) {
            $this->createQueue();
        }

        try {
            $result = $this->getClient()->getQueueAttributes([
                'QueueUrl' => $this->queueUrl,
                'AttributeNames' => ['ApproximateNumberOfMessages'],
            ]);

            if (isset($result['Attributes']['ApproximateNumberOfMessages'])) {
                return (int) $result['Attributes']['ApproximateNumberOfMessages'];
            }
        } catch (AwsException $e) {
            $this->logError('Counting tasks failed with MSG: '. $e->getAwsErrorMessage());
        }

        return 0;
    }

    /**
     * Checks if queue exists
     *
     * @return bool
     */
    protected function queueExists()
    {
        if (isset($this->queueUrl)) {
            return true;
        }

        if ($this->getCache()->has($this->getUrlCacheKey())) {
            $this->queueUrl = $this->getCache()->get($this->getUrlCacheKey());
            return true;
        }

        try {
            $result = $this->getClient()->getQueueUrl([
                'QueueName' => $this->getQueueNameWithPrefix()
            ]);

            $this->queueUrl = $result->get('QueueUrl');

            if ($result->hasKey('QueueUrl')) {
                $this->queueUrl = $result->get('QueueUrl');
            } else {
                $this->logError('Queue url for'. $this->getQueueNameWithPrefix() .' not fetched');
            }

            if ($this->queueUrl !== null) {
                $this->getCache()->put($this->queueUrl, $this->getUrlCacheKey());
                $this->logDebug('Queue url '. $this->queueUrl .' fetched and cached');
                return true;
            }
        } catch (AwsException $e) {
            $this->logWarning('Fetching queue url for '. $this->getQueueNameWithPrefix() .' failed. MSG: '. $e->getAwsErrorMessage());
        }

        return false;
    }

    /**
     * @return string
     */
    private function getUrlCacheKey()
    {
        return $this->getQueueNameWithPrefix() .'_url';
    }

    /**
     * SQS can return max 10 messages at once.
     *
     * @return int
     */
    public function getNumberOfTasksToReceive()
    {
        return parent::getNumberOfTasksToReceive() > 10 ? 10 : parent::getNumberOfTasksToReceive();
    }
}
