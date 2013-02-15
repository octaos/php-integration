<?php

$root = realpath(dirname(__FILE__));
require_once $root . '\..\..\..\..\src\Includes.php';

/**
 * Description of WebServiceOrderValidatorTest
 *
 * @author Anneli Halld'n, Daniel Brolund for Svea Webpay
 */
class WebServiceOrderValidatorTest extends PHPUnit_Framework_TestCase {
    
     /**
     * @expectedException ValidationException
     * @expectedExceptionMessage -missing value : Customer values are required for Invoice and PaymentPlan orders.
    
    function te_stFailOnMissingCustomerIdentity() {
        $builder = WebPay::createOrder();
        $order = $builder
                ->beginOrderRow()
                    ->setAmountExVat(100)
                    ->setVatPercent(20)
                    ->setQuantity(1)
                ->endOrderRow()
                ->setCountryCode("SE")
                    ->useInvoicePayment();
        $order->prepareRequest();

       
    }
      * 
      */
    
     /**
     * @expectedException ValidationException
     * @expectedExceptionMessage -duplicated value : Customer is either an individual or a company. You can not use function setSsn() in combination with setCompanyIdNumber() or setVatNumber().
     */
    function t_estFailOnDoubleIdentity() {
        $builder = WebPay::createOrder();
        $order = $builder 
                ->addOrderRow(Item::orderRow()
                    ->setAmountExVat(100)
                    ->setVatPercent(20)
                    ->setQuantity(1)
                    )
                ->setCountryCode("SE")       
                ->addCustomerDetails(Item::individualCustomer()->setSsn(194605092222))
                ->addCustomerDetails(Item::companyCustomer()->setCompanyIdNumber(4608142222))
                    ->useInvoicePayment();
       $order->prepareRequest();
       
    }

    /**
     * @expectedException ValidationException
     * @expectedExceptionMessage -not valid : Given countrycode does not exist in our system.
     * 
     */
    function testFailOnBadCountryCode() {
        $builder = WebPay::createOrder();
        $order = $builder
                ->addOrderRow(Item::orderRow()
                    ->setAmountExVat(100)
                    ->setVatPercent(20)
                    ->setQuantity(1)
                    )
                 ->setCountryCode("ZZ")
                ->addCustomerDetails(Item::individualCustomer()->setSsn(111111))
                    ->useInvoicePayment();
        
     $order->prepareRequest();
    }
    
     

    /**
     * @expectedException ValidationException
     * @expectedExceptionMessage -missing value : CountryCode is required. Use function setCountryCode().
     */
    function testFailOnMissingCountryCode() {
        $builder = WebPay::createOrder();
        $order = $builder
                ->addOrderRow(Item::orderRow()
                    ->setAmountExVat(100)
                    ->setVatPercent(20)
                    ->setQuantity(1)
                    )
                ->addCustomerDetails(Item::individualCustomer()->setSsn(111111))
                    ->useInvoicePayment();
        
        $order->prepareRequest();
    }
    /**
     * @expectedException ValidationException
     * @expectedExceptionMessage -missing value : Ssn is required for individual customers when countrycode is SE, NO, DK or FI. Use function setSsn().
     */
    function testFailOnMissingSsnForSeOrder() {
        $builder = WebPay::createOrder();
        $order = $builder
                ->setCountryCode("SE")
                ->addCustomerDetails(Item::individualCustomer()->setName("Tess", "Testson"))
                    ->useInvoicePayment();
        
       $order->prepareRequest();
    }

    /**
     * @expectedException ValidationException
     * @expectedExceptionMessage -missing value : OrgNumber is required for company customers when countrycode is SE, NO, DK or FI. Use function setCompanyIdNumber().
     */
    function testFailOnMissingOrgNumberForCompanyOrderSe() {
        $builder = WebPay::createOrder();
        $order = $builder
                ->setCountryCode("SE")
                ->setCustomerReference("1")
                  ->addCustomerDetails(Item::companyCustomer()->setCompanyName("Mycompany"))
                    ->useInvoicePayment();
        
        $order->prepareRequest();
    }

    /**
     * @expectedException ValidationException
     * @expectedExceptionMessage 
     * -missing value : BirthDate is required for individual customers when countrycode is DE. Use function setBirthDate().
     * -missing value : Name is required for individual customers when countrycode is DE. Use function setName().
     * -missing value : StreetAddress is required for all customers when countrycode is DE. Use function setStreetAddress().
     * -missing value : Locality is required for all customers when countrycode is DE. Use function setLocality().
     * -missing value : ZipCode is required for all customers when countrycode is DE. Use function setZipCode().
     */
    function testFailOnMissingIdentityValuesForDEPaymentPlanOrder() {
        $builder = WebPay::createOrder();
        $order = $builder
                ->setCountryCode("DE")
                    ->usePaymentPlanPayment(213060);

       $order->prepareRequest();
    }

    /**
     * @expectedException ValidationException
     * @expectedExceptionMessage -missing value : BirthDate is required for individual customers when countrycode is DE. Use function setBirthDate().
     */
    function testFailOnMissingBirthDateForDeOrder() {
        $builder = WebPay::createOrder();
        $order = $builder
                ->setCountryCode("DE")
                ->addCustomerDetails(Item::individualCustomer()
                //->setBirthDate(1923, 12, 12)
                ->setName("Tess", "Testson")
                ->setStreetAddress("Gatan", 23)
                ->setZipCode(9999)
                ->setLocality("Stan")
                )
               
                    ->useInvoicePayment();
        $order->prepareRequest();

    }

    /**
     * @expectedException ValidationException
     * @expectedExceptionMessage 
     * -missing value : Initials is required for individual customers when countrycode is NL. Use function setInitials().
     * -missing value : BirthDate is required for individual customers when countrycode is NL. Use function setBirthDate().
     * -missing value : Name is required for individual customers when countrycode is NL. Use function setName().
     * -missing value : StreetAddress is required for all customers when countrycode is NL. Use function setStreetAddress().
     * -missing value : Locality is required for all customers when countrycode is NL. Use function setLocality().
     * -missing value : ZipCode is required for all customers when countrycode is NL. Use function setZipCode().
     */
    function testFailOnMissingValuesForNlOrder() {
        $builder = WebPay::createOrder();
        $order = $builder
                ->addOrderRow(Item::orderRow()
                    ->setAmountExVat(100)
                    ->setVatPercent(20)
                    ->setQuantity(1)
                        )
                ->setCountryCode("NL")
                    ->useInvoicePayment();
        //$errorArray = $order->validateOrder(); 
        //print_r($errorArray);
        $order->prepareRequest(); //throws esception
        
      
    }

    /**
     * @expectedException ValidationException
     * @expectedExceptionMessage -missing value : Initials is required for individual customers when countrycode is NL. Use function setInitials().
     */
    function testFailOnMissingInitialsForNlOrder() {
        $builder = WebPay::createOrder();
        $order = $builder
                ->setCountryCode("NL")
                ->addCustomerDetails(Item::individualCustomer()
                //->setInitials("SB")
                ->setBirthDate(1923, 12, 12)
                ->setName("Tess", "Testson")
                ->setStreetAddress("Gatan", 23)
                ->setZipCode(9999)
                ->setLocality("Stan")
                )
               
                    ->useInvoicePayment();

      $order->prepareRequest();
    }

    /**
     * @expectedException ValidationException
     * @expectedExceptionMessage -missing values : OrderRows are required. Use function addOrderRow(Item::orderRow) to get orderrow setters.
     */
    function testFailOnMissingOrderRows() {
        $builder = WebPay::createOrder();
        $order = $builder
                ->setCountryCode("SE")
                ->setCustomerReference("1")
                ->addCustomerDetails(Item::individualCustomer()->setSsn(46111111))
                    ->useInvoicePayment();
       $order->prepareRequest();
      
    }
   

    /**
     * @expectedException ValidationException
     * @expectedExceptionMessage 
     * -missing values : At least two of the values must be set in object Item::  AmountExVat, AmountIncVat or VatPercent for Orderrow. Use functions setAmountExVat(), setAmountIncVat() or setVatPercent().
     * -missing value : Quantity is required in object Item. Use function Item::setQuantity().
     */
    function testFailOnMissingOrderRowValues() {
        $builder = WebPay::createOrder();
        $order = $builder
                ->addOrderRow(Item::orderRow())
                ->setCountryCode("SE")
                ->setCustomerReference("ref1")
                 ->addCustomerDetails(Item::individualCustomer()->setSsn(46111111))
                    ->useInvoicePayment();
        $order->prepareRequest(); 
    }
}

?>
