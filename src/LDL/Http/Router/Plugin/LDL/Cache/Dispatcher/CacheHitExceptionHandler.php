<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Dispatcher;

use LDL\Framework\Base\Traits\IsActiveInterfaceTrait;
use LDL\Framework\Base\Traits\NamespaceInterfaceTrait;
use LDL\Framework\Base\Traits\PriorityInterfaceTrait;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Handler\Exception\ExceptionHandlerInterface;
use LDL\Http\Router\Handler\Exception\ModifiesResponseInterface;
use LDL\Http\Router\Router;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class CacheHitExceptionHandler implements ExceptionHandlerInterface, ModifiesResponseInterface
{
    use NamespaceInterfaceTrait;
    use IsActiveInterfaceTrait;
    use PriorityInterfaceTrait;

    /**
     * @var AdapterInterface
     */
    private $cacheAdapter;

    /**
     * @var array
     */
    private $content;

    public function __construct(
        AdapterInterface $cacheAdapter
    )
    {
        $this->_tNamespace = 'LDLCachePlugin';
        $this->_tName = 'Cache exception handler';
        $this->_tActive = true;
        $this->_tPriority = 9999;

        $this->cacheAdapter = $cacheAdapter;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function handle(Router $router, \Exception $e, string $context): ?int
    {
        if(!$e instanceof CacheHitException){
            return null;
        }

        $responseParserRepository = $router->getResponseParserRepository();

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
        $this->content = $item['data'];

        $router->getResponse()->setExpires($expiresAt);

        return ResponseInterface::HTTP_CODE_OK;
    }
}