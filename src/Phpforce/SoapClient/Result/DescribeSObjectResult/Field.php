<?php

namespace Phpforce\SoapClient\Result\DescribeSObjectResult;

class Field
{
    
/**
 * added for compatibility v48
 */
    public $aggregatable;
    public $aiPredictionField;
    public $permissionable;
    public $polymorphicForeignKey;
    public $queryByDistance;
    public $searchPrefilterable;
    public $externalId;

    public $autoNumber;
    public $byteLength;
    public $calculated;
    public $caseSensitive;
    public $createable;
    public $custom;
    public $defaultedOnCreate;
    public $dependentPicklist;
    public $deprecatedAndHidden;
    public $digits;
    public $filterable;
    public $groupable;
    public $htmlFormatted;
    public $idLookup;
    public $inlineHelpText;
    public $label;
    public $length;
    public $name;
    public $nameField;
    public $namePointing;
    public $nillable;
    public $picklistValues;
    public $precision;
    public $relationshipName;
    public $relationshipOrder;
    public $referenceTo = array();
    public $restrictedPicklist;
    public $scale;
    public $soapType;
    public $sortable;
    public $type;
    public $unique;
    public $updateable;
    public $writeRequiresMasterRead;

    /**
     * Get whether this field references a certain object
     *
     * @param string $object     Name of the referenced object
     * @return boolean
     */
    public function references($object)
    {
        foreach ($this->referenceTo as $referencedObject) {
            return $object == $referencedObject;
        }

        return false;
    }
}