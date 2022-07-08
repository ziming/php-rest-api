<?php

namespace MessageBird\Resources;

use GuzzleHttp\ClientInterface;
use JsonMapper;
use MessageBird\Objects;

/**
 * Class EmailMessage
 *
 * @package MessageBird\Resources
 */
class EmailMessage extends Base
{
    /**
     * @param ClientInterface $httpClient
     * @param JsonMapper $jsonMapper
     */
    public function __construct(ClientInterface $httpClient, JsonMapper $jsonMapper)
    {
        parent::__construct($httpClient, $jsonMapper, 'verify/messages/email');
    }

    /**
     * @return string
     */
    protected function responseClass(): string
    {
        return Objects\EmailMessage::class;
    }
}
