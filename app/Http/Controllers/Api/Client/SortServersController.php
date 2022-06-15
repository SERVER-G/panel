<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Auth;
use Pterodactyl\Http\Controllers\Controller;

class SortServersController extends Controller
{
    public function index(Request $request)
    {
        $sortedServer = Server::where('uuidShort', $request->input('uuid'))->first();

        $values = range($request->input('oldIndex'), $request->input('newIndex'));

        if (($key = array_search($request->input('oldIndex'), $values)) !== false) {
            unset($values[$key]);
        }

        $firstValue = array_values($values);
        
        foreach(Server::where('owner_id', Auth::user()->id)->whereIn('position', $values)->get() as $server) {
            $firstValue = array_values($values);
            if($request->input('oldIndex') <= $request->input('newIndex')) {
                Server::where('id', $server->id)->update([
                    'position' => $server->position - 1,
                ]);
            } else {
                Server::where('id', $server->id)->update([
                    'position' => $server->position + 1,
                ]);
            }
        }

        Server::where('id', $sortedServer->id)->update([
            'position' => $request->input('newIndex'),
        ]);
    }
}
