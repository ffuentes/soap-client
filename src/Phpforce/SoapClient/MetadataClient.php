<?php

namespace Phpforce\SoapClient;

use Phpforce\SoapClient\Common\AbstractHasDispatcher;
use Phpforce\SoapClient\Soap\SoapClient;

class MetadataClient extends AbstractHasDispatcher {

    /*
     * SOAP namespace
     *
     * @var string
     */
    const SOAP_NAMESPACE = 'urn:partner.soap.sforce.com';

    /**
     * SOAP session header
     *
     * @var \SoapHeader
     */
    protected $sessionHeader;

    /**
     * PHP SOAP client for interacting with the Salesforce API
     *
     * @var SoapClient
     */
    protected $soapClient;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $token;

    /**
     * Type collection as derived from the WSDL
     *
     * @var array
     */
    protected $types = array();

    /**
     * Login result
     *
     * @var Result\LoginResult
     */
    protected $loginResult;

    /**
     * Construct Salesforce SOAP client
     *
     * @param SoapClient $soapClient SOAP client
     * @param string     $username   Salesforce username
     * @param string     $password   Salesforce password
     * @param string     $token      Salesforce security token
     */
    public function __construct(SoapClient $soapClient, $username, $password, $token)
    {
        $this->soapClient = $soapClient;
        $this->soapClient->setRootObject('Metadata');
        $this->username = $username;
        $this->password = $password;
        $this->token = $token;
    }



    /**
     * Issue a call to Salesforce API
     *
     * @param string $method SOAP operation name
     * @param array  $params SOAP parameters
     *
     * @return array | \Traversable An empty array or a result object, such
     *                              as QueryResult, SaveResult, DeleteResult.
     */
    protected function call($method, array $params = array())
    {
        $this->init();

        // Prepare headers
        $this->soapClient->__setSoapHeaders($this->getSessionHeader());

        $requestEvent = new Event\RequestEvent($method, $params);
        $this->dispatch($requestEvent, Events::REQUEST);

        try {
            $result = $this->soapClient->$method($params);
        } catch (\SoapFault $soapFault) {
            $faultEvent = new Event\FaultEvent($soapFault, $requestEvent);
            $this->dispatch($faultEvent, Events::FAULT);

            throw $soapFault;
        }

        // No result e.g. for logout, delete with empty array
        if (!isset($result->result)) {
            return array();
        }

        $this->dispatch(   
            new Event\ResponseEvent($requestEvent, $result->result),
            Events::RESPONSE
        );

        return $result->result;
    }


    /**
     * Create a Salesforce Metadata object
     *
     * Converts PHP \DateTimes to their SOAP equivalents.
     *
     * @param object $object     Any object with public properties
     * @param string $objectType Salesforce Metadata Api object type
     *
     * @return object
     */
    protected function createMetadataObject($object, $objectType)
    {
        $metadataObject = new \stdClass();

        foreach (get_object_vars($object) as $field => $value) {
            $type = $this->soapClient->getSoapElementType($objectType, $field);
            if ($field != 'Id' && !$type) {
                continue;
            }

            // As PHP \DateTime to SOAP dateTime conversion is not done
            // automatically with the SOAP typemap for sObjects, we do it here.
            switch ($type) {
                case 'date':
                    if ($value instanceof \DateTime) {
                        $value  = $value->format('Y-m-d');
                    }
                    break;
                case 'dateTime':
                    if ($value instanceof \DateTime) {
                        $value  = $value->format('Y-m-d\TH:i:sP');
                    }
                    break;
                case 'base64Binary':
                    $value = base64_encode($value);
                    break;
            }

            $metadataObject->$field = $value;
        }
        return $metadataObject;
    }


    /**
     * Turn Sobjects into \SoapVars
     *
     * @param array  $objects Array of objects
     * @param string $type    Object type
     *
     * @return \SoapVar[]
     */
    protected function createSoapVars(array $objectsByMetadata)
    {
        $soapVars = array();

        foreach ($objectsByMetadata as $type=>$objects) {
            foreach ($objects as $object) {
                $sObject = $this->createMetadataObject($object, $type);                
                $soapVar = new \SoapVar($sObject, SOAP_ENC_OBJECT, $type, self::SOAP_NAMESPACE);
                $soapVars[] = $soapVar;
            }
        }
        return $soapVars;
    }



    /**
     * Initialize connection
     *
     */
    protected function init()
    {
        // If there’s no session header yet, this means we haven’t yet logged in
        if (!$this->getSessionHeader()) {
            $this->doLogin($this->username, $this->password, $this->token);
        }
    }

    /**
     * Set soap headers
     *
     * @param array $headers
     */
    protected function setSoapHeaders(array $headers)
    {
        $soapHeaderObjects = array();
        foreach ($headers as $key => $value) {
            $soapHeaderObjects[] = new \SoapHeader(self::SOAP_NAMESPACE, $key, $value);
        }
        $this->soapClient->__setSoapHeaders($soapHeaderObjects);
    }

    /**
     * Get session header
     *
     * @return \SoapHeader
     */
    protected function getSessionHeader()
    {
        return $this->sessionHeader;
    }

    /**
     * Save session id to SOAP headers to be used on subsequent requests
     *
     * @param string $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionHeader = new \SoapHeader(
            self::SOAP_NAMESPACE,
            'SessionHeader',
            array(
                'sessionId' => $sessionId
            )
        );
    }

    protected function setLoginResult(Result\LoginResult $loginResult)
    {
        $this->loginResult = $loginResult;
        $this->setEndpointLocation($loginResult->getServerUrl());
        $this->setSessionId($loginResult->getSessionId());
    }

    /**
     * After successful log in, Salesforce wants us to change the endpoint
     * location
     *
     * @param string $location
     */
    public function setEndpointLocation($location)
    {
        $this->soapClient->__setLocation($location);
    }




    /**
     * Cancels a metadata deploy.
     *
     * @param ID $id
     * @return array
     */
    public function cancelDeploy(string $id)
    {
      return $this->call('cancelDeploy', array('id'=>$id));
    }

    /**
     * Check the current status of an asyncronous deploy call.
     *
     * @param checkDeployStatus $parameters
     * @return array
     */
    public function checkDeployStatus(string $id,  bool $includeDetails = false)
    {
      return $this->call( 'checkDeployStatus', array('id' => $id, 'includeDetails' => $includeDetails ) );
    }

    /**
     * Check the current status of an asyncronous deploy call.
     *
     * @param string $id	ID	ID obtained from an AsyncResult object returned by a retrieve() call or a subsequent RetrieveResult object returned by a checkRetrieveStatus() call.
     * @param boolean $includeZip	boolean	Set to true to retrieve the zip file. You can retrieve the zip file only after the retrieval operation is completed. After the zip file is retrieved, it is deleted from the server. Set to false to check the status of the retrieval without attempting to retrieve the zip file. If set to null, this argument defaults to true, which means that the zip file is retrieved on the last call to checkRetrieveStatus() when the retrieval has finished.
     * @return array
     */
    public function checkRetrieveStatus(string $id, bool $includeZip = true)
    {
      return $this->call( 'checkRetrieveStatus', array( 'id'=>$id, 'includeZip' => $includeZip ) );
    }

    /**
     * Creates metadata entries synchronously.
     *
     * @param array $type type of metadata to create (CustomMetadata)
     * @param array $metadata Array of one or more metadata components. Limit: 10. (For CustomMetadata and CustomApplication only, the limit is 200.)
     * @return array
     */
    public function createMetadata(string $type, array $metadatas)
    {
      return $this->call('createMetadata', array('metadata'=>$this->createSoapVars( [$type => $metadatas ] ) ));
    }

    /**
     * Deletes metadata entries synchronously.
     *
     * @param string $metadataType		The metadata type of the components to delete.
     * @param array  $fullNames	Array of full names of the components to delete.
     *  Limit: 10. (For CustomMetadata and CustomApplication only, the limit is 200.)
     *  You must submit arrays of only one type of component. For example, you can submit an array of 10 custom objects or 10 profiles, but not a mix of both types.
     * @return array
     */
    public function deleteMetadata($metadataType, array $fullNames)
    {
      return $this->call('deleteMetadata', array('metadataType'=> $metadataType, 'fullNames'=>$fullNames));
    }

    /**
     * Deploys a previously validated payload without running tests.
     *
     * @param string $validationID	The ID of a recent validation.
     * @return deployRecentValidationResponse
     */
    public function deployRecentValidation( $validationID )
    {
      return $this->call('deployRecentValidation', array('validationID'=>$validationID));
    }

    /**
     * Describes features of the metadata API.
     *
     * @param float $apiVersion The API version for which you want metadata, for example, 51.0.
     * @return array
     */
    public function describeMetadata(float $apiVersion)
    {
      return $this->call('describeMetadata', array( 'apiVersion' => $apiVersion));
    }

    /**
     * Describe a complex value type
     *
     * @param string $type The name of the metadata type for which you want metadata; for example, ApexClass. Include the namespace.
     * @return array
     */
    public function describeValueType(string $type)
    {
      return $this->call('describeValueType', array('type'=>$type));
    }

    /**
     * Lists the available metadata components.
     *
     * @param Request\ListMetadataQuery[]  $queries A list of objects that specify which components you are interested in.
     * @param float $asOfVersion The API version for the metadata listing request. If you don't specify a value in this field, it defaults to the API version specified when you logged in. This field allows you to override the default and set another API version. For example, you can list the metadata for a metadata type that was added in a later version than the API version specified when you logged in. This field is available in API version 18.0 and later.
     * @return array
     */
    public function listMetadata(array $queries, float $asOfVersion = null)
    {
      return $this->call('listMetadata', array('queries'=>$queries,'asOfVersion' => $asOfVersion));
    }

    /**
     * Reads metadata entries synchronously.
     *
     * @param string metadataType		The metadata type of the components to read.
     * @param string[] fullNames	string[]	Array of full names of the components to read.
     *  Limit: 10. (For CustomMetadata and CustomApplication only, the limit is 200.)
     *  You must submit arrays of only one type of component. For example, you can submit an array of 10 custom objects or 10 profiles, but not a mix of both types.
     * @return array
     */
    public function readMetadata(string $metadataType, array $fullNames)
    {
      return $this->call('readMetadata', array( 'metadataType' => $metadataType, 'fullNames' => $fullNames ) );
    }

    /**
     * Renames a metadata entry synchronously.
     *
     * @param $metadataType	string	The metadata type of the components to rename.
     * @param $oldFullName	string	The current component full name.
     * @param $newFullName	string	The new component full name.
     * @return array
     */
    public function renameMetadata(string $metadataType, string $oldFullName,  string $newFullName)
    {
      return $this->call('renameMetadata', array('metadataType'=>$metadataType,'oldFullName'=>$oldFullName, 'newFullName'=>$newFullName));
    }

    /**
     * Updates metadata entries synchronously.
     *
     * @param metadata	array	Array of one or more metadata components you wish to update.
     * Limit: 10. (For CustomMetadata and CustomApplication only, the limit is 200.)
     *
     * You must submit arrays of only one type of component. For example, you can submit an array of 10 custom objects or 10 profiles, but not a mix of both types.
     * @return array
     */
    public function updateMetadata(string $type, array $metadata)
    {
      return $this->call('updateMetadata', array('metadata'=>$this->createSoapVars( [$type => $metadata ] ) ) );
    }

    /**
     * Upserts metadata entries synchronously.
     *
     * @param metadata	array	Array of one or more metadata components you wish to update.
     * Limit: 10. (For CustomMetadata and CustomApplication only, the limit is 200.)
     *
     * You must submit arrays of only one type of component. For example, you can submit an array of 10 custom objects or 10 profiles, but not a mix of both types.
     * @return array
     */
    public function upsertMetadata(string $type, array $metadata)
    {
      return $this->call('updateMetadata', array('metadata'=>$this->createSoapVars( [$type => $metadata ] ) ) );
    }

     /**
     * Deploys a zipfile full of metadata entries asynchronously.
     *
     * @param string $zipFile	Path to the package zip file (or packagezipfile as string from file_gets_contents)
     * @param Request\DeployOptions $deployOptions Encapsulates options for determining which packages or files are deployed.
     * @return array
     */
    public function deploy($zipFile ,Request\DeployOptions $deployOptions)
    {
      if( is_file( $zipFile ) ){
        $zipFile = file_get_contents($zipFile);
      }
      return $this->call('deploy', array('zipFile'=>$zipFile, 'deployOptions'=>$deployOptions));
    }

    /**
     * Retrieves a set of individually specified metadata entries.
     *
     * @param packageTypeMembers $packageTypeMembers 	A list of components to retrieve that are not in a package. ( for example array( 'ApexTrigger' => array('AccountTrigger', 'OpportunityTrigger' ), 'CustomObject' => array('*') ) )
     * @param Package $packageOptions Options for the package to retrieve (@see https://developer.salesforce.com/docs/atlas.en-us.api_meta.meta/api_meta/meta_package.htm#meta_package )
     * @param string[] $packageNames	string[]	A list of package names to be retrieved. If you are retrieving only unpackaged components, do not specify a name here. You can retrieve packaged and unpackaged components in the same retrieve.
     * @param boolean $singlePackage	Specifies whether only a single package is being retrieved (true) or not (false). If false, then more than one package is being retrieved.
     * @param string[]  $specificFiles	A list of file names to be retrieved. If a value is specified for this property, packageNames must be set to null and singlePackage must be set to true.
     * @param float  $apiVersion	A list of file names to be retrieved. If a value is specified for this property, packageNames must be set to null and singlePackage must be set to true.
     * @return array
     */
    public function retrieve(array $packageMembers, $packageOptions = [], array $packageNames = null,bool $singlePackage = null, array $specificFiles = null , float $apiVersion = null)
    {
      $packageOptions['types'] = $packageMembers;
      return $this->call('retrieve', array('unpackaged'=>$this->createSoapVars(array('Package' => $pkg ) ), 
                                          'packageNames'=>$packageNames, 
                                          'singlePackage'=>$singlePackage, 
                                          'specificFiles'=>$specificFiles, 
                                          'apiVersion'=> $apiVersion)
                                        );
    }

}