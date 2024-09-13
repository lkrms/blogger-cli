<?php declare(strict_types=1);

namespace Lkrms\Blogger\Provider;

use Psr\Http\Message\RequestInterface;
use Salient\Contract\Curler\CurlerInterface;
use Salient\Contract\Curler\CurlerPageInterface;
use Salient\Contract\Curler\CurlerPagerInterface;
use Salient\Contract\Http\HttpResponseInterface;
use Salient\Curler\CurlerPage;
use Salient\Curler\CurlerPageRequest;
use UnexpectedValueException;

final class BloggerApi3Pager implements CurlerPagerInterface
{
    private BloggerApi3 $Provider;
    private ?int $PageSize;

    public function __construct(BloggerApi3 $provider, ?int $pageSize)
    {
        $this->Provider = $provider;
        $this->PageSize = $pageSize;
    }

    /**
     * @inheritDoc
     */
    public function getFirstRequest(
        RequestInterface $request,
        CurlerInterface $curler,
        ?array $query = null
    ) {
        if (
            $this->PageSize !== null
            && (!$query || !array_key_exists('maxResults', $query))
        ) {
            $query['maxResults'] = $this->PageSize;
            $request = $request->withUri(
                $curler->replaceQuery($request->getUri(), $query)
            );
        }

        return new CurlerPageRequest($request, $query);
    }

    /**
     * @inheritDoc
     */
    public function getPage(
        $data,
        RequestInterface $request,
        HttpResponseInterface $response,
        CurlerInterface $curler,
        ?CurlerPageInterface $previousPage = null,
        ?array $query = null
    ): CurlerPageInterface {
        if (!is_array($data) || !isset($data['items'])) {
            throw new UnexpectedValueException(sprintf(
                '%s did not return a list of items',
                substr((string) $curler->getUri(), strlen($this->Provider->getBaseUrl())),
            ));
        }

        if (isset($data['nextPageToken'])) {
            $query['pageToken'] = $data['nextPageToken'];
            $nextRequest = $request->withUri(
                $curler->replaceQuery($request->getUri(), $query)
            );
        }

        return new CurlerPage($data['items'], $nextRequest ?? null, $query);
    }
}
