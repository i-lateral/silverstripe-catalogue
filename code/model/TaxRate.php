<?php

/**
 * A tax rate can be added to a product and allows you to map a product
 * to a percentage of tax.
 * 
 * If added to a product, the tax will then be added to the price
 * automatically. 
 * 
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class TaxRate extends DataObject {
    
    /**
     * @config
     */
    private static $db = array(
        "Title" => "Varchar",
        "Amount"=> "Decimal",
        "Code"  => "Varchar"
    );
    
    public function getCMSValidator() {
        return new RequiredFields(array(
            "Title",
            "Amount"
        ));
    }
    
    public function requireDefaultRecords() {
        
        // If no tax rates, setup some defaults
        if(!TaxRate::get()->exists()) {
            $vat = TaxRate::create();
            $vat->Title = "VAT";
            $vat->Amount = 20;
            $vat->Code = "T1";
            $vat->write();
            DB::alteration_message('VAT tax rate created.', 'created');
            
            $reduced = TaxRate::create();
            $reduced->Title = "Reduced rate";
            $reduced->Amount = 5;
            $reduced->Code = "T2";
            $reduced->write();
            DB::alteration_message('Reduced tax rate created.', 'created');
            
            $zero = TaxRate::create();
            $zero->Title = "Zero rate";
            $zero->Amount = 0;
            $zero->Code = "T4";
            $zero->write();
            DB::alteration_message('Zero tax rate created.', 'created');
        }
        
        parent::requireDefaultRecords();
    }
    
}
