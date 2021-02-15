<?php

namespace Phpforce\SoapClient\Soap;

/**
 * SOAP client used for the Salesforce API client
 *
 */
class SoapClient extends \SoapClient
{
    /**
     * SOAP types derived from WSDL
     *
     * @var array
     */
    protected $types;
    protected $rootObject;

    public function setRootObject( $rootObject ){
        $this->rootObject = $rootObject;
    }

    /**
     * Retrieve SOAP types from the WSDL and parse them
     *
     * @return array    Array of types and their properties
     */
    public function getSoapTypes()
    {
        if (null === $this->types) {
            $this->types = [];
            $soapTypes = $this->__getTypes();
            foreach ($soapTypes as $soapType) {
                $properties = array();
                $lines = explode("\n", $soapType);
                if (!preg_match('/struct (.*) {/', $lines[0], $matches)) {
                    continue;
                }
                $typeName = $matches[1];

                foreach (array_slice($lines, 1) as $line) {
                    if ($line == '}') {
                        continue;
                    }
                    preg_match('/\s* (.*) (.*);/', $line, $matches);
                    $properties[$matches[2]] = $matches[1];
                }

                // Since every object extends $rootObject, need to append sObject elements to all native and custom objects
                if ($typeName !== $this->rootObject && array_key_exists($this->rootObject, $this->types)) {
                    $properties = array_merge($properties, $this->types[$this->rootObject]);
                }

                $this->types[$typeName] = $properties;
            }
        }

        return $this->types;
    }

    /**
     * Get a SOAP type’s elements
     *
     * @param string $type Object name
     * @return array       Elements for the type
     */

    /**
     * Get SOAP elements for a complexType
     *
     * @param string $complexType Name of SOAP complexType
     *
     * @return array  Names of elements and their types
     */
    public function getSoapElements($complexType)
    {
        $types = $this->getSoapTypes();
        if (isset($types[$complexType])) {
            return $types[$complexType];
        }
    }

    /**
     * Get a SOAP type’s element
     *
     * @param string $complexType Name of SOAP complexType
     * @param string $element     Name of element belonging to SOAP complexType
     *
     * @return string
     */
    public function getSoapElementType($complexType, $element)
    {
        $elements = $this->getSoapElements($complexType);
        if ($elements && isset($elements[$element])) {
            return $elements[$element];
        }
    }
}