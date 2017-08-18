<?php
class Mage_SwapMachine_Model_Observer {


    public function view($observer) {
        //$observer contains data passed from when the event was triggered.
        //You can use this data to manipulate the order data before it's saved.
        
        //Mage::log($observer);
        
        /////////////////////////////////////////////////////////////////////////////////
        $pdata = Mage::app()->getRequest()->getParams();
         
        if (Mage::app()->getRequest()->isPost()) {

            if(Mage::getSingleton('customer/session')->isLoggedIn()){
                    
                if (isset($pdata['swapId']) && !Mage::getSingleton('core/session')->getSwapId()) {
                
                    $swapId = $_POST['swapId'];
                    //die("YEAH MAN The value is : " . $swapId);$postOrderId
                    
                    if ($swapId<>"") {
                        if (strpos($swapId, "0") !== false) {
                            Mage::getSingleton('core/session')->setSwapId(Mage::getSingleton('core/session')->getSwapId0());
                        }
                        if (strpos($swapId, "1") !== false) {
                            Mage::getSingleton('core/session')->setSwapId(Mage::getSingleton('core/session')->getSwapId1());
                        }
                        if (strpos($swapId, "2") !== false) {
                            Mage::getSingleton('core/session')->setSwapId(Mage::getSingleton('core/session')->getSwapId2());
                        }
                        if (strpos($swapId, "3") !== false) {
                            Mage::getSingleton('core/session')->setSwapId(Mage::getSingleton('core/session')->getSwapId3());
                        }
                        if (strpos($swapId, "4") !== false) {
                            Mage::getSingleton('core/session')->setSwapId(Mage::getSingleton('core/session')->getSwapId4());
                        }
                        if (strpos($swapId, "5") !== false) {
                            Mage::getSingleton('core/session')->setSwapId(Mage::getSingleton('core/session')->getSwapId5());
                        }
                        if (strpos($swapId, "6") !== false) {
                            Mage::getSingleton('core/session')->setSwapId(Mage::getSingleton('core/session')->getSwapId6());
                        }                        
                        
                    }
                    
                } //if isset $pdata['swapId'] //entity_id
                
                
		if (isset($pdata['returnOrder'])) {
                    $postOrderId = $pdata['returnOrder'];
                    $collection =  Mage::getModel("sales/order")->getCollection()->addFieldToFilter('increment_id',$postOrderId);
                    
                    Mage::getSingleton('core/session')->setPostCollection($postOrderId);
                    
                     Mage::app()->getResponse()
                        ->setRedirect('/index.php/rmasystem/index/new/')
                        ->sendResponse();
                }
                
                
                //if other post datas
                
            } ////if POST 
            
        }/**/
        /////////////////////////////////////////////////////////////////////////////////
        //die("1 C'mon this needs to stop!");
    }
    
    public function successAction($observer)
    {
        
        if(Mage::getSingleton('customer/session')->isLoggedIn()){
        
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $readConnection = $resource->getConnection('core_read');
            $table = $resource->getTableName('recurly_cloth');
            $table_cloth_qbarz = $resource->getTableName('cloth_questionnaire');
            $sales_flat_order = $resource->getTableName('sales_flat_order');
            $customer = Mage::getSingleton('customer/session')->getCustomer(); 
            $customer_id_var = $customer->getId();  
            $email =  $customer->getEmail();
            $box_shipto_firstname = $customer->getFirstname(); //$this->getRequest()->getParam('shipto_firstname');
            $box_shipto_lastname = $customer->getLastname(); 
            
            $order = Mage::getModel('sales/order');
            $incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
            $order->loadByIncrementId($incrementId);

            $items = $order->getAllVisibleItems();
            $products = [];
            $current_date = date('Y-m-d');
            
            foreach($items as $i){
                $products[] = $i;
            };
            
            if($products[0]){
                $ProductId = $products[0]->getProduct()->getId();
                //e"CHECK 3 ProductID" . $ProductId);
                if($ProductId == 1477 || $ProductId == 1478 || $ProductId == 1479 || $ProductId == 1480){
                    $account_code = $products[0]->getName();
                    
                    $query = "UPDATE {$table} 
                    SET customer_firstname='{$box_shipto_firstname}', customer_lastname='{$box_shipto_lastname}', recurly_email = '{$email}', cloth_plan_name='{$account_code}', create_at='{$current_date}' WHERE customer_id={$customer_id_var}";
                    
                    $update_cloth_qbarz_query = "UPDATE {$table_cloth_qbarz} SET recurly_email = '{$email}', plan_name = '{$account_code}' WHERE customer_id={$customer_id_var}";
                    
                    $update_sales_flat_order_query = "UPDATE {$sales_flat_order} SET ext_order_id = 'plan' WHERE increment_id={$incrementId}";
                    //Mage::log($update_sales_flat_order_query);
                    
                    $writeConnection->query($update_sales_flat_order_query);
                    $writeConnection->query($update_cloth_qbarz_query);
                    $writeConnection->query($query);
                    
                    
                    $order->setShippingMethod('plan');
                } else {
                
                    $update_sales_flat_order_query = "UPDATE {$sales_flat_order} SET ext_order_id = 'purchase' WHERE increment_id={$incrementId}";
                    $writeConnection->query($update_sales_flat_order_query);
                }
            }
            
        } else {
                
            Mage::app()->getResponse()
                ->setRedirect('/customer/account/login')
                ->sendResponse();
        
        }
        
    }

}
?>
