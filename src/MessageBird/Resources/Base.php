<?php

namespace MessageBird\Resources;

use GuzzleHttp\ClientInterface;
use JsonMapper;
use MessageBird\Common\HttpClient;
use MessageBird\Exceptions;
use MessageBird\Objects;
use MessageBird\Objects\Arrayable;
use MessageBird\Objects\BaseList;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Base
 *
 * @package MessageBird\Resources
 *
 * @method BaseList list(array $params = [])
 * @method Base read(string $id, array $params = [])
 * @method Base delete(string $id)
 * @method Base create(Arrayable $params, array $query = [])
 * @method Base update(string $id, Arrayable $params)
 */
abstract class Base
{
    /**
     * @var ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * @var string The resource name as it is known at the server. Uses to build request uri.
     */
    private string $resourceName;

    /**
     * @param ClientInterface $httpClient
     * @param string $resourceName
     */
    public function __construct(ClientInterface $httpClient, string $resourceName)
    {
        $this->httpClient = $httpClient;
        $this->resourceName = $resourceName;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return Objects\Base|Objects\BaseList
     * @throws Exceptions\ServerException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonMapper_Exception
     */
    public function __call(string $name, array $arguments)
    {
        if ($name == 'read') {
            return $this->readBasic(...$arguments);
        }
        if ($name == 'list') {
            return $this->listBasic(...$arguments);
        }
        if ($name == 'delete') {
            return $this->deleteBasic(...$arguments);
        }
        if ($name == 'create') {
            return $this->createBasic(...$arguments);
        }
        if ($name == 'update') {
            return $this->updateBasic(...$arguments);
        }
    }

    /**
     * @param string $id
     * @param array $params
     * @return Objects\Base
     * @throws Exceptions\ServerException|\GuzzleHttp\Exception\GuzzleException
     * @throws \JsonMapper_Exception
     */
    protected function readBasic(string $id, array $params = []): Objects\Base
    {
        $uri = $this->resourceName . '/' . $id . '?' . http_build_query($params);
        $response = $this->httpClient->request(HttpClient::REQUEST_GET, $uri);

        return $this->handleCreateResponse($response);
    }

    /**
     * @param Arrayable $params
     * @param array $query
     *
     * @return Objects\Balance|Objects\Conversation\Conversation|Objects\Hlr|Objects\Lookup|Objects\MessageResponse|Objects\Verify|Objects\VoiceMessage|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonMapper_Exception
     */
    public function createBasic(Arrayable $params, array $query = []): Objects\Base
    {
        if (empty($query)) {
            $uri = $this->resourceName;
        } else {
            $uri = $this->resourceName . '?' . http_build_query($query);
        }

        $response = $this->httpClient->request(HttpClient::REQUEST_POST, $uri, [
            'body' => $params->toArray()
        ]);

        return $this->handleCreateResponse($response);
    }

    /**
     * @param string $id
     * @param Arrayable $params
     *
     * @return Objects\Base
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonMapper_Exception
     */
    public function updateBasic(string $id, Arrayable $params): Objects\Base
    {
        $uri = $this->resourceName . '/' . $id;
        $response = $this->httpClient->request(HttpClient::REQUEST_PUT, $uri, [
            'body' => $params->toArray()
        ]);

        return $this->handleCreateResponse($response);
    }

    /**
     * @param array $params
     * @return BaseList
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function listBasic(array $params = []): BaseList
    {
        $uri = $this->resourceName . '?' . http_build_query($params);
        $response = $this->httpClient->request(HttpClient::REQUEST_GET, $uri);

        return $this->handleListResponse($response);
    }

    /**
     * @param string $id
     * @return Objects\Base
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonMapper_Exception
     */
    public function deleteBasic(string $id): Objects\Base
    {
        $uri = $this->resourceName . '/' . $id;
        $response = $this->httpClient->request(HttpClient::REQUEST_DELETE, $uri);

        return $this->handleDeleteResponse($response);
    }

    /**
     * @param ResponseInterface $response
     * @return Objects\Base
     * @throws \JsonMapper_Exception
     *
     * @todo rename to handleNoContentResponse
     */
    protected function handleDeleteResponse(ResponseInterface $response): Objects\Base
    {
        if ($response->getStatusCode() === HttpClient::HTTP_NO_CONTENT) {
            return new Objects\DeleteResponse();
        }

        return $this->handleCreateResponse($response);
    }

    /**
     * Transform response to specified response object.
     *
     * @param ResponseInterface $response
     * @return Objects\Base
     * @throws \JsonMapper_Exception
     *
     * * @todo rename to handleNoContentResponse
     */
    protected function handleCreateResponse(ResponseInterface $response): Objects\Base
    {
        $responseArray = json_decode($response->getBody(), true);

        $mapper = new JsonMapper();
        $mapper->bEnforceMapType = false;
        return $mapper->map($responseArray, new ($this->responseClass()));
    }

    /**
     * @param ResponseInterface $response
     * @return BaseList
     */
    protected function handleListResponse(ResponseInterface $response): BaseList
    {
        $responseArray = json_decode($response->getBody(), true);

        $list = new BaseList();
        $list->limit = $responseArray['limit'] ?? 0;
        $list->offset = $responseArray['offset'] ?? 0;
        $list->count = $responseArray['count'] ?? 0;
        $list->totalCount = $responseArray['totalCount'] ?? 0;
        $list->links = $responseArray['links'] ?? [];

        $list->items = [];

        foreach ($responseArray['items'] as $item) {
            $responseObject = new ($this->responseClass());

            foreach ($item as $key => $value) {
                $responseObject->$key = $value;
            }

            $list->items[] = $responseObject;
        }


        return $list;
    }

    /**
     * Should return class name of response object.
     * Example: MessageResponse::class
     *
     * @return string
     */
    abstract protected function responseClass(): string;

    /**
     * @return string
     */
    protected function getResourceName(): string
    {
        return $this->resourceName;
    }
}
