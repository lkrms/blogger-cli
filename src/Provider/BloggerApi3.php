<?php declare(strict_types=1);

namespace Lkrms\Blogger\Provider;

use Psr\Http\Message\RequestInterface;
use Salient\Contract\Container\SingletonInterface;
use Salient\Contract\Core\DateFormatterInterface;
use Salient\Contract\Curler\CurlerInterface;
use Salient\Contract\Http\HttpResponseInterface;
use Salient\Core\DateFormatter;
use Salient\Http\Http;
use Salient\Sync\Http\HttpSyncProvider;
use Salient\Utility\Env;
use Salient\Utility\Json;
use Closure;

final class BloggerApi3 extends HttpSyncProvider implements SingletonInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'Blogger API v3.0';
    }

    /**
     * @inheritDoc
     */
    public function getBackendIdentifier(): array
    {
        return [self::class];
    }

    /**
     * @inheritDoc
     */
    public function getBaseUrl(?string $path = null): string
    {
        return 'https://www.googleapis.com/blogger/v3';
    }

    /**
     * @inheritDoc
     */
    protected function createDateFormatter(): DateFormatterInterface
    {
        return new DateFormatter();
    }

    /**
     * @inheritDoc
     */
    protected function getExpiry(string $path): ?int
    {
        $ttl = Env::getNullableInt('blogger_cache_ttl', 3600) ?? -1;
        return $ttl < 0 ? null : $ttl;
    }

    /**
     * @inheritDoc
     */
    protected function filterCurler(CurlerInterface $curler, string $path): CurlerInterface
    {
        return $curler
            ->withThrowHttpErrors(false)
            ->withPager(new BloggerApi3Pager($this, Env::getNullableInt('blogger_page_size', 50)))
            ->withMiddleware(Closure::fromCallable([$this, 'filterRequest']), __CLASS__);
    }

    /**
     * @param Closure(RequestInterface): HttpResponseInterface $next
     */
    protected function filterRequest(
        RequestInterface $request,
        Closure $next,
        CurlerInterface $curler
    ): HttpResponseInterface {
        $query = ['key' => Env::get('blogger_api_key')];
        $response = $next($request->withUri(
            Http::mergeQuery($request->getUri(), $query)
        ));

        if (
            $response->getStatusCode() >= 400
            && $curler->lastResponseIsJson()
            && ($body = (string) $response->getBody()) !== ''
            && ($error = Json::parseObjectAsArray($body)['error'] ?? null)
        ) {
            /** @var array{code:int,message:string,errors:array<array<string,mixed>>} $error */
            throw new BloggerApi3Exception(
                $error['code'],
                $error['message'],
                $error['errors'],
                $request,
                $response,
            );
        }

        return $response;
    }
}
