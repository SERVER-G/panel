<?php

namespace Pterodactyl\Repositories\Wings;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class DaemonVersionsRepository extends DaemonRepository
{
    /**
     * Get versions from a folder
     *
     * @param string $folder
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws DaemonConnectionException
     */
    public function getVersions(string $folder): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/versions/list', $this->server->uuid),
                [
                    'json' => [
                        'folder' => $folder,
                    ],
                    'timeout' => 120,
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }

    /**
     * Switch version
     *
     * @param $method
     * @param $folder
     * @param $version
     * @param $filename
     * @param $action
     * @param $deleteFiles
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws DaemonConnectionException
     */
    public function switchVersion($method, $folder, $version, $filename, $action, $deleteFiles): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/versions/switch', $this->server->uuid),
                [
                    'json' => [
                        'method' => $method,
                        'folder' => $folder,
                        'version' => $version,
                        'filename' => $filename,
                        'action' => $action,
                        'deleteFiles' => $deleteFiles,
                    ],
                    'timeout' => 120,
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
