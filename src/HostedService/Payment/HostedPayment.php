<?php
namespace Svea\HostedService;

require_once SVEA_REQUEST_DIR . '/Includes.php';

/**
 * HostedPayment and its descendants sets up the various hosted payment methods.
 * 
 * Set all required attributes in the hosted payment class instance using the 
 * setAttribute() methods. Instance methods can be chained together, as they 
 * return the instance itself in a fluent fashion.
 * 
 * Finish by using the getPaymentForm() method which returns an HTML form with
 * the POST request to Svea prepared. After the customer has completed the
 * hosted payment request, a response xml message is returned to the specified
 * return url, where it can be parsed using i.e. the SveaResponse class.
 * 
 * Alternatively, you can use the getPaymentURL() to get a response with
 * an URL that the customer can visit later to complete the payment at a later
 * time.
 * 
 * Uses HostedXmlBuilder to turn formatted $order into xml
 * @author Anneli Halld'n, Daniel Brolund, Kristian Grossman-Madsen for Svea Webpay
 */

class HostedPayment {

    /** @var CreateOrderBuilder $order  holds the order information */
    public $order;
    
    /** @var string $xmlMessage  holds the generated message XML used in request */
    public $xmlMessage;
    
    /** @var string $xmlMessageBase64  holds the Base64-encoded $xmlMessage */
    public $xmlMessageBase64;
    
    /** @var string $returnUrl  holds the return URL used in request */    
    public $returnUrl;
    
    /** @var string $callbackUrl  holds the callback URL used in request */    
    public $callbackUrl;
    
    /** @var string $cancelUrl  holds the cancel URL used in request */    
    public $cancelUrl;
    
    /** @var string $langCode  holds the language code used in request */
    public $langCode;

    /** @var string[] $request placeholder for the request parameter key/value pair array */
    public $request;

    /**
     * Creates a HostedPayment, sets default language to english
     * @param CreateOrderBuilder $order
     */
    public function __construct($order) {
        $this->langCode = "en";
        $this->order = $order;
        $this->request = array();   
    }

    /**
     * Required - setReturnUrl sets the hosted payment return url
     * 
     * When a hosted payment transaction completes (regardless of outcome, i.e. accepted or denied),
     * the payment service will answer with a response xml message sent to the return url specified.
     * 
     * @param string $returnUrlAsString
     * @return $this
     */
    public function setReturnUrl($returnUrlAsString) {
        $this->returnUrl = $returnUrlAsString;
        return $this;
    }
    
    /**
     * setCallbackUrl sets up a callback url. Optional.
     * 
     * In case the hosted payment transaction completes, but the service is unable to return a 
     * response to the return url, the payment service will retry several times using the callback 
     * url as a fallback, if specified. This may happen if i.e. the user closes the browser before 
     * the payment service redirects back to the shop.
     * 
     * @param string $callbackUrlAsString
     * @return $this
     */
    public function setCallbackUrl($callbackUrlAsString) {
        $this->callbackUrl = $callbackUrlAsString;
        return $this;
    }    
    
    /**
     * setCancelUrl sets the hosted payment cancel url and includes a cancel button on the hosted pay page. Optional.
     * 
     * In case the hosted payment service is cancelled by the user, the payment service will redirect back to the 
     * cancel url. Unless a return url is specified, no cancel button will be presented at the payment service.
     * 
     * @param string $cancelUrlAsString
     * @return $this
     */
    public function setCancelUrl($cancelUrlAsString) {
        $this->cancelUrl = $cancelUrlAsString;
        return $this;
    }    
    
    /* Sets the pay page display language. Optional.
     * Default pay page language is English, unless another is specified using this method.
     * @param string $languageCodeAsISO639
     * @return $this
     */
    public function setPayPageLanguage($languageCodeAsISO639){
        switch ($languageCodeAsISO639) {
            case "sv":
            case "en":
            case "da":
            case "no":
            case "fi":
            case "es":
            case "nl":
            case "fr":
            case "de":
            case "it":
                $this->langCode = $languageCodeAsISO639;
                break;
            default:
                $this->langCode = "en";
                break;
        }
        return $this;
    }
    
    // TODO refactor getPaymentForm, getPaymentURL to move validation, xml building details to HostedRequest subclasses + add tests
    
    /**
     * getPaymentForm returns a form object containing a webservice payment request
     * @return PaymentForm
     * @throws ValidationException
     */
    public function getPaymentForm() {
        //validate the order
        $errors = $this->validateOrder();
        $exceptionString = "";
        if (count($errors) > 0 || (isset($this->returnUrl) == FALSE && isset($this->paymentMethod) == FALSE)) { // todo check if this works as expected
            if (isset($this->returnUrl) == FALSE) {
             $exceptionString .="-missing value : ReturnUrl is required. Use function setReturnUrl().\n";
            }

            foreach ($errors as $key => $value) {
                $exceptionString .="-". $key. " : ".$value."\n";
            }

            throw new \Svea\ValidationException($exceptionString);
        }

        $xmlBuilder = new HostedXmlBuilder();
        $this->xmlMessage = $xmlBuilder->getPaymentXML($this->calculateRequestValues(),$this->order);
        $this->xmlMessageBase64 = base64_encode($this->xmlMessage);
        
        $formObject = new PaymentForm( $this->xmlMessage, $this->order->conf, $this->order->countryCode );
        return $formObject;
    }
    
    /**
     * getPaymentURL returns an URL to a prepared hosted payment, use this to
     * to get a link which the customer can use to confirm a payment at a later
     * time after having received the url via i.e. an email message.
     * 
     * @return type
     * @throws ValidationException
     */
    public function getPaymentURL() {
        
        // follow the procedure set out in getPaymentForm, then 
        // 
        //validate the order
        $errors = $this->validateOrder();
        
        //additional validation for PreparedPayment request
        if( !isset( $this->order->customerIdentity->ipAddress ) ) {
            $errors['missing value'] = "ipAddress is required. Use function setIpAddress() on the order customer."; 
        }
        if( !isset( $this->langCode) ) {
            $errors['missing value'] = "langCode is required. Use function setPayPageLanguage()."; 
        }
        
        $exceptionString = "";
        if (count($errors) > 0 || (isset($this->returnUrl) == FALSE && isset($this->paymentMethod) == FALSE)) { // todo check if this works as expected
            if (isset($this->returnUrl) == FALSE) {
             $exceptionString .="-missing value : ReturnUrl is required. Use function setReturnUrl().\n";
            }

            foreach ($errors as $key => $value) {
                $exceptionString .="-". $key. " : ".$value."\n";
            }

            throw new \Svea\ValidationException($exceptionString);
        }
                
        $xmlBuilder = new HostedXmlBuilder();
        $this->xmlMessage = $xmlBuilder->getPreparePaymentXML($this->calculateRequestValues(),$this->order);

        // curl away the request to Svea, and pick up the answer.

        // get our merchantid & secret
        
        // get the config, countryCode from the order object, $message from $this->xmlMessage;
        $this->config = $this->order->conf; 
        $this->countryCode = $this->order->countryCode;
        $message = $this->xmlMessage;
        
        $merchantId = $this->config->getMerchantId( \ConfigurationProvider::HOSTED_TYPE,  $this->countryCode);
        $secret = $this->config->getSecret( \ConfigurationProvider::HOSTED_TYPE, $this->countryCode);
             
        // calculate mac
        $mac = hash("sha512", base64_encode($message) . $secret);
        
        // encode the request elements
        $fields = array( 
            'merchantid' => urlencode($merchantId),
            'message' => urlencode(base64_encode($message)),
            'mac' => urlencode($mac)
        );       

        // below taken from HostedRequest doRequest
        $fieldsString = "";
        foreach ($fields as $key => $value) {
            $fieldsString .= $key.'='.$value.'&';
        }
        rtrim($fieldsString, '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config->getEndpoint(\Svea\SveaConfigurationProvider::HOSTED_ADMIN_TYPE). "preparepayment");
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //force curl to trust https
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //returns a html page with redirecting to bank...
        $responseXML = curl_exec($ch);
        curl_close($ch);
        
        // create SveaResponse to handle annul response
        $responseObj = new \SimpleXMLElement($responseXML);        
        $sveaResponse = new \SveaResponse($responseObj, $this->countryCode, $this->config);

        return $sveaResponse->response;   
    }
    
    /**
     * @return string[] $errors an array containing the validation errors found
     */
    public function validateOrder() {
        $validator = new \Svea\HostedOrderValidator();
        $errors = $validator->validate($this->order);
        if (($this->order->countryCode == "NL" || $this->order->countryCode == "DE") && isset($this->paymentMethod)) {
            if( isset($this->paymentMethod) && 
                ($this->paymentMethod == \PaymentMethod::INVOICE || $this->paymentMethod == \PaymentMethod::PAYMENTPLAN)) {
                $errors = $validator->validateEuroCustomer($this->order, $errors);
            }
        }
        return $errors;
    }
    
    /** 
     * returns a list of request attributes-value pairs 
     * @todo make sure orderValidator validates $this->request contents, not the base object properties, or bypass $request when building xml
     */
    public function calculateRequestValues() {
        // format order data
        $formatter = new HostedRowFormatter();
        $this->request['rows'] = $formatter->formatRows($this->order);
        $this->request['amount'] = $formatter->formatTotalAmount($this->request['rows']);
        $this->request['totalVat'] = $formatter->formatTotalVat( $this->request['rows']);        

        $this->request['clientOrderNumber'] = $this->order->clientOrderNumber; /// used by payment

        if (isset($this->order->customerIdentity->ipAddress)) {
             $this->request['ipAddress'] = $this->order->customerIdentity->ipAddress; /// used by payment (optional), preparepayment (required)
        }        

        $this->request['langCode'] = $this->langCode;
        
        $this->request['returnUrl'] = $this->returnUrl;
        $this->request['callbackUrl'] = $this->callbackUrl;
        $this->request['cancelUrl'] = $this->cancelUrl;

        $this->request['currency'] = strtoupper(trim($this->order->currency));

        if (isset($this->subscriptionType)) {
             $this->request['subscriptionType'] = $this->subscriptionType;
        }                
        
        return $this->request;
    }
        
}
