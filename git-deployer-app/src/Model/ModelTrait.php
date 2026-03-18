<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Model;

use stdClass;

trait ModelTrait
{
    /**
     * @param stdClass|mixed[] $in
    */
    public function __construct(stdClass|array $in = [])
    {
        $this->populate($in, $this->getDefaults());
    }

    /** @return mixed[] */
    public function toArray(): array
    {
        $vars = \get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (\is_object($value) && \method_exists($value, 'toArray')) {
                $vars[$key] = $value->toArray();
            }
        }

        return $vars;
    }

    public function toStdClass(): stdClass
    {
        return (object) $this->toArray();
    }

    /**
     * @param stdClass|mixed[] $in
     * @param array<string, mixed> $defaults
    */
    protected function populate(stdClass|array $in, array $defaults = []): void
    {
        if ($in instanceof stdClass) {
            $in = \get_object_vars($in);
        }

        // overwrite and set provided values
        $set = [];
        foreach ($in as $key => $value) {
            $set[$key] = true;
            if (\property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        // set default value if not provided
        foreach ($defaults as $key => $value) {
            if (\property_exists($this, $key) && !isset($set[$key])) {
                $this->{$key} = $value;
            }
        }
    }

    /** @return array<string, mixed> */
    protected function getDefaults(): array
    {
        return [];
    }
}
