<?php declare(strict_types=1);

namespace Lkrms\Blogger\Provider;

use Psr\Http\Message\RequestInterface;
use Salient\Contract\Curler\Exception\HttpErrorExceptionInterface;
use Salient\Contract\Http\HttpResponseInterface;
use Salient\Curler\Exception\AbstractResponseException;

final class BloggerApi3Exception extends AbstractResponseException implements HttpErrorExceptionInterface
{
    /**
     * @param array<array<string,mixed>> $errors
     */
    public function __construct(
        int $code,
        string $message,
        array $errors,
        RequestInterface $request,
        HttpResponseInterface $response
    ) {
        parent::__construct($message, $request, $response, [
            'code' => $code,
            'errors' => $errors,
        ]);
    }
}
