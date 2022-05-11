<?php
// Buyer Username: MG8853
// Buyer ID: 2276
// Resource Version: 1.1
// Resource Name: Pterodactyl Addon [1.X] - Enhanced ServerDetails
// Transaction ID: 3NL784753D1183235

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HastebinController extends ClientApiController
{
    public function sendToHastebin(Request $request)
    {           
            $hastebinPost = Http::withHeaders(['Content-Type' => 'text/plain'])->send('POST', 'https://www.toptal.com/developers/hastebin/documents', [
                'body' => $request['logs']
            ])->json();
            return $hastebinPost; 
    }
}