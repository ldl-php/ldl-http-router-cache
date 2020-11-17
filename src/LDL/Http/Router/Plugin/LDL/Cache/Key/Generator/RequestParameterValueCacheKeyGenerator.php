<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Key\Generator;

use LDL\Http\Router\Router;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestParameterValueCacheKeyGenerator extends AbstractCacheKeyGenerator
{
    /**
     * @var string
     */
    private $key;

    /**
     * Generates a cache key which contains the name and *values* of the request parameters
     *
     * SECURITY NOTE: Be careful when using this cache key generator on requests which contain sensitive data,
     * make sure your cache storage is properly protected from third parties.
     *
     * @param Router $router
     * @param ParameterBag|null $urlParameters
     *
     * @throws \RuntimeException if no parameters have been established to make the cache key
     *
     * @return string
     */
    public function generate(
        Router $router,
        ParameterBag $urlParameters=null
    ) : string
    {
        if(null !== $this->key){
            return $this->key;
        }

        $request = $router->getRequest();

        $defaults = [
            'maxLength' => 5000,
            'debug' => false,
            'headers' => [
                'list' => 'none',
                'casing' => [
                    'key' => null,
                    'value' => null
                ]
            ],
            'cookies' => [
                'list' => 'none',
                'casing' => [
                    'key' => null,
                    'value' => null
                ]
            ],
            'get' => [
                'list' => 'none',
                'casing' => [
                    'key' => null,
                    'value' => null
                ]
            ],
            'post' => [
                'list' => 'none',
                'casing' => [
                    'key' => null,
                    'value' => null
                ]
            ],
            'urlParams' => [
                'list' => 'none',
                'casing' => [
                    'key' => null,
                    'value' => null
                ]
            ],
            'body' => [
                'active' => false,
                'casing' => null
            ]
        ];

        $merge = array_replace_recursive($defaults, $this->getOptions());

        $headers = $this->parseValues($merge['headers'], $request->getHeaderBag()->getIterator());

        $params = [];

        if(false !== $headers){
            $params['headers'] = $headers;
        }

        $get = $this->parseValues($merge['get'], $request->getQuery()->getIterator());

        if(false !== $get){
            $params['get'] = $get;
        }

        $post = $this->parseValues($merge['post'], $request->request->getIterator());

        if(false !== $post){
            $params['post'] = $post;
        }

        $urlParams = $this->parseValues($merge['urlParams'], $urlParameters->getIterator());

        if(false !== $urlParams){
            $params['urlParams'] = $urlParams;
        }

        $cookies = $this->parseValues($merge['cookies'], $request->cookies->getIterator());

        if(false !== $cookies){
            $params['cookies'] = $cookies;
        }

        if(true === $merge['body']['active']){
            $params['body'] = $request->getContent();

            if(null !== $merge['body']['casing']){
                $params['body'] = 'lower' === $merge['body']['casing'] ? mb_strtolower($params['body']) : mb_strtoupper($params['body']);
            }
        }

        if(empty($params)){
            $msg = 'Please add parameters to use as a cache key in your route';
            throw new \RuntimeException($msg);
        }

        $key = preg_replace(
            '#[{}\(\)\\\/@:]#',
            '_',
            json_encode($params, \JSON_THROW_ON_ERROR)
        );

        if($merge['maxLength'] > 0 && mb_strlen($key) > $merge['maxLength']){
            $key = mb_substr($key, 0, (int) $merge['maxLength']);
        }

        if(true === $merge['debug']){
            $router->getResponse()
                ->getHeaderBag()
                ->add(['X-Cache-Key' => $key]);
        }

        $this->key = $key;

        return $this->key;
    }

    //<editor-fold desc="Private methods">
    /**
     * @param array $section
     * @param \Iterator $values
     * @return array|bool|\Iterator
     */
    private function parseValues(array $section, \Iterator $values)
    {
        if(is_string($section['list']) && 'none' === $section['list']) {
            return false;
        }

        $values = \iterator_to_array($values);

        $this->transformCase(
            $values,
            $section['casing']['key'],
            $section['casing']['value']
        );

        if(is_string($section['list']) && 'all' === $section['list']){
            return $values;
        }

        if(!is_array($section['list'])){
            return false;
        }

        if(null !== $section['casing']['key']){
            $section['list'] = array_map(
                'lower' === $section['casing']['key'] ? 'mb_strtolower' : 'mb_strtoupper',
                $section['list']
            );
        }

        $list = array_flip($section['list']);

        foreach($values as $key => $value){
            if(null !== $section['casing']['key']){
                $key = 'lower' === $section['casing']['key'] ? mb_strtolower($key) : mb_strtoupper($key);
            }

            if(false === array_key_exists($key, $list)){
                unset($values[$key]);
            }
        }

        $this->transformCase(
            $values,
            $section['casing']['key'],
            $section['casing']['value']
        );

        return $values;
    }

    private function transformCase(
        array &$array,
        string $keyCase=null,
        string $valueCase=null
    ) : void
    {
        if(null === $keyCase && null === $valueCase){
            return;
        }

        foreach($array as $key => $value){
            unset($array[$key]);

            if(null !== $keyCase){
                $key = 'lower' === $keyCase ? mb_strtolower((string)$key) : mb_strtoupper((string)$key);
            }

            if(null !== $valueCase){
                if(is_array($value)) {
                    $this->transformCase($value, $keyCase, $valueCase);
                }else{
                    $value = 'lower' === $valueCase ? mb_strtolower((string)$value) : mb_strtoupper((string)$value);
                }
            }

            $array[$key] = $value;
        }

    }
    //</editor-fold>

}