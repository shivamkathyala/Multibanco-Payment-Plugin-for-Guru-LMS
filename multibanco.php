<?php







defined('_JEXEC') or die;







use Joomla\CMS\Factory;



use Joomla\CMS\Mail\MailHelper;



use Joomla\CMS\Language\Text;



use Joomla\CMS\Plugin\CMSPlugin;



use Joomla\CMS\Uri\Uri;



use Joomla\Registry\Registry;







$document = Factory::getDocument();



// Load Bootstrap CSS



$document->addStyleSheet('https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css');



// Load jQuery



$document->addScript('https://code.jquery.com/jquery-3.6.0.min.js');







// Load custom CSS and JS



$document->addStyleSheet(Uri::base() . 'plugins/gurupayment/multibanco/assets/css/style.css');



$document->addScript(Uri::base() . 'plugins/gurupayment/multibanco/assets/js/script.js');







$input = Factory::getApplication()->input;



class plgGuruPaymentMultibanco extends CMSPlugin



{



    public function __construct(&$subject, $config)



    {



        parent::__construct($subject, $config);



        $this->loadLanguage();



    }



    public function onSendPayment(&$post)



    {



        if ($post['processor'] != 'multibanco') {



            return false;



        }



        $orderId = $post['order_id'];



        $params = new Registry($post['params']);



        $mbKey = $params->get('multibanco_key');



        $phishingKey = $params->get('phishing_key');



        $expirationDate = $params->get('multibanco_expiry_date');



        //Factory::getApplication()->enqueueMessage($expirationDate, 'error');



        if (!$mbKey || !$phishingKey) {



            Factory::getApplication()->enqueueMessage(Text::_('PLG_MB_MISSING_FIELDS_ERROR'), 'error');



            return;



        }



        // url for production and sandbox



        $url = 'https://ifthenpay.com/api/multibanco/reference/init'; 







		if($params->get('multibanco_sandbox')){







			$url = 'https://ifthenpay.com/api/multibanco/reference/sandbox'; 







		}







        $link_params = [



            'option' => $post['option'],



            'controller' => $post['controller'],



            'task' => $post['task'],



            'processor' => $post['processor'],



            'order_id' => @$post['order_id'],



            //'sid' => @$post['sid'],



            //'Itemid' => isset($post['Itemid']) ? $post['Itemid'] : '0',



        ];







        //$notify_url = Uri::base() . 'index.php?' . $this->DotPayArray2Url($link_params) . '&customer_id=' . intval($post['customer_id']) . '&pay=ipn';



        $callback_url = Uri::base() . 'index.php?' . $this->DotPayArray2Url($link_params) . '&key=' . $phishingKey . '&pay=ipn';

        $cart_page_url = JURI::base().'gurubuy';

        $home_page_url = JURI::base();



        //Factory::getApplication()->enqueueMessage($callback_url, 'success');



        $db = Factory::getDbo();



        $query = $db->getQuery(true)



            ->select(['userid', 'amount', 'amount_paid'])



            ->from($db->quoteName('#__guru_order'))



            ->where($db->quoteName('id') . ' = ' . (int) $link_params['order_id']);



        $db->setQuery($query);



        $order_details = $db->loadAssoc();



        //Factory::getApplication()->enqueueMessage(print_r($order_details,), 'error');



        $amount = $order_details['amount'];



        if(isset($amount)){



            $user = Factory::getUser();



            $user_id = $user->id;



            $db = Factory::getDBO();



            $email = "SELECT email FROM #__users WHERE id=" . intval($user_id);



		    $db->setQuery($email);



		    $email = $db->loadResult();



            //Factory::getApplication()->enqueueMessage(print_r($email, true), 'error');



        }







        // api request







        if (isset($mbKey, $orderId, $email, $amount)) {



            $expiry = isset($expirationDate) ? $expirationDate : "";



            if($expiry != ""){



                    $data = array(



                        "mbKey" => $mbKey,



                        "orderId" => $orderId,



                        "amount" => $amount,



                        "clientEmail" => $email,



                        "expiryDays" => $expiry



                    );



                }else{



                    $data = array(



                        "mbKey" => $mbKey,



                        "orderId" => $orderId,



                        "amount" => $amount,



                        "clientEmail" => $email



                    );



                }



            $jsonData = json_encode($data);







            $curl = curl_init();







            curl_setopt_array($curl, array(



                CURLOPT_URL => $url,



                CURLOPT_RETURNTRANSFER => true,



                CURLOPT_ENCODING => '',



                CURLOPT_MAXREDIRS => 10,



                CURLOPT_TIMEOUT => 0,



                CURLOPT_FOLLOWLOCATION => true,



                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,



                CURLOPT_CUSTOMREQUEST => 'POST',



                CURLOPT_POSTFIELDS => $jsonData,



                CURLOPT_HTTPHEADER => array(



                    'Content-Type: application/json'



                ),



            ));







            $response = curl_exec($curl);







            if (curl_errno($curl)) {



                $error_msg = curl_error($curl);



                echo json_encode(array('error' => $error_msg));



            } else {



                echo $response;



                $referenceData = json_decode($response, true);



                $message = $referenceData['Message'];



                $status = $referenceData['Status'];



                if($message === "Success" && $status === "0"){



                    $amt = $referenceData['Amount'];



                    $entity = $referenceData['Entity'];



                    $expiryDate = $referenceData['ExpiryDate'];



                    



                    $orderId = $referenceData['OrderId'];



                    $reference = $referenceData['Reference'];



                    $requestId = $referenceData['RequestId'];



                    



                    //Factory::getApplication()->enqueueMessage(print_r($referenceData, true), 'error');

                    function formatReference($reference) {

                        // CHECK STR LEN

                        if (strlen($reference) == 9) {

                            return substr($reference, 0, 3) . ' ' . substr($reference, 3, 3) . ' ' . substr($reference, 6, 3);

                        }

                        return $reference; 

                    }



                    $formattedReference = formatReference($reference);

                    // OUTPUT FORM



                    function buildForm($entity, $formattedReference, $amt, $expiryDate = '', $cart_page_url, $home_page_url, $includeLogo = true) {





                        // The HTML form with inline CSS for email compatibility



                        $form  = '<div id="multibanco-refrence" style="font-family: Poppins, Helvetica, Tahoma, Geneva, Arial, sans-serif; margin: 0 auto; max-width: 740px; padding: 0 50px; padding-top: 60px; margin-bottom: 30px;">';



                        $form .= '<div id="multibanco-after-payment" class="multibanco-after-payment-cls" style="width: 100%; max-width: 740px; margin: 0 auto; border: 1px solid #0c629e; border-width: 4px 1px 1px 1px;">';
                        if ($includeLogo) {
                        $form .= '<div class="alert alert-info" style="text-align:center;" >'.Text::_('PLG_MULTIBANCO_EMAIL_HEADING').'</div>';
                        }else{
                            $form .= '<div class="alert alert-info" style="text-align:center;" >'.Text::_('PLG_MUYLTIBANCO_EMAIL_BODY').'</div>';
                        }
                        $form .= '<div>';



                        $form .= '<h4 id="top_sec_heading_multibanco_design" style="background: #1766ac; color: #fff; padding: 13px 0; text-align: center; margin: 0;">' . Text::_('PLG_MB_PAY_MULTIBANCO') . '</h4>';



                        $form .= '</div>';



                        $form .= '<div class="down_sec_logo_text_multibanco" style="display: flex; gap: 40px; padding: 20px 50px 0 50px;">';



                    



                        if ($includeLogo) {



                            // Include the logo only if $includeLogo is true



                            $form .= '<div class="logo__multibanco" style="flex: 1;">';



                            $form .= '<img src="' . Uri::base(true) . '/plugins/gurupayment/multibanco/multibanco-logo.png" alt="Logo" style="width: 150px; height: 130px;">';



                            $form .= '</div>';



                        }



                    



                        $form .= '<div class="right_side_fields_multibanco" id="multibanco-refrence-data-fields" style="flex: 2; width: 100%;">';



                        $form .= '<div class="inner_fields_text_multibanco" style="margin-bottom: 10px; border-bottom: 1px solid #ddd;">';



                        $form .= '<p style="font-size: 16px; color: #666;">' . Text::_('PLG_MB_PAY_ENTITY') . '<span id="entidade_multibanco" style="color: #666; font-weight: 700; font-size: 17px; float: right;">' . $entity . '</span></p>';



                        $form .= '</div>';



                        $form .= '<div class="inner_fields_text_multibanco" style="margin-bottom: 10px; border-bottom: 1px solid #ddd;">';



                        $form .= '<p style="font-size: 16px; color: #666;">' . Text::_('PLG_MB_PAY_REFERENCE') . '<span id="refrencia_multibanco" style="color: #666; font-weight: 700; font-size: 17px; float: right;">' . $formattedReference . '</span></p>';



                        $form .= '</div>';



                        $form .= '<div class="inner_fields_text_multibanco" style="margin-bottom: 10px; border-bottom: 1px solid #ddd;">';



                        $form .= '<p style="font-size: 16px; color: #666;">' . Text::_('PLG_MB_PAY_AMOUNT') . '<span id="amount_multibanco" style="color: #666; font-weight: 700; font-size: 17px; float: right;">' . $amt . ' €</span></p>';



                        $form .= '</div>';



                    



                        if (!empty($expiryDate)) {



                            $form .= '<div class="inner_fields_text_multibanco" style="margin-bottom: 10px; border-bottom: 1px solid #ddd;">';



                            $form .= '<p style="font-size: 16px; color: #666;">' . Text::_('PLG_MB_PAY_EXPIRY_DATE') . '<span id="expiration_multibanco" style="color: #666; font-weight: 700; font-size: 17px; float: right;">' . $expiryDate . '</span></p>';



                            $form .= '</div>';



                        }



                    



                        $form .= '</div>';



                        $form .= '</div>';



                        $form .= '</div>';

                        

                        // BUTTONS
                        if ($includeLogo) {
                        $form .= '<div class="multibanco-btn" style="text-align: center; margin-top: 20px;">';

                        $form .= '<input type="button" class="btn btn-primary" onclick="window.location=\'' . $cart_page_url . '\';" value="'.Text::_('PLG_MB_CANCEL_BTN').'" />';

                        $form .= '&nbsp;&nbsp;';

                        $form .= '<input type="button" class="btn btn-warning" onclick="window.location=\'' . $home_page_url . '\';" value="'.Text::_('PLG_MB_MAKE_PAYMENT_BTN').'" />';

                        $form .= '</div>';
                        }


                        $form .= '</div>';



                    



                        return $form;



                    }



                    



                    $formWithLogo = buildForm($entity, $formattedReference, $amt, $expiryDate, $cart_page_url, $home_page_url, true);



                    $emailBody = buildForm($entity, $formattedReference, $amt, $expiryDate, $cart_page_url, $home_page_url, false);                    



                    



                    // SEND THE REFERENCE TO THE USER EMAIL



                    $config = Factory::getConfig();







                    // Get the site administrator's email address



                    $adminEmail = $config->get('mailfrom');



                    $adminName = $config->get('fromname');



                    



                    // Get the Joomla mailer



                    $mailer = Factory::getMailer();



                    $sender = [



                        $adminEmail,



                        $adminName



                    ];



                    $mailer->setSender($sender);



                    



                    // Set the recipient



                    $recipient = $email; 



                    $mailer->addRecipient($recipient);



                    



                    // Set the subject



                    $mailer->setSubject(Text::_('PLG_MUYLTIBANCO_PAY_DETAILS'));



                    



                    // Set the email format to HTML and add the body



                    $mailer->isHtml(true);



                    $mailer->setBody($emailBody);



                    



                    // Optionally add a plain text alternative



                    $mailer->AltBody = Text::_('PLG_MUYLTIBANCO_EMAIL_BODY');



                    // Send the email



                    $send = $mailer->Send();







                    // Check for errors



                    if ($send !== true) {



                        Factory::getApplication()->enqueueMessage(Text::_('PLG_EMAIL_NOTIFICATION_ERROR'), 'error');



                    } else {



                        Factory::getApplication()->enqueueMessage(Text::_('PLG_EMAIL_NOTIFICATION_SUCCESS'), 'success');



                    }



                    



                    return $formWithLogo;



                }else{



                    Factory::getApplication()->enqueueMessage($message, 'error');



                }



            }







            curl_close($curl);



        } else {



            Factory::getApplication()->enqueueMessage(Text::_('PLG_FORM_REQUIRED_FIELDS_MISSING'), 'error');



        }



    }







    public function onReceivePayment($post)



    {



        if ($post['processor'] != 'multibanco') {



            return 0;



        }







        $order = $post['order_id'];



        $params = new Registry($post['params']);



        $phishingKey = $params->get('phishing_key');







        if ($phishingKey != $post['key']) {



            return 0;



        }







        $db = Factory::getDbo();



        $query = $db->getQuery(true)



            ->select(['userid', 'amount', 'amount_paid'])



            ->from($db->quoteName('#__guru_order'))



            ->where($db->quoteName('id') . ' = ' . (int) $order);



        $db->setQuery($query);



        $order_details = $db->loadAssoc();







        $customer_id = $order_details['userid'];



        $gross_amount = $order_details['amount'];







        if ($order_details['amount_paid'] != -1) {



            $gross_amount = $order_details['amount_paid'];



        }







        require_once(JPATH_SITE . '/components/com_guru/models/gurubuy.php');



        $guru_buy_model = new guruModelguruBuy();



        $submit_array = [



            'customer_id' => (int) $customer_id,



            'order_id' => (int) $order,



            'price' => $gross_amount



        ];







        $guru_buy_model->proccessSuccess('guruBuy', $submit_array, false);



    }







    private function DotPayArray2Url($param)



    {



        $out = [];



        foreach ($param as $k => $v) {



            $out[] = "$k=$v";



        }



        return implode('&', $out);



    }



}







?>



