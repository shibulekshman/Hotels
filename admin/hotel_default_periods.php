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

if($objLogin->IsLoggedInAs('owner','mainadmin','hotelowner') && ModulesSettings::Get('rooms', 'allow_default_periods') == 'yes'){	

	$action = MicroGrid::GetParameter('action');
	$rid    = MicroGrid::GetParameter('rid');
	$hotel_id = MicroGrid::GetParameter('hid', false);
	$mode   = 'view';
	$msg    = '';
	
	$objHotels = new Hotels();
	$hotel_info = $objHotels->GetHotelFullInfo($hotel_id);
	
	if($hotel_id > 0 && count($hotel_info) > 0){
		
		$objHotelPeriods = new HotelPeriods($hotel_id);
		
		if($action=='add'){		
			$mode = 'add';
		}else if($action=='create'){
			if($objHotelPeriods->AddRecord()){		
				$msg = draw_success_message(_ADDING_OPERATION_COMPLETED, false);
				$mode = 'view';
			}else{
				$msg = draw_important_message($objHotelPeriods->error, false);
				$mode = 'add';
			}
		}else if($action=='edit'){
			$mode = 'edit';
		}else if($action=='update'){
			if($objHotelPeriods->UpdateRecord($rid)){
				$msg = draw_success_message(_UPDATING_OPERATION_COMPLETED, false);
				$mode = 'view';
			}else{
				$msg = draw_important_message($objHotelPeriods->error, false);
				$mode = 'edit';
			}		
		}else if($action=='delete'){
			if($objHotelPeriods->DeleteRecord($rid)){
				$msg = draw_success_message(_DELETING_OPERATION_COMPLETED, false);
			}else{
				$msg = draw_important_message($objHotelPeriods->error, false);
			}
			$mode = 'view';
		}else if($action=='details'){		
			$mode = 'details';		
		}else if($action=='cancel_add'){		
			$mode = 'view';		
		}else if($action=='cancel_edit'){				
			$mode = 'view';
		}

		// Start main content
		draw_title_bar(
			prepare_breadcrumbs(array(_HOTEL_MANAGEMENT=>'',_HOTELS_INFO=>'',$hotel_info['name']=>'',_PERIODS=>'',ucfirst($action)=>'')),
			prepare_permanent_link('index.php?admin=hotels_info', _BUTTON_BACK)
		);
	
		//if($objSession->IsMessage('notice')) echo $objSession->GetMessage('notice');
		if($mode == 'view' && $msg == ''){
			$msg = draw_message(_DEFAULT_PERIODS_ALERT, false);
		}
		echo $msg;
	
		draw_content_start();
		if($mode == 'view'){		
			$objHotelPeriods->DrawViewMode();	
		}else if($mode == 'add'){		
			$objHotelPeriods->DrawAddMode();		
		}else if($mode == 'edit'){		
			$objHotelPeriods->DrawEditMode($rid);		
		}else if($mode == 'details'){		
			$objHotelPeriods->DrawDetailsMode($rid);		
		}
		draw_content_end();		
	}else{
		draw_title_bar(
			prepare_breadcrumbs(array(_HOTEL_MANAGEMENT=>'',_HOTELS_INFO=>'',_PERIODS=>'')),
			prepare_permanent_link('index.php?admin=hotels_info', _BUTTON_BACK)
		);
		draw_important_message(_WRONG_PARAMETER_PASSED);
	}
}else{
	draw_title_bar(_ADMIN);
	draw_important_message(_NOT_AUTHORIZED);
}

?>