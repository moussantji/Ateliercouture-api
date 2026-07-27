<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use League\Glide\ServerFactory;

class ImageController extends Controller
{
    public function show(Request $request, string $path)
    {
        $server = ServerFactory::create([
            'source' => storage_path('app/public'),
            'cache' => storage_path('app/glide-cache'),
            'cache_path_prefix' => '.glide',
            'base_url' => 'img',
        ]);

        $params = $request->all();

        if (empty($params['w']) && empty($params['h'])) {
            $params['w'] = 800;
            $params['h'] = 800;
            $params['fit'] = 'crop';
        }

        try {
            $cachedPath = $server->makeImage($path, $params);
            $cache = $server->getCache();
            $mimeType = $cache->mimeType($cachedPath);
            $stream = $cache->readStream($cachedPath);

            return response()->stream(function () use ($stream) {
                if (ftell($stream) !== 0) {
                    rewind($stream);
                }
                fpassthru($stream);
                fclose($stream);
            }, 200, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'max-age=31536000, public',
            ]);
        } catch (\Exception $e) {
            abort(404, 'Image introuvable');
        }
    }
}
