<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Support\Arr;
use Pterodactyl\Models\Server;
use Pterodactyl\Classes\MinecraftQuery;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Classes\Exceptions\MinecraftQueryException;
use Pterodactyl\Repositories\Wings\DaemonCommandRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Http\Requests\Api\Client\Servers\MinecraftPlayerManagerRequest;

class MinecraftPlayerManagerController extends ClientApiController
{
    /**
     * @var DaemonFileRepository
     */
    protected $fileRepository;

    /**
     * @var DaemonCommandRepository
     */
    protected $commandRepository;

    /**
     * MinecraftPlayerManagerController constructor.
     * @param DaemonFileRepository $fileRepository
     * @param DaemonCommandRepository $commandRepository
     */
    public function __construct(DaemonFileRepository $fileRepository, DaemonCommandRepository $commandRepository)
    {
        parent::__construct();

        $this->fileRepository = $fileRepository;
        $this->commandRepository = $commandRepository;
    }

    /**
     * @param MinecraftPlayerManagerRequest $request
     * @param Server $server
     * @return array
     */
    public function index(MinecraftPlayerManagerRequest $request, Server $server)
    {
        // op list
        $ops = $this->readJsonFile($server, '/ops.json');

        // whitelist
        $whitelist = $this->readJsonFile($server, '/whitelist.json');

        // ban list
        $bans = $this->readJsonFile($server, '/banned-players.json');

        // ban ip list
        $banIps = $this->readJsonFile($server, '/banned-ips.json');

        // get player list
        $players = [
            'players' => [],
            'list' => [],
        ];

        $query = null;

        try {
            $query = new MinecraftQuery($server->allocation->ip, $server->allocation->port, 1);
            $serverInfo = $query->Query();

            if ($serverInfo === false) {
                $query->Close();
                $query->Connect();;
                $query->QueryOldPre17();
            }

            $list = $serverInfo['players']['sample'] ?? [];

            foreach ($list as $key => $player) {
                $list[$key]['isOp'] = array_search($player['name'], array_column($ops, 'name')) !== false;
                $list[$key]['isWhitelist'] = array_search($player['name'], array_column($whitelist, 'name')) !== false;
            }

            $players = [
                'players' => Arr::only($serverInfo['players'], ['max', 'online']),
                'list' => $list,
            ];
        } catch (MinecraftQueryException $e) {
            unset($counter);
        } finally {
            if ($query !== null) {
                $query->Close();
            }
        }

        return [
            'success' => true,
            'data' => [
                'players' => $players,
                'ops' => $ops,
                'whitelist' => $whitelist,
                'bans' => $bans,
                'banIps' => $banIps,
            ],
        ];
    }

    /**
     * @param MinecraftPlayerManagerRequest $request
     * @param Server $server
     * @return array
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function runCommand(MinecraftPlayerManagerRequest $request, Server $server)
    {
        $this->validate($request, [
            'command' => 'required',
        ]);

        try {
            $this->commandRepository->setServer($server)->send(trim($request->input('command', '')));
        } catch (DaemonConnectionException $e) {
            throw new DisplayException('Failed to perform the action. Maybe it can caused by the server is not running. Please try again...');
        }

        return [
            'success' => true,
            'data' => [],
        ];
    }

    /**
     * @param Server $server
     * @param $file
     * @return array|mixed
     */
    public function readJsonFile(Server $server, $file)
    {
        try {
            $content = $this->fileRepository->setServer($server)->getContent($file);
        } catch (DaemonConnectionException | FileSizeTooLargeException $e) {
            return [];
        }

        if (!is_array(json_decode($content, true))) {
            return [];
        }

        return json_decode($content, true);
    }
}
