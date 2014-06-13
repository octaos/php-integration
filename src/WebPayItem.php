<?php
// WebPayItem class is not included in Svea namespace 

include_once SVEA_REQUEST_DIR . "/Includes.php";

/**
 * Supercedes class Item, while providing the same functionality.
 * WebPayItem is external to Svea namespace along with class WebPay.
 *  
 * @api
 * @author Kristian Grossman-Madsen
 */
class WebPayItem {

    /**
     * 
     * @return \Svea\OrderRow
     */
     public static function orderRow() {
         return new Svea\OrderRow();
    }

    /**
     * @return \Svea\NumberedOrderRow
     */
     public static function numberedOrderRow() {
         return new Svea\NumberedOrderRow();
    }
    
    
    /**
     * Sets shipping fee
     * @return \Svea\ShippingFee
     */
    public static function shippingFee() {
        return new Svea\ShippingFee();
    }

    /**
     * @return \Svea\InvoiceFee
     */
    public static function invoiceFee() {
        return new Svea\InvoiceFee();
    }

    /**
     * 
     * @return \Svea\FixedDiscount
     */
    public static function fixedDiscount() {
        return new Svea\FixedDiscount();
    }

    /**
     * 
     * @return \Svea\RelativeDiscount
     */
    public static function relativeDiscount() {
        return new Svea\RelativeDiscount();
    }

    /**
     * 
     * @return \Svea\IndividualCustomer
     */
    public static function individualCustomer() {
        return new Svea\IndividualCustomer();
    }

    /**
     * 
     * @return \Svea\CompanyCustomer
     */
    public static function companyCustomer() {
        return new Svea\CompanyCustomer();
    }
}