<?php

$root = realpath(dirname(__FILE__));

require_once $root . '/../../../../src/Includes.php';

class WebServiceRowFormatterTest extends PHPUnit_Framework_TestCase {

    public function testFormatOrderRows() {
        $order = WebPay::createOrder();
        $order->addOrderRow(\WebPayItem::orderRow()
                ->setArticleNumber("0")
                ->setName("Tess")
                ->setDescription("Tester")
                ->setAmountExVat(4)
                ->setVatPercent(25)
                ->setQuantity(1)
                ->setUnit("st")
                );

        $formatter = new Svea\WebServiceRowFormatter($order);
        $newRows = $formatter->formatRows();
        $newRow = $newRows[0];

        $this->assertEquals("0", $newRow->ArticleNumber);
        $this->assertEquals("Tess: Tester", $newRow->Description);
        $this->assertEquals(4.0, $newRow->PricePerUnit);
        $this->assertEquals(25.0, $newRow->VatPercent);
        $this->assertEquals(0, $newRow->DiscountPercent);
        $this->assertEquals(1, $newRow->NumberOfUnits);
        $this->assertEquals("st", $newRow->Unit);
    }

    public function testFormatShippingFeeRows() {
        $order = WebPay::createOrder();
        $order->addFee(WebPayItem::shippingFee()
                    ->setShippingId("0")
                    ->setName("Tess")
                    ->setDescription("Tester")
                    ->setAmountExVat(4)
                    ->setVatPercent(25)
                    ->setUnit("st")
                );

        $formatter = new Svea\WebServiceRowFormatter($order);
        $newRows = $formatter->formatRows();
        $newRow = $newRows[0];

        $this->assertEquals("0", $newRow->ArticleNumber);
        $this->assertEquals("Tess: Tester", $newRow->Description);
        $this->assertEquals(4.0, $newRow->PricePerUnit);
        $this->assertEquals(25.0, $newRow->VatPercent);
        $this->assertEquals(0, $newRow->DiscountPercent);
        $this->assertEquals(1, $newRow->NumberOfUnits);
        $this->assertEquals("st", $newRow->Unit);
    }

    public function testFormatInvoiceFeeRows() {
        $order = WebPay::createOrder();
        $order->addFee(WebPayItem::invoiceFee()
                ->setName("Tess")
                ->setDescription("Tester")
                ->setAmountExVat(4)
                ->setVatPercent(25)
                ->setUnit("st")
                );

        $formatter = new Svea\WebServiceRowFormatter($order);
        $newRows = $formatter->formatRows();
        $newRow = $newRows[0];

        $this->assertEquals("", $newRow->ArticleNumber);
        $this->assertEquals("Tess: Tester", $newRow->Description);
        $this->assertEquals(4.0, $newRow->PricePerUnit);
        $this->assertEquals(25.0, $newRow->VatPercent);
        $this->assertEquals(0, $newRow->DiscountPercent);
        $this->assertEquals(1, $newRow->NumberOfUnits);
        $this->assertEquals("st", $newRow->Unit);
    }

    // only amountIncVat => calculate mean vat split into diffrent tax rates present
    public function testFormatFixedDiscountRows_amountIncVat_WithSingleVatRatePresent() {
        $order = WebPay::createOrder();
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(4.0)
                ->setVatPercent(25)
                ->setQuantity(1)
                )
                ->addDiscount(WebPayItem::fixedDiscount()
                    ->setDiscountId("0")
                    ->setName("Tess")
                    ->setDescription("Tester")
                    ->setAmountIncVat(1.0)
                    ->setUnit("st")
                );

        $formatter = new Svea\WebServiceRowFormatter($order);
        $newRows = $formatter->formatRows();
        $newRow = $newRows[1];
        $this->assertEquals("0", $newRow->ArticleNumber);
        $this->assertEquals("Tess: Tester", $newRow->Description);
        $this->assertEquals(-0.8, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);
        $this->assertEquals(0, $newRow->DiscountPercent);
        $this->assertEquals(1, $newRow->NumberOfUnits);
        $this->assertEquals("st", $newRow->Unit);
    }

    // only amountIncVat => calculate mean vat split into diffrent tax rates present
    // if we have two orders items with different vat rate, we need to create
    // two discount order rows, one for each vat rate
    public function testFormatFixedDiscountRows_amountIncVat_WithDifferentVatRatesPresent() {
        $order = WebPay::createOrder();
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(100.00)
                ->setVatPercent(25)
                ->setQuantity(2)
                )
                ->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(100.00)
                ->setVatPercent(6)
                ->setQuantity(1)
                )
                ->addDiscount(WebPayItem::fixedDiscount()
                    ->setDiscountId("42")
                    ->setName("->setAmountIncVat(100)")
                    ->setDescription("testFormatFixedDiscountRowsWithDifferentVatRatesPresent")
                    ->setAmountIncVat(100)
                    ->setUnit("st")
                );

        $formatter = new Svea\WebServiceRowFormatter($order);
        $newRows = $formatter->formatRows();
        
        // 100*250/356 = 70.22 incl. 25% vat => 14.04 vat as amount 
        $newRow = $newRows[2];
        $this->assertEquals("42", $newRow->ArticleNumber);
        $this->assertEquals("->setAmountIncVat(100): testFormatFixedDiscountRowsWithDifferentVatRatesPresent (25%)", $newRow->Description);
        $this->assertEquals(-56.18, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);
        $this->assertEquals(0, $newRow->DiscountPercent);
        $this->assertEquals(1, $newRow->NumberOfUnits);
        $this->assertEquals("st", $newRow->Unit);

        // 100*106/356 = 29.78 incl. 6% vat => 1.69 vat as amount 
        $newRow = $newRows[3];
        $this->assertEquals("42", $newRow->ArticleNumber);
        $this->assertEquals("->setAmountIncVat(100): testFormatFixedDiscountRowsWithDifferentVatRatesPresent (6%)", $newRow->Description);
        $this->assertEquals(-28.09, $newRow->PricePerUnit);
        $this->assertEquals(6, $newRow->VatPercent);
        $this->assertEquals(0, $newRow->DiscountPercent);
        $this->assertEquals(1, $newRow->NumberOfUnits);
        $this->assertEquals("st", $newRow->Unit);
   }
   
   // amountIncVat and vatPercent => add as one row with specified vat rate only
   public function testFormatFixedDiscountRows_amountIncVatAndVatPercent_WithSingleVatRatePresent() {
        $order = WebPay::createOrder();
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(4.0)
                ->setVatPercent(25)
                ->setQuantity(1)
                )
                ->addDiscount(WebPayItem::fixedDiscount()
                    ->setDiscountId("0")
                    ->setName("->setAmountExVat(4.0), ->setVatPercent(25)")
                    ->setDescription("testFormatFixedDiscountRows_amountIncVatAndVatPercent_WithSingleVatRatePresent")
                    ->setAmountIncVat(1.0)
                    ->setVatPercent(25)
                    ->setUnit("st")
                );

        $formatter = new Svea\WebServiceRowFormatter($order);
        $newRows = $formatter->formatRows();
        $newRow = $newRows[1];
        $this->assertEquals("0", $newRow->ArticleNumber);
        $this->assertEquals("->setAmountExVat(4.0), ->setVatPercent(25): testFormatFixedDiscountRows_amountIncVatAndVatPercent_WithSingleVatRatePresent", $newRow->Description);
        $this->assertEquals(-0.8, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);
        $this->assertEquals(0, $newRow->DiscountPercent);
        $this->assertEquals(1, $newRow->NumberOfUnits);
        $this->assertEquals("st", $newRow->Unit);
    }   
    
    // amountIncVat and vatPercent => add as one row with specified vat rate only
    public function testFormatFixedDiscountRows_amountIncVatAndVatPercent_WithDifferentVatRatesPresent() {
        $order = WebPay::createOrder();
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(100.00)
                ->setVatPercent(25)
                ->setQuantity(2)
                )
                ->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(100.00)
                ->setVatPercent(6)
                ->setQuantity(1)
                )
                ->addDiscount(WebPayItem::fixedDiscount()
                    ->setDiscountId("42")
                    ->setName("->setAmountIncVat(111), ->vatPercent(25)")
                    ->setDescription("testFormatFixedDiscountRows_amountIncVatAndVatPercent_WithDifferentVatRatesPresent")
                    ->setAmountIncVat(111)
                    ->setVatPercent(25)
                    ->setUnit("st")
                );

        $formatter = new Svea\WebServiceRowFormatter($order);
        $newRows = $formatter->formatRows();
        
        // 100 @25% vat = -80 excl. vat
        $newRow = $newRows[2];
        $this->assertEquals("42", $newRow->ArticleNumber);
        $this->assertEquals("->setAmountIncVat(111), ->vatPercent(25): testFormatFixedDiscountRows_amountIncVatAndVatPercent_WithDifferentVatRatesPresent", $newRow->Description);
        $this->assertEquals(-88.80, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);
        $this->assertEquals(0, $newRow->DiscountPercent);
        $this->assertEquals(1, $newRow->NumberOfUnits);
        $this->assertEquals("st", $newRow->Unit);
   }  

   // amountExVat and vatPercent => add as one row with specified vat rate only
   public function testFormatFixedDiscountRows_amountExVatAndVatPercent_WithSingleVatRatePresent() {
        $order = WebPay::createOrder();
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(4.0)
                ->setVatPercent(25)
                ->setQuantity(1)
                )
                ->addDiscount(WebPayItem::fixedDiscount()
                    ->setDiscountId("0")
                    ->setName("Tess")
                    ->setDescription("Tester")
                    ->setAmountExVat(1.0)
                    ->setVatPercent(25)
                    ->setUnit("st")
                );

        $formatter = new Svea\WebServiceRowFormatter($order);
        $newRows = $formatter->formatRows();
        $newRow = $newRows[1];
        $this->assertEquals("0", $newRow->ArticleNumber);
        $this->assertEquals("Tess: Tester", $newRow->Description);
        $this->assertEquals(-1.0, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);
        $this->assertEquals(0, $newRow->DiscountPercent);
        $this->assertEquals(1, $newRow->NumberOfUnits);
        $this->assertEquals("st", $newRow->Unit);
    }   
    
    // amountExVat and vatPercent => add as one row with specified vat rate only
    public function testFormatFixedDiscountRows_amountExVatAndVatPercent_WithDifferentVatRatesPresent() {
        $order = WebPay::createOrder();
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(100.00)
                ->setVatPercent(25)
                ->setQuantity(2)
                )
                ->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(100.00)
                ->setVatPercent(6)
                ->setQuantity(1)
                )
                ->addDiscount(WebPayItem::fixedDiscount()
                    ->setDiscountId("42")
                    ->setName("->setAmountIncVat(100)")
                    ->setDescription("testFormatFixedDiscountRowsWithDifferentVatRatesPresent")
                    ->setAmountExVat(111)
                    ->setVatPercent(25)
                    ->setUnit("st")
                );

        $formatter = new Svea\WebServiceRowFormatter($order);
        $newRows = $formatter->formatRows();
        
        // 100 @25% vat = -80 excl. vat
        $newRow = $newRows[2];
        $this->assertEquals("42", $newRow->ArticleNumber);
        $this->assertEquals("->setAmountIncVat(100): testFormatFixedDiscountRowsWithDifferentVatRatesPresent", $newRow->Description);
        $this->assertEquals(-111.00, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);
        $this->assertEquals(0, $newRow->DiscountPercent);
        $this->assertEquals(1, $newRow->NumberOfUnits);
        $this->assertEquals("st", $newRow->Unit);
   }  
   
//        public function testBuildCardPaymentWithDiffrentProductVatAndDiscount() {
//        $form = \WebPay::createOrder()
//                ->addOrderRow(\WebPayItem::orderRow()
//                    ->setArticleNumber("1")
//                    ->setQuantity(1)
//                    ->setAmountExVat(240.00)
//                    ->setDescription("CD")
//                    ->setVatPercent(25)
//                )
//                ->addOrderRow(\WebPayItem::orderRow()
//                    ->setArticleNumber("1")
//                    ->setQuantity(1)
//                    ->setAmountExVat(188.68)
//                    ->setDescription("Bok")
//                    ->setVatPercent(6)
//                )
//                ->addDiscount(\WebPayItem::fixedDiscount()
//                    ->setDiscountId("1")
//                    ->setAmountIncVat(100.00)
//                    ->setUnit("st")
//                    ->setDescription("testBuildCardPaymentWithDiffrentProductVatAndDiscount")
//                    ->setName("Fixed")
//                )
//                ->setCountryCode("SE")
//                ->setClientOrderNumber("33")
//                ->setOrderDate("2012-12-12")
//                ->setCurrency("SEK")
//                ->usePayPageCardOnly() // PayPageObject
//                ->setReturnUrl("http://myurl.se")
//                ->setPayPageLanguage("sv")
//                ->getPaymentForm();
//        $xmlMessage = new \SimpleXMLElement($form->xmlMessage);
//
//        $this->assertEquals('40000', $xmlMessage->amount);
//        $this->assertEquals('5706', $xmlMessage->vat);
//    }
    
    public function testFormatRelativeDiscountRows() {
        $order = WebPay::createOrder();
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(4)
                ->setVatPercent(25)
                ->setQuantity(1)
                )
            ->addDiscount(WebPayItem::relativeDiscount()
                ->setDiscountId("0")
                ->setName("Tess")
                ->setDescription("Tester")
                ->setDiscountPercent(10)
                ->setUnit("st")
                );

        $formatter = new Svea\WebServiceRowFormatter($order);
        $newRows = $formatter->formatRows();
        $newRow = $newRows[1];

        $this->assertEquals("0", $newRow->ArticleNumber);
        $this->assertEquals("Tess: Tester", $newRow->Description);
        $this->assertEquals(-0.4, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);
        $this->assertEquals(0, $newRow->DiscountPercent);
        $this->assertEquals(1, $newRow->NumberOfUnits);
        $this->assertEquals("st", $newRow->Unit);
    }
}
