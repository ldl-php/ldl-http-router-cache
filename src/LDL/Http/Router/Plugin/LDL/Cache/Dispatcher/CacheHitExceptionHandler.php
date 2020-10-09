<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Dispatcher;

use LDL\Framework\Base\Traits\IsActiveInterfaceTrait;
use LDL\Framework\Base\Traits\NamespaceInterfaceTrait;
use LDL\Framework\Base\Traits\PriorityInterfaceTrait;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Handler\Exception\ExceptionHandlerInterface;
use LDL\Http\Router\Plugin\LDL\Cache\Config\RouteCacheConfig;
use LDL\Http\Router\Response\Parser\Repository\ResponseParserRepositoryInterface;
use LDL\Http\Router\Response\Parser\ResponseParserInterface;
use LDL\Http\Router\Router;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class CacheHitExceptionHandler implements ExceptionHandlerInterface
{
    use NamespaceInterfaceTrait;
    use IsActiveInterfaceTrait;
    use PriorityInterfaceTrait;

    /**
     * @var AdapterInterface
     */
    private $cacheAdapter;

    /**
     * @var RouteCacheConfig
     */
    private $cacheConfig;

    public function __construct(
        AdapterInterface $cacheAdapter,
        RouteCacheConfig $config
    )
    {
        $this->_tNamespace = 'LDLCachePlugin';
        $this->_tName = 'Cache exception handler';
        $this->_tActive = true;
        $this->_tPriority = 9999;

        $this->cacheAdapter = $cacheAdapter;
    }

    public function handle(Router $router, \Exception $e, string $context): ?int
    {
        if(!$e instanceof CacheHitException){
            return null;
        }

        $responseParserRepository = $router->getResponseParserRepository();
        /**
         * @var ResponseParserInterface $responseParser
         */
        $responseParser = $responseParserRepository->getSelectedItem();

        $route = $router->getCurrentRoute();

        $dispatcher = $router->getCurrentRoute()
            ->getConfig()
            ->getDispatcher();

        $key = sprintf(
            '%s.%s',
            $dispatcher->getCacheKey($route, $router->getRequest(), $router->getResponse()),
            $responseParserRepository->getSelectedKey()
        );

        $item = $this->cacheAdapter->getItem($key)->get();
        /**
         * @var \DateTime $expiresAt
         */
        $expiresAt = $item['expires'];

        $router->getResponse()->setContent($responseParser->parse($item['data'], 'cache', $router));
        $router->getResponse()->setExpires($expiresAt);
        $router->getResponse()->setStatusCode(ResponseInterface::HTTP_CODE_NOT_MODIFIED);

        return null;
    }
}