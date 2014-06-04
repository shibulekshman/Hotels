<?php

/***
 *	Class Application
 *  -------------- 
 *  Description : encapsulates application properties and methods
 *	Written by  : ApPHP
 *	Version     : 1.0.4
 *  Updated	    : 19.11.2013
 *  Usage       : Core Class (excepting MicroBlog)
 *	Differences : $PROJECT
 *
 *	PUBLIC:				  	STATIC:				 	PRIVATE:
 * 	------------------	  	---------------     	---------------
 *	                        Init
 *	                        Param
 *	                        Get
 *	                        Set
 *	                        SetLibraries
 *	                        DrawPreview
 *
 *  ChangeLog:
 *  ----------
 *	1.0.4
 *	    - templates section moved after META tags section
 *	    -
 *	    -
 *	    -
 *	    -
 *	1.0.3
 *	    - added changes for self::$params['search_in']
 *	    - added 'currency_decimals'
 *	    - added if(file_exists('cron.php')) include_once('cron.php');		
 *	    - added drawing hotel and rooms META tags
 *	    - added specia lMETA tags for customer & user pages	    
 *	1.0.2
 *	    - added Title = Listing | Category for BusinessDirectory
 *	    - added changed - doctor for MedicalAppointment
 *	    - fixed bug for Categories::GetCategoryInfo()
 *	    - added template for offline message
 *	    - issues fixed for creating titel for Listings
 **/

class Application {
	
	// MicroCMS, BusinessDirectory, HotelSite, ShoppingCart, MedicalAppointment
	private static $PROJECT = 'HotelSite';

	private static $params = array(
		'template'        	=> 'default',
		'tag_title'       	=> '',
		'tag_description' 	=> '',
		'tag_keywords'    	=> '',
		
		'defined_left' 	  	=> 'left',
		'defined_right'   	=> 'right',
		'defined_alignment' => 'left',
		'preview'           => '',
		
		'admin'             => '',
		'user'              => '',
		'customer'          => '',
		'patient'           => '',
        'doctor'            => '',
		'system_page'       => '',
		'type'              => '',
		'page'              => '',
		'page_id'           => '',
		'news_id'        	=> '',		
		'album_code'        => '',
		'search_in'         => '',		
		
		'lang'              => 'en',
		'lang_dir'          => 'ltr',
		'lang_name'         => 'English',
		'lang_name_en'      => 'English',
		'lc_time_name'      => 'en_US',

		'currency'          => '',
		'currency_code'     => '',
		'currency_symbol'   => '',
		'currency_rate'     => '',
        'currency_decimals' => '',
		'currency_symbol_place' => '',

		'token'             => '',		

		'js_included'       => '',
		
		'allow_last_visited' => false,

		'listing_id'        => '',
		'category_id'       => '',
		'manufacturer_id'   => '',
		'product_id'        => '',
        'hotel_id'          => '',
        'room_id'           => '',
	);


	//==========================================================================
    // Class Initialization
	//==========================================================================
	public static function Init()
	{
		global $objLogin, $objSettings, $objSiteDescription;
		
		self::$params['page']        = isset($_GET['page']) ? prepare_input($_GET['page']) : 'home';
		self::$params['page_id']     = isset($_REQUEST['pid']) ? prepare_input($_REQUEST['pid']) : 'home';
		self::$params['system_page'] = isset($_GET['system_page']) ? prepare_input($_GET['system_page']) : '';
		self::$params['type']        = isset($_GET['type']) ? prepare_input($_GET['type']) : '';
		self::$params['admin']  	 = isset($_GET['admin']) ? prepare_input($_GET['admin']) : '';
		self::$params['user']	     = isset($_GET['user']) ? prepare_input($_GET['user']) : '';
		self::$params['customer']	 = isset($_GET['customer']) ? prepare_input($_GET['customer']) : '';
		self::$params['patient']	 = isset($_GET['patient']) ? prepare_input($_GET['patient']) : '';
        self::$params['doctor']	     = isset($_GET['doctor']) ? prepare_input($_GET['doctor']) : '';
		self::$params['news_id']     = isset($_GET['nid']) ? (int)$_GET['nid'] : '';
		self::$params['album_code']  = isset($_GET['acode']) ? strip_tags(prepare_input($_GET['acode'])) : '';
		self::$params['search_in']   = isset($_POST['search_in']) ? prepare_input($_POST['search_in']) : '';
        if(self::$params['search_in'] == ''){
            if(self::$PROJECT == 'BusinessDirectory'){
                self::$params['search_in'] = 'listings';
            }else if(self::$PROJECT == 'ShoppingCart'){
                self::$params['search_in'] = 'products';
            }else if(self::$PROJECT == 'HotelSite'){
                self::$params['search_in'] = 'rooms';
            }
        } 
		self::$params['lang']        = isset($_GET['lang']) ? prepare_input($_GET['lang']) : '';
		self::$params['currency']    = isset($_GET['currency']) ? prepare_input($_GET['currency']) : '';
		self::$params['token']       = isset($_GET['token']) ? prepare_input($_GET['token']) : '';
		self::$params['listing_id']  = isset($_GET['lid']) ? (int)$_GET['lid'] : '';
		self::$params['category_id'] = isset($_GET['cid']) ? (int)$_GET['cid'] : '';
		self::$params['manufacturer_id'] = isset($_GET['mid']) ? (int)$_GET['mid'] : '';
		self::$params['product_id']  = isset($_REQUEST['prodid']) ? (int)$_REQUEST['prodid'] : '';
        self::$params['hotel_id']    = isset($_GET['hid']) ? (int)$_GET['hid'] : '';
        self::$params['room_id']     = isset($_GET['room_id']) ? (int)$_GET['room_id'] : '';
		$req_preview    			 = isset($_GET['preview']) ? prepare_input($_GET['preview']) : '';
		
		//------------------------------------------------------------------------------
		// check and set token
		$token = md5(uniqid(rand(), true));	
		self::$params['token'] = $token;
		Session::Set('token', $token);

		//------------------------------------------------------------------------------
		// save last visited page
		if(self::$params['allow_last_visited'] && !$objLogin->IsLoggedIn()){
			$condition = (!empty(self::$params['page']) && self::$params['page'] != 'home');
			if(self::$PROJECT == 'HotelSite') $condition = (self::$params['page'] == 'booking' || self::$params['page'] == 'booking_details');
			else if(self::$PROJECT == 'ShoppingCart') $condition = (self::$params['page'] == 'shopping_cart' || self::$params['page'] == 'checkout');
			else if(self::$PROJECT == 'MedicalAppointment') $condition = (self::$params['page'] == 'checkout_signin'); 
			if($condition){
				Session::Set('last_visited', 'index.php?page='.self::$params['page']);
				if(self::$params['page'] == 'pages' && !empty(self::$params['page_id']) && self::$params['page_id'] != 'home'){
					Session::Set('last_visited', Session::Get('last_visited').'&pid='.self::$params['page_id']);
				}else if(self::$params['page'] == 'news' && !empty(self::$params['news_id'])){
					Session::Set('last_visited', Session::Get('last_visited').'&nid='.self::$params['news_id']);
				}else if(self::$params['page'] == 'listing' && !empty(self::$params['listing_id'])){
					Session::Set('last_visited', Session::Get('last_visited').'&lid='.self::$params['listing_id']);
				}else if(self::$params['page'] == 'category' && !empty(self::$params['category_id'])){
					Session::Set('last_visited', Session::Get('last_visited').'&cid='.self::$params['category_id']);
				}else if(self::$params['page'] == 'manufacturer' && !empty(self::$params['manufacturer_id'])){
					Session::Set('last_visited', Session::Get('last_visited').'&mid='.self::$params['product_id']);
				}else if(self::$params['page'] == 'product' && !empty(self::$params['product_id'])){
					Session::Set('last_visited', Session::Get('last_visited').'&prodid='.self::$params['product_id']);
				}
			}			
		}

		//------------------------------------------------------------------------------
		// set language
		if($objLogin->IsLoggedInAsAdmin()){
			$pref_lang                    = $objLogin->GetPreferredLang();
			self::$params['lang']         = (Languages::LanguageExists($pref_lang, false)) ? $pref_lang : Languages::GetDefaultLang();
			$language_info                = Languages::GetLanguageInfo(self::$params['lang']);			
			self::$params['lang_dir']     = $language_info['lang_dir'];
			self::$params['lang_name']    = $language_info['lang_name'];
			self::$params['lang_name_en'] = $language_info['lang_name_en'];
			self::$params['lc_time_name'] = $language_info['lc_time_name'];
		}else{
			if(!$objLogin->IsLoggedIn() && (self::$params['admin'] == 'login' || self::$params['admin'] == 'password_forgotten')){
				self::$params['lang']         = Languages::GetDefaultLang();
				$language_info                = Languages::GetLanguageInfo(self::$params['lang']);
				self::$params['lang_dir']     = $language_info['lang_dir'];
				self::$params['lang_name']    = $language_info['lang_name'];
				self::$params['lang_name_en'] = $language_info['lang_name_en'];
				self::$params['lc_time_name'] = $language_info['lc_time_name'];
			}else if(!empty(self::$params['lang']) && Languages::LanguageExists(self::$params['lang'])){
				//self::$params['lang']         = self::$params['lang'];
				$language_info                = Languages::GetLanguageInfo(self::$params['lang']);
				Session::Set('lang',          self::$params['lang']);
				Session::Set('lang_dir',      self::$params['lang_dir'] = $language_info['lang_dir']);
				Session::Set('lang_name',     self::$params['lang_name'] = $language_info['lang_name']);
				Session::Set('lang_name_en',  self::$params['lang_name_en'] = $language_info['lang_name_en']);
				Session::Set('lc_time_name',  self::$params['lc_time_name'] = $language_info['lc_time_name']);
			}else if(Session::Get('lang') != '' && Session::Get('lang_dir') != '' && Session::Get('lang_name') != '' && Session::Get('lang_name_en') != ''){
				self::$params['lang']         = Session::Get('lang'); 
				self::$params['lang_dir']     = Session::Get('lang_dir');
				self::$params['lang_name']    = Session::Get('lang_name');
				self::$params['lang_name_en'] = Session::Get('lang_name_en');
				self::$params['lc_time_name'] = Session::Get('lc_time_name');
			}else{
				self::$params['lang']         = Languages::GetDefaultLang();
				$language_info                = Languages::GetLanguageInfo(self::$params['lang']);
				self::$params['lang_dir']     = isset($language_info['lang_dir']) ? $language_info['lang_dir'] : '';
				self::$params['lang_name']    = isset($language_info['lang_name']) ? $language_info['lang_name'] : '';
				self::$params['lang_name_en'] = isset($language_info['lang_name_en']) ? $language_info['lang_name_en'] : '';
				self::$params['lc_time_name'] = isset($language_info['lc_time_name']) ? $language_info['lc_time_name'] : '';
			}
		}		

		//------------------------------------------------------------------------------
		// set currency
		if(self::$PROJECT == 'ShoppingCart' || self::$PROJECT == 'HotelSite' || self::$PROJECT == 'BusinessDirectory' || self::$PROJECT == 'MedicalAppointment'){
			if(!empty(self::$params['currency']) && Currencies::CurrencyExists(self::$params['currency'])){
				self::$params['currency_code']   = self::$params['currency'];
				$currency_info = Currencies::GetCurrencyInfo(self::$params['currency_code']);
				self::$params['currency_symbol'] = $currency_info['symbol'];
				self::$params['currency_rate']   = $currency_info['rate'];
                self::$params['currency_decimals'] = $currency_info['decimals'];
				self::$params['currency_symbol_place'] = $currency_info['symbol_placement'];				
				Session::Set('currency_code', self::$params['currency']);
				Session::Set('currency_symbol', $currency_info['symbol']);
				Session::Set('currency_rate', $currency_info['rate']);
                Session::Set('currency_decimals', $currency_info['decimals']);
				Session::Set('symbol_placement', $currency_info['symbol_placement']);
			}else if(
				Session::Get('currency_code') != '' && Session::Get('currency_symbol') != '' && Session::Get('currency_rate') != '' && Session::Get('currency_decimals') != '' && Session::Get('symbol_placement') != '' && Currencies::CurrencyExists(Session::Get('currency_code'))){
				self::$params['currency_code']   = Session::Get('currency_code');
				self::$params['currency_symbol'] = Session::Get('currency_symbol');
				self::$params['currency_rate']   = Session::Get('currency_rate');
                self::$params['currency_decimals'] = Session::Get('currency_decimals');
				self::$params['currency_symbol_place'] = Session::Get('symbol_placement');
			}else{
				$currency_info = Currencies::GetDefaultCurrencyInfo();
				self::$params['currency_code']   = $currency_info['code'];
				self::$params['currency_symbol'] = $currency_info['symbol'];
				self::$params['currency_rate']   = $currency_info['rate'];
                self::$params['currency_decimals']   = $currency_info['decimals'];
				self::$params['currency_symbol_place'] = $currency_info['symbol_placement'];
			}
		}

		// preview allowed only for admins
		// -----------------------------------------------------------------------------
		if($objLogin->IsLoggedInAsAdmin()){
			if($req_preview == 'yes' || $req_preview == 'no'){
				self::$params['preview'] = $req_preview;
				Session::Set('preview', self::$params['preview']);
			}else if((self::$params['admin'] == '') && (Session::Get('preview') == 'yes' || Session::Get('preview') == 'no')){
				self::$params['preview'] = Session::Get('preview');
			}else{
				self::$params['preview'] = 'no';
				Session::Set('preview', self::$params['preview']);
			}
		}
				
		// *** get site description
		// -----------------------------------------------------------------------------
		$objSiteDescription->LoadData(self::$params['lang']);
		
		// *** draw offline message
		// -----------------------------------------------------------------------------
		if($objSettings->GetParameter('is_offline')){
			if(!$objLogin->IsLoggedIn() && self::$params['admin'] != 'login'){
                
                $offline_content = @file_get_contents('html/site_offline.html');
                if(!empty($offline_content)){
                    $offline_content = str_ireplace(
                        array('{HEADER_TEXT}', '{SLOGAN_TEXT}', '{OFFLINE_MESSAGE}', '{FOOTER}'),
                        array(
                            $objSiteDescription->GetParameter('header_text'),
                            $objSiteDescription->GetParameter('slogan_text'),
                            $objSettings->GetParameter('offline_message'),
                            $objSiteDescription->DrawFooter(false)
                        ),
                        $offline_content
                    );                    
                }else{
                    $offline_content = $objSettings->GetParameter('offline_message');
                }
                echo $offline_content;
				exit;
			}
		}
		
		// *** draw offline message
		// -----------------------------------------------------------------------------
		if($objSettings->GetParameter('is_offline')){
			if(!$objLogin->IsLoggedIn() && self::$params['admin'] != 'login'){
				echo '<html>';
				echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';
				echo '<body>'.$objSettings->GetParameter('offline_message').'</body>';
				echo '</html>';
				exit;
			}
		}
		
		// *** run cron jobs file
		// -----------------------------------------------------------------------------
		if($objSettings->GetParameter('cron_type') == 'non-batch'){
			if(file_exists('cron.php')) include_once('cron.php');		
		}
		
		// *** default user page
		// -----------------------------------------------------------------------------
		if(self::$PROJECT == 'MicroCMS'){
			if($objLogin->IsLoggedInAsUser()) if(self::$params['user'] == '' && self::$params['page'] == '') self::$params['user'] = 'home';
		}else if(self::$PROJECT == 'BusinessDirectory'){
			if($objLogin->IsLoggedInAsCustomer()) if(self::$params['customer'] == '' && self::$params['page'] == '') self::$params['customer'] = 'home';
		}else if(self::$PROJECT == 'ShoppingCart'){
			if($objLogin->IsLoggedInAsCustomer()) if(self::$params['customer'] == '' && self::$params['page'] == '') self::$params['customer'] = 'home';
		}else if(self::$PROJECT == 'HotelSite'){
			if($objLogin->IsLoggedInAsCustomer()) if(self::$params['customer'] == '' && self::$params['page'] == '') self::$params['customer'] = 'home';	
		}else if(self::$PROJECT == 'MedicalAppointment'){
			if($objLogin->IsLoggedInAsPatient()) if(self::$params['patient'] == '' && self::$params['page'] == '') self::$params['patient'] = 'home';
            if($objLogin->IsLoggedInAsDoctor()) if(self::$params['doctor'] == '' && self::$params['page'] == '') self::$params['doctor'] = 'home';	
		}
		
		// *** use direction of selected language
		// -----------------------------------------------------------------------------
		self::$params['defined_left']  = (self::$params['lang_dir'] == 'ltr') ? 'left' : 'right';
		self::$params['defined_right'] = (self::$params['lang_dir'] == 'ltr') ? 'right' : 'left';
		self::$params['defined_alignment'] = (self::$params['lang_dir'] == 'ltr') ? 'left' : 'right';
		
		// *** prepare META tags
		// -----------------------------------------------------------------------------
		if(self::$params['page'] == 'news' && self::$params['news_id'] != ''){
			$news_info = News::GetNewsInfo(self::$params['news_id'], self::$params['lang']);
			self::$params['tag_title'] = isset($news_info['header_text']) ? $news_info['header_text'] : $objSiteDescription->GetParameter('tag_title');
			self::$params['tag_keywords'] = isset($news_info['header_text']) ? str_replace(' ', ',', $news_info['header_text']) : $objSiteDescription->GetParameter('tag_keywords');
			self::$params['tag_description'] = isset($news_info['header_text']) ? $news_info['header_text'] : $objSiteDescription->GetParameter('tag_description');
        }else if(self::$PROJECT == 'HotelSite' && self::$params['page'] == 'hotels'){
            $hotel_info = Hotels::GetHotelFullInfo(self::$params['hotel_id'], self::$params['lang']);
            self::$params['tag_title'] = isset($hotel_info['name']) ? strip_tags($hotel_info['name']) : '';
            self::$params['tag_keywords'] = isset($hotel_info['name']) ? strip_tags($hotel_info['name']) : '';
            self::$params['tag_description'] = isset($hotel_info['description']) ? strip_tags($hotel_info['description']) : ''; 
        }else if(self::$PROJECT == 'HotelSite' && self::$params['page'] == 'rooms'){
            $room_info = Rooms::GetRoomInfo(self::$params['room_id']);
            self::$params['tag_title'] = isset($room_info['room_type']) ? strip_tags($room_info['room_type'].' | '.$room_info['hotel_name']) : '';
            self::$params['tag_keywords'] = isset($room_info['room_type']) ? strip_tags($room_info['room_type']) : '';
            self::$params['tag_description'] = isset($room_info['room_short_description']) ? strip_tags($room_info['room_short_description']) : ''; 
        }else if(self::$PROJECT == 'BusinessDirectory' && (self::$params['page'] == 'category' || self::$params['page'] == 'listing')){
            if(self::$params['page'] == 'category'){
                $category_info = Categories::GetCategoryInfo(self::$params['category_id']);
                self::$params['tag_title'] = isset($category_info['name']) ? $category_info['name'] : '';
                self::$params['tag_keywords'] = isset($category_info['name']) ? $category_info['name'] : '';
                self::$params['tag_description'] = isset($category_info['description']) ? $category_info['description'] : ''; 
            }else if(self::$params['page'] == 'listing'){
                $listing_info = Listings::GetListingInfo(self::$params['listing_id']);
                self::$params['tag_title'] = isset($listing_info['business_name']) ? $listing_info['business_name'] : '';
                self::$params['tag_keywords'] = isset($listing_info['business_name']) ? trim($listing_info['business_name']) : '';
                self::$params['tag_description'] = isset($listing_info['business_name']) ? trim(strip_tags($listing_info['business_description'])) : '';					
            }
		}else if(in_array(self::$PROJECT, array('HotelSite', 'BusinessDirectory', 'ShoppingCart')) && self::$params['customer'] != ''){
			self::$params['tag_title'] = file_exists('customer/'.self::$params['customer'].'.php') ? ucwords(str_replace('_', ' ', self::$params['customer'])) : 'Error 404';
			self::$params['tag_keywords'] = $objSiteDescription->GetParameter('tag_keywords');
			self::$params['tag_description'] = $objSiteDescription->GetParameter('tag_description');            
		}else if(self::$PROJECT == 'MicroCMS' && self::$params['user'] != ''){
			self::$params['tag_title'] = file_exists('user/'.self::$params['user'].'.php') ? ucwords(str_replace('_', ' ', self::$params['customer'])) : 'Error 404';
			self::$params['tag_keywords'] = $objSiteDescription->GetParameter('tag_keywords');
			self::$params['tag_description'] = $objSiteDescription->GetParameter('tag_description');            
		}else{
			if(self::$params['system_page'] != ''){
				$objPage = new Pages(self::$params['system_page'], true);			
			}else{
				$objPage = new Pages(self::$params['page_id'], true);
			}
			self::$params['tag_title'] = ($objPage->GetParameter('tag_title') != '') ? $objPage->GetParameter('tag_title') : $objSiteDescription->GetParameter('tag_title');
			self::$params['tag_keywords'] = ($objPage->GetParameter('tag_keywords') != '') ? $objPage->GetParameter('tag_keywords') : $objSiteDescription->GetParameter('tag_keywords');
			self::$params['tag_description'] = ($objPage->GetParameter('tag_description') != '') ? $objPage->GetParameter('tag_description') : $objSiteDescription->GetParameter('tag_description');
		}
		
		// *** get site template
		// -----------------------------------------------------------------------------
		self::$params['template'] = ($objSettings->GetTemplate() != '') ? $objSettings->GetTemplate() : DEFAULT_TEMPLATE;
		if($objLogin->IsLoggedInAsAdmin() && (self::$params['preview'] != 'yes' || self::$params['admin'] != '')) self::$params['template'] = 'admin';
		else if(!$objLogin->IsLoggedIn() && (self::$params['admin'] == 'login' || self::$params['admin'] == 'password_forgotten')) self::$params['template'] = 'admin';
		
		// *** included js libraries
		// -----------------------------------------------------------------------------
		self::$params['js_included'] = array();
	}
	
	/**
	 * Include style and javascript files
	 */
	public static function SetLibraries()
	{
		$nl = "\n";

		$output = '<script type="text/javascript" src="'.APPHP_BASE.'js/jquery-1.4.2.min.js"></script>'.$nl;
		$output .= GalleryAlbums::SetLibraries();		
		
		if(!self::Get('js_included', 'lytebox')){
			$output .= '<link rel="stylesheet" href="'.APPHP_BASE.'modules/lytebox/css/lytebox.css" type="text/css" media="screen" />'.$nl;
			$output .= '<script type="text/javascript" src="'.APPHP_BASE.'modules/lytebox/js/lytebox.js"></script>'.$nl;
			Application::Set('js_included', 'lytebox');
		}

		return $output;
	}

	/**
	 * Returns parameter
	 * 		@param $param
	 * 		@param $val
	 */
	public static function Get($param = '', $val = '')
	{
		if(isset(self::$params[$param])){
			if(is_array(self::$params[$param])){
				return isset(self::$params[$param][$val]) ? self::$params[$param][$val] : '';
			}else{
				return isset(self::$params[$param]) ? self::$params[$param] : '';
			}
		}else{
			return '';
		}
	}

	/**
	 * Set parameter value
	 * 		@param $param
	 * 		@param $val
	 */
	public static function Set($param = '', $val = '')
	{
		if(isset(self::$params[$param])){
			if(is_array(self::$params[$param])){
				self::$params[$param][$val] = true;	
			}else{
				self::$params[$param] = $val;	
			}
		}
	}

	/**
	 * Draw Preview mode 
	 */
	public static function DrawPreview()
	{
		$preview = isset($_GET['preview']) ? prepare_input($_GET['preview']) : '';
		$preview_type = isset($_GET['preview_type']) ? prepare_input($_GET['preview_type']) : '';
		$page    = isset($_GET['page']) ? prepare_input($_GET['page']) : 'home';
		$page_id = isset($_GET['pid']) ? prepare_input($_GET['pid']) : 'home';
		$output = '';
		
		if($preview = 'yes' && $preview_type == 'single' && $page == 'pages' && $page_id != ''){
			$output .= '<div style="display:block; position:absolute; top:0%; left:0%; width:100%; height:1900px; background-color:black; z-index:1001; -moz-opacity:0.05; opacity:.05; filter:alpha(opacity=5);"></div>';
			$output .= '<div style="display:block; position: absolute; top: 75px; left: -225px; width: 600px; padding: 10px; font-size: 24px; text-align: center; color: rgb(255, 255, 255); font-family: \'trebuchet ms\',verdana,arial,sans-serif; -o-transform: rotate(-45deg); -moz-transform: rotate(-45deg); -webkit-transform: rotate(-45deg); transform: rotate(-45deg); background-color: rgb(0, 0, 0); border: 1px solid rgb(170, 170, 170); z-index: 12; opacity: 0.5;">PREVIEW</div>';
		}
		echo $output;
	}

}
?>