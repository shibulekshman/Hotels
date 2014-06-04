<?php
/**
* @project ApPHP Hotel Site
* @copyright (c) 2010 - 2013 ApPHP
* @author ApPHP <info@apphp.com>
* @license http://www.gnu.org/licenses/
*/

// *** Make sure the file isn't accessed directly
defined('APPHP_EXEC') or die('Restricted Access');
//--------------------------------------------------------------------------

if(Modules::IsModuleInstalled('booking')){
	if(ModulesSettings::Get('booking', 'is_active') == 'global' ||
	   ModulesSettings::Get('booking', 'is_active') == 'front-end' ||
	  (ModulesSettings::Get('booking', 'is_active') == 'back-end' && $objLogin->IsLoggedInAsAdmin())	
	){
		
		$room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : '0';
		$from_date = isset($_POST['from_date']) ? prepare_input($_POST['from_date']) : '';
		$to_date = isset($_POST['to_date']) ? prepare_input($_POST['to_date']) : '';
		$nights_post  = isset($_POST['nights']) ? (int)$_POST['nights'] : '';
		$adults = isset($_POST['adults']) ? (int)$_POST['adults'] : '0';
		$children = isset($_POST['children']) ? (int)$_POST['children'] : '0';
		$operation_allowed = true;

		// [#001 - 08.12.2013] added to verify post data
		// -----------------------------------------------------------------
		$checkinParts = explode('-', $from_date);
		$checkin_year = isset($checkinParts[0]) ? $checkinParts[0] : '';
		$checkin_month = isset($checkinParts[1]) ? $checkinParts[1] : '';
		$checkin_day = isset($checkinParts[2]) ? $checkinParts[2] : '';
		$checkoutParts = explode('-', $to_date);
		$checkout_year = isset($checkoutParts[0]) ? $checkoutParts[0] : '';
		$checkout_month = isset($checkoutParts[1]) ? $checkoutParts[1] : '';
		$checkout_day = isset($checkoutParts[2]) ? $checkoutParts[2] : '';
		$nights = nights_diff($checkin_year.'-'.$checkin_month.'-'.$checkin_day, $checkout_year.'-'.$checkout_month.'-'.$checkout_day);
		$params = array(
			'from_date' => $checkin_year.'-'.$checkin_month.'-'.$checkin_day,
			'to_date' => $checkout_year.'-'.$checkout_month.'-'.$checkout_day,
			'from_year' => $checkin_year,
			'from_month' => $checkin_month,
			'from_day' => $checkin_day,
			'to_year' => $checkout_year,
			'to_month' => $checkout_month,
			'to_day' => $checkout_day,
			'max_adults' => $adults,
			'max_children' => $children,
		);
		if($nights_post != $nights){			
			draw_important_message(_WRONG_PARAMETER_PASSED);
			$operation_allowed = false;
		}
		// -----------------------------------------------------------------

		$available_rooms  = isset($_POST['available_rooms']) ? prepare_input($_POST['available_rooms']) : '';
		$available_rooms_parts = explode('-', $available_rooms);
		$rooms = isset($available_rooms_parts[0]) ? (int)$available_rooms_parts[0] : '';
		// -----------------------------------------------------------------
		$price_post = isset($available_rooms_parts[1]) ? (float)$available_rooms_parts[1] : '';
		$price = Rooms::GetRoomPrice($room_id, $params) * $rooms;
		if($price_post != $price){
			draw_important_message(_WRONG_PARAMETER_PASSED);
			$operation_allowed = false;
		}		
		// -----------------------------------------------------------------
		
		$available_extra_beds = isset($_POST['available_extra_beds']) ? prepare_input($_POST['available_extra_beds']) : '';
		$available_extra_beds_parts = explode('-', $available_extra_beds);
		$extra_beds = isset($available_extra_beds_parts[0]) ? (int)$available_extra_beds_parts[0] : '';
		// -----------------------------------------------------------------
		$extra_bed_charge_post = isset($available_extra_beds_parts[1]) ? (float)$available_extra_beds_parts[1] : '';		
		$extra_bed_charge = Rooms::GetRoomExtraBedsPrice($room_id, $params) * $extra_beds;
		if($extra_bed_charge_post != $extra_bed_charge){
			draw_important_message(_WRONG_PARAMETER_PASSED);
			$operation_allowed = false;
		}		
		// -----------------------------------------------------------------
		
		$meal_plan_id = isset($_POST['meal_plans']) ? (int)$_POST['meal_plans'] : '';
		$hotel_id = isset($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : '0';

		$objReservation = new Reservation();
		
		if($operation_allowed){
			$act = isset($_GET['act']) ? prepare_input($_GET['act']) : '';
			$rid = isset($_GET['rid']) ? (int)$_GET['rid'] : '';
			if($act == 'remove'){
				$objReservation->RemoveReservation($rid);
			}else{
				$objReservation->AddToReservation($room_id, $from_date, $to_date, $nights, $rooms, $price, $adults, $children, $meal_plan_id, $hotel_id, $extra_beds, $extra_bed_charge);				
			}			
		}
		
		if($objLogin->IsLoggedInAsAdmin()) draw_title_bar(prepare_breadcrumbs(array(_BOOKING=>'')));
		
		draw_content_start();
		draw_reservation_bar('selected_rooms');		

		// test mode alert
		if(Modules::IsModuleInstalled('booking')){
			if(ModulesSettings::Get('booking', 'mode') == 'TEST MODE'){
				draw_message(_TEST_MODE_ALERT_SHORT, true, true);
			}        
		}

		Campaigns::DrawCampaignBanner('standard');
		Campaigns::DrawCampaignBanner('global');

		$objReservation->ShowReservationInfo();
		draw_content_end();
		
	}else{
		draw_title_bar(_BOOKINGS);
		draw_important_message(_NOT_AUTHORIZED);	
	}
}else{
	draw_title_bar(_BOOKINGS);
	draw_important_message(_NOT_AUTHORIZED);
}

?>