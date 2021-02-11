<?php

namespace Phpforce\SoapClient\Request;

class DeployOptions
{

    /**
     * @var boolean $allowMissingFiles
     */
    public $allowMissingFiles = null;

    /**
     * @var boolean $autoUpdatePackage
     */
    public $autoUpdatePackage = null;

    /**
     * @var boolean $checkOnly
     */
    public $checkOnly = null;

    /**
     * @var boolean $ignoreWarnings
     */
    public $ignoreWarnings = null;

    /**
     * @var boolean $performRetrieve
     */
    public $performRetrieve = null;

    /**
     * @var boolean $purgeOnDelete
     */
    public $purgeOnDelete = null;

    /**
     * @var boolean $rollbackOnError
     */
    public $rollbackOnError = null;

    /**
     * @var string[] $runTests
     */
    public $runTests = null;

    /**
     * @var boolean $singlePackage
     */
    public $singlePackage = null;

    /**
     * @var TestLevel $testLevel
     */
    public $testLevel = null;

    /**
     * @param boolean $allowMissingFiles
     * @param boolean $autoUpdatePackage
     * @param boolean $checkOnly
     * @param boolean $ignoreWarnings
     * @param boolean $performRetrieve
     * @param boolean $purgeOnDelete
     * @param boolean $rollbackOnError
     * @param boolean $singlePackage
     * @param TestLevel $testLevel
     */
    public function __construct($allowMissingFiles, $autoUpdatePackage, $checkOnly, $ignoreWarnings, $performRetrieve, $purgeOnDelete, $rollbackOnError, $singlePackage, $testLevel)
    {
      $this->allowMissingFiles = $allowMissingFiles;
      $this->autoUpdatePackage = $autoUpdatePackage;
      $this->checkOnly = $checkOnly;
      $this->ignoreWarnings = $ignoreWarnings;
      $this->performRetrieve = $performRetrieve;
      $this->purgeOnDelete = $purgeOnDelete;
      $this->rollbackOnError = $rollbackOnError;
      $this->singlePackage = $singlePackage;
      $this->testLevel = $testLevel;
    }

}
