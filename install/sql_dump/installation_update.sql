
CREATE TABLE IF NOT EXISTS `<DB_PREFIX>states` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `country_id` smallint(6) unsigned NOT NULL DEFAULT '0',
  `abbrv` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(70) COLLATE utf8_unicode_ci NOT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `priority_order` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `abbrv` (`abbrv`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

CREATE TABLE IF NOT EXISTS  `<DB_PREFIX>ratings_items` (
`item` VARCHAR( 200 ) NOT NULL DEFAULT  '',
`totalrate` INT( 10 ) NOT NULL DEFAULT  '0',
`nrrates` INT( 9 ) NOT NULL DEFAULT  '1',
PRIMARY KEY (  `item` )
) ENGINE = MYISAM DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS  `<DB_PREFIX>ratings_users` (
`day` INT( 2 ) DEFAULT NULL ,
`rater` VARCHAR( 15 ) DEFAULT NULL ,
`item` VARCHAR( 200 ) NOT NULL DEFAULT  ''
) ENGINE = MYISAM DEFAULT CHARSET = utf8;

ALTER TABLE  `<DB_PREFIX>news` ADD  `is_active` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '1';

ALTER TABLE `<DB_PREFIX>currencies` ADD  `decimals` TINYINT( 1 ) NOT NULL DEFAULT  '2' AFTER  `rate`;
ALTER TABLE `<DB_PREFIX>currencies` CHANGE  `symbol_placement`  `symbol_placement` ENUM(  'left',  'right',  'before',  'after' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT  'before';

ALTER TABLE `<DB_PREFIX>modules` ADD  `show_on_dashboard` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `is_system`;
UPDATE  `<DB_PREFIX>modules` SET  `show_on_dashboard` =  '1' WHERE  `name` = 'pages';
UPDATE  `<DB_PREFIX>modules` SET  `show_on_dashboard` =  '1' WHERE  `name` = 'rooms';
UPDATE  `<DB_PREFIX>modules` SET  `show_on_dashboard` =  '1' WHERE  `name` = 'booking';
INSERT INTO `<DB_PREFIX>modules` (`id`, `name`, `name_const`, `description_const`, `icon_file`, `module_tables`, `dependent_modules`, `settings_page`, `settings_const`, `settings_access_by`, `management_page`, `management_const`, `management_access_by`, `is_installed`, `is_system`, `priority_order`) VALUES (NULL, 'ratings', '_RATINGS', '_MD_RATINGS', 'ratings.png', 'ratings_items,ratings_users', '', 'mod_ratings_settings', '_RATINGS_SETTINGS', 'owner,mainadmin', '', '', '', '1', '0', '13');


INSERT INTO `<DB_PREFIX>modules_settings` (`id`, `module_name`, `settings_key`, `settings_value`, `settings_name`, `settings_description_const`, `key_display_type`, `key_is_required`, `key_display_source`) VALUES (NULL, 'rooms', 'show_default_prices', 'yes', 'Show Default Prices', '_MS_SHOW_DEFAULT_PRICES', 'yes/no', '1', '');
INSERT INTO `<DB_PREFIX>modules_settings` (`id`, `module_name`, `settings_key`, `settings_value`, `settings_name`, `settings_description_const`, `key_display_type`, `key_is_required`, `key_display_source`) VALUES (NULL, 'rooms', 'allow_default_periods', 'yes', 'Allow Default Periods', '_MS_ALLOW_DEFAULT_PERIODS', 'yes/no', '1', '');
INSERT INTO `<DB_PREFIX>modules_settings` (`id`, `module_name`, `settings_key`, `settings_value`, `settings_name`, `settings_description_const`, `key_display_type`, `key_is_required`, `key_display_source`) VALUES (NULL, 'gallery', 'show_items_numeration_in_album', 'yes', 'Show Items Numeration in Album', '_MS_ALBUM_ITEMS_NUMERATION', 'yes/no', 1, '');
INSERT INTO `<DB_PREFIX>modules_settings` (`id`, `module_name`, `settings_key`, `settings_value`, `settings_name`, `settings_description_const`, `key_display_type`, `key_is_required`, `key_display_source`) VALUES (NULL, 'rooms', 'watermark', 'yes', 'Add Watermark to Images', '_MS_ADD_WATERMARK', 'yes/no', '1', '');
INSERT INTO `<DB_PREFIX>modules_settings` (`id`, `module_name`, `settings_key`, `settings_value`, `settings_name`, `settings_description_const`, `key_display_type`, `key_is_required`, `key_display_source`) VALUES (NULL, 'rooms', 'watermark_text', '', 'Watermark Text', '_MS_WATERMARK_TEXT', 'string', '0', '');
INSERT INTO `<DB_PREFIX>modules_settings` (`id`, `module_name`, `settings_key`, `settings_value`, `settings_name`, `settings_description_const`, `key_display_type`, `key_is_required`, `key_display_source`) VALUES (NULL, 'rooms', 'max_adults', '8', 'Maximum adults in search box', '_MS_MAX_ADULTS_IN_SEARCH', 'enum', '1', '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15');
INSERT INTO `<DB_PREFIX>modules_settings` (`id`, `module_name`, `settings_key`, `settings_value`, `settings_name`, `settings_description_const`, `key_display_type`, `key_is_required`, `key_display_source`) VALUES (NULL, 'rooms', 'max_children', '3', 'Maximum children in search box', '_MS_MAX_CHILDREN_IN_SEARCH', 'enum', '1', '1,2,3,4,5');
INSERT INTO `<DB_PREFIX>modules_settings` (`id`, `module_name`, `settings_key`, `settings_value`, `settings_name`, `settings_description_const`, `key_display_type`, `key_is_required`, `key_display_source`) VALUES (NULL, 'rooms', 'check_partially_overlapping', 'yes', 'Check Partially Overlapping', '_MS_CHECK_PARTIALLY_OVERLAPPING', 'yes/no', '1', '');
INSERT INTO `<DB_PREFIX>modules_settings` (`id`, `module_name`, `settings_key`, `settings_value`, `settings_name`, `settings_description_const`, `key_display_type`, `key_is_required`, `key_display_source`) VALUES (NULL, 'ratings', 'user_type', 'all', 'User Type', '_MS_RATINGS_USER_TYPE', 'enum', '1', 'all,registered');
INSERT INTO `<DB_PREFIX>modules_settings` (`id`, `module_name`, `settings_key`, `settings_value`, `settings_name`, `settings_description_const`, `key_display_type`, `key_is_required`, `key_display_source`) VALUES (NULL, 'ratings', 'multiple_items_per_day', 'yes', 'Multiple Items per Day', '_MS_MULTIPLE_ITEMS_PER_DAY', 'yes/no', '1', '');

UPDATE `<DB_PREFIX>modules_settings` SET  `key_display_type` =  'enum', `settings_value`='Frontend & Backend', `key_display_source` =  'yes,No,Frontend Only,Backend Only,Frontend & Backend' WHERE `settings_key` = 'payment_type_poa';
UPDATE `<DB_PREFIX>modules_settings` SET  `key_display_type` =  'enum', `settings_value`='Frontend & Backend', `key_display_source` =  'yes,No,Frontend Only,Backend Only,Frontend & Backend' WHERE `settings_key` = 'payment_type_paypal';
UPDATE `<DB_PREFIX>modules_settings` SET  `key_display_type` =  'enum', `settings_value`='Frontend & Backend', `key_display_source` =  'yes,No,Frontend Only,Backend Only,Frontend & Backend' WHERE `settings_key` = 'payment_type_2co';
UPDATE `<DB_PREFIX>modules_settings` SET  `key_display_type` =  'enum', `settings_value`='Frontend & Backend', `key_display_source` =  'yes,No,Frontend Only,Backend Only,Frontend & Backend' WHERE `settings_key` = 'payment_type_bank_transfer';
UPDATE `<DB_PREFIX>modules_settings` SET  `key_display_type` =  'enum', `settings_value`='Frontend & Backend', `key_display_source` =  'yes,No,Frontend Only,Backend Only,Frontend & Backend' WHERE `settings_key` = 'payment_type_online';
UPDATE `<DB_PREFIX>modules_settings` SET `key_display_source` = '1,2,3,4,5,6,7,8,9,10,15,20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,99,100,125,150,175,200,250,500,750,1000' WHERE `settings_key` = 'pre_payment_value';
UPDATE `<DB_PREFIX>modules_settings` SET  `settings_key` = 'prebooking_orders_timeout', `settings_name` =  '''Prebooking'' Orders Timeout', `settings_description_const` =  '_MS_PREBOOKING_ORDERS_TIMEOUT' WHERE  `settings_key` =  'preparing_orders_timeout';
UPDATE `<DB_PREFIX>modules_settings` SET  `settings_name` = 'Allow Extra Beds in Rooms', `settings_description_const` =  '_MS_ALLOW_EXTRA_BEDS' WHERE  `settings_key` = 'allow_guests';
UPDATE `<DB_PREFIX>modules_settings` SET  `settings_key` = 'allow_extra_beds' WHERE `settings_key` = 'allow_guests';

INSERT INTO `<DB_PREFIX>email_templates` (`id`, `language_id`, `template_code`, `template_name`, `template_subject`, `template_content`, `is_system_template`) VALUES		
(NULL, 'de', 'order_refunded', 'Die Reservierung wurde erstattet Administrator', 'Ihre Zahlung wurde erstattet.', 'Liebe <b>{FIRST NAME} {LAST NAME}</b>!\r\n\r\nIhre Zahlung {BOOKING NUMBER} wurde erstattet!\r\n\r\n{BOOKING DETAILS}\r\n\r\n-\r\nMit freundlichen Grüßen,\r\nCustomer Support', 1),
(NULL, 'en', 'order_refunded', 'Reservation has been refunded by administrator', 'Your payment has been refunded.', 'Dear <b>{FIRST NAME} {LAST NAME}</b>!\r\n\r\nYour payment {BOOKING NUMBER} has been refunded!\r\n\r\n{BOOKING DETAILS}\r\n\r\n-\r\nSincerely,\r\nCustomer Support\r\n', 1),
(NULL, 'es', 'order_refunded', 'Reserva ha sido reembolsado por el administrador', 'Su pago ha sido reembolsado.', 'Querido <b>{FIRST NAME} {LAST NAME}</b>!\r\n\r\nSu pago {BOOKING NUMBER} ha sido devuelto!\r\n\r\n{BOOKING DETAILS}\r\n\r\n-\r\nAtentamente,\r\nAtención al cliente', 1);

INSERT INTO `<DB_PREFIX>email_templates` (`id`, `language_id`, `template_code`, `template_name`, `template_subject`, `template_content`, `is_system_template`) VALUES		
(NULL, 'de', 'order_status_changed', 'Reservation Status wurde geändert', 'Ihre Reservierung Status wurde geändert.', 'Liebe <b>{FIRST NAME} {LAST NAME}</b>!\r\n\r\nIhre Reservierung {BOOKING NUMBER} Status hat {STATUS DESCRIPTION} wurde geändert!\r\n\r\n-\r\nMit freundlichen Grüßen,\r\nCustomer Support', 1),
(NULL, 'en', 'order_status_changed', 'Reservation status has been changed', 'Your reservation status has been changed.', 'Dear <b>{FIRST NAME} {LAST NAME}</b>!\r\n\r\nYour reservation {BOOKING NUMBER} status has been changed to {STATUS DESCRIPTION}!\r\n\r\n-\r\nSincerely,\r\nCustomer Support\r\n', 1),
(NULL, 'es', 'order_status_changed', 'Estado de la reserva se ha cambiado', 'Su estado de la reserva se ha cambiado.', 'Querido <b>{FIRST NAME} {LAST NAME}</b>!\r\n\r\nReservar {BOOKING NUMBER} Estado se ha cambiado a {STATUS DESCRIPTION}!\r\n\r\n-\r\nAtentamente,\r\nAtención al cliente', 1);

ALTER TABLE `<DB_PREFIX>hotels` CHANGE  `stars`  `stars` VARCHAR( 1 ) NOT NULL DEFAULT  '0';
ALTER TABLE `<DB_PREFIX>hotels` CHANGE  `priority_order`  `priority_order` SMALLINT( 6 ) UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE `<DB_PREFIX>hotels` ADD  `agent_commision` DECIMAL( 4, 1 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `hotel_image_thumb`;

ALTER TABLE `<DB_PREFIX>bookings` CHANGE  `status`  `status` TINYINT( 1 ) NOT NULL DEFAULT  '0' COMMENT  '0 - prebooking, 1 - pending, 2 - reserved, 3 - completed, 4 - refunded, 5 - payment error, 6 - canceled' ;
UPDATE `<DB_PREFIX>bookings` SET `status` = IF(status > 0, status + 1, status);
ALTER TABLE `<DB_PREFIX>bookings` CHANGE  `cc_cvv_code`  `cc_cvv_code` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT  '';

ALTER TABLE `<DB_PREFIX>bookings_rooms` CHANGE  `guests_fee`  `extra_beds_charge` DECIMAL( 10, 2 ) UNSIGNED NOT NULL DEFAULT  '0.00';
ALTER TABLE `<DB_PREFIX>bookings_rooms` CHANGE  `guests`  `extra_beds` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0' ;

INSERT INTO `<DB_PREFIX>privileges` (`id`, `code`, `name`, `description`) VALUES (NULL, 'edit_bookings', 'Edit Bookings', 'Edit bookings on the site'), (NULL, 'cancel_bookings', 'Cancel Bookings', 'Cancel bookings on the site'), (NULL, 'delete_bookings', 'Delete Bookings', 'Delete bookings from the site');
INSERT INTO `<DB_PREFIX>role_privileges` (`id`, `role_id`, `privilege_id`, `is_active`) VALUES (NULL, '1', '10', '1'), (NULL, '1', '11', '1'), (NULL, '1', '12', '1');
INSERT INTO `<DB_PREFIX>role_privileges` (`id`, `role_id`, `privilege_id`, `is_active`) VALUES (NULL, '2', '10', '1'), (NULL, '2', '11', '1'), (NULL, '2', '12', '1');
INSERT INTO `<DB_PREFIX>role_privileges` (`id`, `role_id`, `privilege_id`, `is_active`) VALUES (NULL, '3', '10', '1'), (NULL, '3', '11', '0'), (NULL, '3', '12', '0');
INSERT INTO `<DB_PREFIX>role_privileges` (`id`, `role_id`, `privilege_id`, `is_active`) VALUES (NULL, '4', '10', '1'), (NULL, '4', '11', '1'), (NULL, '4', '12', '1');


DROP TABLE IF EXISTS `<DB_PREFIX>hotel_periods`;
CREATE TABLE IF NOT EXISTS `<DB_PREFIX>hotel_periods` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `hotel_id` int(11) NOT NULL DEFAULT '0',
  `period_description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `start_date` date NOT NULL DEFAULT '0000-00-00',
  `finish_date` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`id`),
  KEY `hotel_id` (`hotel_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;


DROP TABLE IF EXISTS `<DB_PREFIX>hotel_images`;
CREATE TABLE IF NOT EXISTS `<DB_PREFIX>hotel_images` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `hotel_id` int(11) NOT NULL DEFAULT '0',
  `item_file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `item_file_thumb` varchar(70) COLLATE utf8_unicode_ci NOT NULL,
  `image_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `priority_order` smallint(6) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `hotel_id` (`hotel_id`),
  KEY `priority_order` (`priority_order`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

INSERT INTO `<DB_PREFIX>hotel_images` (`id`, `hotel_id`, `item_file`, `item_file_thumb`, `image_title`, `priority_order`, `is_active`) VALUES
(1, 1, 's9cehwe5z9rk9wsfya6c.jpg', 's9cehwe5z9rk9wsfya6c_thumb.jpg', '', 4, 1),
(2, 1, 'mxzbx64q3vq12q2t7sbp.jpg', 'mxzbx64q3vq12q2t7sbp_thumb.jpg', '', 3, 1),
(3, 1, 'jg8e1p4dqsu3r6aca1yp.jpg', 'jg8e1p4dqsu3r6aca1yp_thumb.jpg', '', 2, 1),
(4, 1, 'btqjbk705mq89nydo2zb.jpg', 'btqjbk705mq89nydo2zb_thumb.jpg', '', 1, 1),
(5, 1, 'yu0rcyapecboxi0o6td1.jpg', 'yu0rcyapecboxi0o6td1_thumb.jpg', '', 0, 1);


ALTER TABLE  `<DB_PREFIX>rooms` CHANGE  `additional_guest_fee`  `extra_guest_fee` DECIMAL( 10, 2 ) UNSIGNED NOT NULL;
ALTER TABLE  `<DB_PREFIX>rooms` CHANGE  `max_guests`  `max_extra_beds` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE  `<DB_PREFIX>rooms` CHANGE  `extra_guest_fee`  `extra_bed_charge` DECIMAL( 10, 2 ) UNSIGNED NOT NULL;

ALTER TABLE  `<DB_PREFIX>rooms_prices` CHANGE  `guest_fee`  `extra_bed_charge` DECIMAL( 10, 2 ) UNSIGNED NOT NULL DEFAULT  '0.00';


INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_CLICK_TO_COPY', 'Click to copy'), (NULL, 'es', '_CLICK_TO_COPY', 'Haga clic para copiar'), (NULL, 'de', '_CLICK_TO_COPY', 'Klicken Sie zum Kopieren');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MS_SHOW_DEFAULT_PRICES', 'Specifies whether to show default prices  on the Front-End or not'), (NULL, 'es', '_MS_SHOW_DEFAULT_PRICES', 'Especifica si se deben mostrar los precios predeterminados en el Front-End o no'), (NULL, 'de', '_MS_SHOW_DEFAULT_PRICES', 'Gibt an, ob standardmäßig die Preise auf dem Front-End-oder nicht');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_DECIMALS', 'Decimals'), (NULL, 'es', '_DECIMALS', 'Decimales'), (NULL, 'de', '_DECIMALS', 'Dezimalstellen');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MS_ALLOW_DEFAULT_PERIODS', 'Specifies whether to allow adding and management of default periods for rooms '), (NULL, 'es', '_MS_ALLOW_DEFAULT_PERIODS', 'Especifica si se permite la adición y la gestión de los períodos por defecto para los cuartos'), (NULL, 'de', '_MS_ALLOW_DEFAULT_PERIODS', 'Gibt an, ob das Hinzufügen und das Management von Standard-Zeiträume für Räume');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_DEFINE', 'Define'), (NULL, 'es', '_DEFINE', 'Definir'), (NULL, 'de', '_DEFINE', 'Definieren');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_PERIODS', 'Periods'), (NULL, 'es', '_PERIODS', 'Períodos'), (NULL, 'de', '_PERIODS', 'Spielzeiten');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_HOTEL_OWNERS', 'Hotel Owners'), (NULL, 'es', '_HOTEL_OWNERS', 'Los propietarios del hotel'), (NULL, 'de', '_HOTEL_OWNERS', 'Hotelinhaber');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_ADMINS_OWNERS_MANAGEMENT', 'Admins & Hotel Owners Management'), (NULL, 'es', '_ADMINS_OWNERS_MANAGEMENT', 'Administradores & Hotel Propietario Gestión'), (NULL, 'de', '_ADMINS_OWNERS_MANAGEMENT', 'Admins & Hotel Inhaber Geschäftsführung');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_ADD_DEFAULT_PERIODS', 'Add Default Periods'), (NULL, 'es', '_ADD_DEFAULT_PERIODS', 'Añadir períodos predeterminados'), (NULL, 'de', '_ADD_DEFAULT_PERIODS', 'In Standard-Perioden');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_WIDGET_INTEGRATION_MESSAGE', 'You may integrate Hotel Site engine with another existing web site.'), (NULL, 'es', '_WIDGET_INTEGRATION_MESSAGE', 'Puede integrar Engine Hotel Sitio con otro sitio web existente.'), (NULL, 'de', '_WIDGET_INTEGRATION_MESSAGE', 'Sie können Hotel-Site-Motor mit einer anderen bestehenden Website integrieren.');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_WIDGET_INTEGRATION_MESSAGE_HINT', '<b>Hint</b>: To list all available hotels, leave hsJsKey value empty or enter hotel IDs separated by commas.'), (NULL, 'es', '_WIDGET_INTEGRATION_MESSAGE_HINT', '<b>Hint</b>: Para una lista de todos los disponibles, deje el valor hsJsKey vacío o introducir ID de hoteles, separados por comas.'), (NULL, 'de', '_WIDGET_INTEGRATION_MESSAGE_HINT', '<b>Hinweis</b>: Um alle verfügbaren Hotels auflisten, lassen hsJsKey Wert leer oder geben hotel IDs durch Komma getrennt.');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_HOTEL_DELETE_ALERT', 'Are you sure you want to delete this hotel? Remember: after completing this action all related data to this hotel could not be restored!'), (NULL, 'es', '_HOTEL_DELETE_ALERT', '¿Está seguro que desea eliminar este hotel? Recuerde: después de completar esta acción no se podrían restaurar todos los datos relacionados con este hotel!'), (NULL, 'de', '_HOTEL_DELETE_ALERT', 'Sind Sie sicher, dass Sie dieses Hotel wirklich löschen? Denken Sie daran: nach Abschluss dieser Aktion alle Daten in diesem Hotel konnte nicht wiederhergestellt werden!');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_VIEW_ALL', 'View All'), (NULL, 'es', '_VIEW_ALL', 'Ver todos'), (NULL, 'de', '_VIEW_ALL', 'Alle anzeigen');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_DEFAULT_PERIODS_WERE_ADDED', 'Default periods have been successfully added!'), (NULL, 'es', '_DEFAULT_PERIODS_WERE_ADDED', 'Períodos predeterminados se han añadido correctamente!'), (NULL, 'de', '_DEFAULT_PERIODS_WERE_ADDED', 'Standard Perioden wurden erfolgreich hinzugefügt!');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_DISABLED', 'disabled'), (NULL, 'es', '_DISABLED', 'discapacitado'), (NULL, 'de', '_DISABLED', 'behindert');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MENUS_DISABLED_ALERT', 'Take in account that some menus may be disabled for this template.'), (NULL, 'es', '_MENUS_DISABLED_ALERT', 'Tome en cuenta que algunos menús pueden ser deshabilitados para esta plantilla.'), (NULL, 'de', '_MENUS_DISABLED_ALERT', 'Nehmen Sie in Rechnung, dass einige Menüs für diese Vorlage kann deaktiviert werden.');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_COPY_TO_OTHERS', 'Copy to others'), (NULL, 'es', '_COPY_TO_OTHERS', 'Copy to others'), (NULL, 'de', '_COPY_TO_OTHERS', 'Copy to others');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_NAME_A_Z', 'name (a-z)'), (NULL, 'es', '_NAME_A_Z', 'nombre del (a-z)'), (NULL, 'de', '_NAME_A_Z', 'name (a-z)');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_NAME_Z_A', 'name (z-a)'), (NULL, 'es', '_NAME_Z_A', 'nombre del (z-a)'), (NULL, 'de', '_NAME_Z_A', 'name (z-a)');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_PRICE_L_H', 'price (from lowest)'), (NULL, 'es', '_PRICE_L_H', 'precio (más bajo)'), (NULL, 'de', '_PRICE_L_H', 'Preis (von unten)');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_PRICE_H_L', 'price (from highest)'), (NULL, 'es', '_PRICE_H_L', 'precio (de alto)'), (NULL, 'de', '_PRICE_H_L', 'Preis (höchste)');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_SET_PERIODS', 'Set Periods'), (NULL, 'es', '_SET_PERIODS', 'Establecer períodos'), (NULL, 'de', '_SET_PERIODS', 'gesetzt Perioden');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_DEFAULT_PERIODS_ALERT', 'Default Periods are used to specify periods of time that could by easily fulfilled with default prices for each room on the Room Prices page (with just a single click).'), (NULL, 'es', '_DEFAULT_PERIODS_ALERT', 'Períodos predeterminados se utilizan para especificar los periodos de tiempo que podría fácilmente cumplidas por los precios predeterminados para cada habitación en la página de precios de habitaciones (con un solo clic).'), (NULL, 'de', '_DEFAULT_PERIODS_ALERT', 'Standard Perioden verwendet werden, um Zeit, die durch leicht mit Standard-Preise für jedes Zimmer auf der Zimmerpreise Seite (mit nur einem Klick) konnte erfüllt angeben.');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MS_ALBUM_ITEMS_NUMERATION', 'Specifies whether to show items numeration in albums'), (NULL, 'es', '_MS_ALBUM_ITEMS_NUMERATION', 'Especifica si se deben mostrar los elementos de numeración en álbumes'), (NULL, 'de', '_MS_ALBUM_ITEMS_NUMERATION', 'Gibt an, ob Elemente Nummerierung in Alben zeigen');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MS_ADD_WATERMARK', 'Specifies whether to add watermark to rooms images or not'), (NULL, 'es', '_MS_ADD_WATERMARK', 'Especifica si se debe añadir marcas de agua a las imágenes o no habitaciones'), (NULL, 'de', '_MS_ADD_WATERMARK', 'Gibt an, ob Wasserzeichen zur Zimmer Bilder oder nicht hinzufügen');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MS_WATERMARK_TEXT', 'Watermark text that will be added to images'), (NULL, 'es', '_MS_WATERMARK_TEXT', 'Texto de la marca que se añadirá a las imágenes'), (NULL, 'de', '_MS_WATERMARK_TEXT', 'Wasserzeichen Text, der die Bilder hinzugefügt werden');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_OWNER_NOT_ASSIGNED', 'You still has not been assigned to any hotel to see the reports.'), (NULL, 'es', '_OWNER_NOT_ASSIGNED', 'Todavía no se ha asignado a ningún hotel para ver los informes.'), (NULL, 'de', '_OWNER_NOT_ASSIGNED', 'Sie hat immer noch nicht zu jedem Hotel zugewiesen wurde, um die Berichte zu sehen.');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_BEFORE', 'Before'), (NULL, 'es', '_BEFORE', 'Antes'), (NULL, 'de', '_BEFORE', 'Vorher');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_AFTER', 'After'), (NULL, 'es', '_AFTER', 'Después'), (NULL, 'de', '_AFTER', 'Nach');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MS_MAX_ADULTS_IN_SEARCH', 'Specifies the maximum number of adults in the dropdown list of the Search Availability form'), (NULL, 'es', '_MS_MAX_ADULTS_IN_SEARCH', 'Especifica el número máximo de adultos en la lista desplegable del formulario disponibilidad Buscar'), (NULL, 'de', '_MS_MAX_ADULTS_IN_SEARCH', 'Gibt die maximale Anzahl der Erwachsenen in der Dropdown-Liste der Suche Verfügbarkeit Form');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MS_MAX_CHILDREN_IN_SEARCH', 'Specifies the maximum number of children in the dropdown list of the Search Availability form'), (NULL, 'es', '_MS_MAX_CHILDREN_IN_SEARCH', 'Especifica el número máximo de niños en la lista desplegable del formulario disponibilidad Buscar'), (NULL, 'de', '_MS_MAX_CHILDREN_IN_SEARCH', 'Gibt die maximale Anzahl von Kindern in der Dropdown-Liste der Suche Verfügbarkeit Form');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_PERCENTAGE_MAX_ALOWED_VALUE', 'The maximum allowed value for percentage is 99%! Please re-enter.'), (NULL, 'es', '_PERCENTAGE_MAX_ALOWED_VALUE', 'El valor máximo permitido para el porcentaje es del 99%! Vuelva a inscribir.'), (NULL, 'de', '_PERCENTAGE_MAX_ALOWED_VALUE', 'Die maximal zulässige Wert für Anteil beträgt 99%! Bitte geben Sie erneut.');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_ADMIN_FOLDER_CREATION_ERROR', 'Failed to create folder for this author in <b>images/upload/</b> directory. For stable work of the script please create this folder manually.'), (NULL, 'es', '_ADMIN_FOLDER_CREATION_ERROR', 'No se pudo crear la carpeta para este autor en <b>images/upload/</b> directorio. Para el trabajo estable de la secuencia de comandos por favor crear esta carpeta de forma manual.'), (NULL, 'de', '_ADMIN_FOLDER_CREATION_ERROR', 'Fehler beim Ordner zu diesem Autor in <b>images/upload/</b> Verzeichnis. Für eine stabile Arbeit des Skripts erstellen Sie bitte diesen Ordner manuell.');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_NO_DEFAULT_PERIODS', 'Default periods not yet defined for this hotel. Click <a href=_HREF_>here</a> to add them.'), (NULL, 'es', '_NO_DEFAULT_PERIODS', 'Períodos por defecto no definido para este hotel. Haga clic en <a href=_HREF_>aquí</a> para agregarlos.'), (NULL, 'de', '_NO_DEFAULT_PERIODS', 'Standard Zeiträume noch nicht für dieses Hotel definiert. Klicken Sie <a href=_HREF_>hier</a>, um sie hinzuzufügen.');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_SHOW_ON_DASHBOARD', 'Show on Dashboard'), (NULL, 'es', '_SHOW_ON_DASHBOARD', 'Mostrar en el Dashboard'), (NULL, 'de', '_SHOW_ON_DASHBOARD', 'Anzeigen auf Armaturenbrett');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_START_OVER', 'Start Over'), (NULL, 'es', '_START_OVER', 'Comenzar de Nuevo'), (NULL, 'de', '_START_OVER', 'Beginnen');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_LEGEND_PENDING', 'The booking has been created has not yet been confirmed and reserved'), (NULL, 'es', '_LEGEND_PENDING', 'La reserva ha sido creada todavía no ha sido confirmado y reservado'), (NULL, 'de', '_LEGEND_PENDING', 'Die Buchung erstellt wurde noch nicht bestätigt und reserviert');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_EXTRA_BEDS', 'Extra Beds'), (NULL, 'es', '_EXTRA_BEDS', 'Las camas supletorias'), (NULL, 'de', '_EXTRA_BEDS', 'Zustellbetten');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_EXTRA_BED', 'Extra Bed'), (NULL, 'es', '_EXTRA_BED', 'Cama adicional'), (NULL, 'de', '_EXTRA_BED', 'Zustellbett');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MS_CHECK_PARTIALLY_OVERLAPPING', 'Specifies whether to allow check for partially overlapping dates for packages'), (NULL, 'es', '_MS_CHECK_PARTIALLY_OVERLAPPING', 'Especifica si se debe permitir comprobar si las fechas se superponen parcialmente para los paquetes'), (NULL, 'de', '_MS_CHECK_PARTIALLY_OVERLAPPING', 'Gibt an, ob für sich teilweise überlappenden Daten für Pakete überprüfen');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_RATINGS_SETTINGS', 'Ratings Settings'), (NULL, 'es', '_RATINGS_SETTINGS', 'Valoraciones Configuración'), (NULL, 'de', '_RATINGS_SETTINGS', 'Bewertungen Einstellungen');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_RATINGS', 'Ratings'), (NULL, 'es', '_RATINGS', 'Valoraciones'), (NULL, 'de', '_RATINGS', 'Bewertungen');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_STATES', 'States'), (NULL, 'es', '_STATES', 'Unidos'), (NULL, 'de', '_STATES', 'Staaten');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MAX_ADULTS_ACCOMMODATE', 'Maximum number of adults this room can accommodate'), (NULL, 'es', '_MAX_ADULTS_ACCOMMODATE', 'Número máximo de adultos Esta habitación tiene capacidad'), (NULL, 'de', '_MAX_ADULTS_ACCOMMODATE', 'Maximalanzahl Erwachsene Dieses Zimmer bietet Platz');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MAX_CHILDREN_ACCOMMODATE', 'Maximum number of children this room can accommodate'), (NULL, 'es', '_MAX_CHILDREN_ACCOMMODATE', 'El número máximo de niños de esta habitación se pueden alojar'), (NULL, 'de', '_MAX_CHILDREN_ACCOMMODATE', 'Maximale Anzahl der Kinder in diesem Zimmer bietet Platz für');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_AGENT_COMMISION', 'Hotel Owner/Agent Commision'), (NULL, 'es', '_AGENT_COMMISION', 'Hotel Propietario/Agente Commision'), (NULL, 'de', '_AGENT_COMMISION', 'Hotel Management/Agent Provision');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MD_RATINGS', 'The Ratings module allows your users to rate the hotels. The number of votes and average rating will be shown at the appropriate hotel.'), (NULL, 'es', '_MD_RATINGS', 'El módulo Valoraciones permite a sus usuarios valorar los hoteles. El número de votos y la calificación media se mostrará en el hotel adecuado.'), (NULL, 'de', '_MD_RATINGS', 'Die Ratings Modul ermöglicht Benutzern, um die Hotels zu bewerten. Die Zahl der Stimmen und der durchschnittlichen Bewertung werden auf der geeigneten Hotel vorgelegt werden.');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MS_RATINGS_USER_TYPE', 'Type of users, who can rate hotels'), (NULL, 'es', '_MS_RATINGS_USER_TYPE', 'Tipo de usuarios, que pueden votar hoteles'), (NULL, 'de', '_MS_RATINGS_USER_TYPE', 'Typ von Usern, die Hotels bewertet werden können');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_MS_MULTIPLE_ITEMS_PER_DAY', 'Specifies whether to allow users to rate multiple items per day or not'), (NULL, 'es', '_MS_MULTIPLE_ITEMS_PER_DAY', 'Especifica si se permite a los usuarios artículos tasas múltiples por día o no'), (NULL, 'de', '_MS_MULTIPLE_ITEMS_PER_DAY', 'Gibt an, ob Benutzer sich mehrere Artikel pro Tag oder nicht zulassen');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_VISITORS_RATING', 'Visitors Rating'), (NULL, 'es', '_VISITORS_RATING', 'Ustedes evaluación'), (NULL, 'de', '_VISITORS_RATING', 'Besucher Bewertung');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_PAYMENT_METHODS', 'Payment Methods'), (NULL, 'es', '_PAYMENT_METHODS', 'Formas de pago'), (NULL, 'de', '_PAYMENT_METHODS', 'Zahlungsmethoden');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_FOLLOW_US', 'Follow Us'), (NULL, 'es', '_FOLLOW_US', 'Siga con nosotros'), (NULL, 'de', '_FOLLOW_US', 'Folgen Sie uns');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_FOR_BOOKING', 'for booking #'), (NULL, 'es', '_FOR_BOOKING', 'de reserva #'), (NULL, 'de', '_FOR_BOOKING', 'Buchung für #');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_HELP', 'Help'), (NULL, 'es', '_HELP', 'Ayudar'), (NULL, 'de', '_HELP', 'Hilfe');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_VOTE_NOT_REGISTERED', 'Your vote has not been registered! You must be logged in before you can vote.'), (NULL, 'es', '_VOTE_NOT_REGISTERED', 'Su voto no se ha registrado? Debe estar registrado para poder votar.'), (NULL, 'de', '_VOTE_NOT_REGISTERED', 'Ihre Stimme wurde nicht registriert! Sie müssen, bevor Sie abstimmen können protokolliert werden.');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_SIDE_PANEL', 'Side Panel'), (NULL, 'es', '_SIDE_PANEL', 'El panel lateral'), (NULL, 'de', '_SIDE_PANEL', 'Seitenteil');
INSERT INTO `<DB_PREFIX>vocabulary` (`id`, `language_id`, `key_value`, `key_text`) VALUES (NULL, 'en', '_TOP_PANEL', 'Top Panel'), (NULL, 'es', '_TOP_PANEL', 'Panel superior'), (NULL, 'de', '_TOP_PANEL', 'Oberseite');

UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` = '_FIELD_ Hat die maximal zulässige Wert überschritten _MAX_! Bitte geben Sie erneut oder ändern Zimmer gesamt Anzahl <a href=''index.php?admin=mod_rooms_management''>hier</a>.' WHERE `language_id` = 'de' AND key_value	= '_FIELD_VALUE_EXCEEDED';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` = '_FIELD_ has exceeded the maximum allowed value _MAX_! Please re-enter or change total rooms number <a href=''index.php?admin=mod_rooms_management''>here</a>.' WHERE `language_id` = 'en' AND key_value	= '_FIELD_VALUE_EXCEEDED';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` = '_FIELD_ ha superado el valor máximo permitido _MAX_! Por favor vuelva a introducir o modificar el número total de habitaciones <a href=''index.php?admin=mod_rooms_management''>aquí</a>.' WHERE `language_id` = 'es' AND key_value	= '_FIELD_VALUE_EXCEEDED';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` = 'Extra-Gast Gebühr', key_value = '_EXTRA_GUEST_FEE' WHERE `language_id` = 'de' AND key_value = '_ADDITIONAL_GUEST_FEE';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` = 'Extra Guest Fee', key_value = '_EXTRA_GUEST_FEE' WHERE `language_id` = 'en' AND key_value = '_ADDITIONAL_GUEST_FEE';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` = 'Precio por persona adicional', key_value = '_EXTRA_GUEST_FEE' WHERE `language_id` = 'es' AND key_value = '_ADDITIONAL_GUEST_FEE';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_value` = '_PREBOOKING' WHERE `key_value` = '_PREPARING';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_value` = '_MS_PREPARING_ORDERS_TIMEOUT' WHERE `key_value` = '_MS_PREBOOKING_ORDERS_TIMEOUT';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_value` = '_LEGEND_PREBOOKING' WHERE `key_value` = '_LEGEND_PREPARING';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_value` = '_EXTRA_GUEST_FEE' WHERE `key_value` = '_EXTRA_BED_CHARGE';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_value` = '_MAX_GUESTS' WHERE `key_value` = '_MAX_EXTRA_BEDS';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_value` = '_MS_ALLOW_GUESTS_IN_ROOM' WHERE `key_value` = '_MS_ALLOW_EXTRA_BEDS';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` =  'Gibt an, ob der Buchung in der Vergangenheit für Administratoren und Besitzer des Hotels (ab Beginn des laufenden Monats)' WHERE `language_id` = 'de' AND `key_value` = '_MS_ADMIN_BOOKING_IN_PAST';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` =  'Specifies whether to allow booking in the past for admins and hotel owners (from the beginning of current month)' WHERE `language_id` = 'en' AND `key_value` = '_MS_ADMIN_BOOKING_IN_PAST';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` =  'Especifica si se permite la reserva en el pasado para los administradores y propietarios de hoteles (desde el principio del mes actual)' WHERE `language_id` = 'es' AND `key_value` = '_MS_ADMIN_BOOKING_IN_PAST';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` =  'Fertiggestellt (gegen Entgelt)' WHERE `language_id` = 'de' AND `key_value` = '_COMPLETED';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` =  'Completed (Paid)' WHERE `language_id` = 'en' AND `key_value` = '_COMPLETED';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_text` =  'Terminado (pagado)' WHERE `language_id` = 'es' AND `key_value` = '_COMPLETED';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_value` =  '_SCHEDULED_CAMPAIGN' WHERE `key_value` = '_STANDARD_CAMPAIGN';
UPDATE  `<DB_PREFIX>vocabulary` SET  `key_value` =  '_REAL_TIME_CAMPAIGN' WHERE `key_value` = '_GLOBAL_CAMPAIGN';


