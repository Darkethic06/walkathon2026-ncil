<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

/**
 * Start / stop / check the Inertia SSR Node server from a web request.
 *
 * Equivalent to `php artisan inertia:start-ssr`, which just runs
 * `node bootstrap/ssr/ssr.js` in the foreground. A web request can't wait on
 * that (it never returns), so start() launches Node detached in the
 * background and returns once /health answers. Output goes to
 * storage/logs/ssr.log.
 */
class SsrServer
{
    public function url(): string
    {
        return rtrim(config('inertia.ssr.url', 'http://127.0.0.1:13714'), '/');
    }

    public function running(): bool
    {
        try {
            return Http::timeout(2)->get($this->url().'/health')->json('status') === 'OK';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array{ok: bool, message: string} */
    public function start(): array
    {
        if ($this->running()) {
            return ['ok' => true, 'message' => 'SSR server is already running at '.$this->url().'.'];
        }

        $bundle = config('inertia.ssr.bundle', base_path('bootstrap/ssr/ssr.js'));
        if (! is_file($bundle)) {
            return ['ok' => false, 'message' => "SSR bundle not found at {$bundle}. Run `npm run build` (CI does this on deploy)."];
        }

        $node = escapeshellarg(config('inertia.ssr.node_binary', 'node'));
        $script = escapeshellarg($bundle);
        $log = escapeshellarg(storage_path('logs/ssr.log'));

        // setsid/nohup detach Node from this PHP request so it keeps running after the response.
        $cmd = "if command -v setsid >/dev/null 2>&1; then setsid {$node} {$script} >> {$log} 2>&1 < /dev/null & "
             ."else nohup {$node} {$script} >> {$log} 2>&1 < /dev/null & fi";

        try {
            Process::fromShellCommandline($cmd, base_path())->run();
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not launch Node (is proc_open allowed on this host?): '.$e->getMessage()];
        }

        for ($i = 0; $i < 20; $i++) { // wait up to ~5s for it to come up
            usleep(250_000);
            if ($this->running()) {
                return ['ok' => true, 'message' => 'SSR server started at '.$this->url().'. Logs: storage/logs/ssr.log'];
            }
        }

        return ['ok' => false, 'message' => 'Node was launched but /health did not answer within 5s. Check storage/logs/ssr.log '
            .'(common causes: wrong NODE_BINARY path, Node < 18, port already in use, or the host kills background processes).'];
    }

    /** @return array{ok: bool, message: string} */
    public function stop(): array
    {
        if (! $this->running()) {
            return ['ok' => true, 'message' => 'SSR server is not running.'];
        }

        try {
            Http::timeout(2)->get($this->url().'/shutdown'); // the server exits without replying
        } catch (\Throwable $e) {
            // expected: connection closes as the process exits
        }

        usleep(500_000);

        return $this->running()
            ? ['ok' => false, 'message' => 'SSR server did not stop.']
            : ['ok' => true, 'message' => 'SSR server stopped.'];
    }

    /** @return array{ok: bool, message: string} */
    public function restart(): array
    {
        $stop = $this->stop();

        return $stop['ok'] ? $this->start() : $stop;
    }

    /** @return array{ok: bool, message: string} */
    public function status(): array
    {
        $running = $this->running();

        return [
            'ok' => $running,
            'message' => ($running ? 'Running' : 'Not running').' at '.$this->url()
                .' · INERTIA_SSR_ENABLED='.(config('inertia.ssr.enabled') ? 'true' : 'false'),
        ];
    }
}
