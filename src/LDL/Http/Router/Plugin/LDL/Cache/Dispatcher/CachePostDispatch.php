<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Dispatcher;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Middleware\AbstractMiddleware;
use LDL\Http\Router\Plugin\LDL\Cache\Config\RouteCacheConfig;
use LDL\Http\Router\Plugin\LDL\Cache\Key\Generator\CacheKeyGeneratorInterface;
use LDL\Http\Router\Response\Formatter\ResponseFormatterInterface;
use LDL\Http\Router\Response\Parser\ResponseParserInterface;
use LDL\Http\Router\Router;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheAdapterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class CachePostDispatch extends AbstractMiddleware
{
    /**
     * @var CacheAdapterInterface
     */
    private $cacheAdapter;

    /**
     * @var RouteCacheConfig
     */
    private $cacheConfig;

    /**
     * @var CacheKeyGeneratorInterface
     */
    private $cacheKeyGenerator;

    public function init(
        CacheAdapterInterface $cacheAdapter,
        RouteCacheConfig $cacheConfig,
        CacheKeyGeneratorInterface $keyGenerator
    ) : self
    {
        $this->cacheAdapter = $cacheAdapter;
        $this->cacheConfig = $cacheConfig;
        $this->cacheKeyGenerator = $keyGenerator;

        return $this;
    }

    public function _dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        Router $router = null,
        ParameterBag $urlParameters = null
    ) : ?array
    {
        $response->getHeaderBag()->set('X-Cache-Hit',0);

        $storageKey = sprintf(
            '%s.%s',
            $router->getResponseParserRepository()->getSelectedKey(),
            $this->cacheKeyGenerator->generate($router, $urlParameters)
        );

        $item = $this->cacheAdapter->getItem($storageKey);

        $expires = 0;

        if($this->cacheConfig->getExpiresAt()){
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $expires = $now->add($this->cacheConfig->getExpiresAt());
            $item->expiresAfter($this->cacheConfig->getExpiresAt());
            $response->setExpires($expires);
        }

        /**
         * @var ResponseFormatterInterface $formatter
         */
        $formatter = $router->getResponseFormatterRepository()->getSelectedItem();

        $formatter->format($router, $router->getDispatcher()->getResult());

        /**
         * @var ResponseParserInterface $parser
         */
        $parser = $router->getResponseParserRepository()->getSelectedItem();

        $parser->parse($formatter->getResult(), $router);

        $encode = [
            'expires' => $expires,
            'data' => [
                'body'    => $parser->getResult(),
                'headers' => json_encode(
                    \iterator_to_array($response->getHeaderBag()->getIterator()),
                    \JSON_THROW_ON_ERROR
                )
            ]
        ];

        $item->set($encode);

        $this->cacheAdapter->save($item);
        $this->cacheAdapter->commit();

        return null;
    }

}