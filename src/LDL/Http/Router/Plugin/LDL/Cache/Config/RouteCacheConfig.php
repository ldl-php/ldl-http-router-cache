<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Config;

class RouteCacheConfig
{
    /**
     * @var \DateInterval
     */
    private $expiresAt;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var bool
     */
    private $purgeable;

    /**
     * @var string
     */
    private $keyGenerator;

    /**
     * @var bool
     */
    private $enabled = true;

    public static function fromArray(array $data) : self
    {
        $merge = array_merge(get_class_vars(__CLASS__), $data);

        return new static(
            (bool) $merge['purgeable'],
            (bool) $merge['enabled'],
            $merge['keyGenerator'],
            $merge['expiresAt'],
            $merge['secretKey']
        );
    }

    public function __construct(
        bool $purgeable,
        bool $enabled,
        ?string $keyGenerator,
        ?string $expiresAt,
        ?string $secretKey
    )
    {
        if(null !== $expiresAt){
            $expiresAt = \DateInterval::createFromDateString($expiresAt);
        }

        $this->setEnabled($enabled)
            ->setExpiresAt($expiresAt)
            ->setSecretKey($secretKey)
            ->setPurgeable($purgeable)
            ->setKeyGenerator($keyGenerator);
    }

    /**
     * @return \DateInterval
     */
    public function getExpiresAt() : ?\DateInterval
    {
        return $this->expiresAt;
    }

    /**
     * @return string
     */
    public function getSecretKey() : ?string
    {
        return $this->secretKey;
    }

    /**
     * @return bool
     */
    public function isPurgeable() : bool
    {
        return $this->purgeable;
    }

    /**
     * @return string|null
     */
    public function getKeyGenerator() : ?string
    {
        return $this->keyGenerator;
    }

    /**
     * @return bool
     */
    public function isEnabled() : bool
    {
        return $this->enabled;
    }

    //<editor-fold desc="Private methods">
    /**
     * @param bool $enabled
     * @return RouteCacheConfig
     */
    private function setEnabled(bool $enabled) : self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @param bool $purgeable
     * @return RouteCacheConfig
     */
    private function setPurgeable(bool $purgeable) : self
    {
        $this->purgeable = $purgeable;
        return $this;
    }

    /**
     * @param \DateInterval $interval
     * @return RouteCacheConfig
     */
    private function setExpiresAt(\DateInterval $interval) : self
    {
        $this->expiresAt = $interval;
        return $this;
    }

    /**
     * @param string|null $key
     * @return RouteCacheConfig
     */
    private function setSecretKey(string $key=null) : self
    {
        $this->secretKey = $key;
        return $this;
    }

    /**
     * @param string|null $keyGenerator
     * @return RouteCacheConfig
     */
    private function setKeyGenerator(?string $keyGenerator) : self
    {
        $this->keyGenerator = $keyGenerator;
        return $this;
    }
    //</editor-fold>
}