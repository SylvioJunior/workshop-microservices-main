<?php

declare(strict_types=1);

namespace App\Utils\Dto;

use Core\Base\Dto;

/**
 * Class FilterIdDto
 *
 * This class represents a Data Transfer Object for filtering by ID.
 */
class FilterIdDto extends Dto
{
    public ?FilterIdDataDto $id;
    public ?array $ids;

    /**
     * @CustomValidation\getIdTypeAndValue()
     */
    public $filter;

    /**
     * @Validation\list(msg="This field must be a list.")
     * @CustomValidation\getIdTypeAndValues()
     */
    public $filters;

    /**
     * Get the filter data.
     *
     * @return array
     */
    public function getFilterData(): array
    {
        if ($this->id === null) {
            return [];
        }

        return [
            'key' => $this->id->key ?? null,
            'keyType' => $this->id->keyType ?? null
        ];
    }

    /**
     * Get the filters data.
     *
     * @return array
     */
    public function getFiltersData(): array
    {
        return $this->ids;
    }

    /**
     * Validate and set the ID type and value.
     *
     * @param array $options
     * @return string|bool
     */
    public function getIdTypeAndValue(array $options)
    {

        if (!is_array($options['keyTypes'] ?? null)) {
            return "The identifier key types were not provided";
        }

        $keyTypes = $options['keyTypes'];

        if (is_array($options['condition'] ?? null)) {

            foreach ($options['condition'] as $keyType => $key) {

                if (in_array($keyType, $keyTypes)) {

                    $this->id = new FilterIdDataDto([
                        'key' => $key,
                        'keyType' => $keyType
                    ]);

                    return true;
                }
            }
        }

        return "The identifier (" . implode(", ", $keyTypes) . ") was not provided.";
    }

    /**
     * Validate and set multiple ID types and values.
     *
     * @param array $options
     * @return bool
     */
    public function getIdTypeAndValues(array|null $options): bool
    {

        $this->ids = [];

        if (!is_array($options)) {
            return true;
        }

        foreach ($options as $option) {
            $this->ids[] = $this->getIdTypeAndValue($option);
        }

        return true;
    }
}

/**
 * Class FilterIdDataDto
 *
 * This class represents the data structure for a single filter ID.
 */
class FilterIdDataDto extends Dto
{
    public ?string $key;
    public ?string $keyType;
}
