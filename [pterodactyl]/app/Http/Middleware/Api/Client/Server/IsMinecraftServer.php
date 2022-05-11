<?php

namespace Pterodactyl\Http\Middleware\Api\Client\Server;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IsMinecraftServer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $server = $request->route()->parameter('server');
        if(!in_array($server->egg_id, config('egg_features.minecraft.eggs_id')))
            throw new NotFoundHttpException('The requested server is not a minecraft server!');

        return $next($request);
    }
}
