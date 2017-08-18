<?php
class Mage_SwapMachine_Helper_Data extends Mage_Core_Helper_Abstract
{   

    
    function deleteFromClosetOrder(){  
    
        //find order with $theOrderId
        $theOrderId = Mage::getSingleton('core/session')->getTargetOrder();
        $swapId = Mage::getSingleton('core/session')->getSwapId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($theOrderId);
        $OrderGroup = Mage::getSingleton('core/session')->getOrderGroup();
    
        $items = $order->getAllItems();    
        $counter = 0;
        $itemopts = NULL;
        $checker = false;
        
        $closetOrder = array();
        
        foreach ($items as $item){
        
            $base_grand_total = $order->getBaseGrandTotal();
            $base_subtotal = $order->getBaseSubtotal();
            $base_tva = $order->getBaseTaxAmount();
            $grand_total = $order->getGrandTotal();
            $subtotal = $order->getSubtotal();
            $tva = $order->getTaxAmount();
            $base_subtotal_incl_tax = $order->getBaseSubtotalInclTax();
            $subtotal_incl_tax = $order->getSubtotalInclTax();
            $total_item_count = $order->getTotalItemCount();
        
            if ($checker == false) { 
                //$closetOrder[] .= $item->getId();
            }

            if($item->getProductId()==$swapId && $counter == 0){
            //print_r($item);
                //$checker = true;
                $counter =1;
                $item_price = $item->getPrice();
                $item_tva = $item->getTaxAmount();
                $itemopts = $item->getData('product_options');
                $item->delete();
                $order->setBaseGrandTotal($base_grand_total-$item_price-$item_tva);
                $order->setBaseSubtotal($base_subtotal-$item_price);
                $order->setBaseTaxAmount($base_tva-$item_tva);
                $order->setGrandTotal($grand_total-$item_price-$item_tva);
                $order->setSubtotal($subtotal-$item_price);
                $order->setTaxAmount($tva-$item_tva);
                $order->setBaseSubtotalInclTax($base_subtotal_incl_tax-$item_price);
                $order->setSubtotalInclTax($subtotal_incl_tax-$item_price);
                $order->setTotalItemCount(count($items)-1);
                $order->save(); 
                
                $stockProductId = $swapId;
        
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($stockProductId);
                if ($stockItem->getId() > 0) {
                        $stockQty = $stockItem->getQty() + 1;
                        $stockItem->setQty($stockQty);
                        $stockItem->setIsInStock((int)($stockQty > 0));
                        $stockItem->save();
                }
            }
        }
        
        $changeDate = date('l jS \of F Y h:i:s A');
        $allitemopt= "";
        
        $order->save();
        
    }
    
    function addToClosetOrder($theParams){  
        
        $swapId = Mage::getSingleton('core/session')->getSwapId();
        $SwapWithId = Mage::getSingleton('core/session')->getSwapWithId();
        $theOrderId = Mage::getSingleton('core/session')->getTargetOrder();
        $OrderGroup = Mage::getSingleton('core/session')->getOrderGroup();
        $trueId = $SwapWithId;
        $order = Mage::getModel('sales/order')->loadByIncrementId($theOrderId);
    
        $product = Mage::getModel('catalog/product')->getCollection()
        ->addAttributeToFilter('entity_id', $SwapWithId)
        ->addAttributeToSelect('*')
        ->getFirstItem();            
        
        if (isset($theParams['super_attribute'])) {
            $product = $product->getTypeInstance(true)->getProductByAttributes($theParams['super_attribute'], $product);
            $trueId = $product->getId();
            $theParentSku = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            if (sizeof($theParentSku) > 0 && !empty($theParentSku)) {
                $theParent = Mage::getModel('catalog/product')->load($theParentSku[0]);
                $pricer = $theParent->getPrice();
                $product->setPrice($pricer);
                $qty = 1;
                $stockData = $product->getStockData();

                if (!$stockData) {
                    $product->getPriceModel()->getPrice($theParent);
                    $stockData = array(
                        'manage_stock' => 1,
                        'is_in_stock'  => 1,
                        'qty'          => 1
                    );
                    $product->setStockData($stockData);
                }
                
            }
        
        } else {
        
            $product = Mage::getModel('catalog/product')->load($SwapWithId);
            $stockData = $product->getStockData();
            
            if (!$stockData || $stockData ==1) {
                $product->getPriceModel()->getPrice($product);
                $pricer = $product->getPrice();
                $product->setPrice($pricer);
                $stockData = array(
                    'manage_stock' => 1,
                    'is_in_stock'  => 1,
                    'qty'          => 1
                );
                $product->setStockData($stockData);
            }
        }
        
        $base_grand_total2 = $order->getBaseGrandTotal();
        $base_subtotal2 = $order->getBaseSubtotal();
        $base_tva2 = $order->getBaseTaxAmount();
        $grand_total2 = $order->getGrandTotal();
        $subtotal2 = $order->getSubtotal();
        $tva2 = $order->getTaxAmount();
        $base_subtotal_incl_tax2 = $order->getBaseSubtotalInclTax();
        $subtotal_incl_tax2 = $order->getSubtotalInclTax();
        $total_item_count2 = $order->getTotalItemCount();
        
        $pureProduct = Mage::getModel('catalog/product')->load($SwapWithId);
        $original_price = $pureProduct->getPrice();
        
        /* START */
        
        $quoteId = Mage::getSingleton('checkout/session')->getQuoteId(); //checks out
        $quotey = Mage::getSingleton('sales/quote')->load($quoteId); //checks out

        $fakeQuote = clone $quotey;
        $fakeQuote->setId(null);

        //$product = Mage::getModel('catalog/product')->load($SwapWithId);
        
        $item = Mage::getModel('sales/quote_item')->setQuote($fakeQuote)->setProduct($product);
        $item->setAllItems(array($product));
        $item->getProduct()->setProductId($product->getEntityId());
        $item->setQty(1);

        $item->getQuote()->setData('items_collection', array($item));

        
        $ruleBook = Mage::getModel('salesrule/rule')->getResourceCollection()->addFieldToFilter('is_active', 1);
       
        foreach($ruleBook as $rule){
                    
            $ruleAct = Mage::getModel('salesrule/rule')->load($rule->getId());
        
            Mage::log($ruleAct); 
        
            if (!$ruleAct->getConditions()->validate($item)) {
            
                $action = $ruleAct->getData('simple_action');
                
                if($action == "by_percent"){
                    $tt = floatval($ruleAct->getData('discount_amount'));
                    Mage::log("Rule Act" . $tt);
                    $tta = floatval(($tt/ 100));
                    //Mage::log("Rule Act2" . $tta);
                    
                    $original_price2 = floatval($original_price);
                    
                    
                    $reducedPrice = floatval($original_price2) - (floatval($original_price2) * $tta);
                    Mage::log("Original Price" . $original_price2);
                    Mage::log("Reduced Price" . $reducedPrice);
                    
                }
                if($action == "by_fixed"){
                    $by_fixed = $ruleAct->getData('discount_amount');
                }
                if($action == "cart_fixed"){
                    //$cart_fixed = ;
                }
                if($action == "by_x_get_y"){
                    //$by_x_get_y = ;
                }
                if($action == "get_percent_x_max_y"){
                    //$reducedPrice = ;
                }
                
                
                $ruleAct->setIsValid(true);
                $ruleAct->getActions()->validate($item);
            }
            
        }
        
        $orderItem = Mage::getModel('sales/convert_quote')->itemToOrderItem($item)->setOrderID($OrderGroup);
        
        $item_price2 = $product->getPrice();
        $item_tva2 = $product->getTaxAmount();
        $order->setBaseGrandTotal($base_grand_total2+$item_price2+$item_tva2);
        $order->setBaseSubtotal($base_subtotal2+$item_price2);
        $order->setBaseTaxAmount($base_tva2+$item_tva2);
        $order->setGrandTotal($grand_total2+$item_price2+$item_tva2);
        $order->setSubtotal($subtotal2+$item_price2);
        $order->setTaxAmount($tva2+$item_tva2);
        $order->setBaseSubtotalInclTax($base_subtotal_incl_tax2+$item_price2);
        $order->setSubtotalInclTax($subtotal_incl_tax2+$item_price2);
        $order->setTotalItemCount($order->getTotalItemCount()+1);/**/
        
        $whats_the_difference = $original_price - $reducedPrice;
        
        $found = $product->getName();
        $allitemopt2 = "";
        
        if (isset($found) && $found <> "") {
            
            $store = Mage::getSingleton('core/store')->load(1);
            $quote = Mage::getModel('sales/quote')->getCollection()->addFieldToFilter("entity_id", $OrderGroup)->getFirstItem();
            
            $quoteItem = Mage::getModel('sales/quote_item')->setProduct($product)->setQuote($quote)->setQty($qty);
            
            $orderItem = Mage::getModel('sales/convert_quote')->itemToOrderItem($quoteItem)->setOrderID($OrderGroup);
            
            $orderItem->setPrice($pricer);
            $orderItem->setDiscountAmount($whats_the_difference); ///Here
            $orderItem->setOriginalPrice($pricer);
            $orderItem->setBaseSubtotal($pricer);
            $orderItem->setSubtotal($pricer);
            $orderItem->setRowTotal($original_price);
            $orderItem->setBasePrice($pricer);
            
            $orderItem->save($OrderGroup);/**/
        }
        
        $changeDate = date('l jS \of F Y h:i:s A');
        
        $target_product = Mage::getModel('catalog/product')->load($SwapWithId);
        
        $target_name = $target_product->getData('sku');
        $swap_name = $product->getData('sku');
        
        $note = "On " . $changeDate . ", the customer swaped item " . $target_name  . ", with item " . $swap_name . ". ";
        
        $order->addStatusHistoryComment($note);
            
        $order->save(); /**/
        
        $stockProductId = $trueId;
        
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($stockProductId);
        
        if ($stockItem->getId() > 0) {
                $stockQty = $stockItem->getQty() - 1;
                $stockItem->setQty($stockQty);
                $stockItem->setIsInStock((int)($stockQty > 0));
                $stockItem->save();
        }
        
    } //
    
    function cancelOrder($orderId){
       // Mage:log("Order ID: " . $orderId);
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        Mage::log($order);
        
        if ($order->canCancel()) {
            try {
                $order->cancel();

                // remove status history set in _setState
                $order->getStatusHistoryCollection(true);

                // do some more stuff here
                // ...

                $order->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /* Change status functions */
    function changeStatusToClosetConfirmed($orderId){  
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $order->setStatus('closet_confirmed');
            
        $changeDate = date('l jS \of F Y h:i:s A');    
        $order->addStatusHistoryComment("On {$changeDate}, the order was confirmed.");
        $order->setCreatedAt(strtotime("now"))->save();
        $order->save();
        
	$customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
	
	$customerOrders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id',$customer_id)->addFieldToFilter( 'ext_order_id', array('null' => true));
	
	$x = 0;
	
	foreach($customerOrders as $customerOrder){
            if($x==0){
                Mage::log($customerOrder);
                mage::log($customerOrder->getIncrementId());
            }
            
            if(strtolower($customerOrder->getStatusLabel()) == 'repick request' && $orderId <> $customerOrder->getIncrementId()){
                $this->cancelOrder($customerOrder->getIncrementId());
            }
            if(strtolower($customerOrder->getStatusLabel()) == 'revert to original' && $orderId <> $customerOrder->getIncrementId()){
                $this->cancelOrder($customerOrder->getIncrementId());
            }
            if(strtolower($customerOrder->getStatusLabel()) == 'revert to mulligan' && $orderId <> $customerOrder->getIncrementId()){
                $this->cancelOrder($customerOrder->getIncrementId());
            }
            if(strtolower($customerOrder->getStatusLabel()) == 'closet confirmed' && $orderId <> $customerOrder->getIncrementId()){
                $this->cancelOrder($customerOrder->getIncrementId());
            }
            $x++;
        }	
	
    }
		
    function requestRepick($orderId, $myJsonString = ""){
        str_replace("[", "", $myJsonString);
        str_replace("]", "", $myJsonString);
        str_replace("\"", "", $myJsonString);
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $order->setStatus('repick_request');
            
        $changeDate = date('l jS \of F Y h:i:s A');    
        $order->addStatusHistoryComment("On {$changeDate}, the order was repick requested. And wanted to replace {$myJsonString} ");
        $order->save();
    }
		
    function loadMulligan($orderId){
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        //$order->setStatus('revert_mulligan');
            
        $changeDate = date('l jS \of F Y h:i:s A'); 
        $order->setCreatedAt(strtotime("now"))->save();    
        $order->addStatusHistoryComment("On {$changeDate}, the order was reverted to the Mulligan order.");
        $order->save();
    }
		
    function loadOriginal($orderId){
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        //$order->setStatus('revert_original');
        
        //print_r($order);
            
        $changeDate = date('l jS \of F Y h:i:s A');  
        $order->setCreatedAt(strtotime("now"))->save(); 
        $order->addStatusHistoryComment("On {$changeDate}, the order was reverted to the Orignal order.");
        $order->save();
    }

}
?>
