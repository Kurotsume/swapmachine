<?php
require_once(Mage::getBaseDir()."/app/code/core/Mage/Cms/controllers/PageController.php");
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Cms
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * CMS Page controller ZH
 *
 * @category   Mage
 * @package    Mage_Cms
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_SwapMachine_Frontend_PageController extends Mage_Cms_PageController
{
    /**
     * View CMS page action
     *
     */
    public function viewAction()
    {
        $pageId = $this->getRequest()->getParam('page_id', $this->getRequest()->getParam('id', false));
            
        //die("YEAH MAN The value is : " . $pageId); //pageId = 128
        echo $pageId . "</br>";
        
        if ($pageId == 128) {
         
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                
                $resource = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');
                $readConnection = $resource->getConnection('core_read');
                $table = $resource->getTableName('recurly_cloth');
                $table_cloth_qbarz = $resource->getTableName('cloth_questionnaire');
                $customer = Mage::getSingleton('customer/session')->getCustomer(); 
                $customer_id_var = Mage::getSingleton('customer/session')->getCustomer()->getId();  
                
                $select_cloth_qbarz_query = "SELECT customer_id FROM {$table_cloth_qbarz} WHERE customer_id={$customer_id_var}";
                $select_cloth_plan_query = "SELECT customer_id FROM {$table} WHERE customer_id={$customer_id_var}"; 
                
                $customer_ids = $readConnection->fetchAll($select_cloth_qbarz_query);
                $customer_ids_plan = $readConnection->fetchAll($select_cloth_plan_query); 
            
                if($customer_ids_plan){ //if($customer_ids_plan && $customer_ids){ ///NOW $customer_ids
                            
                        
                    if ($this->getRequest()->isPost()) {
                    
                        if (Mage::getSingleton('core/session')->getSwapId() && isset($_POST['SwapWithId'])) {
                            
                            Mage::getSingleton('core/session')->setSwapWithId($_POST['SwapWithId']);
                            
                            /////////////////////////////////////////////////////////////////
                            //DELETION OF THE ORIGINAL PRODUCT                              /
                            /////////////////////////////////////////////////////////////////
                            
                            Mage::helper('SwapMachine')->deleteFromClosetOrder();
                            
                            
                            /////////////////////////////////////////////////////////////////
                            //ADDING PRODUCT TO THE ORDER                                   /
                            /////////////////////////////////////////////////////////////////
                            
                            $params = $this->getRequest()->getParams();
                            Mage::helper('SwapMachine')->addToClosetOrder($params);
                            
                            
                            
                            /////////////////////////////////////////////////////////////////
                            //REMOVE SESSION VARIABLES                                      /
                            /////////////////////////////////////////////////////////////////
                            
                            Mage::getSingleton('core/session')->unsTargetOrder();
                            Mage::getSingleton('core/session')->unsSwapId();
                            Mage::getSingleton('core/session')->unsSwapWithId();
                            Mage::getSingleton('core/session')->unsOrderGroup();
                            /* */
                        } 
                        
                        if (isset($_POST['closetConfirm']) || $this->getRequest()->getParam('closetConfirm')) {
                            $orderId = $this->getRequest()->getParam('closetConfirm');
                            Mage::helper('SwapMachine')->changeStatusToClosetConfirmed($orderId);
                        
                        } elseif (isset($_POST['mulligan'])) {
                            $mully = Mage::getSingleton('core/session')->getMulliganOrder();
                            Mage::helper('SwapMachine')->loadMulligan($mully);
                            
                        } elseif (isset($_POST['original'])) { 
                            $orig = Mage::getSingleton('core/session')->getOriginalOrder();
                            Mage::helper('SwapMachine')->loadOriginal($orig);
                            
                        } elseif (isset($_POST['repick'])) {
                            $orderId = $this->getRequest()->getParam('repick');
                            $myJsonString = $this->getRequest()->getParam('values');
                            Mage::helper('SwapMachine')->requestRepick($orderId,$myJsonString);
                            
                        } elseif (isset($_POST['repurchase'])) { 
                            
                            $orderId = $this->getRequest()->getParam('repurchase');
                            
                            $cart = Mage::getModel('checkout/cart');                
                            $cart->truncate()->save(); // remove all active items in cart page
                            $cart->init();
                            
                            Mage::getSingleton('checkout/session')->clear();
                        }
                    }
                } else {
                
                    Mage::app()->getResponse()
                        ->setRedirect('/clothes_quiz')
                        ->sendResponse();
                    
                }
            }
        } 
        
        if ($pageId == 121) {
        
            if(Mage::getSingleton('customer/session')->isLoggedIn()){
                $resource = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');
                $table_cloth_qbarz = $resource->getTableName('cloth_questionnaire');
                $writeConnection = $resource->getConnection('core_write');
                $url = "/clothes_quiz";
                
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                
                if($customer){
                    $customer_id_var = $customer->getId();
                    $select_cloth_qbarz_query = "SELECT * FROM {$table_cloth_qbarz} WHERE customer_id={$customer_id_var}";
                    $customer_info = $readConnection->fetchAll($select_cloth_qbarz_query);
                    //$theCustomer = Mage::getSingleton('core/session')->setCustomerInfo($customer_info[0]);
                    Mage::register('customerInfo', $customer_info[0]);
                }
                
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                 
                    $height = $this->getRequest()->getParam('height-ft'); //. '_' . $this->getRequest()->getParam('height-in');
                    $weight = $this->getRequest()->getParam('weight'); 
                    $skirt = $this->getRequest()->getParam('skirt'); 
                    $shoes1 = $this->getRequest()->getParam('shoes1'); 
                    $shoes2 = $this->getRequest()->getParam('shoes2'); 
                    $jean_waist = $this->getRequest()->getParam('jean_waist'); 
                    $pants = $this->getRequest()->getParam('pants'); 
                    $bra1 = $this->getRequest()->getParam('bra1'); 
                    $bra2 = $this->getRequest()->getParam('bra2'); 
                    $dress1 = $this->getRequest()->getParam('dress1'); 
                    $dress2 = $this->getRequest()->getParam('dress2'); 
                    $pregnancy = $this->getRequest()->getParam('pregnancy'); 
                    $shape_value = $this->getRequest()->getParam('shape_value'); 
                    $work_apparel_value = $this->getRequest()->getParam('work_apparel_value'); 
                    $style_value = $this->getRequest()->getParam('quiz_style_value'); 
                    $preference_accessory = $this->getRequest()->getParam('quiz_preference_accessory'); 
                    $preference_color = $this->getRequest()->getParam('quiz_preference_color'); 
                    $preference_texture = $this->getRequest()->getParam('quiz_preference_texture'); 
                    $preference_bodypart = $this->getRequest()->getParam('quiz_preference_bodypart'); 
                    $birth_date_year = $this->getRequest()->getParam('birth_date_year'); 
                    $birth_date_month = $this->getRequest()->getParam('birth_date_month'); 
                    $birth_date_day = $this->getRequest()->getParam('birth_date_day'); 
                    $birthdate = $birth_date_year.'/'.$birth_date_month.'/'.$birth_date_day;
                    $occasion_preference = $this->getRequest()->getParam('occasion_preference'); 
                    $top_half = $this->getRequest()->getParam('top_half'); 
                    $bottom_half = $this->getRequest()->getParam('bottom_half'); 
                    $motherhood = $this->getRequest()->getParam('motherhood'); 
                    $hear_about = $this->getRequest()->getParam('hear_about'); 
                    $facebook = $this->getRequest()->getParam('facebook_account'); 
                    $instagram = $this->getRequest()->getParam('instagram_account'); 
                    $twitter = $this->getRequest()->getParam('twitter_account'); 
                    $month_spend = $this->getRequest()->getParam('month_spend'); 
                    $favourate_brands = $this->getRequest()->getParam('favourate_brands'); 
                    
                    if($customer){
                    
                        $box_shipto_firstname = $customer->getFirstname();
                        $box_shipto_lastname = $customer->getLastname();
                        
                        
                        $customer_id_var = $customer->getId();
                        $select_cloth_qbarz_query = "SELECT customer_id FROM {$table_cloth_qbarz} WHERE customer_id={$customer_id_var}";
                        $insert_cloth_qbarz_query = "INSERT INTO {$table_cloth_qbarz} 
                                ( customer_id, 
                                        customer_first_name, 
                                        customer_last_name, 
                                        height, 
                                        weight, 
                                        skirt, 
                                        shoes_size, 
                                        shoes_width, 
                                        jean_waist, 
                                        pants, 
                                        bra_size, 
                                        bra_cup, 
                                        dress_length, 
                                        dress_size, 
                                        pregnancy, 
                                        body_shape, 
                                        work_apparel, 
                                        cloth_style, 
                                        accessory_dont_want, 
                                        cloth_color_dont_want, 
                                        cloth_prints_dont_want, 
                                        body_exposion_dont_want, 
                                        birth_date, 
                                        motherhood, 
                                        hear_about_us, 
                                        facebook_account, 
                                        instagram_account, 
                                        twitter_account, 
                                        month_spend, 
                                        favourate_brands,
                                        top_half,
                                        bottom_half,
                                        occasion_preference) 
                                        
                        VALUES (
                        {$customer_id_var}, 
                        '{$box_shipto_firstname}', 
                        '{$box_shipto_lastname}', 
                        '{$height}', 
                        '{$weight}', 
                        '{$skirt}', 
                        '{$shoes1}', 
                        '{$shoes2}', 
                        '{$jean_waist}', 
                        '{$pants}', 
                        '{$bra1}', 
                        '{$bra2}', 
                        '{$dress1}', 
                        '{$dress2}', 
                        '{$pregnancy}', 
                        '{$shape_value}', 
                        '{$work_apparel_value}', 
                        '{$style_value}', 
                        '{$preference_accessory}', 
                        '{$preference_color}', 
                        '{$preference_texture}', 
                        '{$preference_bodypart}', 
                        '{$birthdate}',  
                        '{$motherhood}', 
                        '{$hear_about}', 
                        '{$facebook}', 
                        '{$instagram}', 
                        '{$twitter}', 
                        '{$month_spend}', 
                        '{$favourate_brands}',
                        '{$top_half}', 
                        '{$bottom_half}', 
                        '{$occasion_preference}')";
                        
                        $update_cloth_qbarz_query = "UPDATE {$table_cloth_qbarz} 
                        SET customer_first_name = '{$box_shipto_firstname}', 
                        customer_last_name= '{$box_shipto_lastname}',
                        height = '{$height}', 
                        weight='{$weight}', 
                        skirt = '{$skirt}', 
                        shoes_size = '{$shoes1}', 
                        shoes_width = '{$shoes2}', 
                        jean_waist = '{$jean_waist}', 
                        pants = '{$pants}', 
                        bra_size = '{$bra1}', 
                        bra_cup = '{$bra2}', 
                        dress_length = '{$dress1}', 
                        dress_size = '{$dress2}', 
                        pregnancy = '{$pregnancy}', 
                        body_shape = '{$shape_value}', 
                        work_apparel = '{$work_apparel_value}', 
                        cloth_style = '{$style_value}', 
                        accessory_dont_want = '{$preference_accessory}', 
                        cloth_color_dont_want = '{$preference_color}', 
                        cloth_prints_dont_want = '{$preference_texture}', 
                        body_exposion_dont_want = '{$preference_bodypart}', 
                        birth_date = '{$birthdate}', 
                        top_half = '{$top_half}', 
                        bottom_half = '{$bottom_half}', 
                        occasion_preference = '{$occasion_preference}', 
                        motherhood = '{$motherhood}', 
                        hear_about_us = '{$hear_about}', 
                        facebook_account = '{$facebook}', 
                        instagram_account = '{$instagram}', 
                        twitter_account = '{$twitter}', 
                        month_spend = '{$month_spend}', 
                        favourate_brands = '{$favourate_brands}' 
                        WHERE customer_id = {$customer_id_var}";
                        
                        $customer_ids = $readConnection->fetchAll($select_cloth_qbarz_query);
                        //$readConnection->fetchAll($select_cloth_qbarz_query);
                        
                        if(sizeof($customer_ids) > 0){   
                            $writeConnection->query($update_cloth_qbarz_query);
                            $select_cloth_qbarz_query = "SELECT * FROM {$table_cloth_qbarz} WHERE customer_id={$customer_id_var}";
                            $customer_info = $readConnection->fetchAll($select_cloth_qbarz_query);
                            
                            if($customer_info['plan_name'] == "" || $customer_info['plan_name'] == null){
                                //$this->_redirect("/clothes_shipping_address_form");
                                Mage::app()->getResponse()
                                    ->setRedirect('/clothes_shipping_address_form')
                                    ->sendResponse();
                            } else {
                                //Mage::getSingleton('core/session')->setCustomerPlan($plan);
                                $this->_redirect("/customer/account/");
                            }
                        }else{ 
                            $writeConnection->query($insert_cloth_qbarz_query);
                                //$this->_redirect("/clothes_shipping_address_form");
                                Mage::app()->getResponse()
                                    ->setRedirect('/clothes_shipping_address_form')
                                    ->sendResponse();
                        }
                    }
                }
            } else {    
                
                Mage::app()->getResponse()
                    ->setRedirect('/customer/account/login/referer/')
                    ->sendResponse();
            }
            
        }
        
        if ($pageId == 145) {
        
            if(Mage::getSingleton('customer/session')->isLoggedIn()){
                $resource = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');
                $table_cloth_qbarz = $resource->getTableName('cloth_questionnaire');
                $writeConnection = $resource->getConnection('core_write');
                $url = "/clothes_quiz_save";
                
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                
                if($customer){
                    $customer_id_var = $customer->getId();
                    $select_cloth_qbarz_query = "SELECT * FROM {$table_cloth_qbarz} WHERE customer_id={$customer_id_var}";
                    $customer_info = $readConnection->fetchAll($select_cloth_qbarz_query);
                    //$theCustomer = Mage::getSingleton('core/session')->setCustomerInfo($customer_info[0]);
                    if(!$customer_info){
                        Mage::app()->getResponse()
                                ->setRedirect('/clothes_quiz')
                                ->sendResponse();
                    
                    }
                    Mage::register('customerInfo', $customer_info[0]);
                }
                
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                 
                    $height = $this->getRequest()->getParam('height-ft'); //. '_' . $this->getRequest()->getParam('height-in');
                    $weight = $this->getRequest()->getParam('weight'); 
                    $skirt = $this->getRequest()->getParam('skirt'); 
                    $shoes1 = $this->getRequest()->getParam('shoes1'); 
                    $shoes2 = $this->getRequest()->getParam('shoes2'); 
                    $jean_waist = $this->getRequest()->getParam('jean_waist'); 
                    $pants = $this->getRequest()->getParam('pants'); 
                    $bra1 = $this->getRequest()->getParam('bra1'); 
                    $bra2 = $this->getRequest()->getParam('bra2'); 
                    $dress1 = $this->getRequest()->getParam('dress1'); 
                    $dress2 = $this->getRequest()->getParam('dress2'); 
                    $pregnancy = $this->getRequest()->getParam('pregnancy'); 
                    $shape_value = $this->getRequest()->getParam('shape_value'); 
                    $work_apparel_value = $this->getRequest()->getParam('work_apparel_value'); 
                    $style_value = $this->getRequest()->getParam('quiz_style_value'); 
                    $preference_accessory = $this->getRequest()->getParam('quiz_preference_accessory'); 
                    $preference_color = $this->getRequest()->getParam('quiz_preference_color'); 
                    $preference_texture = $this->getRequest()->getParam('quiz_preference_texture'); 
                    $preference_bodypart = $this->getRequest()->getParam('quiz_preference_bodypart'); 
                    $birth_date_year = $this->getRequest()->getParam('birth_date_year'); 
                    $birth_date_month = $this->getRequest()->getParam('birth_date_month'); 
                    $birth_date_day = $this->getRequest()->getParam('birth_date_day'); 
                    $birthdate = $birth_date_year.'/'.$birth_date_month.'/'.$birth_date_day;
                    $occasion_preference = $this->getRequest()->getParam('occasion_preference'); 
                    $top_half = $this->getRequest()->getParam('top_half'); 
                    $bottom_half = $this->getRequest()->getParam('bottom_half'); 
                    $motherhood = $this->getRequest()->getParam('motherhood'); 
                    $hear_about = $this->getRequest()->getParam('hear_about'); 
                    $facebook = $this->getRequest()->getParam('facebook_account'); 
                    $instagram = $this->getRequest()->getParam('instagram_account'); 
                    $twitter = $this->getRequest()->getParam('twitter_account'); 
                    $month_spend = $this->getRequest()->getParam('month_spend'); 
                    $favourate_brands = $this->getRequest()->getParam('favourate_brands'); 
                    
                    if($customer){
                    
                        $box_shipto_firstname = $customer->getFirstname();
                        $box_shipto_lastname = $customer->getLastname();
                        
                        
                        $customer_id_var = $customer->getId();
                        $select_cloth_qbarz_query = "SELECT customer_id FROM {$table_cloth_qbarz} WHERE customer_id={$customer_id_var}";
                        $insert_cloth_qbarz_query = "INSERT INTO {$table_cloth_qbarz} 
                                ( customer_id, 
                                        customer_first_name, 
                                        customer_last_name, 
                                        height, 
                                        weight, 
                                        skirt, 
                                        shoes_size, 
                                        shoes_width, 
                                        jean_waist, 
                                        pants, 
                                        bra_size, 
                                        bra_cup, 
                                        dress_length, 
                                        dress_size, 
                                        pregnancy, 
                                        body_shape, 
                                        work_apparel, 
                                        cloth_style, 
                                        accessory_dont_want, 
                                        cloth_color_dont_want, 
                                        cloth_prints_dont_want, 
                                        body_exposion_dont_want, 
                                        birth_date, 
                                        motherhood, 
                                        hear_about_us, 
                                        facebook_account, 
                                        instagram_account, 
                                        twitter_account, 
                                        month_spend, 
                                        favourate_brands,
                                        top_half,
                                        bottom_half,
                                        occasion_preference) 
                                        
                        VALUES (
                        {$customer_id_var}, 
                        '{$box_shipto_firstname}', 
                        '{$box_shipto_lastname}', 
                        '{$height}', 
                        '{$weight}', 
                        '{$skirt}', 
                        '{$shoes1}', 
                        '{$shoes2}', 
                        '{$jean_waist}', 
                        '{$pants}', 
                        '{$bra1}', 
                        '{$bra2}', 
                        '{$dress1}', 
                        '{$dress2}', 
                        '{$pregnancy}', 
                        '{$shape_value}', 
                        '{$work_apparel_value}', 
                        '{$style_value}', 
                        '{$preference_accessory}', 
                        '{$preference_color}', 
                        '{$preference_texture}', 
                        '{$preference_bodypart}', 
                        '{$birthdate}',  
                        '{$motherhood}', 
                        '{$hear_about}', 
                        '{$facebook}', 
                        '{$instagram}', 
                        '{$twitter}', 
                        '{$month_spend}', 
                        '{$favourate_brands}',
                        '{$top_half}', 
                        '{$bottom_half}', 
                        '{$occasion_preference}')";
                        
                        $update_cloth_qbarz_query = "UPDATE {$table_cloth_qbarz} 
                        SET customer_first_name = '{$box_shipto_firstname}', 
                        customer_last_name= '{$box_shipto_lastname}',
                        height = '{$height}', 
                        weight='{$weight}', 
                        skirt = '{$skirt}', 
                        shoes_size = '{$shoes1}', 
                        shoes_width = '{$shoes2}', 
                        jean_waist = '{$jean_waist}', 
                        pants = '{$pants}', 
                        bra_size = '{$bra1}', 
                        bra_cup = '{$bra2}', 
                        dress_length = '{$dress1}', 
                        dress_size = '{$dress2}', 
                        pregnancy = '{$pregnancy}', 
                        body_shape = '{$shape_value}', 
                        work_apparel = '{$work_apparel_value}', 
                        cloth_style = '{$style_value}', 
                        accessory_dont_want = '{$preference_accessory}', 
                        cloth_color_dont_want = '{$preference_color}', 
                        cloth_prints_dont_want = '{$preference_texture}', 
                        body_exposion_dont_want = '{$preference_bodypart}', 
                        birth_date = '{$birthdate}', 
                        top_half = '{$top_half}', 
                        bottom_half = '{$bottom_half}', 
                        occasion_preference = '{$occasion_preference}', 
                        motherhood = '{$motherhood}', 
                        hear_about_us = '{$hear_about}', 
                        facebook_account = '{$facebook}', 
                        instagram_account = '{$instagram}', 
                        twitter_account = '{$twitter}', 
                        month_spend = '{$month_spend}', 
                        favourate_brands = '{$favourate_brands}' 
                        WHERE customer_id = {$customer_id_var}";
                        
                        $customer_ids = $readConnection->fetchAll($select_cloth_qbarz_query);
                        //$readConnection->fetchAll($select_cloth_qbarz_query);
                        
                        if(sizeof($customer_ids) > 0){   
                            $writeConnection->query($update_cloth_qbarz_query);
                            $select_cloth_qbarz_query = "SELECT * FROM {$table_cloth_qbarz} WHERE customer_id={$customer_id_var}";
                            $customer_info = $readConnection->fetchAll($select_cloth_qbarz_query);
                            
                            if($customer_info['plan_name'] == "" || $customer_info['plan_name'] == null){
                                //$this->_redirect("/clothes_shipping_address_form");
                                Mage::app()->getResponse()
                                    ->setRedirect('/customer/account/')
                                    ->sendResponse();
                            } else {
                                //Mage::getSingleton('core/session')->setCustomerPlan($plan);
                                $this->_redirect("/customer/account/");
                            }
                        }else{ 
                            $writeConnection->query($insert_cloth_qbarz_query);
                                //$this->_redirect("/clothes_shipping_address_form");
                                Mage::app()->getResponse()
                                    ->setRedirect('/customer/account/')
                                    ->sendResponse();
                        }
                    }
                }
            } else {    
                
                Mage::app()->getResponse()
                    ->setRedirect('/customer/account/login/referer/')
                    ->sendResponse();
            }
            
        }
        
        //122 clothes_shipping_address_form
        //$casualpage == 141 $buisnesspage == 140  
        
        if($pageId == 140 || $pageId == 141){
        
            if(Mage::getSingleton('customer/session')->isLoggedIn()){
                
                $resource = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');
                $readConnection = $resource->getConnection('core_read');
                $table = $resource->getTableName('recurly_cloth');
                $table_cloth_qbarz = $resource->getTableName('cloth_questionnaire');
                $customer = Mage::getSingleton('customer/session')->getCustomer(); 
                $customer_id_var = Mage::getSingleton('customer/session')->getCustomer()->getId();  
                
                $select_cloth_qbarz_query = "SELECT customer_id FROM {$table_cloth_qbarz} WHERE customer_id={$customer_id_var}";
                $select_cloth_plan_query = "SELECT customer_id FROM {$table} WHERE customer_id={$customer_id_var}"; 
                
                $customer_ids = $readConnection->fetchAll($select_cloth_qbarz_query);
                $customer_ids_plan = $readConnection->fetchAll($select_cloth_plan_query); 
            
                if($customer_ids){
            
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        
                        $box_shipto_firstname = $customer->getFirstname(); //$this->getRequest()->getParam('shipto_firstname');
                        $box_shipto_lastname = $customer->getLastname(); //$this->getRequest()->getParam('shipto_lastname');
                        $plan_choice = $this->getRequest()->getParam('clothes_plan_choice');
                        
                        $plan = "/checkout/cart/";
                        
                        if($plan_choice == 4){  // Clear Magento cart and quote items
                            $cart = Mage::getModel('checkout/cart');                
                            $cart->truncate()->save(); // remove all active items in cart page
                            $cart->init();
                            
                            Mage::getSingleton('checkout/session')->clear();
                            
                            $params = array(
                                'product' => 1477,
                                'qty' => 1,
                            );
                            
                            $cart = Mage::getSingleton('checkout/cart');
                            $cart->init();
                            
                            $product = new Mage_Catalog_Model_Product();
                            $product->load(1477);
                            $account_code = $product->getName();
                            
                            $cart->addProduct($product, $params);
                            $cart->save();
                            
                            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                            
                            $message = $this->__('Custom message: %s was successfully added to your shopping cart.', $product->getName());
                            Mage::getSingleton('checkout/session')->addSuccess($message);
                        }            
                        if($plan_choice == 5){
                            $cart = Mage::getModel('checkout/cart');                
                            $cart->truncate()->save(); // remove all active items in cart page
                            $cart->init();
                            Mage::getSingleton('checkout/session')->clear();
                            
                            $params = array(
                                'product' => 1478,
                                'qty' => 1,
                            );
                            
                            $cart = Mage::getSingleton('checkout/cart');
                            $cart->init();
                            
                            $product = new Mage_Catalog_Model_Product();
                            $product->load(1478);
                            $account_code = $product->getName();
                            
                            $cart->addProduct($product, $params);
                            $cart->save();
                            
                            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                            
                            $message = $this->__('Custom message: %s was successfully added to your shopping cart.', $product->getName());
                            Mage::getSingleton('checkout/session')->addSuccess($message);
                                                        
                        }
                        if($plan_choice == 6){
                            $cart = Mage::getModel('checkout/cart');                
                            $cart->truncate()->save(); // remove all active items in cart page
                            $cart->init();
                            Mage::getSingleton('checkout/session')->clear();
                        
                            $params = array(
                                'product' => 1479,
                                'qty' => 1,
                            );
                            
                            $cart = Mage::getSingleton('checkout/cart');
                            $cart->init();
                            
                            $product = new Mage_Catalog_Model_Product();
                            $product->load(1479);
                            $account_code = $product->getName();
                            
                            $cart->addProduct($product, $params);
                            $cart->save();
                            
                            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                            
                            $message = $this->__('Custom message: %s was successfully added to your shopping cart.', $product->getName());
                            Mage::getSingleton('checkout/session')->addSuccess($message);
                            
                        }
                        if($plan_choice == 7){
                            $cart = Mage::getModel('checkout/cart');                
                            $cart->truncate()->save(); // remove all active items in cart page
                            $cart->init();
                            Mage::getSingleton('checkout/session')->clear();
                        
                            $params = array(
                                'product' => 1480,
                                'qty' => 1,
                            );
                            
                            $cart = Mage::getSingleton('checkout/cart');
                            $cart->init();
                            
                            $product = new Mage_Catalog_Model_Product();
                            $product->load(1480);
                            $account_code = $product->getName();
                            
                            $cart->addProduct($product, $params);
                            $cart->save();
                            
                            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                            
                            $message = $this->__('Custom message: %s was successfully added to your shopping cart.', $product->getName());
                            Mage::getSingleton('checkout/session')->addSuccess($message);
                            
                        }
                        
                        if($box_shipto_firstname){
                            
                            $current_date = date('Y-m-d');
                            
                            if ($customer_ids_plan) {
                                
                                if(count($customer_ids_plan) > 0){
                                    $customer = Mage::getSingleton('customer/session')->getCustomer(); 
                                    $customer_id_var = Mage::getSingleton('customer/session')->getCustomer()->getId();  
                                    $query = "UPDATE {$table} 
                                    SET customer_firstname='{$box_shipto_firstname}', customer_lastname='{$box_shipto_lastname}', create_at='{$current_date}' WHERE customer_id={$customer_id_var}";
                                    $writeConnection->query($query);
                        
                                    //ROUTE TO NEXT PAGE FOR RECURLY
                                    Mage::app()->getResponse()
                                        ->setRedirect($plan)
                                        ->sendResponse();
                                } else {
                                    $customer = Mage::getSingleton('customer/session')->getCustomer(); 
                                    $customer_id_var = Mage::getSingleton('customer/session')->getCustomer()->getId();  
                                    $query = "INSERT INTO {$table} (customer_id, customer_firstname, customer_lastname, create_at) VALUES ({$customer_id_var},'{$box_shipto_firstname}','{$box_shipto_lastname}','{$current_date}')";
                                    $writeConnection->query($query);
                                    //ROUTE TO NEXT PAGE FOR RECURLY
                                    Mage::app()->getResponse()
                                        ->setRedirect($plan)
                                        ->sendResponse();
                                }
                            } else {
                                $query = "INSERT INTO {$table} (customer_id, customer_firstname, customer_lastname, create_at) VALUES ({$customer_id_var},'{$box_shipto_firstname}','{$box_shipto_lastname}', '{$current_date}')";
                                $writeConnection->query($query);
                                //ROUTE TO NEXT PAGE FOR RECURLY
                                Mage::app()->getResponse()
                                    ->setRedirect($plan)
                                    ->sendResponse();
                            }
                         
                        }
                        
                    }
                    
                    ////NOT post request
                
                } else { // NO $customer_id in the questionestionnaire
                
                    Mage::app()->getResponse()
                        ->setRedirect('/clothes_quiz')
                        ->sendResponse();
                }
            
            } else { /// not logged in
            
                Mage::app()->getResponse()
                    ->setRedirect('/customer/account/login')
                    ->sendResponse();
            
            }
        } 
        
        if($pageId == 140 || $pageId == 141){
        
            if(Mage::getSingleton('customer/session')->isLoggedIn()){
                
                $resource = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');
                $readConnection = $resource->getConnection('core_read');
                $table = $resource->getTableName('recurly_cloth');
                $table_cloth_qbarz = $resource->getTableName('cloth_questionnaire');
                $customer = Mage::getSingleton('customer/session')->getCustomer(); 
                $customer_id_var = Mage::getSingleton('customer/session')->getCustomer()->getId();  
                
                $select_cloth_qbarz_query = "SELECT customer_id FROM {$table_cloth_qbarz} WHERE customer_id={$customer_id_var}";
                $select_cloth_plan_query = "SELECT customer_id FROM {$table} WHERE customer_id={$customer_id_var}"; 
                
                $customer_ids = $readConnection->fetchAll($select_cloth_qbarz_query);
                $customer_ids_plan = $readConnection->fetchAll($select_cloth_plan_query); 
            
                if($customer_ids){
            
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        
                        $box_shipto_firstname = $customer->getFirstname(); //$this->getRequest()->getParam('shipto_firstname');
                        $box_shipto_lastname = $customer->getLastname(); //$this->getRequest()->getParam('shipto_lastname');
                        $plan_choice = $this->getRequest()->getParam('clothes_plan_choice');
                        
                        $plan = "/checkout/cart/";
                        
                        if($plan_choice == 4){  // Clear Magento cart and quote items
                            $cart = Mage::getModel('checkout/cart');                
                            $cart->truncate()->save(); // remove all active items in cart page
                            $cart->init();
                            
                            Mage::getSingleton('checkout/session')->clear();
                            
                            $params = array(
                                'product' => 1477,
                                'qty' => 1,
                            );
                            
                            $cart = Mage::getSingleton('checkout/cart');
                            $cart->init();
                            
                            $product = new Mage_Catalog_Model_Product();
                            $product->load(1477);
                            $account_code = $product->getName();
                            
                            $cart->addProduct($product, $params);
                            $cart->save();
                            
                            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                            
                            $message = $this->__('Custom message: %s was successfully added to your shopping cart.', $product->getName());
                            Mage::getSingleton('checkout/session')->addSuccess($message);
                        }            
                        if($plan_choice == 5){
                            $cart = Mage::getModel('checkout/cart');                
                            $cart->truncate()->save(); // remove all active items in cart page
                            $cart->init();
                            Mage::getSingleton('checkout/session')->clear();
                            
                            $params = array(
                                'product' => 1478,
                                'qty' => 1,
                            );
                            
                            $cart = Mage::getSingleton('checkout/cart');
                            $cart->init();
                            
                            $product = new Mage_Catalog_Model_Product();
                            $product->load(1478);
                            $account_code = $product->getName();
                            
                            $cart->addProduct($product, $params);
                            $cart->save();
                            
                            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                            
                            $message = $this->__('Custom message: %s was successfully added to your shopping cart.', $product->getName());
                            Mage::getSingleton('checkout/session')->addSuccess($message);
                                                        
                        }
                        if($plan_choice == 6){
                            $cart = Mage::getModel('checkout/cart');                
                            $cart->truncate()->save(); // remove all active items in cart page
                            $cart->init();
                            Mage::getSingleton('checkout/session')->clear();
                        
                            $params = array(
                                'product' => 1479,
                                'qty' => 1,
                            );
                            
                            $cart = Mage::getSingleton('checkout/cart');
                            $cart->init();
                            
                            $product = new Mage_Catalog_Model_Product();
                            $product->load(1479);
                            $account_code = $product->getName();
                            
                            $cart->addProduct($product, $params);
                            $cart->save();
                            
                            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                            
                            $message = $this->__('Custom message: %s was successfully added to your shopping cart.', $product->getName());
                            Mage::getSingleton('checkout/session')->addSuccess($message);
                            
                        }
                        if($plan_choice == 7){
                            $cart = Mage::getModel('checkout/cart');                
                            $cart->truncate()->save(); // remove all active items in cart page
                            $cart->init();
                            Mage::getSingleton('checkout/session')->clear();
                        
                            $params = array(
                                'product' => 1480,
                                'qty' => 1,
                            );
                            
                            $cart = Mage::getSingleton('checkout/cart');
                            $cart->init();
                            
                            $product = new Mage_Catalog_Model_Product();
                            $product->load(1480);
                            $account_code = $product->getName();
                            
                            $cart->addProduct($product, $params);
                            $cart->save();
                            
                            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                            
                            $message = $this->__('Custom message: %s was successfully added to your shopping cart.', $product->getName());
                            Mage::getSingleton('checkout/session')->addSuccess($message);
                            
                        }
                        
                        if($box_shipto_firstname){
                            
                            $current_date = date('Y-m-d');
                            
                            if ($customer_ids_plan) {
                                
                                if(count($customer_ids_plan) > 0){
                                    $customer = Mage::getSingleton('customer/session')->getCustomer(); 
                                    $customer_id_var = Mage::getSingleton('customer/session')->getCustomer()->getId();  
                                    $query = "UPDATE {$table} 
                                    SET customer_firstname='{$box_shipto_firstname}', customer_lastname='{$box_shipto_lastname}', create_at='{$current_date}' WHERE customer_id={$customer_id_var}";
                                    $writeConnection->query($query);
                        
                                    //ROUTE TO NEXT PAGE FOR RECURLY
                                    Mage::app()->getResponse()
                                        ->setRedirect($plan)
                                        ->sendResponse();
                                } else {
                                    $customer = Mage::getSingleton('customer/session')->getCustomer(); 
                                    $customer_id_var = Mage::getSingleton('customer/session')->getCustomer()->getId();  
                                    $query = "INSERT INTO {$table} (customer_id, customer_firstname, customer_lastname, create_at) VALUES ({$customer_id_var},'{$box_shipto_firstname}','{$box_shipto_lastname}','{$current_date}')";
                                    $writeConnection->query($query);
                                    //ROUTE TO NEXT PAGE FOR RECURLY
                                    Mage::app()->getResponse()
                                        ->setRedirect($plan)
                                        ->sendResponse();
                                }
                            } else {
                                $query = "INSERT INTO {$table} (customer_id, customer_firstname, customer_lastname, create_at) VALUES ({$customer_id_var},'{$box_shipto_firstname}','{$box_shipto_lastname}', '{$current_date}')";
                                $writeConnection->query($query);
                                //ROUTE TO NEXT PAGE FOR RECURLY
                                Mage::app()->getResponse()
                                    ->setRedirect($plan)
                                    ->sendResponse();
                            }
                         
                        }
                        
                    }
                    
                    ////NOT post request
                
                } else { // NO $customer_id in the questionestionnaire
                
                    Mage::app()->getResponse()
                        ->setRedirect('/clothes_quiz')
                        ->sendResponse();
                }
            
            } else { /// not logged in
            
                Mage::app()->getResponse()
                    ->setRedirect('/customer/account/login')
                    ->sendResponse();
            
            }
        }
        
        //123 clothes_holding_page
        
        if($pageId == 123){
         
            if(Mage::getSingleton('customer/session')->isLoggedIn()){
                
                $resource = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');
                $readConnection = $resource->getConnection('core_read');
                $table = $resource->getTableName('recurly_cloth');
                $table_cloth_qbarz = $resource->getTableName('cloth_questionnaire');
                $customer = Mage::getSingleton('customer/session')->getCustomer(); 
                $customer_id_var = Mage::getSingleton('customer/session')->getCustomer()->getId();  
                
                $select_cloth_qbarz_query = "SELECT customer_id FROM {$table_cloth_qbarz} WHERE customer_id={$customer_id_var}";
                $select_cloth_plan_query = "SELECT customer_id FROM {$table} WHERE customer_id={$customer_id_var}"; 
                
                $customer_ids = $readConnection->fetchAll($select_cloth_qbarz_query);
                $customer_ids_plan = $readConnection->fetchAll($select_cloth_plan_query); 
            
                if($customer_ids_plan && $customer_ids){
            
                    if ("" <> $_GET['account']) {
                        
                        ////////////////////////////////////////////////////////////////////////////
                        
                        $plan = $_GET['plan'];    
                        
                        $resource = Mage::getSingleton('core/resource');
                        $writeConnection = $resource->getConnection('core_write');
                        $table_cloth_qbarz = $resource->getTableName('cloth_questionnaire');
                        $table = $resource->getTableName('recurly_cloth');
                        $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
                        $readConnection = $resource->getConnection('core_read');
                        
                        require_once('lib/recurly.php');
                        Recurly_Client::$subdomain = "testesianmall";
                        Recurly_Client::$apiKey = "3b714e3e0eef4d6d8936293d901cc43c";
                        $account_code = $_GET['account'];
                        $query_param_array = array('sort' => 'created_at', 'order' => 'desc');
                        
                        $current_date = date('c');
                        
                        if($box_shipto_firstname){  // redirect from recurly payment page
                                $subscriptions = Recurly_SubscriptionList::getForAccount($account_code, $query_param_array);
                                $sub = [];
                                foreach($subscriptions as $subscription){
                                        $my_plan = $subscription->plan;
                                        $my_plan_code =$my_plan->plan_code;
                                        $sub[]=array('ubard'=>$subscription->ubard, 'plan'=>$my_plan_code,'notes'=>$subscription->customer_notes,'date'=>$subscription->activated_at,'amount'=>$subscription->unit_amount_in_cents,'currency'=>$subscription->currency);
                                        break;
                                }
                                
                                $ubard = $sub[0]['ubard'];
                                $sub_notes = $sub[0]['notes'];
                                $sub_date = $sub[0]['date']->format('Y-m-d H:i:s');

                                foreach($sub as $subarray){
                                        if($subarray['plan']===$plan){
                                                $ubard = $subarray['ubard'];
                                                $sub_notes = $subarray['notes'];
                                                $sub_date = $subarray['date']->format('Y-m-d H:i:s');
                                                $amount = $subarray['amount']/100;
                                                $currency = $subarray['currency'];
                                        }
                                    
                                }
                            
                        } 
                        
                        $update_cloth_qbarz_query = "UPDATE {$table_cloth_qbarz} SET recurly_email = '{$account_code}', plan_name = '{$plan}' WHERE customer_id={$customer_id}";
                        
                        $update_recurly_cloth_query = "UPDATE {$table} SET recurly_email = '{$account_code}', cloth_plan_name = '{$plan}' WHERE customer_id={$customer_id}";                   
                        
                        
                        $writeConnection->query($update_cloth_qbarz_query);
                        $writeConnection->query($update_recurly_cloth_query);
                        
                        Mage::app()->getResponse()
                                ->setRedirect('/my_cloth_box/')
                                ->sendResponse();
                        
                        
                        ///////////////////////////////////////////////////////////////////////////////////////////  
                        
                        
                    }
                    
                } else {
                
                    Mage::app()->getResponse()
                        ->setRedirect('/clothes_quiz')
                        ->sendResponse();
                }
                
            } else {
                
                Mage::app()->getResponse()
                    ->setRedirect('/customer/account/login')
                    ->sendResponse();
            
            }
        }
        
        
        if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
            $this->_forward('noRoute');
        }
    }
}
