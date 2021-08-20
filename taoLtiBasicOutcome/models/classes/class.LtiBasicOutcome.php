<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

use oat\taoLti\models\classes\LtiService;
use oat\taoResultServer\models\classes\ResultAliasServiceInterface;

/**
 * Implements tao results storage with respect to LTI 1.1.1 specs acting as a Tool provider calling back the consumer outcome service
 *
 */

class taoLtiBasicOutcome_models_classes_LtiBasicOutcome
    extends tao_models_classes_GenerisService
    implements taoResultServer_models_classes_WritableResultStorage
    {

    const VARIABLE_IDENTIFIER = 'LtiOutcome';

    //private $ltiConsumer;//the kb resource modelling the LTI consumer
    /**
    * @param string deliveryResultIdentifier if no such deliveryResult with this identifier exists a new one gets created
    */
    public function __construct(){
		parent::__construct();
        common_ext_ExtensionsManager::singleton()->getExtensionById("taoLtiBasicOutcome");
    }

    /**
     * @param $deliveryResultIdentifier lis_result_sourcedid
     * @param $test ignored
     * @param taoResultServer_models_classes_Variable $testVariable
     * @param $callIdTest ignored
     * @throws \oat\taoLti\models\classes\LtiException
     * @throws common_exception_Error
     */
    public function storeTestVariable($deliveryResultIdentifier, $test, taoResultServer_models_classes_Variable $testVariable, $callIdTest)
    {
        if (get_class($testVariable)=="taoResultServer_models_classes_OutcomeVariable") {
            common_Logger::d(
                "Outcome submission VariableId. (".$testVariable->getIdentifier().") Result Identifier ("
                .$deliveryResultIdentifier.")Service URL (".$this->serviceUrl.")"
                );
            $variableIdentifier = $testVariable->getIdentifier();
            if (($variableIdentifier == self::VARIABLE_IDENTIFIER)
               // or true
                ) {
                $grade = (string)$testVariable->getValue();

                /** @var ResultAliasServiceInterface $resultAliasService */
                $resultAliasService = $this->getServiceLocator()->get(ResultAliasServiceInterface::SERVICE_ID);
                $deliveryResultAlias = $resultAliasService->getResultAlias($deliveryResultIdentifier);
                $deliveryResultIdentifier = empty($deliveryResultAlias) ? $deliveryResultIdentifier: current($deliveryResultAlias);

                $message = taoLtiBasicOutcome_helpers_LtiBasicOutcome::buildXMLMessage($deliveryResultIdentifier, $grade, 'replaceResultRequest');

                //common_Logger::i("Preparing POX message for the outcome service :".$message."\n");

                $credentialResource = LtiService::singleton()->getCredential($this->consumerKey);
                //common_Logger::i("Credential for the consumerKey :". $credentialResource->getUri()."\n");
                $credentials = new tao_models_classes_oauth_Credentials($credentialResource);
                //$this->serviceUrl = "http://tao-dev/log.php";
                //Building POX raw http message
                $unSignedOutComeRequest = new common_http_Request($this->serviceUrl, 'POST', array());
                $unSignedOutComeRequest->setBody($message);
                $signingService = new tao_models_classes_oauth_Service();
                $signedRequest = $signingService->sign($unSignedOutComeRequest, $credentials, true );
                common_Logger::d("Request sent (Body)\n".($signedRequest->getBody())."\n");
                common_Logger::d("Request sent (Headers)\n".(serialize($signedRequest->getHeaders()))."\n");
                common_Logger::d("Request sent (Headers)\n".(serialize($signedRequest->getParams()))."\n");
                 //Hack for moodle comaptibility, the header is ignored for the signature computation
                $signedRequest->setHeader("Content-Type", "application/xml");

                $response = $signedRequest->send();
                common_Logger::d("\nHTTP Code received: ".($response->httpCode)."\n" );
                common_Logger::d("\nHTTP From: ".($response->effectiveUrl)."\n" );
                common_Logger::d("\nHTTP Content received: ".($response->responseData)."\n" );
                if ($response->httpCode != "200") {
                    throw new common_exception_Error("An HTTP level proble occured when sending the outcome to the service url");
                }
            }
        }
       
    }
    
    public function storeTestVariables($deliveryResultIdentifier, $test, array $testVariables, $callIdTest)
    {
        foreach ($testVariables as $testVariable) {
            $this->storeTestVariable($deliveryResultIdentifier, $test, $testVariable, $callIdTest);
        }
    }
    
    /*
    * retrieve specific parameters from the resultserver to configure the storage
    */
    /*sic*/
    public function configure($callOptions = array())
    {
        /**
         * Retrieve the lti consumer associated with the result server in the KB , those rpoperties are available within taoLtiBasicComponent only
         */
       
        if (isset($callOptions["service_url"])) {
            $this->serviceUrl =  $callOptions["service_url"];
        } else {

            throw new common_Exception("LtiBasicOutcome Storage requires a call parameter service_url");
        }
        if (isset($callOptions["consumer_key"])) {
            $this->consumerKey =  $callOptions["consumer_key"];
        } else {
            throw new common_Exception("LtiBasicOutcome Storage requires a call parameter consumerKey");
        }

        common_Logger::d("ResultServer configured with ".$callOptions["service_url"]. " and ".$callOptions["consumer_key"]);
        
    }
     /**
     * In the case of An LtiBasic OutcomeSubmission, spawnResult has no effect
     */
    public function spawnResult()
    {
       
    }
    public function storeRelatedTestTaker($deliveryResultIdentifier, $testTakerIdentifier)
    {
        
    }

    public function storeRelatedDelivery($deliveryResultIdentifier, $deliveryIdentifier)
    {
        
    }

    public function storeItemVariable($deliveryResultIdentifier, $test, $item, taoResultServer_models_classes_Variable $itemVariable, $callIdItem)
    {
        // For testing purpose.            
        common_Logger::d("Item Variable Submission: ".$itemVariable->getIdentifier() );
        $this->storeTestVariable($deliveryResultIdentifier, $test, $itemVariable, $callIdItem);
    }

    public function storeItemVariables($deliveryResultIdentifier, $test, $item, array $itemVariables, $callIdItem)
    {
        foreach ($itemVariables as $itemVariable) {
            $this->storeItemVariable($deliveryResultIdentifier, $test, $item, $itemVariable, $callIdItem);
        }
    }
}
