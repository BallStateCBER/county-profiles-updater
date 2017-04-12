<?php
namespace App\Import;

class Import
{
    public $callback;
    public $categoryIds;
    public $dataGrabber;
    public $defaultYear;
    public $geography;
    public $mapName;
    public $sourceId;
    public $stateId;
    public $type;

    const DEFAULT_YEAR = 2015;
    const DEFAULT_STATE = 18; // Indiana

    /**
     * Import constructor.
     *
     * @param array $params Parameters
     */
    public function __construct($params)
    {
        $defaultValues = [
            'callback' => function ($results) {
                return $results;
            },
            'categoryIds' => [],
            'dataGrabber' => 'AcsUpdater',
            'defaultYear' => static::DEFAULT_YEAR,
            'geography' => 'county',
            'mapName' => null,
            'sourceId' => null,
            'stateId' => static::DEFAULT_STATE,
            'type' => null
        ];

        foreach ($defaultValues as $field => $defaultValue) {
            $this->$field = isset($params[$field]) ? $params[$field] : $defaultValue;
        }
    }
}
