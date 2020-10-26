<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Dispatcher;

use LDL\Framework\Base\Traits\IsActiveInterfaceTrait;
use LDL\Framework\Base\Traits\NamespaceInterfaceTrait;
use LDL\Framework\Base\Traits\PriorityInterfaceTrait;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Handler\Exception\ExceptionHandlerInterface;
use LDL\Http\Router\Handler\Exception\ModifiesResponseInterface;
use LDL\Http\Router\Plugin\LDL\Cache\Key\Generator\CacheKeyGeneratorInterface;
use LDL\Http\Router\Router;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class CacheHitExceptionHandler implements ExceptionHandlerInterface, ModifiesResponseInterface
{
    public const NAME = 'ldl.router.cache.exception.handler';

    use IsActiveInterfaceTrait;
    use PriorityInterfaceTrait;

    /**
     * @var AdapterInterface
     */
    private $cacheAdapter;

    /**
     * @var CacheKeyGeneratorInterface
     */
    private $cacheKeyGenerator;

    /**
     * @var array
     */
    private $content;

    public function __construct(
        AdapterInterface $cacheAdapter,
        CacheKeyGeneratorInterface $cacheKeyGenerator
    )
    {
        $this->_tActive = true;
        $this->_tPriority = 1;
        $this->cacheKeyGenerator = $cacheKeyGenerator;

        $this->cacheAdapter = $cacheAdapter;
    }

    public function getName() : string
    {
        return self::NAME;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function handle(
        Router $router,
        \Exception $e,
        ParameterBag $parameters=null
    ): ?int
    {
        if(!$e instanceof CacheHitException){
            return null;
        }

        $router->getResponse()->getHeaderBag()->set('X-Cache-Hit', 1);
        $responseParserRepository = $router->getResponseParserRepository();

        $storageKey = sprintf(
            '%s.%s',
            $responseParserRepository->getSelectedKey(),
            $this->cacheKeyGenerator->generate($router->getCurrentRoute(), $parameters)
        );

        $item = $this->cacheAdapter->getItem($storageKey)->get();

        /**
         * @var \DateTime $expiresAt
         */
        $expiresAt = $item['expires'];
        $this->content = $item['data'];

        $router->getResponse()->setExpires($expiresAt);

        return ResponseInterface::HTTP_CODE_OK;
    }
}