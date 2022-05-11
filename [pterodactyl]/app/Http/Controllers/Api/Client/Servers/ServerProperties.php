<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;

class ServerProperties extends ClientApiController
{
    public DaemonFileRepository $repository;

    public function __construct(
        DaemonFileRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function index(GetServerRequest $request, Server $server)
    {
        $data = [];
        try {
            $properties = $this->repository->setServer($server)->getContent('server.properties');
            $properties = explode("\n", $properties);

            foreach ($properties as $var) {
                if (
                    empty($var) ||
                    (substr($var, 0, 1) === '#') ||
                    (strpos($var, '=') === false)
                ) {
                    continue;
                }

                $variable = explode('=', $var);
                array_push($data, [
                    'envVariable' => $variable[0],
                    'serverValue' => $variable[1] ?? '',
                ]);
            }
        } catch (\Exception $error) {
            $properties = [];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    public function update(GetServerRequest $request, Server $server)
    {
        $data = [];

        //! Validating fields.
        $this->validate($request, [
            'key' => 'required|string|between:1,64',
            'value' => 'required|string|between:1,191'
        ]);
        
        try {
            $this->repository->setServer($server);

            $properties = $this->repository->getContent('server.properties');
            $properties = explode("\n", $properties);
            
            foreach($properties as $var) {
                if (
                    empty($var) ||
                    (substr($var, 0, 1) === '#') ||
                    (strpos($var, '=') === false)
                ) {
                    continue;
                }

                $variable = explode('=', $var);
                if($variable[0] == $request->input('key'))
                    $variable[1] = $request->input('value');
                
                $var = implode("=", $variable);
                array_push($data, $var);
            }

            $file = implode("\n", $data);
            $this->repository->putContent('server.properties', $file);
        } catch (\Exception $error) {
            $properties = [];
        }

        return [
            'success' => true,
        ];
    }
}
