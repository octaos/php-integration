<?php
/**
 * minimal invoice payment example
 * 
 * @author Kristian Grossman-madsen for Svea WebPay
 */
error_reporting( E_ALL );
ini_set('display_errors', 'On');

// Include Svea PHP integration package.
$svea_directory = "../../src/";
require( $svea_directory . "Includes.php" );

// get configuration object holding the Svea service login credentials
$myConfig = Svea\SveaConfig::getTestConfig(); 

// We assume that you've collected the following information about the order in your shop: 
// The shop cart contains one item "Billy" which cost 700,99 kr excluding vat (25%).
// When selecting to pay using the invoice payment method, the customer has also provided their social security number, which is required for invoice orders.

// Begin the order creation process by creating an order builder object using the WebPay::createOrder() method:
$myOrder = WebPay::createOrder( $myConfig );

// We then add information to the order object by using the various methods in the Svea\CreateOrderBuilder class.

// We begin by adding any additional information required by the payment method, which for an invoice order means:
$myOrder->setCountryCode("SE");                         
$myOrder->setOrderDate( date('c') );

// To add the cart contents to the order we first create and specify a new orderRow item using methods from the Svea\OrderRow class:
$boughtItem = WebPayItem::orderRow();
$boughtItem->setDescription( "Billy" );
$boughtItem->setAmountExVat( 700.99 );
$boughtItem->setVatPercent( 25 );
$boughtItem->setQuantity( 1 );

// Add the order rows to the order: 
$myOrder->addOrderRow( $boughtItem ); 

// Next, we create a customer identity object, for invoice orders Svea will look up the customer address et al based on the social security number
$customerInformation = WebPayItem::individualCustomer();
$customerInformation->setNationalIdNumber("194605092222");

// Add the customer to the order: 
$myOrder->addCustomerDetails( $customerInformation );

// We have now completed specifying the order, and wish to send the payment request to Svea. To do so, we first select the invoice payment method:
$myInvoiceOrderRequest = $myOrder->useInvoicePayment();

// Then send the request to Svea using the doRequest method, and immediately receive the service response object
$myResponse = $myInvoiceOrderRequest->doRequest();

// If the response attribute accepted is true, the payment succeeded.
if( $myResponse->accepted == true ) { echo "invoice payment succeeded"; };

// The response also contains a customerIdentity object containing the invoice address of the customer, which should match the order shipping address.
print_r( $myResponse->customerIdentity );
?>