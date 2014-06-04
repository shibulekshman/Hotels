<?php

/**
 *	Rooms Class (for HotelSite ONLY)
 *  -------------- 
 *	Written by  : ApPHP
 *  Updated	    : 08.01.2014
 *	Written by  : ApPHP
 *
 *	PUBLIC:						STATIC:							PRIVATE:
 *  -----------					-----------						-----------
 *  __construct					GetRoomAvalibilityForWeek		GetMonthMaxDay  
 *  __destruct                  GetRoomAvalibilityForMonth      CheckAvailabilityForPeriod
 *  DrawRoomAvailabilitiesForm  GetRoomInfo 					DrawPaginationLinks
 *  DrawRoomPricesForm          GetRoomTypes                    DrawHotelInfoBlock
 *  DeleteRoomAvailability      GetMonthLastDay                 DrawExtraBedsDDL
 *  DeleteRoomPrices 		    DrawSearchAvailabilityBlock     
 *  AddRoomAvailability         DrawSearchAvailabilityFooter    
 *  AddRoomPrices               DrawRoomsInfo                   
 *  UpdateRoomAvailability      ConvertToDecimal (private)      
 *  UpdateRoomPrices            GetPriceForDate
 *  AfterInsertRecord           GetRoomPricesTable
 *  BeforeUpdateRecord          DrawRoomDescription 
 *	AfterUpdateRecord           DrawRoomsInHotel
 *	AfterDeleteRecord           GetAllActive 
 *	SearchFor                   GetRoomPrice
 *	DrawSearchResult            GetRoomDefaultPrice
 *	                            GetRoomWeekDefaultPrice
 *	                            GetRoomExtraBedsPrice
 **/


class Rooms extends MicroGrid {
	
	protected $debug = false;
	
	//-------------------------
	private $arrAvailableRooms;
	private $arrBeds;
	private $arrBathrooms;
	private $currencyFormat;
	private $roomsCount;
	private $hotelsList;

	//==========================================================================
    // Class Constructor
	//==========================================================================
	function __construct()
	{		
		parent::__construct();

		global $objLogin;

		$this->params = array();
		$this->arrAvailableRooms = array();
		
		## for standard fields
		if(isset($_POST['room_type']))  $this->params['room_type'] = prepare_input($_POST['room_type']);
		if(isset($_POST['room_short_description'])) $this->params['room_short_description'] = prepare_input($_POST['room_short_description']);
		if(isset($_POST['room_long_description'])) $this->params['room_long_description'] = prepare_input($_POST['room_long_description']);
		if(isset($_POST['max_adults'])) $this->params['max_adults'] = prepare_input($_POST['max_adults']);
		if(isset($_POST['max_children'])) $this->params['max_children'] = prepare_input($_POST['max_children']);
		if(isset($_POST['max_extra_beds'])) $this->params['max_extra_beds'] = prepare_input($_POST['max_extra_beds']);
		if(isset($_POST['room_count'])) $this->params['room_count'] = prepare_input($_POST['room_count']);		
		if(isset($_POST['default_price'])) $this->params['default_price'] = prepare_input($_POST['default_price']);
		if(isset($_POST['extra_bed_charge'])) $this->params['extra_bed_charge'] = prepare_input($_POST['extra_bed_charge']);		
		if(isset($_POST['priority_order'])) $this->params['priority_order'] = prepare_input($_POST['priority_order']);
		if(isset($_POST['beds'])) $this->params['beds'] = prepare_input($_POST['beds']);
		if(isset($_POST['bathrooms'])) $this->params['bathrooms'] = prepare_input($_POST['bathrooms']);
		if(isset($_POST['room_area'])) $this->params['room_area'] = prepare_input($_POST['room_area']);		
		if(isset($_POST['facilities'])) $this->params['facilities'] = prepare_input($_POST['facilities']);
		if(isset($_POST['hotel_id'])) $this->params['hotel_id'] = prepare_input($_POST['hotel_id']);
		$image_prefix = (isset($_POST['hotel_id'])) ? prepare_input($_POST['hotel_id']).'_' : '';
		
		## for checkboxes 
		if(isset($_POST['is_active'])) $this->params['is_active'] = (int)$_POST['is_active']; else $this->params['is_active'] = '0';

		## for images
		if(isset($_POST['room_icon'])) { 
			$this->params['room_icon'] = prepare_input($_POST['room_icon']);
		}else if(isset($_FILES['room_icon']['name']) && $_FILES['room_icon']['name'] != ''){
			// nothing 			
		}else if (self::GetParameter('action') == 'create'){
			$this->params['room_icon'] = '';
		}
		
		$this->params['language_id'] = MicroGrid::GetParameter('language_id');
        $watermark = (ModulesSettings::Get('rooms', 'watermark') == 'yes') ? true : false;
        $watermark_text = ModulesSettings::Get('rooms', 'watermark_text');
	
		$this->primaryKey 	= 'id';
		$this->tableName 	= TABLE_ROOMS;
		$this->dataSet 		= array();
		$this->error 		= '';
		$this->formActionURL = 'index.php?admin=mod_rooms_management';
		$this->actions      = array('add'=>true, 'edit'=>true, 'details'=>true, 'delete'=>true);
		$this->actionIcons  = true;
		$this->allowRefresh = true;
		$this->allowTopButtons = true;
		
		$this->allowLanguages = false;
		$this->languageId  	= ($this->params['language_id'] != '') ? $this->params['language_id'] : Languages::GetDefaultLang();

		$this->WHERE_CLAUSE = '';
		$this->hotelsList = '';
		if($objLogin->IsLoggedInAs('hotelowner')){
			$this->hotelsList = implode(',', $objLogin->AssignedToHotels());
			if(!empty($this->hotelsList)) $this->WHERE_CLAUSE .= 'WHERE '.$this->tableName.'.hotel_id IN ('.$this->hotelsList.')';
		}
		$this->ORDER_CLAUSE = 'ORDER BY '.$this->tableName.'.hotel_id ASC, '.$this->tableName.'.priority_order ASC';
		
		$this->isAlterColorsAllowed = true;

		$this->isPagingAllowed = true;
		$this->pageSize = 20;	

		$this->isSortingAllowed = true;

		// prepare hotels array		
		$total_hotels = Hotels::GetAllActive((!empty($this->hotelsList) ? TABLE_HOTELS.'.id IN ('.$this->hotelsList.')' : ''));
		$arr_hotels = array();
		foreach($total_hotels[0] as $key => $val){
			$arr_hotels[$val['id']] = $val['name'];
		}		

		// prepare facilities array		
		$total_facilities = RoomFacilities::GetAllActive();
		$arr_facilities = array();
		foreach($total_facilities[0] as $key => $val){
			$arr_facilities[$val['id']] = $val['name'];
		}

		$this->isFilteringAllowed = true;
		// define filtering fields
		$this->arrFilteringFields = array(
			_HOTEL  => array('table'=>$this->tableName, 'field'=>'hotel_id', 'type'=>'dropdownlist', 'source'=>$arr_hotels, 'sign'=>'=', 'width'=>'130px', 'visible'=>true),
		);

		$this->isAggregateAllowed = true;
		// define aggregate fields for View Mode
		$this->arrAggregateFields = array(
			'room_count' => array('function'=>'SUM'),
			///'field2' => array('function'=>'AVG'),
		);

		// prepare languages array		
		/// $total_languages = Languages::GetAllActive();
		/// $arr_languages      = array();
		/// foreach($total_languages[0] as $key => $val){
		/// 	$arr_languages[$val['abbreviation']] = $val['lang_name'];
		/// }

		$this->currencyFormat = get_currency_format();		
	
		$this->arrBeds = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
		$this->arrBathrooms = array(0, 1, 2, 3);
		$arr_is_active = array('0'=>'<span class=no>'._NO.'</span>', '1'=>'<span class=yes>'._YES.'</span>');
		
		$default_currency = Currencies::GetDefaultCurrency();
		
		$random_name = true;
		$booking_active = (Modules::IsModuleInstalled('booking')) ? ModulesSettings::Get('booking', 'is_active') : false;
		$allow_children = ModulesSettings::Get('rooms', 'allow_children');
		$allow_extra_beds = ModulesSettings::Get('rooms', 'allow_extra_beds');

		//---------------------------------------------------------------------- 
		// VIEW MODE
		//---------------------------------------------------------------------- 
		$this->VIEW_MODE_SQL = 'SELECT
									'.$this->tableName.'.'.$this->primaryKey.',
									'.$this->tableName.'.hotel_id,
									'.$this->tableName.'.max_adults,
									'.$this->tableName.'.max_children,
									'.$this->tableName.'.max_extra_beds,
									'.$this->tableName.'.room_count,
									'.$this->tableName.'.default_price,
									'.$this->tableName.'.extra_bed_charge,
									'.$this->tableName.'.room_icon,
									'.$this->tableName.'.room_icon_thumb,
									'.$this->tableName.'.priority_order,
									'.$this->tableName.'.is_active,
									CONCAT("<a href=\"index.php?admin=mod_room_prices&rid=", '.$this->tableName.'.'.$this->primaryKey.', "\" title=\"'._CLICK_TO_MANAGE.'\">", "[ '._PRICES.' ]", "</a>") as link_prices,
									CONCAT("<a href=\"index.php?admin=mod_room_availability&rid=", '.$this->tableName.'.'.$this->primaryKey.', "\" title=\"'._CLICK_TO_MANAGE.'\">", "[ '._AVAILABILITY.' ]", "</a>") as link_room_availability,
									CONCAT("<a href=\"index.php?admin=mod_booking_rooms_occupancy&sel_room_types=", '.$this->tableName.'.'.$this->primaryKey.', "\" title=\"'._CLICK_TO_MANAGE.'\">", "[ '._OCCUPANCY.' ]", "</a>") as link_room_occupancy,
									CONCAT("<a href=\"index.php?admin=mod_room_description&room_id=", '.$this->tableName.'.'.$this->primaryKey.', "\" title=\"'._CLICK_TO_MANAGE.'\">[ ", "'._DESCRIPTION.'", " ]</a>") as link_room_description,
									rd.room_type,
									rd.room_short_description,
									rd.room_long_description
								FROM '.$this->tableName.'
									INNER JOIN '.TABLE_HOTELS.' ON '.$this->tableName.'.hotel_id = '.TABLE_HOTELS.'.id AND '.TABLE_HOTELS.'.is_active = 1
									LEFT OUTER JOIN '.TABLE_ROOMS_DESCRIPTION.' rd ON '.$this->tableName.'.'.$this->primaryKey.' = rd.room_id AND rd.language_id = \''.$this->languageId.'\' ';
		// define view mode fields
		$this->arrViewModeFields = array(

			'hotel_id'        => array('title'=>_HOTEL, 'type'=>'enum',  'align'=>'left', 'width'=>'100px', 'sortable'=>true, 'nowrap'=>'', 'visible'=>true, 'source'=>$arr_hotels),
			'room_icon_thumb' => array('title'=>_ICON_IMAGE, 'type'=>'image', 'align'=>'center', 'width'=>'80px', 'image_width'=>'60px', 'image_height'=>'30px', 'target'=>'images/rooms_icons/', 'no_image'=>'no_image.png'),
			'room_type'  	  => array('title'=>_TYPE, 'type'=>'label', 'align'=>'left', 'width'=>'', 'maxlength'=>'32'),
			'room_count' 	  => array('title'=>_COUNT, 'type'=>'label', 'align'=>'center', 'width'=>'49px', 'maxlength'=>''),
			'max_adults'      => array('title'=>_ADULTS, 'type'=>'label', 'align'=>'center', 'width'=>'49px', 'maxlength'=>''),
			'max_children'    => array('title'=>_CHILD, 'type'=>'label', 'align'=>'center', 'width'=>'49px', 'maxlength'=>'', 'visible'=>(($allow_children == 'yes') ? true : false)),
			'max_extra_beds'      => array('title'=>_EXTRA_BEDS, 'type'=>'label', 'align'=>'center', 'width'=>'49px', 'maxlength'=>'', 'visible'=>(($allow_extra_beds == 'yes') ? true : false)),
			'is_active' 	  => array('title'=>_ACTIVE, 'type'=>'enum',  'align'=>'center', 'width'=>'49px', 'sortable'=>true, 'nowrap'=>'', 'visible'=>true, 'source'=>$arr_is_active),
			'priority_order'  => array('title'=>_ORDER, 'type'=>'label', 'align'=>'center', 'width'=>'60px', 'maxlength'=>'', 'movable'=>true),
			'link_room_description' => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'', 'maxlength'=>'', 'nowrap'=>'nowrap'),			
			'link_prices' 	  => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'', 'maxlength'=>'', 'nowrap'=>'nowrap'),
			'link_room_availability' => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'', 'maxlength'=>'', 'nowrap'=>'nowrap'),
			'link_room_occupancy'    => array('title'=>'', 'type'=>'label', 'align'=>'center', 'width'=>'', 'maxlength'=>'', 'nowrap'=>'nowrap', 'visible'=>(($booking_active == 'global') ? true : false)),
			'_empty_'  	      => array('title'=>'', 'type'=>'label', 'align'=>'left', 'width'=>'15px'), 
		);
		
		//---------------------------------------------------------------------- 
		// ADD MODE
		// Validation Type: alpha|numeric|float|alpha_numeric|text|email
		// Validation Sub-Type: positive (for numeric and float)
		// Ex.: 'validation_type'=>'numeric', 'validation_type'=>'numeric|positive'
		//---------------------------------------------------------------------- 
		// define add mode fields
		$this->arrAddModeFields = array(
			'separator_1'   =>array(
				'separator_info' => array('legend'=>_ROOM_DETAILS),
				'hotel_id'       => array('title'=>_HOTEL, 'type'=>'enum',  'width'=>'',   'required'=>true, 'readonly'=>false, 'default'=>((count($arr_hotels) == 1) ? key($arr_hotels) : ''), 'source'=>$arr_hotels, 'default_option'=>'', 'unique'=>false, 'javascript_event'=>'', 'view_type'=>'dropdownlist', 'multi_select'=>false),
				'room_type'  	 => array('title'=>_TYPE, 'type'=>'textbox',  'width'=>'270px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'70', 'default'=>'', 'validation_type'=>'text'),
				'room_short_description' => array('title'=>_SHORT_DESCRIPTION, 'type'=>'textarea', 'editor_type'=>'wysiwyg', 'width'=>'410px', 'height'=>'40px', 'required'=>true, 'readonly'=>false, 'default'=>'', 'validation_type'=>'text', 'validation_maxlength'=>'512'),
				'room_long_description' => array('title'=>_LONG_DESCRIPTION, 'type'=>'textarea', 'editor_type'=>'wysiwyg', 'width'=>'410px', 'height'=>'70px', 'required'=>false, 'readonly'=>false, 'default'=>'', 'validation_type'=>'text', 'validation_maxlength'=>'4096'),
				'max_adults'     => array('title'=>_MAX_ADULTS, 'type'=>'textbox', 'header_tooltip'=>_MAX_ADULTS_ACCOMMODATE, 'width'=>'40px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'2', 'default'=>'1', 'validation_type'=>'numeric|positive'),
				'max_children'   => array('title'=>_MAX_CHILDREN, 'type'=>'textbox', 'header_tooltip'=>_MAX_CHILDREN_ACCOMMODATE, 'width'=>'40px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'2', 'default'=>'0', 'validation_type'=>'numeric|positive', 'visible'=>(($allow_children == 'yes') ? true : false)),			
				'max_extra_beds'     => array('title'=>_MAX_EXTRA_BEDS, 'type'=>'textbox',  'width'=>'30px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'1', 'default'=>'0', 'validation_type'=>'numeric|positive', 'visible'=>(($allow_extra_beds == 'yes') ? true : false)),			
				'room_count'     => array('title'=>_ROOMS_COUNT, 'type'=>'textbox',  'width'=>'50px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'3', 'default'=>'1', 'validation_type'=>'numeric|positive'),
				'beds'           => array('title'=>_BEDS, 'type'=>'enum', 'width'=>'', 'required'=>false, 'readonly'=>false, 'default'=>'', 'source'=>$this->arrBeds, 'default_option'=>false, 'unique'=>false, 'javascript_event'=>'', 'view_type'=>'dropdownlist', 'multi_select'=>false),
				'bathrooms'      => array('title'=>_BATHROOMS, 'type'=>'enum', 'width'=>'', 'required'=>false, 'readonly'=>false, 'default'=>'', 'source'=>$this->arrBathrooms, 'default_option'=>false, 'unique'=>false, 'javascript_event'=>'', 'view_type'=>'dropdownlist', 'multi_select'=>false),
				'room_area'      => array('title'=>_ROOM_AREA, 'type'=>'textbox',  'width'=>'60px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'4', 'default'=>'0', 'validation_type'=>'float|positive', 'validation_maximum'=>'999', 'post_html'=>' m<sup>2</sup>'),
				'default_price'  => array('title'=>_DEFAULT_PRICE, 'type'=>'textbox',  'width'=>'60px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'10', 'default'=>'0', 'validation_type'=>'float|positive', 'pre_html'=>$default_currency.' '),
				'extra_bed_charge' => array('title'=>_EXTRA_BED_CHARGE, 'type'=>'textbox',  'width'=>'60px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'10', 'default'=>'0.00', 'validation_type'=>'float|positive', 'pre_html'=>$default_currency.' '),
				'priority_order' => array('title'=>_ORDER, 'type'=>'textbox',  'width'=>'35px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'3', 'default'=>'0', 'validation_type'=>'numeric|positive'),
				'is_active'      => array('title'=>_ACTIVE, 'type'=>'checkbox', 'readonly'=>false, 'default'=>'1', 'true_value'=>'1', 'false_value'=>'0'),
			),
			'separator_2'   =>array(
				'separator_info' => array('legend'=>_ROOM_FACILITIES),
				'facilities'     => array('title'=>_FACILITIES, 'type'=>'enum',  'width'=>'', 'required'=>false, 'readonly'=>false, 'default'=>'', 'source'=>$arr_facilities, 'default_option'=>'', 'unique'=>false, 'javascript_event'=>'', 'view_type'=>'checkboxes', 'multi_select'=>true),
			),
			'separator_3'   =>array(
				'separator_info' => array('legend'=>_IMAGES, 'columns'=>'2'),
				'room_icon'      => array('title'=>_ICON_IMAGE, 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'icon_', 'unique'=>true, 'thumbnail_create'=>true, 'thumbnail_field'=>'room_icon_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'500k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
				'room_picture_1' => array('title'=>_IMAGE.' 1', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'view1_', 'unique'=>true, 'thumbnail_create'=>true, 'thumbnail_field'=>'room_picture_1_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'900k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
				'room_picture_2' => array('title'=>_IMAGE.' 2', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'view2_', 'unique'=>true, 'thumbnail_create'=>true, 'thumbnail_field'=>'room_picture_2_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'900k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
				'room_picture_3' => array('title'=>_IMAGE.' 3', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'view3_', 'unique'=>true, 'thumbnail_create'=>true, 'thumbnail_field'=>'room_picture_3_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'900k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
				'room_picture_4' => array('title'=>_IMAGE.' 4', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'view4_', 'unique'=>true, 'thumbnail_create'=>true, 'thumbnail_field'=>'room_picture_4_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'900k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
				'room_picture_5' => array('title'=>_IMAGE.' 5', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'view5_', 'unique'=>true, 'thumbnail_create'=>true, 'thumbnail_field'=>'room_picture_5_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'900k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
			)
		);

		//---------------------------------------------------------------------- 
		// EDIT MODE
		// Validation Type: alpha|numeric|float|alpha_numeric|text|email
		// Validation Sub-Type: positive (for numeric and float)
		// Ex.: 'validation_type'=>'numeric', 'validation_type'=>'numeric|positive'
		//---------------------------------------------------------------------- 
		$this->EDIT_MODE_SQL = 'SELECT
								'.$this->tableName.'.'.$this->primaryKey.',
								'.$this->tableName.'.hotel_id,
								'.$this->tableName.'.room_type,
								'.$this->tableName.'.room_short_description,
								'.$this->tableName.'.room_long_description,
								'.$this->tableName.'.max_adults,
								'.$this->tableName.'.max_children,
								'.$this->tableName.'.max_extra_beds,
								'.$this->tableName.'.room_count,
								'.$this->tableName.'.default_price,
								'.$this->tableName.'.extra_bed_charge,
								'.$this->tableName.'.beds,
								'.$this->tableName.'.bathrooms,
								'.$this->tableName.'.room_area,
								'.$this->tableName.'.facilities,
								'.$this->tableName.'.room_icon,
								'.$this->tableName.'.room_icon_thumb,
								'.$this->tableName.'.room_picture_1,
								'.$this->tableName.'.room_picture_1_thumb,
								'.$this->tableName.'.room_picture_2,
								'.$this->tableName.'.room_picture_2_thumb,
								'.$this->tableName.'.room_picture_3,
								'.$this->tableName.'.room_picture_3_thumb,
								'.$this->tableName.'.room_picture_4,
								'.$this->tableName.'.room_picture_4_thumb,
								'.$this->tableName.'.room_picture_5,
								'.$this->tableName.'.room_picture_5_thumb,
								'.$this->tableName.'.priority_order,
								'.$this->tableName.'.is_active,
								rd.room_type as m_room_type
							FROM '.$this->tableName.'
								LEFT OUTER JOIN '.TABLE_ROOMS_DESCRIPTION.' rd ON '.$this->tableName.'.'.$this->primaryKey.' = rd.room_id
							WHERE '.$this->tableName.'.'.$this->primaryKey.' = _RID_';		
		// define edit mode fields
		$this->arrEditModeFields = array(
			'separator_1'   =>array(
				'separator_info' => array('legend'=>_ROOM_DETAILS),
				'hotel_id'       => array('title'=>_HOTEL, 'type'=>'enum',  'width'=>'',   'required'=>true, 'readonly'=>false, 'default'=>'', 'source'=>$arr_hotels, 'default_option'=>'', 'unique'=>false, 'javascript_event'=>'', 'view_type'=>'dropdownlist', 'multi_select'=>false),
				'm_room_type'    => array('title'=>_ROOM_TYPE, 'type'=>'label'),
				'max_adults'     => array('title'=>_MAX_ADULTS, 'type'=>'textbox', 'header_tooltip'=>_MAX_ADULTS_ACCOMMODATE, 'width'=>'40px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'2', 'default'=>'', 'validation_type'=>'numeric|positive'),
				'max_children'   => array('title'=>_MAX_CHILDREN, 'type'=>'textbox', 'header_tooltip'=>_MAX_CHILDREN_ACCOMMODATE, 'width'=>'40px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'2', 'default'=>'', 'validation_type'=>'numeric|positive', 'visible'=>(($allow_children == 'yes') ? true : false)),
				'max_extra_beds'     => array('title'=>_MAX_EXTRA_BEDS, 'type'=>'textbox',  'width'=>'30px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'1', 'default'=>'0', 'validation_type'=>'numeric|positive', 'visible'=>(($allow_extra_beds == 'yes') ? true : false)),			
				'room_count'     => array('title'=>_ROOMS_COUNT, 'type'=>'textbox',  'width'=>'50px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'3', 'default'=>'0', 'validation_type'=>'numeric|positive'),
				'beds'           => array('title'=>_BEDS, 'type'=>'enum', 'width'=>'', 'required'=>false, 'readonly'=>false, 'default'=>'', 'source'=>$this->arrBeds, 'default_option'=>false, 'unique'=>false, 'javascript_event'=>'', 'view_type'=>'dropdownlist', 'multi_select'=>false),
				'bathrooms'      => array('title'=>_BATHROOMS, 'type'=>'enum', 'width'=>'', 'required'=>false, 'readonly'=>false, 'default'=>'', 'source'=>$this->arrBathrooms, 'default_option'=>false, 'unique'=>false, 'javascript_event'=>'', 'view_type'=>'dropdownlist', 'multi_select'=>false),
				'room_area'      => array('title'=>_ROOM_AREA, 'type'=>'textbox',  'width'=>'60px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'4', 'default'=>'0', 'validation_type'=>'float|positive', 'validation_maximum'=>'999', 'post_html'=>' m<sup>2</sup>'),
				'default_price'  => array('title'=>_DEFAULT_PRICE, 'type'=>'textbox',  'width'=>'60px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'10', 'default'=>'0', 'validation_type'=>'float|positive', 'pre_html'=>$default_currency.' '),
				'extra_bed_charge' => array('title'=>_EXTRA_BED_CHARGE, 'type'=>'textbox',  'width'=>'60px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'10', 'default'=>'0', 'validation_type'=>'float|positive', 'pre_html'=>$default_currency.' '),
				'priority_order' => array('title'=>_ORDER, 'type'=>'textbox',  'width'=>'35px', 'required'=>true, 'readonly'=>false, 'maxlength'=>'3', 'default'=>'0', 'validation_type'=>'numeric|positive'),
				'is_active'      => array('title'=>_ACTIVE, 'type'=>'checkbox', 'readonly'=>false, 'default'=>'1', 'true_value'=>'1', 'false_value'=>'0'),
			),
			'separator_2'   =>array(
				'separator_info' => array('legend'=>_ROOM_FACILITIES),
				'facilities'     => array('title'=>_FACILITIES, 'type'=>'enum',  'width'=>'', 'required'=>false, 'readonly'=>false, 'default'=>'', 'source'=>$arr_facilities, 'default_option'=>'', 'unique'=>false, 'javascript_event'=>'', 'view_type'=>'checkboxes', 'multi_select'=>true),
			),
			'separator_3'   =>array(
				'separator_info' => array('legend'=>_IMAGES, 'columns'=>'2'),
				'room_icon'      => array('title'=>_ICON_IMAGE, 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'icon_', 'thumbnail_create'=>true, 'thumbnail_field'=>'room_icon_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'500k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
				'room_picture_1' => array('title'=>_IMAGE.' 1', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'view1_', 'thumbnail_create'=>true, 'thumbnail_field'=>'room_picture_1_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'900k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
				'room_picture_2' => array('title'=>_IMAGE.' 2', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'view2_', 'thumbnail_create'=>true, 'thumbnail_field'=>'room_picture_2_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'900k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
				'room_picture_3' => array('title'=>_IMAGE.' 3', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'view3_', 'thumbnail_create'=>true, 'thumbnail_field'=>'room_picture_3_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'900k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
				'room_picture_4' => array('title'=>_IMAGE.' 4', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'view4_', 'thumbnail_create'=>true, 'thumbnail_field'=>'room_picture_4_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'900k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
				'room_picture_5' => array('title'=>_IMAGE.' 5', 'type'=>'image', 'width'=>'210px', 'required'=>false, 'target'=>'images/rooms_icons/', 'no_image'=>'', 'random_name'=>$random_name, 'image_name_pefix'=>$image_prefix.'view5_', 'thumbnail_create'=>true, 'thumbnail_field'=>'room_picture_5_thumb', 'thumbnail_width'=>'190px', 'thumbnail_height'=>'', 'file_maxsize'=>'900k', 'watermark'=>$watermark, 'watermark_text'=>$watermark_text),
			)
		);

		//---------------------------------------------------------------------- 
		// DETAILS MODE
		//----------------------------------------------------------------------
		$this->DETAILS_MODE_SQL = $this->EDIT_MODE_SQL;
		$this->arrDetailsModeFields = array(
			'separator_1'   =>array(
				'separator_info' => array('legend'=>_ROOM_DETAILS),
				'hotel_id'       => array('title'=>_HOTEL, 'type'=>'enum', 'source'=>$arr_hotels),
				'room_type'  	 => array('title'=>_TYPE, 'type'=>'label'),
				'max_adults' 	 => array('title'=>_MAX_ADULTS, 'type'=>'label', 'header_tooltip'=>_MAX_ADULTS_ACCOMMODATE, ),
				'max_children' 	 => array('title'=>_MAX_CHILDREN, 'type'=>'label', 'header_tooltip'=>_MAX_CHILDREN_ACCOMMODATE, 'visible'=>(($allow_children == 'yes') ? true : false)),
				'max_extra_beds' 	 => array('title'=>_MAX_EXTRA_BEDS, 'type'=>'label', 'visible'=>(($allow_extra_beds == 'yes') ? true : false)),				
				'room_count'     => array('title'=>_ROOMS_COUNT, 'type'=>'label'),
				'beds'           => array('title'=>_BEDS, 'type'=>'label'),
				'bathrooms'      => array('title'=>_BATHROOMS, 'type'=>'label'),
				'room_area'      => array('title'=>_ROOM_AREA, 'type'=>'label', 'format'=>'currency', 'format_parameter'=>$this->currencyFormat.'|2', 'post_html'=>' m<sup>2</sup>'),
				'default_price'  => array('title'=>_DEFAULT_PRICE, 'type'=>'label', 'format'=>'currency', 'format_parameter'=>$this->currencyFormat.'|2', 'pre_html'=>$default_currency),
				'extra_bed_charge'  => array('title'=>_EXTRA_BED_CHARGE, 'type'=>'label', 'format'=>'currency', 'format_parameter'=>$this->currencyFormat.'|2', 'pre_html'=>$default_currency),
				'priority_order' => array('title'=>_ORDER, 'type'=>'label'),
				'is_active'      => array('title'=>_ACTIVE, 'type'=>'enum', 'source'=>$arr_is_active),
			),
			'separator_2'   =>array(
				'separator_info' => array('legend'=>_ROOM_FACILITIES),
				'facilities'     => array('title'=>_FACILITIES, 'type'=>'enum',  'width'=>'', 'required'=>false, 'readonly'=>false, 'default'=>'', 'source'=>$arr_facilities, 'default_option'=>'', 'unique'=>false, 'javascript_event'=>'', 'view_type'=>'checkboxes', 'multi_select'=>true),
			),
			'separator_3'   =>array(
				'separator_info' => array('legend'=>_IMAGES, 'columns'=>'2'),
				'room_icon'      => array('title'=>_ICON_IMAGE, 'type'=>'image', 'target'=>'images/rooms_icons/', 'no_image'=>'no_image.png'),
				'room_picture_1' => array('title'=>_IMAGE.' 1', 'type'=>'image', 'target'=>'images/rooms_icons/', 'no_image'=>'no_image.png'),
				'room_picture_2' => array('title'=>_IMAGE.' 2', 'type'=>'image', 'target'=>'images/rooms_icons/', 'no_image'=>'no_image.png'),
				'room_picture_3' => array('title'=>_IMAGE.' 3', 'type'=>'image', 'target'=>'images/rooms_icons/', 'no_image'=>'no_image.png'),
				'room_picture_4' => array('title'=>_IMAGE.' 4', 'type'=>'image', 'target'=>'images/rooms_icons/', 'no_image'=>'no_image.png'),
				'room_picture_5' => array('title'=>_IMAGE.' 5', 'type'=>'image', 'target'=>'images/rooms_icons/', 'no_image'=>'no_image.png'),
			)
		);
	}		

	//==========================================================================
    // Class Destructor
	//==========================================================================
    function __destruct()
	{
		// echo 'this object has been destroyed';
    }
	/**
	 *	Draws room availabilities form
	 *		@param $rid
	 */
	public function DrawRoomAvailabilitiesForm($rid)
	{
		global $objSettings;
		
		$nl = "\n";

		$sql = 'SELECT *
				FROM '.TABLE_ROOMS.'
				WHERE id = '.(int)$rid.'
				'.(!empty($this->hotelsList) ? ' AND '.TABLE_ROOMS.'.hotel_id IN ('.$this->hotelsList.')' : '');
		$room = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($room[1] == 0){
			draw_important_message(_WRONG_PARAMETER_PASSED);
			return false;
		}

		$lang['weeks'][0] = (defined('_SU')) ? _SU : 'Su';
		$lang['weeks'][1] = (defined('_MO')) ? _MO : 'Mo';
		$lang['weeks'][2] = (defined('_TU')) ? _TU : 'Tu';
		$lang['weeks'][3] = (defined('_WE')) ? _WE : 'We';
		$lang['weeks'][4] = (defined('_TH')) ? _TH : 'Th';
		$lang['weeks'][5] = (defined('_FR')) ? _FR : 'Fr';
		$lang['weeks'][6] = (defined('_SA')) ? _SA : 'Sa';

		$lang['months'][1] = (defined('_JANUARY')) ? _JANUARY : 'January';
		$lang['months'][2] = (defined('_FEBRUARY')) ? _FEBRUARY : 'February';
		$lang['months'][3] = (defined('_MARCH')) ? _MARCH : 'March';
		$lang['months'][4] = (defined('_APRIL')) ? _APRIL : 'April';
		$lang['months'][5] = (defined('_MAY')) ? _MAY : 'May';
		$lang['months'][6] = (defined('_JUNE')) ? _JUNE : 'June';
		$lang['months'][7] = (defined('_JULY')) ? _JULY : 'July';
		$lang['months'][8] = (defined('_AUGUST')) ? _AUGUST : 'August';
		$lang['months'][9] = (defined('_SEPTEMBER')) ? _SEPTEMBER : 'September';
		$lang['months'][10] = (defined('_OCTOBER')) ? _OCTOBER : 'October';
		$lang['months'][11] = (defined('_NOVEMBER')) ? _NOVEMBER : 'November';
		$lang['months'][12] = (defined('_DECEMBER')) ? _DECEMBER : 'December';

		$room_type 	   = isset($_REQUEST['room_type']) ? prepare_input($_REQUEST['room_type']) : '';
		$from_new 	   = isset($_POST['from_new']) ? prepare_input($_POST['from_new']) : '';
		$to_new 	   = isset($_POST['to_new']) ? prepare_input($_POST['to_new']) : '';
		$year 	       = isset($_REQUEST['year']) ? prepare_input($_REQUEST['year']) : 'current';
		$ids_list 	   = '';
		$max_days 	   = 0;
		$output        = '';
		$output_week_days = '';		
		$current_month = date('m');
		$current_year  = date('Y');		
		$selected_year  = ($year == 'next') ? $current_year+1 : $current_year;

		$room_info = $this->GetInfoByID($rid);
		$room_count = isset($room_info['room_count']) ? $room_info['room_count'] : '0';
		
		$output .= '<script type="text/javascript">
			function submitAvailabilityForm(task){
				if(task == "refresh"){
					document.getElementById("task").value = task;
					document.getElementById("frmRoomAvailability").submit();				
				}else if(task == "delete"){
					if(confirm("'._DELETE_WARNING_COMMON.'")){
						document.getElementById("task").value = task;
						document.getElementById("frmRoomAvailability").submit();
					}				
				}else if(task == "update" || task == "add_new"){
					document.getElementById("task").value = task;
					document.getElementById("frmRoomAvailability").submit();
				}
			}
			function toggleAvailability(selection_type, rid){				
				var selection_type = (selection_type == 1) ? true : false;
				var room_count = "'.$room_count.'";
				for(i=1; i<=31; i++){
					if(document.getElementById("aval_"+rid+"_"+i))
					   document.getElementById("aval_"+rid+"_"+i).value = (selection_type) ? room_count : "0";
				}
			}
		</script>'.$nl;

		$output .= '<form action="index.php?admin=mod_room_availability" id="frmRoomAvailability" method="post">';
		$output .= draw_hidden_field('task', 'update', false, 'task');
		$output .= draw_hidden_field('rid', $rid, false, 'rid');
		$output .= draw_hidden_field('year', $year, false, 'year');
		$output .= draw_hidden_field('room_type', $room_type, false, 'room_type');
		$output .= draw_token_field(false);
		
		$output .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
		$output .= '<tr>';
		$output .= '<td align="left" colspan="27">
						<span class="gray">'.str_replace('_MAX_', $room_count, _AVAILABILITY_ROOMS_NOTE).'</span>						
					</td>
					<td align="right" colspan="5">
						<input type="button" class="form_button" style="width:100px" onclick="javascript:submitAvailabilityForm(\'refresh\')" value="'._REFRESH.'">
					</td>
					<td></td>
					<td align="right" colspan="6">
						<input type="button" class="form_button" style="width:130px" onclick="javascript:submitAvailabilityForm(\'update\')" value="'._BUTTON_SAVE_CHANGES.'">
					</td>';
		$output .= '</tr>';
		$output .= '<tr><td colspan="39">&nbsp;</td></tr>';

		$count = 0;
		$week_day = date('w', mktime('0', '0', '0', '1', '1', $selected_year));
		// fill empty cells from the beginning of month line
		while($count < $week_day){
			$td_class = (($count == 0 || $count == 6) ? 'day_td_w' : '');	// 0 - 'Sun', 6 - 'Sat'
			$output_week_days .= '<td class="'.$td_class.'">'.$lang['weeks'][$count].'</td>';
			$count++;
		}
		// fill cells at the middle
		for($day = 1; $day <= 31; $day ++){
			$week_day = date('w', mktime('0', '0', '0', '1', $day, $selected_year));			
			$td_class = (($week_day == 0 || $week_day == 6) ? 'day_td_w' : '');	// 0 - 'Sun', 6 - 'Sat'
			$output_week_days .= '<td class="'.$td_class.'">'.$lang['weeks'][$week_day].'</td>';
		}
		$max_days = $count + 31;
		// fill empty cells at the end of month line 
		if($max_days < 37){
			$count=0;
			while($count < (37-$max_days)){
				$week_day++;
				$count++;				
				$week_day_mod = $week_day % 7;
				$td_class = (($week_day_mod == 0 || $week_day_mod == 6) ? 'day_td_w' : '');	// 0 - 'Sun', 6 - 'Sat'
				$output_week_days .= '<td class="'.$td_class.'">'.$lang['weeks'][$week_day_mod].'</td>';							
			}
			$max_days += $count;
		}		

		// draw week days
		$output .= '<tr style="text-align:center;background-color:#cccccc;">';
		$output .= '<td style="text-align:left;background-color:#ffffff;">';
		$output .= '<select name="selYear" onchange="javascript:appGoTo(\'admin=mod_room_availability\',\'&rid='.$rid.'&year=\'+this.value)">';
		$output .= '<option value="current" '.(($year == 'current') ? 'selected="selected"' : '').'>'.$current_year.'</option>';
		$output .= '<option value="next" '.(($year == 'next') ? 'selected="selected"' : '').'>'.($current_year+1).'</option>';
		$output .= '</select>';
		$output .= '</td>';		
		$output .= '<td align="center" style="padding:0px 4px;background-color:#ffffff;"><img src="images/check_all.gif" alt="check all" /></td>';
		$output .= $output_week_days;
		$output .= '</tr>';		

		$sql = 'SELECT * FROM '.TABLE_ROOMS_AVAILABILITIES.' WHERE room_id = '.(int)$rid.' AND y = '.(($selected_year == $current_year) ? '0' : '1').' ORDER BY m ASC';
		$room = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
		for($i=0; $i < $room[1]; $i++){
			$selected_month = $room[0][$i]['m'];
			if($selected_month == $current_month) $tr_class = 'm_current';
			else $tr_class = (($i%2==0) ? 'm_odd' : 'm_even'); 
			
			$output .= '<tr align="center" class="'.$tr_class.'">';			
			$output .= '<td align="left">&nbsp;<b>'.$lang['months'][$selected_month].'</b></td>';
			$output .= '<td><input type="checkbox" class="form_checkbox" onclick="toggleAvailability(this.checked,\''.$room[0][$i]['id'].'\')" /></td>';
			$max_day = $this->GetMonthMaxDay($selected_year, $selected_month);

			// fill empty cells from the beginning of month line
			$count = date('w', mktime('0', '0', '0', $selected_month, 1, $selected_year));
			$max_days -= $count; /* subtract days that were missed from the beginning of the month */
			while($count--) $output .= '<td></td>';
			// fill cells at the middle
			for($day = 1; $day <= $max_day; $day ++){
				if($room[0][$i]['d'.$day] >= $room_count){
					$day_color = 'dc_all';
				}else if($room[0][$i]['d'.$day] > 0 && $room[0][$i]['d'.$day] < $room_count){
					$day_color = 'dc_part';
				}else{
					$day_color = 'dc_none';
				}
				$week_day = date('w', mktime('0', '0', '0', $selected_month, $day, $selected_year));
				$td_class = (($week_day == 0 || $week_day == 6) ? 'day_td_w' : 'day_td'); // 0 - 'Sun', 6 - 'Sat'				
				$output .= '<td class="'.$td_class.'"><label class="l_day">'.$day.'</label><br><input class="day_a '.$day_color.'" maxlength="3" name="aval_'.$room[0][$i]['id'].'_'.$day.'" id="aval_'.$room[0][$i]['id'].'_'.$day.'" value="'.$room[0][$i]['d'.$day].'" /></td>';
			}
			// fill empty cells at the end of the month line 
			while($day <= $max_days){
				$output .= '<td></td>';
				$day++;
			}
			$output .= '</tr>';
			if($ids_list != '') $ids_list .= ','.$room[0][$i]['id'];
			else $ids_list = $room[0][$i]['id'];
		}
		
		$output .= '<tr><td colspan="39">&nbsp;</td></tr>';
		$output .= '<tr><td align="'.Application::Get('defined_right').'" colspan="39"><input type="button" class="form_button" style="width:130px" onclick="javascript:submitAvailabilityForm(\'update\')" value="'._BUTTON_SAVE_CHANGES.'"></td></tr>';
		$output .= '<tr><td colspan="39"><b>'._LEGEND.':</b> </td></tr>';
		$output .= '<tr><td colspan="39" nowrap="nowrap" height="5px"></td></tr>';
		$output .= '<tr><td colspan="39"><div class="dc_all" style="width:16px;height:15px;float:'.Application::Get('defined_left').';margin:1px;"></div> &nbsp;- '._ALL_AVAILABLE.'</td></tr>';
		$output .= '<tr><td colspan="39"><div class="dc_part" style="width:16px;height:15px;float:'.Application::Get('defined_left').';margin:1px;"></div> &nbsp;- '._PARTIALLY_AVAILABLE.'</td></tr>';
		$output .= '<tr><td colspan="39"><div class="dc_none" style="width:16px;height:15px;float:'.Application::Get('defined_left').';margin:1px;"></div> &nbsp;- '._NO_AVAILABLE.'</td></tr>';
		$output .= '</table>';
		$output .= draw_hidden_field('ids_list', $ids_list, false);
		$output .= '</form>';
	
		echo $output;		
	}

	/**
	 *	Draws room prices form
	 *		@param $rid
	 */
	public function DrawRoomPricesForm($rid)
	{		
		global $objSettings;

        $nl = "\n";
		$default_price = '0';
		$output = '';

		$sql = 'SELECT *
				FROM '.TABLE_ROOMS.'
				WHERE id = '.(int)$rid.'
				'.(!empty($this->hotelsList) ? ' AND '.TABLE_ROOMS.'.hotel_id IN ('.$this->hotelsList.')' : '');
		$room = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($room[1] > 0){
			$default_price = $room[0]['default_price'];
			$max_adults = $room[0]['max_adults'];
			$max_children = $room[0]['max_children'];
			$max_extra_beds = $room[0]['max_extra_beds'];
			$extra_bed_charge = $room[0]['extra_bed_charge'];
			$hotel_id = $room[0]['hotel_id'];
		}else{
			draw_important_message(_WRONG_PARAMETER_PASSED);
			return false;
		}

		$default_currency_info = Currencies::GetDefaultCurrencyInfo();
		if($default_currency_info['symbol_placement'] == 'before'){
			$currency_l_sign = $default_currency_info['symbol'];
			$currency_r_sign = '';
		}else{
			$currency_l_sign = '';
			$currency_r_sign = $default_currency_info['symbol'];			
		}
		
		if($objSettings->GetParameter('date_format') == 'mm/dd/yyyy'){
			$calendar_date_format = '%m-%d-%Y';
			$field_date_format = 'M d, Y';
		}else{
			$calendar_date_format = '%d-%m-%Y';
			$field_date_format = 'd M, Y';
		}

		$room_type 	   = isset($_REQUEST['room_type']) ? prepare_input($_REQUEST['room_type']) : '';
		$from_new 	   = isset($_POST['from_new']) ? prepare_input($_POST['from_new']) : '';
		$to_new 	   = isset($_POST['to_new']) ? prepare_input($_POST['to_new']) : '';		
		$adults_new    = isset($_POST['adults_new']) ? prepare_input($_POST['adults_new']) : $max_adults;
		$children_new  = isset($_POST['children_new']) ? prepare_input($_POST['children_new']) : $max_children;
		$extra_bed_charge_new = isset($_POST['extra_bed_charge_new']) ? number_format((float)$_POST['extra_bed_charge_new'], 2, '.', '') : $extra_bed_charge;
		$price_new_mon = isset($_POST['price_new_mon']) ? number_format((float)$_POST['price_new_mon'], 2, '.', '') : $default_price;
		$price_new_tue = isset($_POST['price_new_tue']) ? number_format((float)$_POST['price_new_tue'], 2, '.', '') : $default_price;
		$price_new_wed = isset($_POST['price_new_wed']) ? number_format((float)$_POST['price_new_wed'], 2, '.', '') : $default_price;
		$price_new_thu = isset($_POST['price_new_thu']) ? number_format((float)$_POST['price_new_thu'], 2, '.', '') : $default_price;
		$price_new_fri = isset($_POST['price_new_fri']) ? number_format((float)$_POST['price_new_fri'], 2, '.', '') : $default_price;
		$price_new_sat = isset($_POST['price_new_sat']) ? number_format((float)$_POST['price_new_sat'], 2, '.', '') : $default_price;
		$price_new_sun = isset($_POST['price_new_sun']) ? number_format((float)$_POST['price_new_sun'], 2, '.', '') : $default_price;
		$ids_list 	   = '';
		$width         = '53px';
		$text_align    = (Application::Get('defined_alignment') == 'left') ? 'right' : 'left';
		$allow_default_periods = ModulesSettings::Get('rooms', 'allow_default_periods');

		$output .= '<link type="text/css" rel="stylesheet" href="modules/jscalendar/skins/aqua/theme.css" />'.$nl;
		$output .= '<script type="text/javascript">
			function submitPriceForm(task, rpid){
				if(task == "refresh" || task == "add_default_periods"){
					document.getElementById("task").value = task;
					document.getElementById("frmRoomPrices").submit();				
				}else if(task == "delete"){
					if(confirm("'._DELETE_WARNING_COMMON.'")){
						document.getElementById("task").value = task;
						document.getElementById("rpid").value = rpid;
						document.getElementById("frmRoomPrices").submit();
					}				
				}else if(task == "update" || task == "add_new"){
					document.getElementById("task").value = task;
					document.getElementById("frmRoomPrices").submit();
				}				
			}
			function copy_room_prices(room_name_id){
				var frm = jQuery("#frmRoomPrices");	
				var default_price = jQuery("#frmRoomPrices input[name="+room_name_id+"_mon]").val();
				if(frm){
					jQuery("#frmRoomPrices input[name="+room_name_id+"_tue]").val(default_price);
					jQuery("#frmRoomPrices input[name="+room_name_id+"_wed]").val(default_price); 
					jQuery("#frmRoomPrices input[name="+room_name_id+"_thu]").val(default_price); 
					jQuery("#frmRoomPrices input[name="+room_name_id+"_fri]").val(default_price);
					jQuery("#frmRoomPrices input[name="+room_name_id+"_sat]").val(default_price);
					jQuery("#frmRoomPrices input[name="+room_name_id+"_sun]").val(default_price);
				}
			}
		</script>'.$nl;
		$output .= '<script type="text/javascript" src="modules/jscalendar/calendar.js"></script>'.$nl;
		$output .= '<script type="text/javascript" src="modules/jscalendar/lang/calendar-'.((file_exists('modules/jscalendar/lang/calendar-'.Application::Get('lang').'.js')) ? Application::Get('lang') : 'en').'.js"></script>'.$nl;
		$output .= '<script type="text/javascript" src="modules/jscalendar/calendar-setup.js"></script>'.$nl;
		
		$output .= '<form action="index.php?admin=mod_room_prices" id="frmRoomPrices" method="post">';
		$output .= draw_hidden_field('task', 'update', false, 'task');
		$output .= draw_hidden_field('rid', $rid, false, 'rid');
		$output .= draw_hidden_field('rpid', '', false, 'rpid');
        $output .= draw_hidden_field('room_type', $room_type, false, 'room_type');
		$output .= draw_token_field(false);
		
		$output .= '<table width="99%" border="0" cellpadding="1" cellspacing="0">';
		$output .= '<tr style="text-align:center;font-weight:bold;">';
		$output .= '  <td></td>';
		$output .= '  <td colspan="4" align="left">';
		$output .= '  <input type="button" class="form_button" style="width:80px" onclick="javascript:submitPriceForm(\'refresh\')" value="'._REFRESH.'">';
		if($allow_default_periods){
			$output .= ' &nbsp;<input type="button" class="form_button" style="width:150px" onclick="javascript:submitPriceForm(\'add_default_periods\')" value="'._ADD_DEFAULT_PERIODS.'">';
			$output .= ' &nbsp;<a href="index.php?admin=hotel_default_periods&hid='.$hotel_id.'">[ '._SET_PERIODS.' ]</a>';
		}
		$output .= '  </td>';
		$output .= '  <td colspan="9"></td>';
		$output .= '</tr>';
		$output .= '<tr><td colspan="14" nowrap height="5px"></td></tr>';
		$output .= '<tr style="text-align:center;font-weight:bold;">';
		$output .= '  <td width="5px"></td>';
		$output .= '  <td colspan="3"></td>';
		$output .= '  <td width="">'._ADULTS.' '._CHILDREN.' '._EXTRA_BED.'</td>';
		$output .= '  <td width="10px"></td>';
		$output .= '  <td>'._MON.'</td>';
		$output .= '  <td>'._TUE.'</td>';
		$output .= '  <td>'._WED.'</td>';
		$output .= '  <td>'._THU.'</td>';
		$output .= '  <td>'._FRI.'</td>';
		$output .= '  <td style="background-color:#ffcc33;">'._SAT.'</td>';
		$output .= '  <td style="background-color:#ffcc33;">'._SUN.'</td>';
		$output .= '  <td></td>';
		$output .= '</tr>';

		$sql = 'SELECT *
				FROM '.TABLE_ROOMS_PRICES.'
				WHERE room_id = '.(int)$rid.'
				ORDER BY is_default DESC, date_from ASC';
		$room = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
		for($i=0; $i < $room[1]; $i++){
			$output .= '<tr align="center" style="'.(($i%2==0) ? '' : 'background-color:#f1f2f3;').'">';

			$output .= '<td></td>';
			if($i == 0){
				$output .= '<td align="left" nowrap="nowrap" colspan="3"><b>'._STANDARD_PRICE.'</b></td>';	
				$output .= '<td>';
				$output .= '  &nbsp;'.draw_numbers_select_field('adults_'.$room[0][$i]['id'], $max_adults, 1, $max_adults, 1, '', 'disabled', false);
				$output .= '  &nbsp;'.draw_numbers_select_field('children_'.$room[0][$i]['id'], $max_children, 0, $max_children, 1, '', 'disabled', false);
				$output .= '  &nbsp;'.$currency_l_sign.' <input type="text" maxlength="7" '.($max_extra_beds == 0 ? 'readonly="readonly" class="readonly"' : '').' name="extra_bed_charge_'.$room[0][$i]['id'].'" value="'.(isset($_POST['extra_bed_charge_'.$room[0][$i]['id']]) ? number_format((float)$_POST['extra_bed_charge_'.$room[0][$i]['id']], 2, '.', '') : $room[0][$i]['extra_bed_charge']).'" style="padding:0 2px;text-align:'.$text_align.';width:'.$width.'"> '.$currency_r_sign;
				$output .= '</td>';
			}else{
				$output .= '<td align="left" nowrap="nowrap"><input type="text" readonly="readonly" name="date_from_'.$room[0][$i]['id'].'" style="width:85px;border:0px;'.(($i%2==0) ? '' : 'background-color:#f1f2f3;').'" value="'.format_datetime($room[0][$i]['date_from'], $field_date_format).'" /></td>';
				$output .= '<td align="left" nowrap="nowrap" width="20px">-</td>';
				$output .= '<td align="left" nowrap="nowrap"><input type="text" readonly="readonly" name="date_to_'.$room[0][$i]['id'].'" style="width:85px;border:0px;'.(($i%2==0) ? '' : 'background-color:#f1f2f3;').'" value="'.format_datetime($room[0][$i]['date_to'], $field_date_format).'" /></td>';	
				$output .= '<td>';
				$output .= '  &nbsp;'.draw_numbers_select_field('adults_'.$room[0][$i]['id'], (isset($_POST['adults_'.$room[0][$i]['id']]) ? $_POST['adults_'.$room[0][$i]['id']] : $room[0][$i]['adults']), 1, $max_adults, 1, '', 'disabled', false);
				$output .= '  &nbsp;'.draw_numbers_select_field('children_'.$room[0][$i]['id'], (isset($_POST['children_'.$room[0][$i]['id']]) ? $_POST['children_'.$room[0][$i]['id']] : $room[0][$i]['children']), 0, $max_children, 1, '', 'disabled', false);
				$output .= '  &nbsp;'.$currency_l_sign.' <input type="text" maxlength="7" '.($max_extra_beds == 0 ? 'readonly="readonly" class="readonly"' : '').' name="extra_bed_charge_'.$room[0][$i]['id'].'" value="'.(isset($_POST['extra_bed_charge_'.$room[0][$i]['id']]) ? number_format((float)$_POST['extra_bed_charge_'.$room[0][$i]['id']], 2, '.', '') : $room[0][$i]['extra_bed_charge']).'" style="padding:0 2px;text-align:'.$text_align.';width:'.$width.'"> '.$currency_r_sign;
				$output .= '</td>';
			}			
			$output .= '<td></td>';
			$output .= '<td nowrap="nowrap">'.$currency_l_sign.' <input type="text" name="price_'.$room[0][$i]['id'].'_mon" value="'.(isset($_POST['price_'.$room[0][$i]['id'].'_mon']) ? number_format((float)$_POST['price_'.$room[0][$i]['id'].'_mon'], 2, '.', '') : $room[0][$i]['mon']).'" maxlength="7" style="padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> <a href="javascript:void(\'copy-price\')" onclick="copy_room_prices(\'price_'.$room[0][$i]['id'].'\')" style="font-size:15px;" title="'._COPY_TO_OTHERS.'">&raquo;</a> '.$currency_r_sign.'</td>';
			$output .= '<td nowrap="nowrap">'.$currency_l_sign.' <input type="text" name="price_'.$room[0][$i]['id'].'_tue" value="'.(isset($_POST['price_'.$room[0][$i]['id'].'_tue']) ? number_format((float)$_POST['price_'.$room[0][$i]['id'].'_tue'], 2, '.', '') : $room[0][$i]['tue']).'" maxlength="7" style="padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
			$output .= '<td nowrap="nowrap">'.$currency_l_sign.' <input type="text" name="price_'.$room[0][$i]['id'].'_wed" value="'.(isset($_POST['price_'.$room[0][$i]['id'].'_wed']) ? number_format((float)$_POST['price_'.$room[0][$i]['id'].'_wed'], 2, '.', '') : $room[0][$i]['wed']).'" maxlength="7" style="padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
			$output .= '<td nowrap="nowrap">'.$currency_l_sign.' <input type="text" name="price_'.$room[0][$i]['id'].'_thu" value="'.(isset($_POST['price_'.$room[0][$i]['id'].'_thu']) ? number_format((float)$_POST['price_'.$room[0][$i]['id'].'_thu'], 2, '.', '') : $room[0][$i]['thu']).'" maxlength="7" style="padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
			$output .= '<td nowrap="nowrap">'.$currency_l_sign.' <input type="text" name="price_'.$room[0][$i]['id'].'_fri" value="'.(isset($_POST['price_'.$room[0][$i]['id'].'_fri']) ? number_format((float)$_POST['price_'.$room[0][$i]['id'].'_fri'], 2, '.', '') : $room[0][$i]['fri']).'" maxlength="7" style="padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
			$output .= '<td style="background-color:#ffcc33;">'.$currency_l_sign.' <input type="text" name="price_'.$room[0][$i]['id'].'_sat" value="'.(isset($_POST['price_'.$room[0][$i]['id'].'_sat']) ? number_format((float)$_POST['price_'.$room[0][$i]['id'].'_sat'], 2, '.', '') : $room[0][$i]['sat']).'" maxlength="7" style="padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
			$output .= '<td style="background-color:#ffcc33;">'.$currency_l_sign.' <input type="text" name="price_'.$room[0][$i]['id'].'_sun" value="'.(isset($_POST['price_'.$room[0][$i]['id'].'_sun']) ? number_format((float)$_POST['price_'.$room[0][$i]['id'].'_sun'], 2, '.', '') : $room[0][$i]['sun']).'" maxlength="7" style="padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
			$output .= '<td width="30px" align="center">'.(($i > 0) ? '<img src="images/delete.gif" alt="'._DELETE_WORD.'" title="'._DELETE_WORD.'" style="cursor:pointer;" onclick="javascript:submitPriceForm(\'delete\',\''.$room[0][$i]['id'].'\')" />' : '').'</td>';
			$output .= '</tr>';
			if($ids_list != '') $ids_list .= ','.$room[0][$i]['id'];
			else $ids_list = $room[0][$i]['id'];
		}		
		$output .= '<tr><td colspan="11"></td><td colspan="2" style="height:5px;background-color:#ffcc33;"></td><td></td></tr>';
		$output .= '<tr><td colspan="14">&nbsp;</td></tr>';
		$output .= '<tr>';
		$output .= '  <td colspan="9"></td>';
		$output .= '  <td align="center" colspan="2"></td>';
		$output .= '  <td align="center" colspan="2"><input type="button" class="form_button" style="width:130px" onclick="javascript:submitPriceForm(\'update\')" value="'._BUTTON_SAVE_CHANGES.'"></td>';
		$output .= '  <td></td>';
		$output .= '</tr>';
		$output .= '<tr><td colspan="14">&nbsp;</td></tr>';
		$output .= '<tr align="center">';
		$output .= '  <td></td>';
		$output .= '  <td colspan="3" align="right">'._FROM.': <input type="text" id="from_new" name="from_new" style="color:#808080;width:80px" readonly="readonly" value="'.$from_new.'" /><img id="from_new_cal" src="images/cal.gif" alt="calendar" title="'._SET_DATE.'" style="margin-left:5px;margin-right:5px;cursor:pointer;" /><br />'._TO.': <input type="text" id="to_new" name="to_new" style="color:#808080;width:80px" readonly="readonly" value="'.$to_new.'" /><img id="to_new_cal" src="images/cal.gif" alt="calendar" title="'._SET_DATE.'" style="margin-left:5px;margin-right:5px;cursor:pointer;" /></td>';
		$output .= '  <td>';
		$output .= '  &nbsp;'.draw_numbers_select_field('adults_new', $adults_new, 1, $max_adults, 1, '', '', false);
		$output .= '  &nbsp;'.draw_numbers_select_field('children_new', $children_new, 0, $max_children, 1, '', '', false);
		$output .= '  &nbsp;'.$currency_l_sign.' <input type="text" maxlength="7" '.($max_extra_beds == 0 ? 'readonly="readonly" class="readonly"' : '').' name="extra_bed_charge_new" value="'.$extra_bed_charge_new.'" style="color:#808080;padding:0 2px;text-align:'.$text_align.';width:'.$width.'"> '.$currency_r_sign;
		$output .= '  </td>';
		$output .= '  <td></td>';
		$output .= '  <td>'.$currency_l_sign.' <input type="text" name="price_new_mon" value="'.$price_new_mon.'" maxlength="7" style="color:#808080;padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> <a href="javascript:void(\'copy-price\')" onclick="copy_room_prices(\'price_new\')" style="font-size:15px;" title="'._COPY_TO_OTHERS.'">&raquo;</a> '.$currency_r_sign.'</td>';
		$output .= '  <td>'.$currency_l_sign.' <input type="text" name="price_new_tue" value="'.$price_new_tue.'" maxlength="7" style="color:#808080;padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
		$output .= '  <td>'.$currency_l_sign.' <input type="text" name="price_new_wed" value="'.$price_new_wed.'" maxlength="7" style="color:#808080;padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
		$output .= '  <td>'.$currency_l_sign.' <input type="text" name="price_new_thu" value="'.$price_new_thu.'" maxlength="7" style="color:#808080;padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
		$output .= '  <td>'.$currency_l_sign.' <input type="text" name="price_new_fri" value="'.$price_new_fri.'" maxlength="7" style="color:#808080;padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
		$output .= '  <td style="background-color:#ffcc33;">'.$currency_l_sign.' <input type="text" name="price_new_sat" value="'.$price_new_sat.'" maxlength="7" style="color:#808080;padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
		$output .= '  <td style="background-color:#ffcc33;">'.$currency_l_sign.' <input type="text" name="price_new_sun" value="'.$price_new_sun.'" maxlength="7" style="color:#808080;padding:0 2px;text-align:'.$text_align.';width:'.$width.'" /> '.$currency_r_sign.'</td>';
		$output .= '  <td></td>';
		$output .= '</tr>';			

		$output .= '<tr><td colspan="14">&nbsp;</td></tr>';
		$output .= '<tr>';
		$output .= '  <td colspan="11"></td>';
		$output .= '  <td align="center" colspan="2"><input type="button" class="form_button" style="width:130px" onclick="javascript:submitPriceForm(\'add_new\')" value="'._ADD_NEW.'"></td>';
		$output .= '  <td></td>';
		$output .= '</tr>';
		$output .= '</table>';
		$output .= draw_hidden_field('ids_list', $ids_list, false);
		$output .= '</form>';
		
		$output .= '<script type="text/javascript"> 
		Calendar.setup({firstDay : '.($objSettings->GetParameter('week_start_day')-1).', inputField : "from_new", ifFormat : "'.$calendar_date_format.'", showsTime : false, button : "from_new_cal"});
		Calendar.setup({firstDay : '.($objSettings->GetParameter('week_start_day')-1).', inputField : "to_new", ifFormat : "'.$calendar_date_format.'", showsTime : false, button : "to_new_cal"});
		</script>';

		echo $output;
	}

	/**
	 *	Returns a table with prices for certain room
	 *		@param $rid
	 */
	public static function GetRoomPricesTable($rid)
	{		
		global $objSettings, $objLogin;
		
		$currency_rate = ($objLogin->IsLoggedInAsAdmin()) ? '1' : Application::Get('currency_rate');
		$currency_format = get_currency_format();
		$show_default_prices = ModulesSettings::Get('rooms', 'show_default_prices');
	
		if($objSettings->GetParameter('date_format') == 'mm/dd/yyyy'){
			$calendar_date_format = '%m-%d-%Y';
			$field_date_format = 'M d, Y';
		}else{
			$calendar_date_format = '%d-%m-%Y';
			$field_date_format = 'd M, Y';
		}

		$sql = 'SELECT * FROM '.TABLE_ROOMS.' WHERE id = '.(int)$rid;
		$room = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($room[1] > 0){
			$default_price = $room[0]['default_price'];
		}else{
			$default_price = '0';
		}

		$output = '<table class="room_prices" border="0" cellpadding="0" cellspacing="0">';
		$output .= '<tr class="header">';
		$output .= '  <th width="5px">&nbsp;</td>';
		$output .= '  <th colspan="3">&nbsp;</td>';
		$output .= '  <th width="10px">&nbsp;'._ADULT.'&nbsp;</td>';
		$output .= '  <th width="10px">&nbsp;'._CHILD.'&nbsp;</td>';
		$output .= '  <th width="10px">&nbsp;'._EXTRA_BED.'&nbsp;</td>';
		//$output .= '  <th width="10px">&nbsp;</td>';
		$output .= '  <th>'._MON.'</td>';
		$output .= '  <th>'._TUE.'</td>';
		$output .= '  <th>'._WED.'</td>';
		$output .= '  <th>'._THU.'</td>';
		$output .= '  <th>'._FRI.'</td>';
		$output .= '  <th>'._SAT.'</td>';
		$output .= '  <th>'._SUN.'</td>';
		$output .= '</tr>';

		$sql = 'SELECT * FROM '.TABLE_ROOMS_PRICES.'
				WHERE
					room_id = '.(int)$rid.' AND
					(
						is_default = 1 OR 
						(is_default = 0 AND date_from >= \''.date('Y').'-01-01\') OR
						(is_default = 0 AND date_to >= \''.date('Y').'-01-01\')
					)
				ORDER BY is_default DESC, date_from ASC';
		$room = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
		$output .= '<tr><td colspan="15" nowrap="nowrap" height="5px"></td></tr>';
		for($i=0; $i < $room[1]; $i++){
			
			if($show_default_prices != 'yes' && $room[0][$i]['is_default'] == 1 && $room[1] > 1) continue;
			
			$output .= '<tr align="'.Application::Get('defined_right').'">';
			$output .= '  <td></td>';
			if($i == 0 && $room[0][$i]['is_default'] == 1){
				$output .= '  <td align="left" nowrap="nowrap" colspan="3"><b>'._STANDARD_PRICE.'</b></td>';	
			}else{
				$output .= '  <td align="left" nowrap="nowrap">'.format_datetime($room[0][$i]['date_from'], $field_date_format).'</td>';
				$output .= '  <td align="left" nowrap="nowrap" width="20px">-</td>';
				$output .= '  <td align="left" nowrap="nowrap">'.format_datetime($room[0][$i]['date_to'], $field_date_format).'</td>';	
			}
			$curr_rate = !$objLogin->IsLoggedInAsAdmin() ? $currency_rate : 1;
							  
			$output .= '  <td align="center">'.$room[0][$i]['adults'].'</td>';
			$output .= '  <td align="center">'.$room[0][$i]['children'].'</td>';
			$output .= '  <td><span>'.Currencies::PriceFormat($room[0][$i]['extra_bed_charge'] * $curr_rate, '', '', $currency_format).'</span></td>';
			//$output .= '  <td></td>';
			$output .= '  <td><span>'.Currencies::PriceFormat($room[0][$i]['mon'] * $curr_rate, '', '', $currency_format).'</span></td>';
			$output .= '  <td><span>'.Currencies::PriceFormat($room[0][$i]['tue'] * $curr_rate, '', '', $currency_format).'</span></td>';
			$output .= '  <td><span>'.Currencies::PriceFormat($room[0][$i]['wed'] * $curr_rate, '', '', $currency_format).'</span></td>';
			$output .= '  <td><span>'.Currencies::PriceFormat($room[0][$i]['thu'] * $curr_rate, '', '', $currency_format).'</span></td>';
			$output .= '  <td><span>'.Currencies::PriceFormat($room[0][$i]['fri'] * $curr_rate, '', '', $currency_format).'</span></td>';
			$output .= '  <td><span>'.Currencies::PriceFormat($room[0][$i]['sat'] * $curr_rate, '', '', $currency_format).'</span></td>';
			$output .= '  <td><span>'.Currencies::PriceFormat($room[0][$i]['sun'] * $curr_rate, '', '', $currency_format).'</span>&nbsp;</td>';
			$output .= '</tr>';
		}		
		$output .= '<tr><td colspan="15" nowrap="nowrap" height="5px"></td></tr>';
		$output .= '</table>';

		return $output;
	}	
	
    /**
	 * Deletes room availability
	 * 		@param $rid
	 */
	public function DeleteRoomAvailability($rpid)
	{
		// Block operation in demo mode
		if(strtolower(SITE_MODE) == 'demo'){
			$this->error = _OPERATION_BLOCKED;
			return false;
		}

		$sql = 'DELETE FROM '.TABLE_ROOMS_AVAILABILITIES.' WHERE id = '.(int)$rpid;
		if(!database_void_query($sql)){
			$this->error = _TRY_LATER;
			return false;
		}
		return true;
	}

    /**
	 * Deletes room prices
	 * 		@param $rpid
	 */
	public function DeleteRoomPrices($rpid)
	{
		// Block operation in demo mode
		if(strtolower(SITE_MODE) == 'demo'){
			$this->error = _OPERATION_BLOCKED;
			return false;
		}

		$sql = 'DELETE FROM '.TABLE_ROOMS_PRICES.' WHERE id = '.(int)$rpid;
		if(!database_void_query($sql)){
			$this->error = _TRY_LATER;
			return false;
		}
		return true;
	}
	
    /**
	 * Adds room availability
	 * 		@param $rid
	 */
	public function AddRoomAvailability($rid)
	{
		// Block operation in demo mode
		if(strtolower(SITE_MODE) == 'demo'){
			$this->error = _OPERATION_BLOCKED;
			return false;
		}

		global $objSettings;

		$task 	  = isset($_POST['task']) ? prepare_input($_POST['task']) : '';
		$from_new = isset($_POST['from_new']) ? prepare_input($_POST['from_new']) : '';
		$to_new   = isset($_POST['to_new']) ? prepare_input( $_POST['to_new']) : '';		
		$aval_mon = isset($_POST['aval_new_mon']) ? '1' : '0';
		$aval_tue = isset($_POST['aval_new_tue']) ? '1' : '0';
		$aval_wed = isset($_POST['aval_new_wed']) ? '1' : '0';
		$aval_thu = isset($_POST['aval_new_thu']) ? '1' : '0';
		$aval_fri = isset($_POST['aval_new_fri']) ? '1' : '0';
		$aval_sat = isset($_POST['aval_new_sat']) ? '1' : '0';
		$aval_sun = isset($_POST['aval_new_sun']) ? '1' : '0';
	
				
		if($objSettings->GetParameter('date_format') == 'mm/dd/yyyy'){
			$from_new = substr($from_new, 6, 4).'-'.substr($from_new, 0, 2).'-'.substr($from_new, 3, 2);
			$to_new = substr($to_new, 6, 4).'-'.substr($to_new, 0, 2).'-'.substr($to_new, 3, 2);
		}else{
			// dd/mm/yyyy
			$from_new = substr($from_new, 6, 4).'-'.substr($from_new, 3, 2).'-'.substr($from_new, 0, 2);
			$to_new = substr($to_new, 6, 4).'-'.substr($to_new, 3, 2).'-'.substr($to_new, 0, 2);
		}

		if($from_new == '--' || $to_new == '--'){
			$this->error = _DATE_EMPTY_ALERT;
			return false;
		}else if($from_new > $to_new){
			$this->error = _FROM_TO_DATE_ALERT;
			return false;			
		}else{
			$sql = 'SELECT * FROM '.TABLE_ROOMS_AVAILABILITIES.'
					WHERE
						room_id = '.(int)$rid.' AND
						is_default = 0 AND 
						(((\''.$from_new.'\' >= date_from) AND (\''.$from_new.'\' <= date_to)) OR
						((\''.$to_new.'\' >= date_from) AND (\''.$to_new.'\' <= date_to))) ';	
			$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
			if($result[1] > 0){
				$this->error = _TIME_PERIOD_OVERLAPPING_ALERT;
				return false;
			}
		}

		if($from_new != '' && $to_new != ''){
			$sql = 'INSERT INTO '.TABLE_ROOMS_AVAILABILITIES.' (id, room_id, date_from, date_to, mon, tue, wed, thu, fri, sat, sun, is_default)
					VALUES (NULL, '.(int)$rid.', \''.$from_new.'\', \''.$to_new.'\', '.$aval_mon.', '.$aval_tue.', '.$aval_wed.', '.$aval_thu.', '.$aval_fri.', '.$aval_sat.', '.$aval_sun.', 0)';
			if(database_void_query($sql)){
				unset($_POST);
				return true;
			}else{
				$this->error = _TRY_LATER;
				return false;
			}
		}
	}

    /**
	 * Adds room prices
	 * 		@param $rid
	 */
	public function AddRoomPrices($rid)
	{
		// Block operation in demo mode
		if(strtolower(SITE_MODE) == 'demo'){
			$this->error = _OPERATION_BLOCKED;
			return false;
		}

		global $objSettings;
		
		$task 	       = isset($_POST['task']) ? prepare_input($_POST['task']) : '';
		$from_new 	   = isset($_POST['from_new']) ? prepare_input($_POST['from_new']) : '';
		$to_new 	   = isset($_POST['to_new']) ? prepare_input($_POST['to_new']) : '';		
		$adults_new    = isset($_POST['adults_new']) ? prepare_input($_POST['adults_new']) : '1';
		$children_new  = isset($_POST['children_new']) ? prepare_input($_POST['children_new']) : '0';
		$extra_bed_charge_new = isset($_POST['extra_bed_charge_new']) ? prepare_input($_POST['extra_bed_charge_new']) : '0';
		$price_new_mon = isset($_POST['price_new_mon']) ? prepare_input($_POST['price_new_mon']) : '';
		$price_new_tue = isset($_POST['price_new_tue']) ? prepare_input($_POST['price_new_tue']) : '';
		$price_new_wed = isset($_POST['price_new_wed']) ? prepare_input($_POST['price_new_wed']) : '';
		$price_new_thu = isset($_POST['price_new_thu']) ? prepare_input($_POST['price_new_thu']) : '';
		$price_new_fri = isset($_POST['price_new_fri']) ? prepare_input($_POST['price_new_fri']) : '';
		$price_new_sat = isset($_POST['price_new_sat']) ? prepare_input($_POST['price_new_sat']) : '';
		$price_new_sun = isset($_POST['price_new_sun']) ? prepare_input($_POST['price_new_sun']) : '';		
				
		if($objSettings->GetParameter('date_format') == 'mm/dd/yyyy'){
			$from_new = substr($from_new, 6, 4).'-'.substr($from_new, 0, 2).'-'.substr($from_new, 3, 2);
			$to_new = substr($to_new, 6, 4).'-'.substr($to_new, 0, 2).'-'.substr($to_new, 3, 2);
		}else{
			// dd/mm/yyyy
			$from_new = substr($from_new, 6, 4).'-'.substr($from_new, 3, 2).'-'.substr($from_new, 0, 2);
			$to_new = substr($to_new, 6, 4).'-'.substr($to_new, 3, 2).'-'.substr($to_new, 0, 2);
		}

		if($from_new == '--' || $to_new == '--'){
			$this->error = _DATE_EMPTY_ALERT;
			return false;
		}else if($from_new > $to_new){
			$this->error = _FROM_TO_DATE_ALERT;
			return false;			
		}else if(!$this->IsFloat($extra_bed_charge_new) || $extra_bed_charge_new < 0){
			$this->error = str_replace('_FIELD_', '<b>'._EXTRA_BED_CHARGE.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
			return false;
		}else if($price_new_mon == '' || $price_new_tue == '' || $price_new_wed == '' || $price_new_thu == '' || $price_new_fri == '' || $price_new_sat == '' || $price_new_sun == ''){
			$this->error = _PRICE_EMPTY_ALERT;
			return false;
		}else if(!$this->IsFloat($price_new_mon) || $price_new_mon < 0){
			$this->error = str_replace('_FIELD_', '<b>'._MON.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
			return false;
		}else if(!$this->IsFloat($price_new_tue) || $price_new_tue < 0){
			$this->error = str_replace('_FIELD_', '<b>'._TUE.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
			return false;
		}else if(!$this->IsFloat($price_new_wed) || $price_new_wed < 0){
			$this->error = str_replace('_FIELD_', '<b>'._WED.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
			return false;
		}else if(!$this->IsFloat($price_new_thu) || $price_new_thu < 0){
			$this->error = str_replace('_FIELD_', '<b>'._THU.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
			return false;
		}else if(!$this->IsFloat($price_new_fri) || $price_new_fri < 0){
			$this->error = str_replace('_FIELD_', '<b>'._FRI.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
			return false;
		}else if(!$this->IsFloat($price_new_sat) || $price_new_sat < 0){
			$this->error = str_replace('_FIELD_', '<b>'._SAT.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
			return false;
		}else if(!$this->IsFloat($price_new_sun) || $price_new_sun < 0){
			$this->error = str_replace('_FIELD_', '<b>'._SUN.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
			return false;
		}else{
			$sql = 'SELECT * FROM '.TABLE_ROOMS_PRICES.'
					WHERE
						room_id = '.(int)$rid.' AND
						adults = '.(int)$adults_new.' AND
						children = '.(int)$children_new.' AND
						is_default = 0 AND 
						(((\''.$from_new.'\' >= date_from) AND (\''.$from_new.'\' <= date_to)) OR
						((\''.$to_new.'\' >= date_from) AND (\''.$to_new.'\' <= date_to))) ';	
			$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
			if($result[1] > 0){
				$this->error = _TIME_PERIOD_OVERLAPPING_ALERT;
				return false;
			}
		}

		if($from_new != '' && $to_new != ''){
			$sql = 'INSERT INTO '.TABLE_ROOMS_PRICES.' (id, room_id, date_from, date_to, adults, children, extra_bed_charge, mon, tue, wed, thu, fri, sat, sun, is_default)
					VALUES (NULL, '.(int)$rid.', \''.$from_new.'\', \''.$to_new.'\', \''.$adults_new.'\', \''.$children_new.'\', \''.$extra_bed_charge_new.'\', '.$price_new_mon.', '.$price_new_tue.', '.$price_new_wed.', '.$price_new_thu.', '.$price_new_fri.', '.$price_new_sat.', '.$price_new_sun.', 0)';
			if(database_void_query($sql)){
				unset($_POST);
				return true;
			}else{
				$this->error = _TRY_LATER;
				return false;
			}
		}
	}
	
    /**
	 * Adds default periods
	 * 		@param $rid
	 */
	public function AddDefaultPeriods($rid)
	{
		// Block operation in demo mode
		if(strtolower(SITE_MODE) == 'demo'){
			$this->error = _OPERATION_BLOCKED;
			return false;
		}
		
		$sql = 'SELECT * FROM '.TABLE_ROOMS.' WHERE id = '.(int)$rid;	
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] > 0){			
			$adults = isset($result[0]['max_adults']) ? $result[0]['max_adults'] : '';
			$children = isset($result[0]['max_children']) ? $result[0]['max_children'] : '';
			$extra_bed_charge = isset($result[0]['extra_bed_charge']) ? $result[0]['extra_bed_charge'] : '';
			$price = isset($result[0]['default_price']) ? $result[0]['default_price'] : '';
			$hotel_id = isset($result[0]['hotel_id']) ? $result[0]['hotel_id'] : 0;
			
			$sql = 'SELECT * FROM '.TABLE_HOTEL_PERIODS.' WHERE hotel_id = '.(int)$hotel_id;	
			$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
			if($result[1] > 0){
				for($i=0; $i<$result[1]; $i++){
					$sql = 'SELECT room_id FROM '.TABLE_ROOMS_PRICES.'
							WHERE room_id = '.(int)$rid.' AND date_from = \''.$result[0][$i]['start_date'].'\' AND date_to = \''.$result[0][$i]['finish_date'].'\'';
					$result_check = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
					if(!$result_check[1]){
						$sql = 'INSERT INTO '.TABLE_ROOMS_PRICES.' (id, room_id, date_from, date_to, adults, children, extra_bed_charge, mon, tue, wed, thu, fri, sat, sun, is_default)
								VALUES (NULL, '.(int)$rid.', \''.$result[0][$i]['start_date'].'\', \''.$result[0][$i]['finish_date'].'\', \''.$adults.'\', \''.$children.'\', \''.$extra_bed_charge.'\', '.$price.', '.$price.', '.$price.', '.$price.', '.$price.', '.$price.', '.$price.', 1) ';
						database_void_query($sql);
					}
				}				
				return true;
			}else{
				$this->error = str_ireplace('_HREF_', 'index.php?admin=hotel_default_periods&hid='.(int)$hotel_id, _NO_DEFAULT_PERIODS);
				return false;
			}
		}
		
		return false;
	}
	
    /**
	 * Updates room availability
	 * 		@param $rid
	 */
	public function UpdateRoomAvailability($rid)
	{
		// Block operation in demo mode
		if(strtolower(SITE_MODE) == 'demo'){
			$this->error = _OPERATION_BLOCKED;
			return false;
		}

		$ids_list = isset($_POST['ids_list']) ? prepare_input($_POST['ids_list']) : '';
		$ids_list_array = explode(',', $ids_list);

		$room_info = $this->GetInfoByID($rid);
		$room_count = isset($room_info['room_count']) ? $room_info['room_count'] : '0';
		
		// update availability		
		foreach($ids_list_array as $key){
			
			$sql = 'UPDATE '.TABLE_ROOMS_AVAILABILITIES.' SET ';
			for($day = 1; $day <= 31; $day ++){
				// input validation
				$aval_day = isset($_POST['aval_'.$key.'_'.$day]) ? $_POST['aval_'.$key.'_'.$day] : '0';
				if(!$this->IsInteger($aval_day) || $aval_day < 0){
					$this->error = str_replace('_FIELD_', '\'<b>Day '.$day.'</b>\'', _FIELD_MUST_BE_NUMERIC_POSITIVE);
					return false;
				}else if($aval_day > $room_count){
					$this->error = str_replace('_FIELD_', '\'<b>Day '.$day.'</b>\'', _FIELD_VALUE_EXCEEDED);
					$this->error = str_replace('_MAX_', $room_count, $this->error);
					return false;					
				}
				
				if($day > 1) $sql .= ', ';
				$sql .= 'd'.$day.' = '.(int)$aval_day;
			}
			$sql .= ' WHERE id = '.$key.' AND room_id = '.(int)$rid;
			if(!database_void_query($sql)){
				$this->error = _TRY_LATER;				
				return false;
			}
		}
		unset($_POST);
		return true;		
	}

    /**
	 * Updates room prices
	 * 		@param $rid
	 */
	public function UpdateRoomPrices($rid)
	{
		// Block operation in demo mode
		if(strtolower(SITE_MODE) == 'demo'){
			$this->error = _OPERATION_BLOCKED;
			return false;
		}

		$ids_list = isset($_POST['ids_list']) ? prepare_input($_POST['ids_list']) : '';
		$ids_list_array = explode(',', $ids_list);
		
		// input validation
		$arrPrices = array();			
		$count = 0;
		foreach($ids_list_array as $key){

			$adults    = (isset($_POST['adults_'.$key]) ? prepare_input($_POST['adults_'.$key]) : '1');
			$children  = (isset($_POST['children_'.$key]) ? prepare_input($_POST['children_'.$key]) : '0');
			$extra_bed_charge = (isset($_POST['extra_bed_charge_'.$key]) ? prepare_input($_POST['extra_bed_charge_'.$key]) : '0');			
			$price_mon = (isset($_POST['price_'.$key.'_mon']) ? prepare_input($_POST['price_'.$key.'_mon']) : '0');
			$price_tue = (isset($_POST['price_'.$key.'_tue']) ? prepare_input($_POST['price_'.$key.'_tue']) : '0');
			$price_wed = (isset($_POST['price_'.$key.'_wed']) ? prepare_input($_POST['price_'.$key.'_wed']) : '0');
			$price_thu = (isset($_POST['price_'.$key.'_thu']) ? prepare_input($_POST['price_'.$key.'_thu']) : '0');
			$price_fri = (isset($_POST['price_'.$key.'_fri']) ? prepare_input($_POST['price_'.$key.'_fri']) : '0');
			$price_sat = (isset($_POST['price_'.$key.'_sat']) ? prepare_input($_POST['price_'.$key.'_sat']) : '0');
			$price_sun = (isset($_POST['price_'.$key.'_sun']) ? prepare_input($_POST['price_'.$key.'_sun']) : '0');
			
			if(!$this->IsFloat($extra_bed_charge) || $extra_bed_charge < 0){
				$this->error = str_replace('_FIELD_', '<b>'._EXTRA_BED_CHARGE.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
				return false;
			}else if(!$this->IsFloat($price_mon) || $price_mon < 0){
				$this->error = str_replace('_FIELD_', '<b>'._MON.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
				return false;
			}else if(!$this->IsFloat($price_tue) || $price_tue < 0){
				$this->error = str_replace('_FIELD_', '<b>'._TUE.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
				return false;
			}else if(!$this->IsFloat($price_wed) || $price_wed < 0){
				$this->error = str_replace('_FIELD_', '<b>'._WED.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
				return false;
			}else if(!$this->IsFloat($price_thu) || $price_thu < 0){
				$this->error = str_replace('_FIELD_', '<b>'._THU.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
				return false;
			}else if(!$this->IsFloat($price_fri) || $price_fri < 0){
				$this->error = str_replace('_FIELD_', '<b>'._FRI.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
				return false;
			}else if(!$this->IsFloat($price_sat) || $price_sat < 0){
				$this->error = str_replace('_FIELD_', '<b>'._SAT.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
				return false;
			}else if(!$this->IsFloat($price_sun) || $price_sun < 0){
				$this->error = str_replace('_FIELD_', '<b>'._SUN.'</b>', _FIELD_MUST_BE_NUMERIC_POSITIVE);
				return false;
			}

			$sql = 'UPDATE '.TABLE_ROOMS_PRICES.'
					SET
						'.(($count == 0) ? 'date_from = \'0000-00-00\',' : '').'
						'.(($count == 0) ? 'date_to = \'0000-00-00\',' : '').'
						'.(isset($_POST['adults_'.$key]) ? 'adults = '.(int)$adults.',' : '').'
						'.(isset($_POST['children_'.$key]) ? 'children = '.(int)$children.',' : '').'
						extra_bed_charge = \''.$extra_bed_charge.'\',
						mon = \''.$price_mon.'\',
						tue = \''.$price_tue.'\',
						wed = \''.$price_wed.'\',
						thu = \''.$price_thu.'\',
						fri = \''.$price_fri.'\',
						sat = \''.$price_sat.'\',
						sun = \''.$price_sun.'\',
						is_default = '.(($count == 0) ? '1' : '0').'
					WHERE id = '.$key.' AND room_id = '.(int)$rid;
			if(!database_void_query($sql)){
				$this->error = _TRY_LATER;
				return false;
			}
			$count++;
		}
		unset($_POST);
		return true;		
	}

    /**
	 * After-Insert operation
	 */
	public function AfterInsertRecord()
	{		
		// Block operation in demo mode
		if(strtolower(SITE_MODE) == 'demo'){
			$this->error = _OPERATION_BLOCKED;
			return false;
		}

		$default_price 			= isset($_POST['default_price']) ? prepare_input($_POST['default_price']) : '0';				
		$room_type 			    = isset($_POST['room_type']) ? prepare_input($_POST['room_type']) : '';
		$room_count 			= isset($_POST['room_count']) ? (int)$_POST['room_count'] : '0';
		$room_short_description = isset($_POST['room_short_description']) ? prepare_input($_POST['room_short_description']) : '';
		$room_long_description  = isset($_POST['room_long_description']) ? prepare_input($_POST['room_long_description']) : '';
		$max_adults             = isset($_POST['max_adults']) ? prepare_input($_POST['max_adults']) : '';
		$max_children           = isset($_POST['max_children']) ? prepare_input($_POST['max_children']) : '';
		$extra_bed_charge        = isset($_POST['extra_bed_charge']) ? prepare_input($_POST['extra_bed_charge']) : '';
		$hotel_id               = isset($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : '0';				
		
		// add room prices
		// ---------------------------------------------------------------------
		$sql = 'SELECT * FROM '.TABLE_ROOMS_PRICES.' WHERE room_id = '.$this->lastInsertId;
		$room = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($room[1] > 0){
			$sql = 'UPDATE '.TABLE_ROOMS_PRICES.'
					SET
						date_from = NULL,
						date_to   = NULL,
						mon = \''.$default_price.'\',
						tue = \''.$default_price.'\',
						wed = \''.$default_price.'\',
						thu = \''.$default_price.'\',
						fri = \''.$default_price.'\',
						sat = \''.$default_price.'\',
						sun = \''.$default_price.'\',
						is_default = 1
					WHERE room_id = '.$this->lastInsertId;
			$result = database_void_query($sql);			
		}else{			
			$sql = 'INSERT INTO '.TABLE_ROOMS_PRICES.' (id, room_id, date_from, date_to, adults, children, extra_bed_charge, mon, tue, wed, thu, fri, sat, sun, is_default)
					VALUES (NULL, '.$this->lastInsertId.', \'0000-00-00\', \'0000-00-00\', '.(int)$max_adults.', '.(int)$max_children.', '.$extra_bed_charge.', \''.$default_price.'\', \''.$default_price.'\', \''.$default_price.'\', \''.$default_price.'\', \''.$default_price.'\', \''.$default_price.'\', \''.$default_price.'\', 1)';
			$result = database_void_query($sql);
			
			// add prices for default periods (if specified)
			$sql = 'SELECT id, hotel_id, period_description, start_date, finish_date FROM '.TABLE_HOTEL_PERIODS.' WHERE hotel_id = '.(int)$hotel_id;
			$periods = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
			for($i = 0; $i < $periods[1]; $i++){ 
				$sql = 'INSERT INTO '.TABLE_ROOMS_PRICES.' (id, room_id, date_from, date_to, adults, children, extra_bed_charge, mon, tue, wed, thu, fri, sat, sun, is_default)
						VALUES (NULL, '.$this->lastInsertId.', \''.$periods[0][$i]['start_date'].'\', \''.$periods[0][$i]['finish_date'].'\', '.(int)$max_adults.', '.(int)$max_children.', '.$extra_bed_charge.', \''.$default_price.'\', \''.$default_price.'\', \''.$default_price.'\', \''.$default_price.'\', \''.$default_price.'\', \''.$default_price.'\', \''.$default_price.'\', 0)';
				$result = database_void_query($sql);
			}
		}
		
		// add room availability
		// ---------------------------------------------------------------------
		$sql = 'SELECT * FROM '.TABLE_ROOMS_AVAILABILITIES.' WHERE room_id = '.$this->lastInsertId;
		$room = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($room[1] <= 0){
			for($y = 0; $y <= 1; $y++){ // 0 - current, 1 - next year
				$sql_temp = 'INSERT INTO '.TABLE_ROOMS_AVAILABILITIES.' (id, room_id, y, m ';
				$sql_temp_values = '';
				for($i=1; $i<=31; $i++){
					$sql_temp .= ', d'.$i;
					$sql_temp_values .= ', '.$room_count;
				}
				$sql_temp .= ')';
				$sql_temp .= 'VALUES (NULL, '.$this->lastInsertId.', '.$y.', _MONTH_'.$sql_temp_values.');';
				
				for($i = 1; $i <= 12; $i++){
					$sql = str_replace('_MONTH_', $i, $sql_temp);
					$result = database_void_query($sql);
				}
			}
		}		

		// languages array
		// ---------------------------------------------------------------------
		$total_languages = Languages::GetAllActive();
		foreach($total_languages[0] as $key => $val){			
			$sql = 'INSERT INTO '.TABLE_ROOMS_DESCRIPTION.'(
						id, room_id, language_id, room_type, room_short_description, room_long_description
					)VALUES(
						NULL, '.$this->lastInsertId.', \''.$val['abbreviation'].'\', \''.encode_text($room_type).'\', \''.encode_text($room_short_description).'\', \''.encode_text($room_long_description).'\'
					)';
			database_void_query($sql);
		}		
	}	
	
	public function BeforeUpdateRecord()
	{
		$record_info = $this->GetInfoByID($this->curRecordId);
		$this->roomsCount = isset($record_info['room_count']) ? $record_info['room_count'] : '';
	   	return true;
	}
	 	
	public function AfterUpdateRecord()
	{
		$room_count = MicroGrid::GetParameter('room_count', false);
		if($room_count != $this->roomsCount){
			$sql = 'UPDATE '.TABLE_ROOMS_AVAILABILITIES.' SET ';
			for($day = 1; $day <= 31; $day ++){
				if($day > 1) $sql .= ', ';
				$sql .= 'd'.$day.' = '.$room_count;
			}			
			$sql .= ' WHERE room_id = '.(int)$this->curRecordId;
			database_void_query($sql);	
		}		
	}

    /**
	 * After-Delete operation
	 */
	public function AfterDeleteRecord()
	{
		// Block operation in demo mode
		if(strtolower(SITE_MODE) == 'demo'){
			$this->error = _OPERATION_BLOCKED;
			return false;
		}

		$rid = self::GetParameter('rid');

		$sql = 'DELETE FROM '.TABLE_ROOMS_PRICES.' WHERE room_id = '.(int)$rid;
		database_void_query($sql);	
		$sql = 'DELETE FROM '.TABLE_ROOMS_AVAILABILITIES.' WHERE room_id = '.(int)$rid;
		database_void_query($sql);	
		$sql = 'DELETE FROM '.TABLE_ROOMS_DESCRIPTION.' WHERE room_id = '.(int)$rid;		
		database_void_query($sql);
	}
	
    /**
	 * Search available rooms
	 * 		@param $params
	 */
	public function SearchFor($params = array())
	{		
		$lang 		    = Application::Get('lang');		
		$checkin_date 	= $params['from_year'].'-'.$params['from_month'].'-'.$params['from_day'];
		$checkout_date 	= $params['to_year'].'-'.$params['to_month'].'-'.$params['to_day'];
		$max_adults 	= isset($params['max_adults']) ? $params['max_adults'] : '';
		$max_children 	= isset($params['max_children']) ? $params['max_children'] : '';
		$room_id 	    = isset($params['room_id']) ? $params['room_id'] : '';
		$hotel_sel_id   = isset($params['hotel_sel_id']) ? $params['hotel_sel_id'] : '';
		$hotel_sel_loc_id  = isset($params['hotel_sel_loc_id']) ? $params['hotel_sel_loc_id'] : '';
		$sort_by 		= isset($params['sort_by']) ? $params['sort_by'] : '';
		$order_by_clause = '';

		// prepare sort by clause				
		switch($sort_by){
			case 'stars-1-5':
				$order_by_clause = 'h.stars ASC'; break;
			case 'stars-5-1':
				$order_by_clause = 'h.stars DESC'; break;
			case 'name-a-z':
				$order_by_clause = 'hd.name ASC'; break;
			case 'name-z-a':
				$order_by_clause = 'hd.name DESC'; break;
			case 'price-l-h':
				$order_by_clause = 'r.default_price	ASC'; break;
			case 'price-h-l':
				$order_by_clause = 'r.default_price	DESC'; break;
			default:
				$order_by_clause = 'r.priority_order ASC'; break;
		}
			
		$hotel_where_clause = (!empty($hotel_sel_id)) ? 'h.id = '.(int)$hotel_sel_id.' AND ' : '';
		$hotel_where_clause .= (!empty($hotel_sel_loc_id)) ? 'h.hotel_location_id = '.(int)$hotel_sel_loc_id.' AND ' : '';

		$rooms_count    = 0;
		$show_fully_booked_rooms = ModulesSettings::Get('booking', 'show_fully_booked_rooms');

    	$sql = 'SELECT
					r.id, r.hotel_id, r.room_count
			    FROM '.TABLE_ROOMS.' r
					INNER JOIN '.TABLE_HOTELS.' h ON r.hotel_id = h.id
					'.(($sort_by == 'name-z-a' || $sort_by == 'name-a-z') ? 'INNER JOIN '.TABLE_HOTELS_DESCRIPTION.' hd ON h.id = hd.hotel_id AND hd.language_id = \''.$lang.'\'' : '').'
				WHERE 1=1 AND 
					'.$hotel_where_clause.'
					h.is_active = 1 AND
					r.is_active = 1					
					'.(($room_id != '') ? ' AND r.id='.(int)$room_id : '').'
					'.(($max_adults != '') ? ' AND r.max_adults >= '.(int)$max_adults : '').'
					'.(($max_children != '') ? ' AND r.max_children >= '.(int)$max_children : '').'
				ORDER BY '.$order_by_clause;
				
		$rooms = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
		if($rooms[1] > 0){
			// loop by rooms
			for($i=0; $i < $rooms[1]; $i++){
				//echo '<br />'.$rooms[0][$i]['id'].' '.$rooms[0][$i]['room_count'];

                // maximum available rooms in hotel for one day
				$maximal_rooms = (int)$rooms[0][$i]['room_count'];				
				$max_booked_rooms = '0';
				$sql = 'SELECT
							MAX('.TABLE_BOOKINGS_ROOMS.'.rooms) as max_booked_rooms
						FROM '.TABLE_BOOKINGS.'
							INNER JOIN '.TABLE_BOOKINGS_ROOMS.' ON '.TABLE_BOOKINGS.'.booking_number = '.TABLE_BOOKINGS_ROOMS.'.booking_number
						WHERE
                            ('.TABLE_BOOKINGS.'.status = 2 OR '.TABLE_BOOKINGS.'.status = 3) AND
							'.TABLE_BOOKINGS_ROOMS.'.room_id = '.(int)$rooms[0][$i]['id'].' AND
							(
								(\''.$checkin_date.'\' <= checkin AND \''.$checkout_date.'\' > checkin) 
								OR
								(\''.$checkin_date.'\' < checkout AND \''.$checkout_date.'\' >= checkout)
								OR
								(\''.$checkin_date.'\' >= checkin  AND \''.$checkout_date.'\' < checkout)
							)';
				$rooms_booked = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
				if($rooms_booked[1] > 0){
					$max_booked_rooms = (int)$rooms_booked[0]['max_booked_rooms'];
				}
				
				// this is only a simple check if there is at least one room wirh available num > booked rooms
				$available_rooms = (int)($maximal_rooms - $max_booked_rooms);
				// echo '<br> Room ID: '.$rooms[0][$i]['id'].' Max: '.$maximal_rooms.' Booked: '.$max_booked_rooms.' Av:'.$available_rooms;

				// this is advanced check that takes in account max availability for each specific day is selected period of time
				$fully_booked_rooms = true;
				if($available_rooms > 0){
					$available_rooms_updated = $this->CheckAvailabilityForPeriod($rooms[0][$i]['id'], $checkin_date, $checkout_date, $available_rooms);
					if($available_rooms_updated){
						$rooms_count++;
						$this->arrAvailableRooms[$rooms[0][$i]['hotel_id']][] = array('id'=>$rooms[0][$i]['id'], 'available_rooms'=>$available_rooms_updated);						
						$fully_booked_rooms = false;
					}
				}

				if($show_fully_booked_rooms == 'yes' && $fully_booked_rooms){
					$rooms_count++;
					$this->arrAvailableRooms[$rooms[0][$i]['hotel_id']][] = array('id'=>$rooms[0][$i]['id'], 'available_rooms'=>'0');
				}
			}
		}
		
		return $rooms_count;		
	}

    /**
	 * Draws search result
	 * 		@param $params
	 * 		@param $rooms_total
	 * 		@param $draw
	 */
	public function DrawSearchResult($params, $rooms_total = 0, $draw = true)
	{		
		global $objLogin;
		
		$nl = "\n";
		$output = '';
		$currency_rate = Application::Get('currency_rate');
		$currency_format = get_currency_format();
		$lang 		   = Application::Get('lang');		
		$rooms_count   = 0;
		$hotels_count  = 0;
		$total_hotels  = Hotels::HotelsCount();

		$search_page_size = (int)ModulesSettings::Get('rooms', 'search_availability_page_size');
		$show_room_types_in_search = ModulesSettings::Get('rooms', 'show_room_types_in_search');
		if($search_page_size <= 0) $search_page_size = '1';
		$allow_children = ModulesSettings::Get('rooms', 'allow_children');
		$allow_extra_beds = ModulesSettings::Get('rooms', 'allow_extra_beds');

		$allow_booking = false;
		if(Modules::IsModuleInstalled('booking')){
			if(ModulesSettings::Get('booking', 'is_active') == 'global' ||
			   ModulesSettings::Get('booking', 'is_active') == 'front-end' ||
			  (ModulesSettings::Get('booking', 'is_active') == 'back-end' && $objLogin->IsLoggedInAsAdmin())	
			){
				$allow_booking = true;
			}
		}
		
		$sql = 'SELECT
					r.id,
					r.room_type,
					r.room_count,
					r.room_icon,
					IF(r.room_icon_thumb != \'\', r.room_icon_thumb, \'no_image.png\') as room_icon_thumb,
					r.room_picture_1,
					r.room_picture_2,
					r.room_picture_3,
					r.room_picture_4,
					r.room_picture_5,
					CASE
						WHEN r.room_picture_1 != \'\' THEN r.room_picture_1
						WHEN r.room_picture_2 != \'\' THEN r.room_picture_2
						WHEN r.room_picture_3 != \'\' THEN r.room_picture_3
						WHEN r.room_picture_4 != \'\' THEN r.room_picture_4
						WHEN r.room_picture_5 != \'\' THEN r.room_picture_5
						ELSE \'\'
					END as first_room_image,
					r.max_adults,
					r.max_children,
					r.max_extra_beds,
					r.default_price as price,
					rd.room_type as loc_room_type,
					rd.room_short_description as loc_room_short_description
				FROM '.TABLE_ROOMS.' r
					INNER JOIN '.TABLE_ROOMS_DESCRIPTION.' rd ON r.id = rd.room_id
				WHERE
					r.id = _KEY_ AND
					rd.language_id = \''.$lang.'\'';

		if(count($this->arrAvailableRooms) == 1){

			// -------- pagination		
			$current_page = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : '1';
			$total_pages = (int)($rooms_total / $search_page_size);		
			if($current_page > ($total_pages+1)) $current_page = 1;
			if(($rooms_total % $search_page_size) != 0) $total_pages++;
			if(!is_numeric($current_page) || (int)$current_page <= 0) $current_page = 1;
			// --------
			
			if($rooms_total > 0){
				
				// get a first key of the array
				reset($this->arrAvailableRooms);
				$first_key = key($this->arrAvailableRooms);
				
				if($total_hotels > 1){
					$output .= '<div style="margin:10px 0;"><b>'._FOUND_HOTELS.': 1, '._TOTAL_ROOMS.': '.$rooms_total.'</b></div>';
					$output .= $this->DrawHotelInfoBlock($first_key, $lang, false);
					$output .= '<div class="line-hor"></div>';
				}
				
				$meal_plans = MealPlans::GetAllMealPlans($first_key);
				
				foreach($this->arrAvailableRooms[$first_key] as $key){
					
					if($show_room_types_in_search != 'all' && $key['available_rooms'] < 1) continue;					
					$rooms_count++;
					
					if($rooms_count <= ($search_page_size * ($current_page - 1))){
						continue;
					}else if($rooms_count > ($search_page_size * ($current_page - 1)) + $search_page_size){
						break;
					}
					
					$room = database_query(str_replace('_KEY_', $key['id'], $sql), DATA_AND_ROWS, FIRST_ROW_ONLY);
					if($room[1] > 0){					
						$output .= '<br />';
						$output .= '<form action="index.php?page=booking" method="post">'.$nl;
						$output .= draw_hidden_field('hotel_id', $first_key, false).$nl;
						$output .= draw_hidden_field('room_id', $room[0]['id'], false).$nl;
						$output .= draw_hidden_field('from_date', $params['from_date'], false).$nl;
						$output .= draw_hidden_field('to_date', $params['to_date'], false).$nl;
						$output .= draw_hidden_field('nights', $params['nights'], false).$nl;
						$output .= draw_hidden_field('adults', $params['max_adults'], false).$nl;
						$output .= draw_hidden_field('children', $params['max_children'], false).$nl;
						$output .= draw_hidden_field('hotel_sel_id', $params['hotel_sel_id'], false).$nl;
						$output .= draw_hidden_field('hotel_sel_loc_id', $params['hotel_sel_loc_id'], false).$nl;
						$output .= draw_hidden_field('checkin_year_month', $params['from_year'].'-'.(int)$params['from_month'], false).$nl;
						$output .= draw_hidden_field('checkin_monthday', $params['from_day'], false).$nl;
						$output .= draw_hidden_field('checkout_year_month', $params['to_year'].'-'.(int)$params['to_month'], false).$nl;
						$output .= draw_hidden_field('checkout_monthday', $params['to_day'], false).$nl;
						$output .= draw_token_field(false).$nl;
						
						$output .= '<table border="0" width="100%">'.$nl;
						$output .= '<tr valign="top">';
							$output .= '<td>';
								$output .= '<table border="0" width="420px">';					
								$room_price = self::GetRoomPrice($room[0]['id'], $params);							
								if(empty($key['available_rooms'])) $rooms_descr = '<span class="gray">('._FULLY_BOOKED.')</span>';
								else if($room[0]['room_count'] > '1' && $key['available_rooms'] == '1') $rooms_descr = '<span class="red">('._ROOMS_LAST.')</span>';
								else if($room[0]['room_count'] > '1' && $key['available_rooms'] <= '5') $rooms_descr = '<span class="red">('.$key['available_rooms'].' '._ROOMS_LEFT.')</span>';
								else $rooms_descr = '<span class="green">('._AVAILABLE.')</span>';

								$output .= '<tr><td colspan="2"><h4>'.prepare_link('rooms', 'room_id', $room[0]['id'], $room[0]['loc_room_type'], $room[0]['loc_room_type'], '', _CLICK_TO_VIEW).' '.$rooms_descr.'</h4></td></tr>';
								$output .= '<tr><td colspan="2" height="70px">'.$room[0]['loc_room_short_description'].'</td></tr>';
								$output .= '<tr><td colspan="2" nowrap="nowrap" height="5px"></td></tr>';
								$output .= '<tr><td colspan="2">'._MAX_ADULTS.': '.$room[0]['max_adults'].(($allow_children == 'yes') ? ', '._MAX_CHILDREN.': '.$room[0]['max_children'] : '').'</td></tr>';
								if($key['available_rooms']){ 
									$output .= '<tr><td>'._ROOMS.':</td>';
									$output .= '<td>';
										
										if($key['available_rooms'] == 1){
											$output .= '<input type="hidden" name="available_rooms" value="1-'.$room_price.'" />'; 
											$output .= '1 ('.Currencies::PriceFormat($room_price * $currency_rate, '', '', $currency_format).')';
										}else{
											$options = '<select name="available_rooms" class="available_rooms_ddl" '.($allow_booking ? '' : 'disabled="disabled"').'>';
											for($i = 1; $i <= $key['available_rooms']; $i++){
												$room_price_i = $room_price * $i;
												$room_price_i_formatted = Currencies::PriceFormat(($room_price * $i) * $currency_rate, '', '', $currency_format);
												$options .= '<option value="'.$i.'-'.$room_price_i.'" '; 
												$options .= ($i == '0') ? 'selected="selected" ' : '';
												$options .= '>'.$i.(($i != 0) ? ' ('.$room_price_i_formatted.')' : '').'</option>';
											}
											$output .= $options.'</select>';											
										}
										
										if($params['nights'] > 1){
											$output .= '<span class="rooms_description"> <span class="red">*</span> '._RATE_PER_NIGHT;
											$output .= ': '.Currencies::PriceFormat(($room_price * $currency_rate) / $params['nights'], '', '', $currency_format).'</span>';
										}									
									$output .= '</td>';
									$output .= '</tr>';
									if($meal_plans[1] > 0){
										$output .= '<tr>';
											$output .= '<td>'._MEAL_PLANS.':</td>';
											$output .= '<td>';
											$output .= MealPlans::DrawMealPlansDDL($meal_plans, $currency_rate, $currency_format, $allow_booking, false);
											$output .= '<span class="meal_plans_description"> <span class="red">*</span> '._PERSON_PER_NIGHT.'</span>';
											$output .= '</td>';
										$output .= '</tr>';									
									}
									if($allow_extra_beds == 'yes' && $room[0]['max_extra_beds'] > 0){
										$output .= '<tr>';
											$output .= '<td>'._EXTRA_BEDS.':</td>';
											$output .= '<td>';
											$output .= $this->DrawExtraBedsDDL($room[0]['id'], $room[0]['max_extra_beds'], $params, $currency_rate, $currency_format, $allow_booking, false);
											$output .= '<span class="extra_beds_description"> <span class="red">*</span> '._PER_NIGHT.'</span>';
											$output .= '</td>';
										$output .= '</tr>';
									}
								}
								$output .= '<tr><td colspan="2"><a class="price_link" href="javascript:void(0);" onclick="javascript:appToggleElement(\'row_prices_'.$room[0]['id'].'\')" title="'._CLICK_TO_SEE_PRICES.'">'._PRICES.' (+)</a></td></tr>';
								$output .= '</table>';
							$output .= '</td>';
							$output .= '<td width="200px" align="center">';					
								if($room[0]['first_room_image'] != '') $output .= '<a href="images/rooms_icons/'.$room[0]['first_room_image'].'" rel="lyteshow_'.$room[0]['id'].'" title="'._IMAGE.' 1">';
								$output .= '<img class="room_icon" src="images/rooms_icons/'.$room[0]['room_icon_thumb'].'" width="165px" alt="icon" />';
								if($room[0]['first_room_image'] != '') $output .= '</a>';							
								if($room[0]['room_picture_1'] != '') $output .= '  <a href="images/rooms_icons/'.$room[0]['room_picture_1'].'" rel="lyteshow_'.$room[0]['id'].'" title="'._IMAGE.' 1"></a>';					
								if($room[0]['room_picture_2'] != '') $output .= '  <a href="images/rooms_icons/'.$room[0]['room_picture_2'].'" rel="lyteshow_'.$room[0]['id'].'" title="'._IMAGE.' 2"></a>';					
								if($room[0]['room_picture_3'] != '') $output .= '  <a href="images/rooms_icons/'.$room[0]['room_picture_3'].'" rel="lyteshow_'.$room[0]['id'].'" title="'._IMAGE.' 3"></a>';
								if($room[0]['room_picture_4'] != '') $output .= '  <a href="images/rooms_icons/'.$room[0]['room_picture_4'].'" rel="lyteshow_'.$room[0]['id'].'" title="'._IMAGE.' 4"></a>';
								if($room[0]['room_picture_5'] != '') $output .= '  <a href="images/rooms_icons/'.$room[0]['room_picture_5'].'" rel="lyteshow_'.$room[0]['id'].'" title="'._IMAGE.' 5"></a>';
								if($allow_booking && $key['available_rooms']) $output .= '<input type="submit" class="form_button_middle" style="margin-top:10px;" value="'._BOOK_NOW.'!" />';
							$output .= '</td>';
						$output .= '</tr>';
						$output .= '<tr><td colspan="2"><span id="row_prices_'.$room[0]['id'].'" style="margin:5px 5px 10px 5px;display:none;">'.self::GetRoomPricesTable($room[0]['id']).'</span></td></tr>';
						if($rooms_count <= ($rooms_total - 1)) $output .= '<tr><td colspan="2"><div class="line-hor"></div><td></tr>';
						else $output .= '<tr><td colspan="2"><br /><td></tr>';
						$output .= '</table>'.$nl;
						$output .= '</form>'.$nl;
					}
				}
			}
	
			$output .= $this->DrawPaginationLinks($total_pages, $current_page, $params, false);	
			
		}else{
			// multi hotels found
			
			// -------- pagination
			$hotels_total = count($this->arrAvailableRooms);
			$current_page = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : '1';
			$total_pages = (int)($hotels_total / $search_page_size);		
			if($current_page > ($total_pages+1)) $current_page = 1;
			if(($hotels_total % $search_page_size) != 0) $total_pages++;
			if(!is_numeric($current_page) || (int)$current_page <= 0) $current_page = 1;
			// --------

			if($rooms_total > 0){				
				$output .= '<div style="margin:10px 0;"><b>'._FOUND_HOTELS.': '.count($this->arrAvailableRooms).', '._TOTAL_ROOMS.': '.$rooms_total.'</b><div class="line-hor"></div></div>';
				
				foreach($this->arrAvailableRooms as $key => $val){

					$meal_plans = MealPlans::GetAllMealPlans($key);				
					$hotels_count++;					
					
					if($hotels_count <= ($search_page_size * ($current_page - 1))){
						continue;
					}else if($hotels_count > ($search_page_size * ($current_page - 1)) + $search_page_size){
						break;
					}

					if($hotels_count > 1) $output .= '<br><div class="line-hor"></div>';
					
					$output .= $this->DrawHotelInfoBlock($key, $lang, false);
					$output .= '<br>';
					
					$output .= '<table class="room_prices" border="0" cellpadding="0" cellspacing="0">';
					$output .= '<tr class="header">';
					$output .= '  <th align="left">&nbsp;'._ROOM_TYPE.'</th>';
					$output .= '  <th align="center" colspan="3" width="80px">'._MAX_OCCUPANCY.'</th>';
					$output .= '  <th align="center">'._ROOMS.'</th>';
					$output .= '  <th align="center" width="80px">'._RATE.'</th>';
					if($meal_plans[1] > 0) $output .= '<th align="center">'._MEAL_PLANS.'</th>'; 
					$output .= '  <th align="center">&nbsp;</th>';
					$output .= '</tr>';

					$output .= '<tr class="header" style="font-size:10px;background-color:transparent;">';
					$output .= '  <th align="left">&nbsp;</th>';
					$output .= '  <th align="center">'._ADULT.'</th>';
					$output .= '  '.(($allow_children == 'yes') ? '<th align="center">'._CHILD.'</th>' : '<th></th>');
					$output .= '  '.(($allow_extra_beds == 'yes') ? '<th align="center">'._EXTRA_BED.' <span class="help" title="'._PER_NIGHT.'">[?]</span></th>' : '<th></th>');
					$output .= '  <th align="center">&nbsp;</th>';
					$output .= '  <th align="center">'.(($params['nights'] > 1) ? _RATE_PER_NIGHT_AVG : _RATE_PER_NIGHT).'</th>';
					if($meal_plans[1] > 0) $output .= '  <th align="center">'._PERSON_PER_NIGHT.'</th>';
					$output .= '  <th align="center">&nbsp;</th>';
					$output .= '</tr>';

					foreach($val as $k_key => $v_val){
						
						if($show_room_types_in_search != 'all' && $v_val['available_rooms'] < 1) continue;					

						$room = database_query(str_replace('_KEY_', $v_val['id'], $sql), DATA_AND_ROWS, FIRST_ROW_ONLY);
						if($room[1] > 0){					
						
							$room_price = self::GetRoomPrice($room[0]['id'], $params);							
							if(empty($v_val['available_rooms'])) $rooms_descr = '<span class="gray">('._FULLY_BOOKED.')</span>';
							else if($room[0]['room_count'] > '1' && $v_val['available_rooms'] == '1') $rooms_descr = '<span class="red">('._ROOMS_LAST.')</span>';
							else if($room[0]['room_count'] > '1' && $v_val['available_rooms'] <= '5') $rooms_descr = '<span class="red">('.$v_val['available_rooms'].' '._ROOMS_LEFT.')</span>';
							else $rooms_descr = '<span class="green">('._AVAILABLE.')</span>';

							$output .= '<form action="index.php?page=booking" method="post">'.$nl;
							$output .= draw_hidden_field('hotel_id', $key, false).$nl;
							$output .= draw_hidden_field('room_id', $room[0]['id'], false).$nl;
							$output .= draw_hidden_field('from_date', $params['from_date'], false).$nl;
							$output .= draw_hidden_field('to_date', $params['to_date'], false).$nl;
							$output .= draw_hidden_field('nights', $params['nights'], false).$nl;
							$output .= draw_hidden_field('adults', $params['max_adults'], false).$nl;
							$output .= draw_hidden_field('children', $params['max_children'], false).$nl;
							$output .= draw_hidden_field('hotel_sel_id', $params['hotel_sel_id'], false).$nl;
							$output .= draw_hidden_field('hotel_sel_loc_id', $params['hotel_sel_loc_id'], false).$nl;
							$output .= draw_hidden_field('sort_by', $params['sort_by'], false).$nl;
							$output .= draw_hidden_field('checkin_year_month', $params['from_year'].'-'.(int)$params['from_month'], false).$nl;
							$output .= draw_hidden_field('checkin_monthday', $params['from_day'], false).$nl;
							$output .= draw_hidden_field('checkout_year_month', $params['to_year'].'-'.(int)$params['to_month'], false).$nl;
							$output .= draw_hidden_field('checkout_monthday', $params['to_day'], false).$nl;
							$output .= draw_token_field(false).$nl;							

							$output .= '<tr>';
							$output .= '  <td align="left">&nbsp;'.prepare_link('rooms', 'room_id', $room[0]['id'], $room[0]['loc_room_type'], $room[0]['loc_room_type'], '', _CLICK_TO_VIEW).' '.$rooms_descr.'</td>';
							$output .= '  <td align="center">'.$room[0]['max_adults'].'</td>';
							$output .= '  <td align="center">'.(($allow_children == 'yes') ? $room[0]['max_children'] : '').'</td>';
							$output .= '  <td align="center">';
									if(!empty($v_val['available_rooms']) && $allow_extra_beds == 'yes' && $room[0]['max_extra_beds'] > 0){
										$output .= $this->DrawExtraBedsDDL($room[0]['id'], $room[0]['max_extra_beds'], $params, $currency_rate, $currency_format, $allow_booking, false);
									}else{
										$output .= '--';
									}
							$output .= '  </td>';
							$output .= '  <td align="center">';
								if(!empty($v_val['available_rooms'])){
									$output .= '<select name="available_rooms" class="available_rooms_ddl" '.($allow_booking ? '' : 'disabled="disabled"').'>';
 									$options = '';
									for($i = 1; $i <= $v_val['available_rooms']; $i++){
										$room_price_i = $room_price * $i;
										$room_price_i_formatted = Currencies::PriceFormat(($room_price * $i) * $currency_rate, '', '', $currency_format);
										$options .= '<option value="'.$i.'-'.$room_price_i.'" '; 
										$options .= ($i == '0') ? 'selected="selected" ' : '';
										$options .= '>'.$i.(($i != 0) ? ' ('.$room_price_i_formatted.')' : '').'</option>';
									}
									$output .= $options.'</select>';
								}
							$output .= '  </td>';
							if($params['nights'] > 1){
								$output .= '<td align="center">'.Currencies::PriceFormat(($room_price * $currency_rate) / $params['nights'], '', '', $currency_format).'</td>';
							}else{
								$output .= '<td align="center">'.Currencies::PriceFormat($room_price, '', '', $currency_format).'</td>';
							}
							if($meal_plans[1] > 0){
								$output .= '<td align="center">';
								if(!empty($v_val['available_rooms'])){
									$output .= MealPlans::DrawMealPlansDDL($meal_plans, $currency_rate, $currency_format, $allow_booking, false);
								}
								$output .= '</td>';
							}
							$output .= '<td align="right">';
							$output .= (($allow_booking && $v_val['available_rooms']) ? '<input type="submit" class="form_button_middle" style="margin:3px;" value="'._BOOK_NOW.'!" />' : '');
							$output .= '</td>';
							$output .= '</tr>';
							$output .= '</form>'.$nl;
						}
					}
					$output .= '</table>';					
				}				
			}
			
			$output .= $this->DrawPaginationLinks($total_pages, $current_page, $params, false);			
		}
		
		$output .= '<br>';

		if($draw) echo $output;
		else return $output;
	}

    /**
	 * Draws room description
	 * 		@param $room_id
	 * 		@param $back_button
	 */
	public static function DrawRoomDescription($room_id, $back_button = true)
	{		
		global $objLogin;

		$lang = Application::Get('lang');
		$allow_children = ModulesSettings::Get('rooms', 'allow_children');
		$hotels_count = Hotels::HotelsCount();
		$output = '';
		
		$sql = 'SELECT
					r.id,
					r.room_type,
					r.hotel_id,
					r.room_count,
					r.max_adults,
					r.max_children,
					r.beds,
					r.bathrooms,
					r.room_area,
					r.default_price,
					r.facilities,
					r.room_icon,
					r.room_icon_thumb,
					r.room_picture_1,
					r.room_picture_1_thumb,
					r.room_picture_2,
					r.room_picture_2_thumb,
					r.room_picture_3,
					r.room_picture_3_thumb,
					r.room_picture_4,
					r.room_picture_4_thumb,
					r.room_picture_5,
					r.room_picture_5_thumb,
					r.is_active,
					rd.room_type as loc_room_type,
					rd.room_long_description as loc_room_long_description,
					hd.name as hotel_name
				FROM '.TABLE_ROOMS.' r
					INNER JOIN '.TABLE_ROOMS_DESCRIPTION.' rd ON r.id = rd.room_id
					INNER JOIN '.TABLE_HOTELS.' h ON r.hotel_id = h.id
					INNER JOIN '.TABLE_HOTELS_DESCRIPTION.' hd ON r.hotel_id = hd.hotel_id
				WHERE
					h.is_active = 1 AND 
					r.id = '.(int)$room_id.' AND
					hd.language_id = \''.$lang.'\' AND
					rd.language_id = \''.$lang.'\'';
					
		$room_info = database_query($sql, DATA_ONLY, FIRST_ROW_ONLY);

		$room_type 		  = isset($room_info['loc_room_type']) ? $room_info['loc_room_type'] : '';
		$room_long_description = isset($room_info['loc_room_long_description']) ? $room_info['loc_room_long_description'] : '';
		$facilities       = isset($room_info['facilities']) ? unserialize($room_info['facilities']) : array();
		$room_count       = isset($room_info['room_count']) ? $room_info['room_count'] : '';
		$max_adults       = isset($room_info['max_adults']) ? $room_info['max_adults'] : '';
		$max_children  	  = isset($room_info['max_children']) ? $room_info['max_children'] : '';
		$room_area  	  = isset($room_info['room_area']) ? $room_info['room_area'] : '';
		$beds             = isset($room_info['beds']) ? $room_info['beds'] : '';
		$bathrooms        = isset($room_info['bathrooms']) ? $room_info['bathrooms'] : '';
		$default_price    = isset($room_info['default_price']) ? $room_info['default_price'] : '';
		$room_icon        = isset($room_info['room_icon']) ? $room_info['room_icon'] : '';
		$room_picture_1	  = isset($room_info['room_picture_1']) ? $room_info['room_picture_1'] : '';
		$room_picture_2	  = isset($room_info['room_picture_2']) ? $room_info['room_picture_2'] : '';
		$room_picture_3	  = isset($room_info['room_picture_3']) ? $room_info['room_picture_3'] : '';
		$room_picture_4	  = isset($room_info['room_picture_4']) ? $room_info['room_picture_4'] : '';
		$room_picture_5	  = isset($room_info['room_picture_5']) ? $room_info['room_picture_5'] : '';
		$room_picture_1_thumb = isset($room_info['room_picture_1_thumb']) ? $room_info['room_picture_1_thumb'] : '';
		$room_picture_2_thumb = isset($room_info['room_picture_2_thumb']) ? $room_info['room_picture_2_thumb'] : '';
		$room_picture_3_thumb = isset($room_info['room_picture_3_thumb']) ? $room_info['room_picture_3_thumb'] : '';
		$room_picture_4_thumb = isset($room_info['room_picture_4_thumb']) ? $room_info['room_picture_4_thumb'] : '';
		$room_picture_5_thumb = isset($room_info['room_picture_5_thumb']) ? $room_info['room_picture_5_thumb'] : '';
		$hotel_name       = isset($room_info['hotel_name']) ? $room_info['hotel_name'] : '';
		$is_active		  = (isset($room_info['is_active']) && $room_info['is_active'] == 1) ? '<span class="green">'._AVAILABLE.'</span>' : '<span class="red">'._NOT_AVAILABLE.'</span>';		

		if(count($room_info) > 0){

			// prepare facilities array		
			$total_facilities = RoomFacilities::GetAllActive();
			$arr_facilities = array();
			foreach($total_facilities[0] as $key => $val){
				$arr_facilities[$val['id']] = $val['name'];
			}

			$output .= '<table border="0" class="room_description">';
			$output .= '<tr valign="top">';
			$output .= '<td width="200px">';
			///$output .= '  <img class='room_icon' src='images/rooms_icons/'.$room_icon.'' width='165px' alt='icon' />';
			if($room_picture_1 == '' && $room_picture_2 == '' && $room_picture_3 == '' && $room_picture_4 == '' && $room_picture_5 == ''){
				$output .= '<img class="room_icon" src="images/rooms_icons/no_image.png" width="165px" alt="icon" />';
			}
			if($room_picture_1 != '') $output .= ' <a href="'.APPHP_BASE.'images/rooms_icons/'.$room_picture_1.'" rel="lyteshow" title="'._IMAGE.' 1"><img class="room_icon" src="'.APPHP_BASE.'images/rooms_icons/'.$room_picture_1_thumb.'" width="165px" height="140px" alt="icon" /></a><br />';
			if($room_picture_2 != '') $output .= ' <a href="'.APPHP_BASE.'images/rooms_icons/'.$room_picture_2.'" rel="lyteshow" title="'._IMAGE.' 2"><img class="room_icon" src="'.APPHP_BASE.'images/rooms_icons/'.$room_picture_2_thumb.'" width="165px" height="140px" alt="icon" /></a><br />';
			if($room_picture_3 != '') $output .= ' <a href="'.APPHP_BASE.'images/rooms_icons/'.$room_picture_3.'" rel="lyteshow" title="'._IMAGE.' 3"><img class="room_icon" src="'.APPHP_BASE.'images/rooms_icons/'.$room_picture_3_thumb.'" width="165px" height="140px" alt="icon" /></a><br />';
			if($room_picture_4 != '') $output .= ' <a href="'.APPHP_BASE.'images/rooms_icons/'.$room_picture_4.'" rel="lyteshow" title="'._IMAGE.' 4"><img class="room_icon" src="'.APPHP_BASE.'images/rooms_icons/'.$room_picture_4_thumb.'" width="79px" height="67px" alt="icon" /></a>';
			if($room_picture_5 != '') $output .= ' <a href="'.APPHP_BASE.'images/rooms_icons/'.$room_picture_5.'" rel="lyteshow" title="'._IMAGE.' 5"><img class="room_icon" src="'.APPHP_BASE.'images/rooms_icons/'.$room_picture_5_thumb.'" width="79px" height="67px" alt="icon" /></a><br />';
			$output .= '</td>';
			$output .= '<td>';
				$output .= '<table class="room_description_inner">';
				$output .= '<tr><td>';
					$output .= '<h4>'.$room_type.'&nbsp;';				
					if($hotels_count > 1) $output .= ' ('.prepare_link('hotels', 'hid', $room_info['hotel_id'], $hotel_name, $hotel_name, '', _CLICK_TO_VIEW).')';
					$output .= '</h4>';
				$output .= '</td></tr>';
				
				$output .= '<tr><td>'.$room_long_description.'</td></tr>';
				
				$output .= '<tr><td><b>'._FACILITIES.':</b></td></tr>';
				$output .= '<tr><td>';
				$output .= '<ul class="facilities">';
				if(is_array($facilities)){
					foreach($facilities as $key => $val){
						if(isset($arr_facilities[$val])) $output .= '<li>'.$arr_facilities[$val].'</li>';
					}					
				}
				$output .= '</ul>';
				$output .= '</td></tr>';
				
				$output .= '<tr><td>&nbsp;</td></tr>';
				$output .= '<tr><td><b>'._COUNT.':</b> '.$room_count.'</td></tr>';
				$output .= '<tr><td><b>'._ROOM_AREA.':</b> '.number_format($room_area, 1, '.', '').' m<sup>2</sup></td></tr>';
				$output .= '<tr><td><b>'._MAX_ADULTS.':</b> '.$max_adults.'</td></tr>';
				if(!empty($beds)) $output .= '<tr><td><b>'._BEDS.':</b> '.$beds.'</td></tr>';
				if(!empty($bathrooms)) $output .= '<tr><td><b>'._BATHROOMS.':</b> '.$bathrooms.'</td></tr>';
				
				$output .= '<tr><td><b>'._AVAILABILITY.':</b> '.$is_active.'</td></tr>';
				$output .= '</tr>';
				$output .= '</table>';
			$output .= '</td>';
			$output .= '</tr>';

			// draw prices table
			$output .= '<tr><td colspan="2" nowrap="nowrap" height="5px"><td></tr>';
			$output .= '<tr><td colspan="2"><h4>'._PRICES.'</h4><td></tr>';
			$output .= '<tr><td colspan="2">'.self::GetRoomPricesTable($room_id).'<td></tr>';
			$output .= '<tr><td colspan="2" nowrap="nowrap" height="10px"><td></tr>';
			
			if($back_button){ 
				if(!$objLogin->IsLoggedInAsAdmin()){ 
					if(Modules::IsModuleInstalled('booking')){
						if(ModulesSettings::Get('booking', 'show_reservation_form') == 'yes'){
							$output .= '<tr><td colspan="2"><h4>'._RESERVATION.'</h4><td></tr>';
							$output .= '<tr><td colspan="2">'.self::DrawSearchAvailabilityBlock(false, $room_id, '', $max_adults, $max_children, 'room-inline', '', '', false).'<td></tr>';
						}
					}
				}
			}
			$output .= '</table>';
			$output .= '<br>';
			
		}else{
			$output .= draw_important_message(_WRONG_PARAMETER_PASSED, false);		
		}
		
		echo $output;	
	}
	
	/**
	 *	Get room price for a certain period of time
	 *		@param $room_id
	 *		@param $params
	 */
	public static function GetRoomPrice($room_id, $params)
	{		
		// improve: how to make it takes defult price if not found another ?
		// make check periods for 2, 3 days?
		$debug = false;
		
		$date_from = $params['from_year'].'-'.self::ConvertToDecimal($params['from_month']).'-'.self::ConvertToDecimal($params['from_day']);
		$date_to = $params['to_year'].'-'.self::ConvertToDecimal($params['to_month']).'-'.self::ConvertToDecimal($params['to_day']);
		$room_default_price = self::GetRoomDefaultPrice($room_id);
		$arr_week_default_price = self::GetRoomWeekDefaultPrice($room_id);
        
		// calculate available discounts for specific period of time
		$arr_standard_discounts = Campaigns::GetCampaignInfo('', $date_from, $date_to, 'standard');
	
		$total_price = '0';
		$offset = 0;
		while($date_from < $date_to){
			$curr_date_from = $date_from;

			$offset++;			
			$current = getdate(mktime(0,0,0,$params['from_month'],$params['from_day']+$offset,$params['from_year']));
			$date_from = $current['year'].'-'.self::ConvertToDecimal($current['mon']).'-'.self::ConvertToDecimal($current['mday']);
			
			$curr_date_to = $date_from;
			if($debug) echo '<br> ('.$curr_date_from.' ... '.$curr_date_to.') ';
			
			$sql = 'SELECT
						r.id,
						r.default_price,
						rp.adults,
						rp.children,
						rp.mon,
						rp.tue,
						rp.wed,
						rp.thu,
						rp.fri,
						rp.sat,
						rp.sun,
						rp.sun,
						rp.is_default
					FROM '.TABLE_ROOMS.' r
						INNER JOIN '.TABLE_ROOMS_PRICES.' rp ON r.id = rp.room_id
					WHERE
						r.id = '.(int)$room_id.' AND
						rp.adults >= '.(int)$params['max_adults'].' AND
						rp.children >= '.(int)$params['max_children'].' AND 
						(
							(rp.date_from <= \''.$curr_date_from.'\' AND rp.date_to = \''.$curr_date_from.'\') OR
							(rp.date_from <= \''.$curr_date_from.'\' AND rp.date_to >= \''.$curr_date_to.'\')
						) AND
						rp.is_default = 0
					ORDER BY rp.adults ASC, rp.children ASC';
						
			$room_info = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
			if($room_info[1] > 0){
				$arr_week_price = $room_info[0];
				
				// calculate total sum, according to week day prices
				$start = $current_date = strtotime($curr_date_from); 
				$end = strtotime($curr_date_to); 
				while($current_date < $end) {
					// take default weekday price if weekday price is empty
					if(empty($arr_week_price[strtolower(date('D', $current_date))])){
						if($debug) echo '-'.$arr_week_default_price[strtolower(date('D', $current_date))];	
						$room_price = $arr_week_default_price[strtolower(date('D', $current_date))];	
					}else{
						if($debug) echo '='.$arr_week_price[strtolower(date('D', $current_date))];
						$room_price = $arr_week_price[strtolower(date('D', $current_date))];
					}

					if(isset($arr_standard_discounts[$curr_date_from])){
						$room_price = $room_price * (1 - ($arr_standard_discounts[$curr_date_from] / 100));
						if($debug) echo ' after '.$arr_standard_discounts[$curr_date_from].'%= '.$room_price;
					}
					$total_price += $room_price;
					$current_date = strtotime('+1 day', $current_date); 
				}
				
			}else{
				// add default (standard) price
				if($debug) echo '>'.$arr_week_default_price[strtolower(date('D', strtotime($curr_date_from)))];
				$t_price = $arr_week_default_price[strtolower(date('D', strtotime($curr_date_from)))];
				if(!empty($t_price)) $room_price = $t_price;
				else $room_price = $room_default_price;

				if(isset($arr_standard_discounts[$curr_date_from])){
					$room_price = $room_price * (1 - ($arr_standard_discounts[$curr_date_from] / 100));
					if($debug) echo ' after '.$arr_standard_discounts[$curr_date_from].'%= '.$room_price;
				}			
				$total_price += $room_price;
			}
		}
        return $total_price;
	}

	/**
	 *	Get room extra beds price for a certain period of time
	 *		@param $room_id
	 *		@param $params
	 */
	public static function GetRoomExtraBedsPrice($room_id, $params)
	{
		$extra_bed_price = '0';
		
		$sql = 'SELECT
					r.id,
					r.id,
					rp.extra_bed_charge
				FROM '.TABLE_ROOMS.' r
					INNER JOIN '.TABLE_ROOMS_PRICES.' rp ON r.id = rp.room_id
				WHERE
					r.id = '.(int)$room_id.' AND
					(
						(
							rp.is_default = 0 AND 
							rp.adults >= '.(int)$params['max_adults'].' AND
							rp.children >= '.(int)$params['max_children'].' AND 
							( (rp.date_from <= \''.$params['from_date'].'\' AND rp.date_to = \''.$params['from_date'].'\') OR
							  (rp.date_from <= \''.$params['from_date'].'\' AND rp.date_to >= \''.$params['to_date'].'\')
							) 						
						)
						OR
						(
							rp.is_default = 1
						)
					)
				ORDER BY rp.adults ASC, rp.children ASC, rp.is_default ASC';
		$room_info = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($room_info[1] > 0){
			$extra_bed_price = $room_info[0]['extra_bed_charge'];			
		}
		
		return $extra_bed_price;		
	}

	/**
	 *	Returns room default price
	 *		@param $room_id
	 */
	private static function GetRoomDefaultPrice($room_id)
	{
		$sql = 'SELECT
					r.id,
					r.default_price,
					rp.mon,
					rp.tue,
					rp.wed,
					rp.thu,
					rp.fri,
					rp.sat,
					rp.sun,
					rp.sun,
					rp.is_default
				FROM '.TABLE_ROOMS.' r
					INNER JOIN '.TABLE_ROOMS_PRICES.' rp ON r.id = rp.room_id
				WHERE
					r.id = '.(int)$room_id.' AND
					rp.is_default = 1';
					
		$room_info = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($room_info[1] > 0){
			return isset($room_info[0]['mon']) ? $room_info[0]['mon'] : 0;
		}else{
			return isset($room_info[0]['default_price']) ? $room_info[0]['default_price'] : 0;
		}
	}

	/**
	 *	Returns room week default price
	 *		@param $room_id
	 */
	private static function GetRoomWeekDefaultPrice($room_id)
	{		
		$sql = 'SELECT
					r.id,
					r.default_price,
					rp.mon,
					rp.tue,
					rp.wed,
					rp.thu,
					rp.fri,
					rp.sat,
					rp.sun,
					rp.sun,
					rp.is_default
				FROM '.TABLE_ROOMS.' r
					INNER JOIN '.TABLE_ROOMS_PRICES.' rp ON r.id = rp.room_id
				WHERE
					r.id = '.(int)$room_id.' AND
					rp.is_default = 1';					
		$room_default_info = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($room_default_info[1] > 0){
			return $room_default_info[0];
		}
		return array();
	}

	/**
	 *	Returns room availability for month
	 *		@param $arr_rooms
	 *		@param $year
	 *		@param $month
	 *		@param $day
	 */
	public static function GetRoomAvalibilityForWeek($arr_rooms, $year, $month, $day)
	{
		//echo '$year, $month, $day';
		$end_date = date('Y-m-d', strtotime('+7 day', strtotime($year.'-'.$month.'-'.$day)));
		$end_date = explode('-', $end_date);
		$year_end = $end_date['0'];
		$month_end = $end_date['1'];
		$day_end = $end_date['2'];
		
		$today = date('Ymd');
		$today_month = date('Ym');
				
		for($i=0; $i<count($arr_rooms); $i++){
			$arr_rooms[$i]['availability'] = array('01'=>0, '02'=>0, '03'=>0, '04'=>0, '05'=>0, '06'=>0, '07'=>0, '08'=>0, '09'=>0, '10'=>0, '11'=>0, '12'=>0, '13'=>0, '14'=>0, '15'=>0,
										           '16'=>0, '17'=>0, '18'=>0, '19'=>0, '20'=>0, '21'=>0, '22'=>0, '23'=>0, '24'=>0, '25'=>0, '26'=>0, '27'=>0, '28'=>0, '29'=>0, '30'=>0, '31'=>0);
			// exit if we in the past
			if($today_month > $year.$month) continue;

			// fill array with rooms availability
			// ------------------------------------
			$sql = 'SELECT * FROM '.TABLE_ROOMS_AVAILABILITIES.' WHERE room_id = '.(int)$arr_rooms[$i]['id'].' AND m = '.(int)$month;
			$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);			
			if($result[1] > 0){
				for($d = (int)$day; (($d <= (int)$day+7) && ($d <= 31)); $d ++){
					$arr_rooms[$i]['availability'][self::ConvertToDecimal($d)] = (int)$result[0]['d'.$d];
				}				
			}
			
			// fill array with rooms availability
			// ------------------------------------
			if($month_end != $month){
				$sql = 'SELECT * FROM '.TABLE_ROOMS_AVAILABILITIES.' WHERE room_id = '.(int)$arr_rooms[$i]['id'].' AND m = '.(int)$month_end;
				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);			
				if($result[1] > 0){
					for($d = 1; ($d <= (int)$day_end); $d ++){
						$arr_rooms[$i]['availability'][self::ConvertToDecimal($d)] = (int)$result[0]['d'.$d];
					}				
				}				
			}
		}

		///echo '<pre>';
		///print_r($arr_rooms[0]);
		///echo '</pre>';
				
		return $arr_rooms;
	}

	/**
	 *	Returns room availability for month
	 *		@param $arr_rooms
	 *		@param $year
	 *		@param $month
	 */
	public static function GetRoomAvalibilityForMonth($arr_rooms, $year, $month)
	{
		$today = date('Ymd');
		$today_year_month = date('Ym');
		$today_year = date('Y');
				
		for($i=0; $i<count($arr_rooms); $i++){
			$arr_rooms[$i]['availability'] = array('01'=>0, '02'=>0, '03'=>0, '04'=>0, '05'=>0, '06'=>0, '07'=>0, '08'=>0, '09'=>0, '10'=>0, '11'=>0, '12'=>0, '13'=>0, '14'=>0, '15'=>0,
										           '16'=>0, '17'=>0, '18'=>0, '19'=>0, '20'=>0, '21'=>0, '22'=>0, '23'=>0, '24'=>0, '25'=>0, '26'=>0, '27'=>0, '28'=>0, '29'=>0, '30'=>0, '31'=>0);
			// exit if we in the past
			if($today_year_month > $year.$month) continue;

			// fill array with rooms availability
			// ------------------------------------
			if(isset($arr_rooms[$i]['id'])){
				$sql = 'SELECT *
						FROM '.TABLE_ROOMS_AVAILABILITIES.'
				        WHERE room_id = '.(int)$arr_rooms[$i]['id'].' AND
							  y = '.(($today_year == $year) ? '0' : '1').' AND	
						      m = '.(int)$month;
				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
				if($result[1] > 0){
					for($day = 1; $day <= 31; $day ++){
						$arr_rooms[$i]['availability'][self::ConvertToDecimal($day)] = (int)$result[0]['d'.$day];
					}				
				}				
			}
		}

		//echo '<pre>';
		//print_r($arr_rooms);
		//echo '</pre>';
				
		return $arr_rooms;
	}
	
	/**
	 *	Returns room week default availability 
	 *		@param $room_id
	 *		@param $checkin_date
	 *		@param $checkout_date
	 *		@param $avail_rooms
	 */
	private function CheckAvailabilityForPeriod($room_id, $checkin_date, $checkout_date, $avail_rooms = 0)	
	{
		$available_rooms = $avail_rooms;
		$available_until_approval = ModulesSettings::Get('booking', 'available_until_approval');
		
		// calculate total sum, according to week day prices
		$current_date = strtotime($checkin_date);
		$current_year = date('Y');
		$end = strtotime($checkout_date);
		$m_old = '';		
		
		while($current_date < $end) {
			$y = date('Y', $current_date);
			$m = date('m', $current_date);
			$d = date('d', $current_date);
			
            if($m_old != $m){
				$sql = 'SELECT * 
						FROM '.TABLE_ROOMS_AVAILABILITIES.' ra
						WHERE ra.room_id = '.(int)$room_id.' AND
							  ra.y = '.(($y == $current_year) ? '0' : '1').' AND
							  ra.m = '.(int)$m;
				$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
			}

			if($result[1] > 0){
				///echo '<br />'.$result[1].' Room ID: '.$room_id.' Day: '.$d.' Avail: '.$result[0]['d'.(int)$d];
				if($result[0]['d'.(int)$d] <= 0){
					return 0;
				}else{
					$current_date_formated = date('Y-m-d', $current_date);
					// check maximal booked rooms for this day!!!
					$sql = 'SELECT
								SUM('.TABLE_BOOKINGS_ROOMS.'.rooms) as total_booked_rooms
							FROM '.TABLE_BOOKINGS.'
								INNER JOIN '.TABLE_BOOKINGS_ROOMS.' ON '.TABLE_BOOKINGS.'.booking_number = '.TABLE_BOOKINGS_ROOMS.'.booking_number
							WHERE
								('.(($available_until_approval == 'yes') ? '' : TABLE_BOOKINGS.'.status = 2 OR ').' '.TABLE_BOOKINGS.'.status = 3) AND
								'.TABLE_BOOKINGS_ROOMS.'.room_id = '.(int)$room_id.' AND
								(
									(\''.$current_date_formated.'\' >= checkin AND \''.$current_date_formated.'\' < checkout) 
									OR
									(\''.$current_date_formated.'\' = checkin AND \''.$current_date_formated.'\' = checkout) 
								)';
					$result1 = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
					if($result1[1] > 0){
						///echo '<br>T: '.$result[0]['d'.(int)$d].' Reserved/B: '.$result1[0]['total_booked_rooms'];
						if($result1[0]['total_booked_rooms'] >= $result[0]['d'.(int)$d]){
							return 0;
						}else{
							$available_diff = $result[0]['d'.(int)$d] - $result1[0]['total_booked_rooms'];
							if($available_diff < $available_rooms){
								$available_rooms = $available_diff;
							}
						}
					}
				}
			}else{
				return 0;
			}
			$m_old = $m;
			$current_date = strtotime('+1 day', $current_date); 
		}		
		return $available_rooms;		
	}

	/**
	 *	Convert to decimal number with leading zero
	 *  	@param $number
	 */	
	private static function ConvertToDecimal($number)
	{
		return (($number < 0) ? '-' : '').((abs($number) < 10) ? '0' : '').abs($number);
	}

	/**
	 *	Get price for specific date (1 night)
	 *		@param $day
	 */
	public static function GetPriceForDate($rid, $day)
	{
		// get a week day of $day
		$week_day = strtolower(date('D', strtotime($day))); 

		$sql = 'SELECT '.$week_day.' as price
				FROM '.TABLE_ROOMS_PRICES.'
				WHERE
					(
						is_default = 1 OR
						(is_default = 0 AND date_from <= \''.$day.'\' AND \''.$day.'\' <= date_to)
					) AND 
					room_id = '.(int)$rid.'
				ORDER BY is_default ASC
				LIMIT 0, 1';
		$result = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($result[1] > 0){
			return $result[0]['price'];
		}else{
			return '0';
		}
	}

	/**
	 *	Get room info
	 *	  	@param $room_id
	 *	  	@param $param
	 */
	public static function GetRoomInfo($room_id, $param = '')
	{
		$lang = Application::Get('lang');
		$output = '';
		
		$sql = 'SELECT
					r.id,
					r.room_count,
					r.max_adults,
					r.max_children,
					r.beds,
					r.bathrooms,
					r.default_price,
					r.room_icon,					
					r.room_picture_1,
					r.room_picture_2,
					r.room_picture_3,
					r.room_picture_4,
					r.room_picture_5,
					r.is_active,
					rd.room_type,
					rd.room_short_description,
					rd.room_long_description,
					hd.name as hotel_name
				FROM '.TABLE_ROOMS.' r
					INNER JOIN '.TABLE_ROOMS_DESCRIPTION.' rd ON r.id = rd.room_id AND rd.language_id = \''.$lang.'\'
					INNER JOIN '.TABLE_HOTELS_DESCRIPTION.' hd ON r.hotel_id = hd.hotel_id AND hd.language_id = \''.$lang.'\'
				WHERE
					r.id = '.(int)$room_id.'';

		$room_info = database_query($sql, DATA_AND_ROWS, FIRST_ROW_ONLY);
		if($room_info[1] > 0){
			if($param != ''){
				$output = isset($room_info[0][$param]) ? $room_info[0][$param] : '';	
			}else{
				$output = isset($room_info[0]) ? $room_info[0] : array();	
			}
		}
		return $output;
	}

	/**
	 *	Returns room types default price
	 *		@param 4where
	 */
	public static function GetRoomTypes($where = '')
	{
		global $objLogin;
		
		$lang = Application::Get('lang');
		$output = '';
		$where_clause = '';

		if($objLogin->IsLoggedInAs('hotelowner')){
			$hotels_list = implode(',', $objLogin->AssignedToHotels());
			if(!empty($hotels_list)) $where_clause .= ' AND r.hotel_id IN ('.$hotels_list.')';
		}
		
		if(!empty($where)) $where_clause .= ' AND r.hotel_id = '.(int)$where;
		
		$sql = 'SELECT
					r.id,
					r.hotel_id,
					r.room_count,
					rd.room_type,
					\'\' as availability,
					hd.name as hotel_name					
				FROM '.TABLE_ROOMS.' r 
					INNER JOIN '.TABLE_ROOMS_DESCRIPTION.' rd ON r.id = rd.room_id AND rd.language_id = \''.$lang.'\'
					INNER JOIN '.TABLE_HOTELS.' h ON r.hotel_id = h.id AND h.is_active = 1
					INNER JOIN '.TABLE_HOTELS_DESCRIPTION.' hd ON r.hotel_id = hd.hotel_id AND hd.language_id = \''.$lang.'\'
				WHERE 1 = 1
				    '.$where_clause.'
				ORDER BY r.hotel_id ASC, r.priority_order ASC';

		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);

		if($result[1] > 0){
			return $result[0];
		}else{
			return array();
		}
	}

	/**
	 *	Returns last day of month
	 *		@param $month
	 *		@param $year
	 */
	public static function GetMonthLastDay($month, $year)
	{
		if(empty($month)) {
		   $month = date('m');
		}
		if(empty($year)) {
		   $year = date('Y');
		}
		$result = strtotime("{$year}-{$month}-01");
		$result = strtotime('-1 second', strtotime('+1 month', $result));
		return date('d', $result);
	}
	
	/**
	 *	Draws search availability block
	 *	    @param $show_calendar
	 *	    @param $room_id
	 *	    @param $hotel_ids
	 *	    @param $m_adults
	 *	    @param $m_children
	 *	    @param $type values: 'main-vertical', 'room-inline', 'main-inline'
	 *	    @param $action_url
	 *	    @param $target
	 *	    @param $draw
	 */
	public static function DrawSearchAvailabilityBlock($show_calendar = true, $room_id = '', $hotel_ids = '', $m_adults = 8, $m_children = 3, $type = 'main-vertical', $action_url = '', $target = '', $draw = true)
	{
		global $objLogin;

		$current_day = date('d');
		$maximum_adults = ($type == 'room-inline') ? $m_adults : ModulesSettings::Get('rooms', 'max_adults');
		$maximum_children = ($type == 'room-inline') ? $m_children : ModulesSettings::Get('rooms', 'max_children');
		$allow_children = ModulesSettings::Get('rooms', 'allow_children');
		$action_url = ($action_url != '') ? $action_url : APPHP_BASE;
		$target = (!empty($target)) ? $target : '';
		$nl = "\n";
		
		$output = '<link rel="stylesheet" type="text/css" href="'.$action_url.'templates/'.Application::Get('template').'/css/calendar.css" />'.$nl;
		$output .= '<form'.(!empty($target) ? ' target="'.$target.'"' : '').' action="'.$action_url.'index.php?page=check_availability" id="reservation-form" name="reservation-form" method="post">
		'.draw_hidden_field('room_id', $room_id, false).'
		'.draw_hidden_field('p', '1', false, 'page_number').'
		'.draw_token_field(false);
		
		$output_hotels = '';
		$output_locations = '';
		$output_sort_by = '';

		$hotel_sel_loc_id = isset($_POST['hotel_sel_loc_id']) ? prepare_input($_POST['hotel_sel_loc_id']) : '';
		$selected_hotel_id = isset($_POST['hotel_sel_id']) ? (int)$_POST['hotel_sel_id'] : '';
		/// ? if($selected_hotel_id == '' && isset($_GET['hid'])) $selected_hotel_id = (int)$_GET['hid'];
		
		// retrieve all active hotels according (for owner - related only, for selected location - related only)
		///if($hotel_sel_loc_id != ''){
		$hotels_list = ($objLogin->IsLoggedInAs('hotelowner')) ? implode(',', $objLogin->AssignedToHotels()) : '';
        if(!$objLogin->IsLoggedIn() && $hotel_ids != '') $hotels_list = prepare_input($hotel_ids);
		$total_hotels = Hotels::GetAllActive(
			(!empty($hotels_list) ? TABLE_HOTELS.'.id IN ('.$hotels_list.')' : '1=1').
			($hotel_sel_loc_id != '' ? ' AND '.TABLE_HOTELS.'.hotel_location_id = '.(int)$hotel_sel_loc_id : '')
		);
		$hotels_total_number = $total_hotels[1];
        // draw hidden field for widgets (if only one hotel is defined)
        if($hotels_total_number == 1 && $hotels_list != ''){                        
            $output .= draw_hidden_field('hotel_sel_id', (int)$hotels_list, false);					
        }
    	///}else{
		///	$total_hotels = array('0'=>array(), '1'=>0);
		///}

		///if($total_hotels[1] > 1 || ($objLogin->IsLoggedInAs('hotelowner') && $total_hotels[1] > 0)){
		// draw locations
		$total_hotels_locations = HotelsLocations::GetHotelsLocations();
		if($total_hotels_locations[1] > 1){
			$output_locations .= '<select class="select_location" name="hotel_sel_loc_id" onchange="appReloadHotels(this.value,\'hotel_sel_id\',\''.Application::Get('token').'\',\''.$action_url.'\')">';
			$output_locations .= '<option value="">-- '._ALL.' --</option>';
			foreach($total_hotels_locations[0] as $key => $val){
				$output_locations .= '<option'.(($hotel_sel_loc_id == $val['id']) ? ' selected="selected"' : '').' value="'.$val['id'].'">'.$val['name'].'</option>';
			}
			$output_locations .= '</select>';			
		}			

		// draw list of hotels			
		$output_hotels .= '<select class="select_hotel" id="hotel_sel_id" name="hotel_sel_id">';			
		if(!$objLogin->IsLoggedInAs('hotelowner')) $output_hotels .= '<option value="">-- '._ALL.' --</option>';
		foreach($total_hotels[0] as $key => $val){
			$output_hotels .= '<option'.(($selected_hotel_id == $val['id']) ? ' selected="selected"' : '').' value="'.$val['id'].'">'.$val['name'].'</option>';
		}
		$output_hotels .= '</select>';			

		$selected_sort_by = isset($_POST['sort_by']) ? prepare_input($_POST['sort_by']) : '';
		$output_sort_by = (($type == 'main-inline') ? '<label>'._SORT_BY.':</label>' : _SORT_BY.': ');
		
		$output_sort_by .= '<select class="star_rating" name="sort_by">';
		if($hotels_total_number > 1){
			$output_sort_by .= '<option'.(($selected_sort_by == 'stars-5-1') ? ' selected="selected"' : '').' value="stars-5-1">'._STARS_5_1.'</option>
			<option'.(($selected_sort_by == 'stars-1-5') ? ' selected="selected"' : '').' value="stars-1-5">'._STARS_1_5.'</option>
			<option'.(($selected_sort_by == 'name-a-z') ? ' selected="selected"' : '').' value="name-a-z">'._NAME_A_Z.'</option>
			<option'.(($selected_sort_by == 'name-z-a') ? ' selected="selected"' : '').' value="name-z-a">'._NAME_Z_A.'</option>';
		}
		$output_sort_by .= '<option'.(($selected_sort_by == 'price-l-h') ? ' selected="selected"' : '').' value="price-l-h">'._PRICE_L_H.'</option>
			<option'.(($selected_sort_by == 'price-h-l') ? ' selected="selected"' : '').' value="price-h-l">'._PRICE_H_L.'</option>
		</select>&nbsp;';

		
		$output1 = '<select id="checkin_day" name="checkin_monthday" class="checkin_day" onchange="cCheckDateOrder(this,\'checkin_monthday\',\'checkin_year_month\',\'checkout_monthday\',\'checkout_year_month\');cUpdateDaySelect(this);">
						<option class="day prompt" value="0">'._DAY.'</option>';
						$selected_day = isset($_POST['checkin_monthday']) ? prepare_input($_POST['checkin_monthday']) : date('d');
						for($i=1; $i<=31; $i++){													
							$output1  .= '<option value="'.$i.'" '.(($selected_day == $i) ? 'selected="selected"' : '').'>'.$i.'</option>';
						}
					$output1 .= '</select>
					<select id="checkin_year_month" name="checkin_year_month" class="checkin_year_month" onchange="cCheckDateOrder(this,\'checkin_monthday\',\'checkin_year_month\',\'checkout_monthday\',\'checkout_year_month\');cUpdateDaySelect(this);">
						<option class="month prompt" value="0">'._MONTH.'</option>';
						$selected_year_month = isset($_POST['checkin_year_month']) ? prepare_input($_POST['checkin_year_month']) : date('Y-n');
						for($i=0; $i<12; $i++){
							$cur_time = mktime(0, 0, 0, date('m')+$i, '1', date('Y'));
							$val = date('Y', $cur_time).'-'.(int)date('m', $cur_time);
							$output1 .= '<option value="'.$val.'" '.(($selected_year_month == $val) ? 'selected="selected"' : '').'>'.get_month_local(date('n', $cur_time)).' \''.date('y', $cur_time).'</option>';
						}
					$output1 .= '</select>';
					if($show_calendar) $output1 .= '<a class="calendar" onclick="cShowCalendar(this,\'calendar\',\'checkin\');" href="javascript:void(0);"><img title="'._PICK_DATE.'" alt="calendar" src="'.$action_url.'templates/'.Application::Get('template').'/images/button-calendar.png" /></a>';
		
		$output2 = '<select id="checkout_monthday" name="checkout_monthday" class="checkout_day" onchange="cCheckDateOrder(this,\'checkout_monthday\',\'checkout_year_month\');cUpdateDaySelect(this);">
						<option class="day prompt" value="0">'._DAY.'</option>';
						$checkout_selected_day = isset($_POST['checkout_monthday']) ? prepare_input($_POST['checkout_monthday']) : date('d');
						for($i=1; $i<=31; $i++){
							$output2 .= '<option value="'.$i.'" '.(($checkout_selected_day == $i) ? 'selected="selected"' : '').'>'.$i.'</option>';
						}
					$output2 .= '</select>
					<select id="checkout_year_month" name="checkout_year_month" class="checkout_year_month" onchange="cCheckDateOrder(this,\'checkout_monthday\',\'checkout_year_month\');cUpdateDaySelect(this);">
						<option class="month prompt" value="0">'._MONTH.'</option>';
						$checkout_selected_year_month = isset($_POST['checkout_year_month']) ? prepare_input($_POST['checkout_year_month']) : date('Y-n');
						for($i=0; $i<12; $i++){
							$cur_time = mktime(0, 0, 0, date('m')+$i, '1', date('Y'));
							$val = date('Y', $cur_time).'-'.(int)date('m', $cur_time);
							$output2 .= '<option value="'.$val.'" '.(($checkout_selected_year_month == $val) ? 'selected="selected"' : '').'>'.get_month_local(date('n', $cur_time)).' \''.date('y', $cur_time).'</option>';
						}
					$output2 .= '</select>';
					if($show_calendar) $output2 .= '<a class="calendar" onclick="cShowCalendar(this,\'calendar\',\'checkout\');" href="javascript:void(0);"><img title="'._PICK_DATE.'" alt="calendar" src="'.$action_url.'templates/'.Application::Get('template').'/images/button-calendar.png" /></a>';
					
		$output3 = '<select class="max_occupation" name="max_adults" id="max_adults">';
						$max_adults = isset($_POST['max_adults']) ? (int)$_POST['max_adults'] : '1';
						for($i=1; $i<=$maximum_adults; $i++){
							$output3 .= '<option value="'.$i.'" '.(($max_adults == $i) ? 'selected="selected"' : '').'>'.$i.'&nbsp;</option>';
						}
					$output3 .= '</select>&nbsp;';
					
		$output4 = '';
		if($allow_children == 'yes'){
			$output4 .= '<select class="max_occupation" name="max_children" id="max_children">';
				$max_children = isset($_POST['max_children']) ? (int)$_POST['max_children'] : '0';
				for($i=0; $i<=$maximum_children; $i++){
					$output4 .= '<option value="'.$i.'" '.(($max_children == $i) ? 'selected="selected"' : '').'>'.$i.'&nbsp;</option>';
				}
			$output4 .= '</select>';
		}
					
		if($type == 'room-inline'){
			$output .= '<table cellspacing="2" border="0">
				<tr>
					<td><label>'._CHECK_IN.':</label></td>
					<td><label>'._CHECK_OUT.':</label></td>
					<td><label>'._ADULTS.'</label></td>
					<td><label>'._CHILDREN.'</label></td>
					<td></td>
				</tr>
				<tr>
					<td nowrap="nowrap">'.$output1.'</td>
					<td nowrap="nowrap">'.$output2.'</td>
					<td nowrap="nowrap">'.$output3.'</td>
					<td nowrap="nowrap">'.$output4.'</td>
					<td><input class="button" type="button" onclick="document.getElementById(\'reservation-form\').submit()" value="'._CHECK_AVAILABILITY.'" /></td>
				</tr>				
				</table>';			
		}else if($type == 'main-inline'){
			$output .= '<table width="100%" cellspacing="2" border="0">
			<tr>
				<td>';
					if($hotels_total_number > 1 && !empty($output_locations)){
						$output .= '<label>'._SELECT_LOCATION.':</label>';
						$output .= $output_locations;
					}
				$output .= '</td><td>';
					if($hotels_total_number > 1){
						$output .= '<label>'._SELECT_HOTEL.':</label>';
						$output .= $output_hotels;
					}
				$output .= '</td>
				<td colspan="2">'.$output_sort_by.'</td>
				<td><input class="button" type="button" onclick="document.getElementById(\'reservation-form\').submit()" value="'._CHECK_AVAILABILITY.'" /></td>
			<tr>
			<tr>
				<td><label>'._CHECK_IN.':</label>'.$output1.'</td>
				<td><label>'._CHECK_OUT.':</label>'.$output2.'</td>
				<td><label>'._ADULTS.'</label>'.$output3.'</td>
				<td><label>'._CHILDREN.'</label>'.$output4.'</td>				
			</tr>
			</table>';			
		}else{
			$output .= '<table cellspacing="2" border="0">';
			if($hotels_total_number > 1){
				if(!empty($output_locations)){
					$output .= '<tr><td><label>'._SELECT_LOCATION.':</label></td></tr>
							<tr><td nowrap="nowrap">'.$output_locations.'</td></tr>';
				}
				$output .= '<tr><td><label>'._SELECT_HOTEL.':</label></td></tr>
						<tr><td nowrap="nowrap">'.$output_hotels.'</td></tr>';					
			}			
			$output .= '<tr><td><label>'._CHECK_IN.':</label></td></tr>
						<tr><td nowrap="nowrap">'.$output1.'</td></tr>
						<tr><td><label>'._CHECK_OUT.':</label></td></tr>
						<tr><td nowrap="nowrap">'.$output2.'</td></tr>
						<tr><td style="height:5px"></td></tr>
						<tr><td nowrap="nowrap">'._ADULTS.': '.$output3.(!empty($output4) ? _CHILDREN.': '.$output4 : '').'</td></tr>';
			if($hotels_total_number > 0){
				$output .= '<tr><td style="height:5px"></td></tr>
						<tr><td nowrap="nowrap">'.$output_sort_by.'</td></tr>';
			}
			$output .= '<tr><td style="height:7px"></td></tr>
						<tr><td><input class="button" type="button" onclick="document.getElementById(\'reservation-form\').submit()" value="'._CHECK_AVAILABILITY.'" /></td></tr>	
			</table>';
		}

		
		$output .= '</form>
		<div id="calendar"></div>';
		
		if($draw) echo $output;
		else return $output;
	}	
	
	/**
	 *	Draws search availability footer scripts
	 *		@param $dir
	 *		@param $action_url
	 */	
	public static function DrawSearchAvailabilityFooter($dir = '', $action_url = '')
	{
		global $objSettings;

		$nl = "\n";		
		$output = '';		
		if(Modules::IsModuleInstalled('booking')){
			$min_nights = ModulesSettings::Get('booking', 'minimum_nights');
			$min_nights_packages = Packages::GetMinimumNights(date('Y-m-01'), date('Y-m-28'));
			if(isset($min_nights_packages['minimum_nights']) && !empty($min_nights_packages['minimum_nights'])) $min_nights = $min_nights_packages['minimum_nights'];
			$action_url = ($action_url != '') ? $action_url : APPHP_BASE;
	
			$output  = '<script type="text/javascript" src="'.$action_url.'templates/'.Application::Get('template').'/js/calendar'.$dir.'.js"></script>'.$nl;
			$output .= '<script type="text/javascript">'.$nl;
			$output .= 'var calendar = new Object();';
			$output .= 'var trCal = new Object();';
			$output .= 'trCal.nextMonth = "'._NEXT.'";';
			$output .= 'trCal.prevMonth = "'._PREVIOUS.'";';
			$output .= 'trCal.closeCalendar = "'._CLOSE.'";';
			$output .= 'trCal.icons = "templates/'.Application::Get('template').'/images/";';
			$output .= 'trCal.iconPrevMonth2 = "'.((Application::Get('defined_alignment') == 'left') ? 'butPrevMonth2.gif' : 'butNextMonth2.gif').'";';
			$output .= 'trCal.iconPrevMonth = "'.((Application::Get('defined_alignment') == 'left') ? 'butPrevMonth.gif' : 'butNextMonth.gif').'";';
			$output .= 'trCal.iconNextMonth2 = "'.((Application::Get('defined_alignment') == 'left') ? 'butNextMonth2.gif' : 'butPrevMonth2.gif').'";';
			$output .= 'trCal.iconNextMonth = "'.((Application::Get('defined_alignment') == 'left') ? 'butNextMonth.gif' : 'butPrevMonth.gif').'";';
			$output .= 'trCal.currentDay = "'.date('d').'";';
			$output .= 'trCal.currentYearMonth = "'.date('Y-n').'";';
			$output .= 'var minimum_nights = "'.(int)$min_nights.'";';
			$output .= 'var months = ["'._JANUARY.'","'._FEBRUARY.'","'._MARCH.'","'._APRIL.'","'._MAY.'","'._JUNE.'","'._JULY.'","'._AUGUST.'","'._SEPTEMBER.'","'._OCTOBER.'","'._NOVEMBER.'","'._DECEMBER.'"];';
			$output .= 'var days = ["'._MON.'","'._TUE.'","'._WED.'","'._THU.'","'._FRI.'","'._SAT.'","'._SUN.'"];'.$nl;
			if(!isset($_POST['checkin_monthday']) && !isset($_POST['checkin_year_month'])){ 
				$output .= 'cCheckDateOrder(document.getElementById("checkin_day"),"checkin_monthday","checkin_year_month","checkout_monthday","checkout_year_month");';
			}			
			$output .= '</script>';
		}		
		echo $output;
	}
	
	/**
	 *	Draw information about rooms and services
	 *		@param $draw
	 */	
	public static function DrawRoomsInfo($draw = true)
	{
		$lang = Application::Get('lang');
		$allow_children = ModulesSettings::Get('rooms', 'allow_children');
		$allow_extra_beds = ModulesSettings::Get('rooms', 'allow_extra_beds');
		$hotel_id = isset($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : '';
		$total_hotels = Hotels::GetAllActive();
		$output = '';

		if($total_hotels[1] > 1){
			$output .= '<form action="'.prepare_link('pages', 'system_page', 'rooms', 'index', '', '', '', true).'" method="post">';
            $output .= draw_token_field(false);
			$output .= '<div class="hotel_selector"> '._HOTEL.': <select name="hotel_id">';
			$output .= '<option value="0">'._ALL.'</option>';
			$total_hotels = Hotels::GetAllActive();
			foreach($total_hotels[0] as $key => $val){
				$output .= '<option value="'.$val['id'].'" '.(($hotel_id == $val['id']) ? ' selected="selected"' : '').'>'.$val['name'].'</option>';
			}				
			$output .= '</select> ';
			$output .= '<input type="submit" class="form_button" value="'._SHOW.'" />';
			$output .= '</div>';
			$output .= '</form>';
			$output .= '<div class="line-hor"></div>';			
		}

		$sql = 'SELECT
				r.id,
				r.max_adults,
				r.max_children,
				r.max_extra_beds,
				r.room_count,
				r.default_price,
				r.room_icon,
				IF(r.room_icon_thumb != "", r.room_icon_thumb, "no_image.png") as room_icon_thumb,
				r.priority_order,
				r.is_active,
				CONCAT("<a href=\"index.php?admin=mod_room_prices&rid=", r.id, "\" title=\"'._CLICK_TO_MANAGE.'\">", "[ '._PRICES.' ]", "</a>") as link_prices,
				CONCAT("<a href=\"index.php?admin=mod_room_availability&rid=", r.id, "\" title=\"'._CLICK_TO_MANAGE.'\">", "[ '._AVAILABILITY.' ]", "</a>") as link_room_availability,
				IF(r.is_active = 1, "<span class=yes>'._YES.'</span>", "<span class=no>'._NO.'</span>") as my_is_active,
				CONCAT("<a href=\"index.php?admin=mod_room_description&room_id=", r.id, "\" title=\"'._CLICK_TO_MANAGE.'\">[ ", "'._DESCRIPTION.'", " ]</a>") as link_room_description,
				rd.room_type,
				rd.room_short_description,
				rd.room_long_description,
				h.id as hotel_id,
				hd.name as hotel_name
			FROM '.TABLE_ROOMS.' r
				INNER JOIN '.TABLE_HOTELS.' h ON r.hotel_id = h.id
				INNER JOIN '.TABLE_HOTELS_DESCRIPTION.' hd ON r.hotel_id = hd.hotel_id
				INNER JOIN '.TABLE_ROOMS_DESCRIPTION.' rd ON r.id = rd.room_id
			WHERE
				'.(!empty($hotel_id) ? ' h.id = '.(int)$hotel_id.' AND ' : '').'
				h.is_active = 1 AND 
				r.is_active = 1 AND
				hd.language_id = \''.$lang.'\' AND
				rd.language_id = \''.$lang.'\'
			ORDER BY
				r.hotel_id ASC, 
				r.priority_order ASC';
		
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
		for($i=0; $i<$result[1]; $i++){
		    $is_active = (isset($result[0][$i]['is_active']) && $result[0][$i]['is_active'] == 1) ? _AVAILABLE : _NOT_AVAILABLE;		
			$href = prepare_link('rooms', 'room_id', $result[0][$i]['id'], $result[0][$i]['hotel_name'].'/'.$result[0][$i]['room_type'], $result[0][$i]['room_type'], '', '', true);
	
			if($i > 0) $output .= '<div class="line-hor"></div>';					
			$output .= '<table width="100%" border="0">';
			$output .= '<tr valign="top">
						<td><h4><a href="'.$href.'" title="'._CLICK_TO_VIEW.'">'.$result[0][$i]['room_type'].'</a></h4></td>
						<td width="175px" align="center" rowspan="6">
							<a href="'.$href.'" title="'._CLICK_FOR_MORE_INFO.'"><img class="room_icon" src="images/rooms_icons/'.$result[0][$i]['room_icon_thumb'].'" width="165px" alt="icon" /></a>
							'.(($total_hotels[1] > 1 && empty($hotel_id)) ? _HOTEL.': '.prepare_link('hotels', 'hid', $result[0][$i]['hotel_id'], $result[0][$i]['hotel_name'], $result[0][$i]['hotel_name'], '', _CLICK_TO_VIEW) : '').'
						</td>
						</tr>';			
			$output .= '<tr><td>'.$result[0][$i]['room_short_description'].'</td></tr>';
			$output .= '<tr><td><b>'._COUNT.':</b> '.$result[0][$i]['room_count'].'</td></tr>';
			$output .= '<tr><td><b>'._MAX_ADULTS.':</b> '.$result[0][$i]['max_adults'].'</td></tr>';
			if($allow_children == 'yes') $output .= '<tr><td><b>'._MAX_CHILDREN.':</b> '.$result[0][$i]['max_children'].'</td></tr>';
			if($allow_extra_beds == 'yes' && !empty($result[0][$i]['max_extra_beds'])) $output .= '<tr><td><b>'._MAX_EXTRA_BEDS.':</b> '.$result[0][$i]['max_extra_beds'].'</td></tr>';
			//$output .= '<tr><td><b>'._DEFAULT_PRICE.':</b> '.Currencies::PriceFormat($default_price).'</td></tr>';
			$output .= '<tr><td><b>'._AVAILABILITY.':</b> '.$is_active.'</td></tr>';
			$output .= '</tr>';
			$output .= '</table>';			
		}		

		if($draw) echo $output;
		else return $output;
	}	

	/**
	 *	Get max day for month
	 *	  	@param $year
	 *	  	@param $month	 
	 */
	private function GetMonthMaxDay($year, $month)
	{
		if(empty($month)) $month = date('m');
		if(empty($year)) $year = date('Y');
		$result = strtotime("{$year}-{$month}-01");
		$result = strtotime('-1 second', strtotime('+1 month', $result));
		return date('d', $result);
	}
	
	/**
	 * Draws system suggestion form
	 * 		@param $room_id
	 * 		@param $checkin_day
	 * 		@param $checkin_year_month
	 * 		@param $checkout_day
	 * 		@param $checkout_year_month
	 * 		@param $max_adults
	 * 		@param $max_children
	 * 		@param $draw
	 */
	public static function DrawTrySystemSuggestionForm($room_id, $checkin_day, $checkin_year_month, $checkout_day, $checkout_year_month, $max_adults, $max_children, $draw = true)
	{
		$output = '';
		if($max_adults > 1){
			$output .= '<br>';
			$output .= '<form target="_parent" action="index.php?page=check_availability" method="post">';
			$output .= draw_hidden_field('room_id', $room_id, false);
			$output .= draw_hidden_field('p', '1', false, 'page_number');
			$output .= draw_token_field(false);
			$output .= draw_hidden_field('checkin_monthday', $checkin_day, false);
			$output .= draw_hidden_field('checkin_year_month', $checkin_year_month, false);
			$output .= draw_hidden_field('checkout_monthday', $checkout_day, false);
			$output .= draw_hidden_field('checkout_year_month', $checkout_year_month, false);
			$output .= draw_hidden_field('max_adults', (int)($max_adults / 2), false);
			$output .= draw_hidden_field('max_children', (int)($max_children / 2), false);
			
			$output .= _TRY_SYSTEM_SUGGESTION.':<br>';
			$output .= '<input class="button" type="submit" value="'._CHECK_NOW.'" />';
			$output .= '</form>';				
		}
		
		if($draw) echo $output;
		else return $output;		
	}
	
	/**
	 * Draws rooms in specific hotel
	 * 		@param $hotel_id
	 * 		@param $draw
	 */
	public static function DrawRoomsInHotel($hotel_id, $draw = true)
	{
		$output = '';
		
		$sql = 'SELECT
					r.id,
					r.room_count,
					rd.room_type 
				FROM '.TABLE_ROOMS.' r 
					LEFT OUTER JOIN '.TABLE_ROOMS_DESCRIPTION.' rd ON r.id = rd.room_id AND rd.language_id = \''.Application::Get('lang').'\'
				WHERE r.is_active = 1 AND hotel_id = '.(int)$hotel_id.'
				ORDER BY r.priority_order ASC ';
		$result = database_query($sql, DATA_AND_ROWS, ALL_ROWS);
		if($result[1] > 0){
			$output .= '<b>'._ROOMS.'</b>:<br>';
			$output .= '<ul>';
			for($i=0; $i<$result[1]; $i++){				
				$output .= '<li> '.prepare_link('rooms', 'room_id', $result[0][$i]['id'], $result[0][$i]['room_type'], $result[0][$i]['room_type'], '', _CLICK_TO_VIEW).' - '.$result[0][$i]['room_count'].' </li>';
			}
			$output .= '</ul>';
		}
	
		if($draw) echo $output;
		else return $output;		
	}
	
	/**
	 *	Returns all array of all active rooms
	 *		@param $where_clause
	 */
	public static function GetAllActive($where_clause = '')
	{
		$lang = Application::Get('lang');
		
		$sql = 'SELECT
					r.*,
					rd.room_type
				FROM '.TABLE_ROOMS.' r 
					INNER JOIN '.TABLE_ROOMS_DESCRIPTION.' rd ON r.id = rd.room_id AND rd.language_id = \''.$lang.'\'
					INNER JOIN '.TABLE_HOTELS.' h ON r.hotel_id = h.id AND h.is_active = 1
				WHERE r.is_active = 1
					'.(!empty($where_clause) ? ' AND '.$where_clause : '').'	
				ORDER BY r.priority_order ASC';
		return database_query($sql, DATA_AND_ROWS);
	}

	/**
	 * Draws pagination links
	 * 		@param $total_pages
	 * 		@param $current_page
	 * 		@param $params
	 * 		@param $draw
	 */
	private function DrawPaginationLinks($total_pages, $current_page, $params, $draw = true)
	{
		global $objLogin;
		
		$output = '';
		
		// draw pagination links
		if($total_pages > 1){	
			if($objLogin->IsLoggedInAsAdmin()){				
				$output .= '<form action="index.php?page=check_availability" id="reservation-form" name="reservation-form" method="post">
				'.draw_hidden_field('p', '1', false, 'page_number').'
				'.draw_token_field(false).'
				'.draw_hidden_field('checkin_monthday', $params['from_day'], false, 'checkin_monthday').'
				'.draw_hidden_field('checkin_year_month', $params['from_year'].'-'.(int)$params['from_month'], false, 'checkin_year_month').'
				'.draw_hidden_field('checkout_monthday', $params['to_day'], false, 'checkout_monthday').'
				'.draw_hidden_field('checkout_year_month', $params['to_year'].'-'.(int)$params['to_month'], false, 'checkout_year_month');
			}
			
			$output .= '<div class="paging">';
			for($page_ind = 1; $page_ind <= $total_pages; $page_ind++){
				$output .= '<a class="paging_link" href="javascript:void(\'page|'.$page_ind.'\');" onclick="javascript:appFormSubmit(\'reservation-form\',\'page_number='.$page_ind.'\')">'.(($page_ind == $current_page) ? '<b>['.$page_ind.']</b>' : $page_ind).'</a> ';
			}
			$output .= '</div>'; 
			if($objLogin->IsLoggedInAsAdmin()) $output .= '<form>';
		}

		if($draw) echo $output;
		else return $output;		
	}
	
	/**
	 * Draw Hotel Info block
	 * 		@param $hotel_id
	 * 		@param $lang
	 * 		@param $draw
	 */
	private function DrawHotelInfoBlock($hotel_id, $lang = '', $draw = true)
	{
		$output = '';
		$hotel_info = Hotels::GetHotelFullInfo($hotel_id, (!empty($lang) ? $lang : Application::Get('lang')));
		if($hotel_info){
			$arr_stars_vm = array(
				'0'=>_NONE,
				'1'=>'<img src="images/stars1.png" alt="1" title="1-star hotel" />',
				'2'=>'<img src="images/stars2.png" alt="2" title="2-star hotel" />',
				'3'=>'<img src="images/stars3.png" alt="3" title="3-star hotel" />',
				'4'=>'<img src="images/stars4.png" alt="4" title="4-star hotel" />',
				'5'=>'<img src="images/stars5.png" alt="5" title="5-star hotel" />');
			
			$output .= '<table class="tbl_hotel_description">';
			$output .= '<tr valign="top">';
			$hotel_img = ($hotel_info['hotel_image'] != '' && file_exists('images/hotels/'.$hotel_info['hotel_image'])) ? 'images/hotels/'.$hotel_info['hotel_image'] : 'images/hotels/no_image.png';
			$output .= '<td><img class="hotel_icon" src="'.$hotel_img.'" alt="hotel icon" /></td>';
			$output .= '<td>
							<div class="hotel_name">'.prepare_link('hotels', 'hid', $hotel_info['id'], $hotel_info['name'], $hotel_info['name'], '', _CLICK_TO_SEE_DESCR).' &nbsp;'.(($hotel_info['stars'] > 0) ? $arr_stars_vm[$hotel_info['stars']] : '').'</div>
							<div class="hotel_location">'.$hotel_info['location_name'].'</div>
							<div class="hotel_description">'.substr_by_word($hotel_info['description'], 350, true).'</div>
						</td>';
			$output .= '</tr>';
			$output .= '</table>';			
		}

		if($draw) echo $output;
		else return $output;
	}
	
	/**
	 * Draw extra beds dropdownlist
	 * 		@param $room_id
	 * 		@param $max_extra_beds
	 * 		@param $params
	 * 		@param $currency_rate
	 * 		@param $currency_format
	 * 		@param $enabled
	 * 		@param $draw
	 */
	private function DrawExtraBedsDDL($room_id, $max_extra_beds, $params, $currency_rate, $currency_format, $enabled = true, $draw = true)
	{
		$extra_bed_price = self::GetRoomExtraBedsPrice($room_id, $params);		
		$output = '<select class="available_extra_beds_ddl" name="available_extra_beds" '.($enabled ? '' : 'disabled="disabled"').'>';
		$output .= '<option value="0">0</option>';	
		for($i=0; $i<$max_extra_beds; $i++){
			$extra_beds_count = ($i+1);
			$extra_bed_charge_per_night = ($extra_beds_count * $extra_bed_price);
			$extra_bed_charge_per_night_format = Currencies::PriceFormat($extra_bed_charge_per_night * $currency_rate, '', '', $currency_format);
			$output .= '<option value="'.$extra_beds_count.'-'.$extra_bed_charge_per_night.'">'.$extra_beds_count.' ('.$extra_bed_charge_per_night_format.')</option>';	
		}
		$output .= '</select>';
		
		if($draw) echo $output;
		else return $output;
	}
	
}
?>