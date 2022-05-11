<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Repositories\Wings\DaemonVersionsRepository;
use Pterodactyl\Services\Servers\StartupModificationService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Http\Requests\Api\Client\Servers\VersionChangerRequest;

class VersionChangerController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonVersionsRepository
     */
    protected $daemonVersionsRepository;

    /**
     * @var \Pterodactyl\Services\Servers\ReinstallServerService
     */
    protected $reinstallServerService;

    /**
     * @var \Pterodactyl\Services\Servers\StartupModificationService
     */
    protected $startupModificationService;

    /**
     * VersionChangerController constructor.
     * @param DaemonVersionsRepository $daemonVersionsRepository
     * @param ReinstallServerService $reinstallServerService
     * @param StartupModificationService $startupModificationService
     */
    public function __construct(DaemonVersionsRepository $daemonVersionsRepository, ReinstallServerService $reinstallServerService, StartupModificationService $startupModificationService)
    {
        parent::__construct();

        $this->daemonVersionsRepository = $daemonVersionsRepository;
        $this->reinstallServerService = $reinstallServerService;
        $this->startupModificationService = $startupModificationService;
    }

    public function index(VersionChangerRequest $request, Server $server): array
    {
        $versions = [];
        $groups = DB::table('version_groups')->orderBy('sort', 'ASC')->get();

        foreach ($groups as $group) {
            if (in_array($server->egg_id, unserialize($group->eggs))) {
                $group->versions = [];

                if ($group->type == 1) {
                    $versions_from_db = DB::table('versions')->where('group_id', '=', $group->id)->orderBy('sort', 'ASC')->get();

                    foreach ($versions_from_db as $version_from_db) {
                        $version_from_db->method = 'database';
                        $version_from_db->current_check = $version_from_db->id;

                        array_push($group->versions, $version_from_db);
                    }
                }

                if (in_array($group->type, [2, 3])) {
                    try {
                        $versions_from_wings = $this->daemonVersionsRepository->setServer($server)->getVersions($group->folder);
                    } catch (DaemonConnectionException $e) {
                        continue;
                    }

                    if (json_decode($versions_from_wings->getBody())->success != true) {
                        continue;
                    }

                    $loaded_versions = json_decode($versions_from_wings->getBody())->versions;

                    if ($group->type == 2) {
                        foreach ($loaded_versions as $loaded_version) {
                            array_push($group->versions, [
                                'method' => 'file',
                                'current_check' => $loaded_version,
                                'name' => str_replace('.jar', '', $loaded_version),
                                'file' => $loaded_version,
                            ]);
                        }
                    }

                    if ($group->type == 3) {
                        foreach ($loaded_versions as $loaded_version) {
                            $loaded_version = DB::table('versions')->where('group_id', '=', $group->id)->where('download_url', '=', $loaded_version)->get();
                            if (count($loaded_version) > 0) {
                                $loaded_version[0]->method = 'database';
                                $loaded_version[0]->current_check = $loaded_version[0]->id;

                                array_push($group->versions, $loaded_version[0]);
                            }
                        }
                    }
                }

                if ($group->type == 4) {
                    foreach (unserialize($group->eggs) as $egg) {
                        $egg = DB::table('eggs')->select(['id', 'name'])->where('id', '=', $egg)->get();
                        $egg[0]->method = 'egg';
                        $egg[0]->current_check = $egg[0]->id;

                        array_push($group->versions, $egg[0]);
                    }
                }

                if (count($group->versions) > 0) {
                    array_push($versions, $group);
                }
            }
        }

        $current_version = [
            'method' => 'none',
            'key' => 0,
        ];

        if (!is_null($server->version)) {
            $current_version = unserialize($server->version);
        }

        return [
            'success' => true,
            'data' => [
                'versions' => $versions,
                'currentVersion' => $current_version,
            ],
        ];
    }

    /**
     * @param VersionChangerRequest $request
     * @param Server $server
     * @return array
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function switch(VersionChangerRequest $request, Server $server): array
    {
        $this->validate($request, [
            'group' => 'required|integer',
            'method' => 'required',
            'key' => 'required',
            'deleteFiles' => 'required|integer',
        ]);

        $group = (int) $request->input('group', 0);
        $method = trim(strip_tags($request->input('method', '')));
        $key = trim($request->input('key', ''));
        $deleteFiles = (int) $request->input('deleteFiles', 0);

        if (!in_array($deleteFiles, [0, 1])) {
            throw new DisplayException('Invalid delete files.');
        }

        if (!in_array($method, ['file', 'database', 'egg'])) {
            throw new DisplayException('Invalid version type. Please try again...');
        }

        if ($method == 'file') {
            $version_group = DB::table('version_groups')->where('id', '=', $group)->get();
            if (count($version_group) < 1) {
                throw new DisplayException('Version group not found. Please try again...');
            }

            if (!in_array($server->egg_id, unserialize($version_group[0]->eggs))) {
                throw new DisplayException('You can\'t use selected version. Please try again...');
            }

            try {
                $this->daemonVersionsRepository->setServer($server)->switchVersion($method, $version_group[0]->folder, $key, '', 'none', $deleteFiles);
            } catch (DaemonConnectionException $e) {
                throw new DisplayException('Failed to change the version. Please try again...');
            }
        }

        if ($method == 'database') {
            $version_group = DB::table('version_groups')->where('id', '=', $group)->get();
            if (count($version_group) < 1) {
                throw new DisplayException('Version group not found. Please try again...');
            }

            $version = DB::table('versions')->where('id', '=', (int) $key)->where('group_id', '=', $group)->get();
            if (count($version) < 1) {
                throw new DisplayException('Version not found. Please try again...');
            }

            if (!in_array($server->egg_id, unserialize($version_group[0]->eggs))) {
                throw new DisplayException('You can\'t use selected version. Please try again...');
            }

            try {
                $this->daemonVersionsRepository->setServer($server)->switchVersion(
                    ($version_group[0]->type == 1 ? 'download' : 'file'),
                    $version_group[0]->folder,
                    $version[0]->download_url,
                    $version[0]->filename,
                    ($version[0]->action == 2 ? 'unzip' : 'none'),
                    $deleteFiles
                );
            } catch (DaemonConnectionException $e) {
                throw new DisplayException('Failed to change the version. Please try again...');
            }
        }

        if ($method == 'egg') {
            $egg = DB::table('eggs')->where('id', '=', (int) $key)->get();
            if (count($egg) < 1) {
                throw new DisplayException('Version not found. Please try again...');
            }

            $version_group = DB::table('version_groups')->where('type', '=', 4)->where('id', '=', $group)->get();
            if (count($version_group) < 1) {
                throw new DisplayException('Version group not found. Please try again...');
            }

            if (!in_array($server->egg_id, unserialize($version_group[0]->eggs))) {
                throw new DisplayException('You can\'t use selected version. Please try again...');
            }

            $this->startupModificationService->setUserLevel(User::USER_LEVEL_ADMIN);

            try {
                $this->startupModificationService->handle($server, [
                    'nest_id' => $egg[0]->nest_id,
                    'egg_id' => $egg[0]->id,
                    'docker_image' => json_decode($egg[0]->docker_images, true)[0],
                    'startup' => $egg[0]->startup,
                ]);
            } catch (\Throwable $e) {
                throw new DisplayException('Failed to change the server version. Please try again...');
            }

            if ($deleteFiles == 1) {
                try {
                    $this->reinstallServerService->handle($server);
                } catch (\Throwable $e) {
                    throw new DisplayException('Failed to delete server files. Please reinstall it manually.');
                }
            }
        }

        $current_version = [
            'method' => $method,
            'key' => $key,
        ];

        DB::table('servers')->where('id', '=', $server->id)->update([
            'version' => serialize($current_version),
        ]);

        return [
            'success' => true,
            'data' => [],
        ];
    }
}
