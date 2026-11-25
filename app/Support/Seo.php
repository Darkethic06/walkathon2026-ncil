<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Per-request SEO metadata.
 *
 * Values are layered: config('seo.defaults') <- config('seo.pages')[route name]
 * <- whatever a controller passed to set(). The result is shared to Inertia as
 * the `seo` prop (HandleInertiaRequests) and rendered server-side in
 * resources/views/partials/seo.blade.php.
 */
class Seo
{
    protected array $overrides = [];

    public function set(array $values): static
    {
        $this->overrides = array_merge($this->overrides, array_filter($values, fn ($v) => $v !== null));

        return $this;
    }

    public function noindex(bool $follow = true): static
    {
        return $this->set(['robots' => $follow ? 'noindex,follow' : 'noindex,nofollow']);
    }

    public function toArray(Request $request): array
    {
        $routeName = $request->route()?->getName();

        $seo = array_merge(
            config('seo.defaults'),
            // Not config("seo.pages.$name"): route names contain dots.
            config('seo.pages')[$routeName] ?? [],
            $this->overrides,
        );

        // The admin panel is never indexable.
        if ($routeName && Str::startsWith($routeName, 'admin.')) {
            $seo['robots'] = 'noindex,nofollow';
        }

        $seo['canonical'] = $this->absolute($seo['canonical'] ?? $request->path());
        $seo['image'] = $this->absolute($seo['image']);
        $seo['description'] = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($seo['description']))), 160, '…');
        $seo['site_name'] = config('seo.site_name');
        $seo['locale'] = config('seo.locale');

        return $seo;
    }

    /**
     * Absolute URL on the canonical host, no query string, no trailing slash
     * (except the homepage, e.g. "https://example.com/").
     */
    protected function absolute(string $pathOrUrl): string
    {
        if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
            return $pathOrUrl;
        }

        $path = trim(strtok($pathOrUrl, '?'), '/');

        return config('seo.base_url').'/'.$path;
    }
}
