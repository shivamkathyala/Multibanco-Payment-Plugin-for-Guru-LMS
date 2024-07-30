<?php

// No direct access

defined( '_JEXEC' ) or die( 'Restricted access' );



jimport('joomla.html.html');

jimport('joomla.form.formfield');

jimport('joomla.utilities.date');



class JFormFieldMultibancocallback extends JFormField{

	

	protected function getInput(){

		$return  = '<div class="alert alert-info">'.JText::_("PLG_MBWAY_INST_CALLBACK").'</div>';

		$return .= '<ul style="list-style-type: none; margin: 0px;">';

		$return .= "<li><b>".JText::_('PLG_MBWAY_APPROVED_CALLBACK')."</b> - ".JURI::root()."index.php?option=com_guru&controller=guruBuy&task=payment&processor=multibanco&order_id=[ORDER_ID]&key=[ANTI_PHISHING_KEY]&amount=[AMOUNT]&requestId=[REQUEST_ID]&pay=ipn</li>";

		$return .= "</ul>";



		return $return;

	}

}



?>