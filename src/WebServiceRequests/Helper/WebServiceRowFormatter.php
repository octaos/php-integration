<?php
namespace Svea;

/**
 * Helpclass for formatting orderrows in the right format for WebService soap-calls
 * 
 * @author Anneli Halld'n, Daniel Brolund, Kristian Grossman-Madsen for Svea Webpay
 * @package WebServiceRequests/Helper
 */
class WebServiceRowFormatter {

    private $order;
    private $totalAmountExVat;
    private $totalAmountIncVat;
    private $totalVatAsAmount;
    private $totalVatAsPercent;

    private $totalAmountPerVatRate;     // used to calculate "mean vat" split into given vat rates

    private $newRows;

    /**
     * @param type $order
     */
    public function __construct($order) {
        $this->order = $order;
    }

    public function formatRows() {
        $this->newRows = array();

        $this->calculateTotals();

        $this->formatOrderRows();
        $this->formatShippingFeeRows();
        $this->formatInvoiceFeeRows();
        $this->formatFixedDiscountRows();
        $this->formatRelativeDiscountRows();

        return $this->newRows;
    }

    private function calculateTotals() {
        $this->totalAmountExVat = 0;
        $this->totalVatAsAmount = 0;

        $this->totalAmountPerVatRate = array();

        foreach ($this->order->orderRows as $product) {
            $vatPercentAsCeroDecimal = isset($product->vatPercent) ? $product->vatPercent * 0.01 : "";

            if (isset($product->vatPercent) && isset($product->amountExVat)) {                
                $this->totalAmountExVat += $product->amountExVat * $product->quantity;
                $this->totalVatAsAmount += ($vatPercentAsCeroDecimal * $product->amountExVat) * $product->quantity;

                // add to or create cummulative amount for this tax rate 
                if( isset($this->totalAmountPerVatRate[$product->vatPercent]) ) {
                    $this->totalAmountPerVatRate[$product->vatPercent] += ($product->amountExVat * $product->quantity * (1+$vatPercentAsCeroDecimal));
                } else {
                    $this->totalAmountPerVatRate[$product->vatPercent] = ($product->amountExVat * $product->quantity * (1+$vatPercentAsCeroDecimal));
                }

            } elseif (isset($product->vatPercent) && isset($product->amountIncVat)) {
                $this->totalAmountIncVat += $product->amountIncVat * $product->quantity;
                $this->totalVatAsAmount += (($vatPercentAsCeroDecimal /(1 + $vatPercentAsCeroDecimal)) * $product->amountIncVat) * $product->quantity;

                // add to or create cummulative amount for this tax rate 
                if( isset($this->totalAmountPerVatRate[$product->vatPercent]) ) {
                    $this->totalAmountPerVatRate[$product->vatPercent] += ($product->amountIncVat * $product->quantity);
                } else {
                    $this->totalAmountPerVatRate[$product->vatPercent] = ($product->amountIncVat * $product->quantity);
                }

            } else {
                $this->totalAmountIncVat += $product->amountIncVat * $product->quantity;
                $this->totalAmountExVat += $product->amountExVat * $product->quantity;
                $this->totalVatAsAmount += ($product->amountIncVat - $product->amountExVat)* $product->quantity;

                // add to or create cummulative amount for this tax rate 
                $vatRate = round((($product->amountIncVat / $product->amountExVat)-1) * 100);
                if( isset($this->totalAmountPerVatRate[$vatRate]) ) {
                    $this->totalAmountPerVatRate[$vatRate] += ($product->amountExVat * $product->quantity * (1+$vatPercentAsCeroDecimal));
                } else {
                    $this->totalAmountPerVatRate[$vatRate] = ($product->amountExVat * $product->quantity * (1+$vatPercentAsCeroDecimal));
                }
            }
        }
        $this->totalAmountIncVat = $this->totalAmountExVat + $this->totalVatAsAmount;
        $this->totalAmountExVat = $this->totalAmountIncVat - $this->totalVatAsAmount;
        if ($this->totalAmountExVat > 0) {
            $this->totalVatAsPercent = $this->totalVatAsAmount / $this->totalAmountIncVat; //e.g. 0,20 if percentage 20
        }
    }

    private function formatOrderRows() {
        foreach ($this->order->orderRows as $row) {
            $orderRow = new SveaOrderRow();
            if (isset($row->articleNumber)) {
                $orderRow->ArticleNumber = $row->articleNumber;
            }
            if (isset($row->description)) {
                $orderRow->Description = (isset($row->name) ? $row->name . ': ' : "") . $row->description;
            } elseif (isset($row->name) && isset($row->description) == false) {
                $orderRow->Description = $row->name;
            }
            if (isset($row->unit)) {
                $orderRow->Unit = $row->unit;
            }
            $orderRow->DiscountPercent = (isset($row->discountPercent) ? $row->discountPercent : 0);
            $orderRow->NumberOfUnits = $row->quantity;
            if (isset($row->vatPercent) && isset($row->amountExVat)) {
                $orderRow->PricePerUnit = $row->amountExVat;
                $orderRow->VatPercent = round($row->vatPercent);
            } elseif (isset($row->vatPercent) && isset($row->amountIncVat)) {
                $orderRow->PricePerUnit = $row->amountIncVat / ((0.01 * $row->vatPercent) + 1);
                $orderRow->VatPercent = round($row->vatPercent);
            } else {
                $orderRow->PricePerUnit = number_format($row->amountExVat, 2, '.', '');
                $orderRow->VatPercent = round((($row->amountIncVat / $row->amountExVat)-1) * 100);
            }

            $this->newRows[] = $orderRow;
        }
    }

    private function formatShippingFeeRows() {
        if (!isset($this->order->shippingFeeRows)) {
            return;
        }

        foreach ($this->order->shippingFeeRows as $row) {
            $orderRow = new SveaOrderRow();
            if (isset($row->shippingId)) {
                $orderRow->ArticleNumber = $row->shippingId;
            }
            if (isset($row->description)) {
                $orderRow->Description = (isset($row->name) ? $row->name . ': ' : "") . $row->description;
            } elseif (isset($row->name) && isset($row->description) == false) {
                $orderRow->Description = $row->name;
            }
            if (isset($row->unit)) {
                $orderRow->Unit = $row->unit;
            }
            $orderRow->DiscountPercent = (isset($row->discountPercent) ? $row->discountPercent : 0);
            $orderRow->NumberOfUnits = 1; //only one fee per row
           if (isset($row->vatPercent) && isset($row->amountExVat)) {
                $orderRow->PricePerUnit = $row->amountExVat;
                $orderRow->VatPercent = round($row->vatPercent);
            } elseif (isset($row->vatPercent) && isset($row->amountIncVat)) {
                $orderRow->PricePerUnit = $row->amountIncVat / ((0.01 * $row->vatPercent) + 1);
                $orderRow->VatPercent = round($row->vatPercent);
            } else {
                $orderRow->PricePerUnit = number_format($row->amountExVat, 2, '.', '');
                $orderRow->VatPercent = round((($row->amountIncVat / $row->amountExVat)-1) * 100);
            }
            $this->newRows[] = $orderRow;
        }
    }

    private function formatInvoiceFeeRows() {
        if (!isset($this->order->invoiceFeeRows)) {
            return;
        }

        foreach ($this->order->invoiceFeeRows as $row) {
            $orderRow = new SveaOrderRow();
            $orderRow->ArticleNumber = "";
            if (isset($row->description)) {
                $orderRow->Description = (isset($row->name) ? $row->name . ': ' : "") . $row->description;
            } elseif (isset($row->name) && isset($row->description) == false) {
                $orderRow->Description = $row->name;
            }
            if (isset($row->unit)) {
                $orderRow->Unit = $row->unit;
            }
            $orderRow->DiscountPercent = isset($row->discountPercent) ? $row->discountPercent : 0;
            $orderRow->NumberOfUnits = 1; //only one fee per row
            if (isset($row->vatPercent) && isset($row->amountExVat)) {
                $orderRow->PricePerUnit = $row->amountExVat;
                $orderRow->VatPercent = round($row->vatPercent);
            } elseif (isset($row->vatPercent) && isset($row->amountIncVat)) {
                $orderRow->PricePerUnit = $row->amountIncVat / ((0.01 * $row->vatPercent) + 1);
                $orderRow->VatPercent = round($row->vatPercent);
            } else {
                $orderRow->PricePerUnit = number_format($row->amountExVat, 2, '.', '');
                $orderRow->VatPercent = round((($row->amountIncVat / $row->amountExVat)-1) * 100);
            }
            $this->newRows[] = $orderRow;
        }
    }

    private function formatFixedDiscountRows() {
        if (!isset($this->order->fixedDiscountRows)) {
            return;
        }
        foreach ($this->order->fixedDiscountRows as $row) {
            // only amountIncVat (i.e. amount) is set:   
            if( isset($row->amount) && !isset($row->vatPercent) && !isset($row->amountExVat) ) {
                   
                foreach( $this->totalAmountPerVatRate as $vatRate => $amountAtThisVatRate ) {

                    $orderRow = new SveaOrderRow();   
                                    
                    if (isset($row->discountId)) {
                        $orderRow->ArticleNumber = $row->discountId;
                    }
                    if (isset($row->description)) {
                        $orderRow->Description = (isset($row->name) ? $row->name . ': ' : "") . $row->description;
                    } elseif (isset($row->name) && isset($row->description) == false) {
                        $orderRow->Description = $row->name;
                    }
                    if( sizeof($this->totalAmountPerVatRate)>1 ) {  // add tax rate for split discount to description
                        $orderRow->Description .= " (".$vatRate."%)";
                    }
                    if (isset($row->unit)) {
                        $orderRow->Unit = $row->unit;
                    }
                    $orderRow->DiscountPercent = 0; //no discount on discount
                    $orderRow->NumberOfUnits = 1; //only one discount per row

                    //calculate discount
                    $discountAtThisVatRateIncVat = $row->amount * ($amountAtThisVatRate / $this->totalAmountIncVat );
                    $discountAtThisVatRateExVat = $discountAtThisVatRateIncVat - $discountAtThisVatRateIncVat * (1-(1/(1+$vatRate/100)));   // calculate "reverse vat", i.e. 25% => 20%

                    $orderRow->PricePerUnit = - number_format($discountAtThisVatRateExVat, 2,'.','');
                    $orderRow->VatPercent = $vatRate;
                    $this->newRows[] = $orderRow;    
                }
            }
            
            // only amountIncVat (i.e. amount) and vatPercent is set, so we use that vatPercent:   
            if( isset($row->amount) && isset($row->vatPercent) && !isset($row->amountExVat) ) {

                    $orderRow = new SveaOrderRow();  
                    
                    if (isset($row->discountId)) {
                        $orderRow->ArticleNumber = $row->discountId;
                    }
                    if (isset($row->description)) {
                        $orderRow->Description = (isset($row->name) ? $row->name . ': ' : "") . $row->description;
                    } elseif (isset($row->name) && isset($row->description) == false) {
                        $orderRow->Description = $row->name;
                    }

                    if (isset($row->unit)) {
                        $orderRow->Unit = $row->unit;
                    }
                    $orderRow->DiscountPercent = 0; //no discount on discount
                    $orderRow->NumberOfUnits = 1; //only one discount per row
        
                    //calculate discount
                    $vatRate = $row->vatPercent;
                    $discountAtThisVatRateIncVat = $row->amount;
                    $discountAtThisVatRateExVat = $discountAtThisVatRateIncVat - $discountAtThisVatRateIncVat * (1-(1/(1+$vatRate/100)));   // calculate "reverse vat", i.e. 25% => 20%

                    $orderRow->PricePerUnit = - number_format($discountAtThisVatRateExVat, 2,'.','');
                    $orderRow->VatPercent = $vatRate;
                    
                    $this->newRows[] = $orderRow; 
            }
            // only amountIncVat (i.e. amount) and vatPercent is set, so we use that vatPercent:   
            if( !isset($row->amount) && isset($row->vatPercent) && isset($row->amountExVat) ) {

                    $orderRow = new SveaOrderRow();  
                    
                    if (isset($row->discountId)) {
                        $orderRow->ArticleNumber = $row->discountId;
                    }
                    if (isset($row->description)) {
                        $orderRow->Description = (isset($row->name) ? $row->name . ': ' : "") . $row->description;
                    } elseif (isset($row->name) && isset($row->description) == false) {
                        $orderRow->Description = $row->name;
                    }

                    if (isset($row->unit)) {
                        $orderRow->Unit = $row->unit;
                    }
                    $orderRow->DiscountPercent = 0; //no discount on discount
                    $orderRow->NumberOfUnits = 1; //only one discount per row
        
                    //calculate discount
                    $vatRate = $row->vatPercent;
                    $discountAtThisVatRateExVat = $row->amountExVat;

                    $orderRow->PricePerUnit = - number_format($discountAtThisVatRateExVat, 2,'.','');
                    $orderRow->VatPercent = $vatRate;
                    
                    $this->newRows[] = $orderRow; 
            }
        }
    }

    private function formatRelativeDiscountRows() {
        if (!isset($this->order->relativeDiscountRows)) {
            return;
        }

        foreach ($this->order->relativeDiscountRows as $row) {
            $orderRow = new SveaOrderRow();
            if (isset($row->discountId)) {
                $orderRow->ArticleNumber = $row->discountId;
            }
            if (isset($row->description)) {
                $orderRow->Description = (isset($row->name) ? $row->name . ': ' : "") . $row->description;
            } elseif (isset($row->name) && isset($row->description) == false) {
                $orderRow->Description = $row->name;
            }
            if (isset($row->unit)) {
                $orderRow->Unit = $row->unit;
            }
            $pricePerUnitExMoms = round($this->totalAmountExVat * ($row->discountPercent * 0.01), 2);
            $orderRow->DiscountPercent = 0; //no discount on discount
            $orderRow->NumberOfUnits = 1; //only one discount per row
            $orderRow->PricePerUnit = - number_format($pricePerUnitExMoms,2,'.',''); //Discountpercent on toatal price ex vat.
            $orderRow->VatPercent = round((($this->totalVatAsAmount * ($row->discountPercent * 0.01))/$pricePerUnitExMoms)*100,2);//round((($this->totalVatAsAmount * ($row->discountPercent * 0.01))/$orderRow->PricePerUnit) * 100,2); //Discountpercent on total vatamount
            $this->newRows[] = $orderRow;
        }
    }
}
