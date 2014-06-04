<?php
/**
* @project ApPHP Hotel Site
* @copyright (c) 2012 ApPHP
* @author ApPHP <info@apphp.com>
* @license http://www.gnu.org/licenses/
*/

// *** Make sure the file isn't accessed directly
defined('APPHP_EXEC') or die('Restricted Access');
//--------------------------------------------------------------------------

if($objLogin->IsLoggedInAs('owner','mainadmin') && Modules::IsModuleInstalled('rooms')){	

	// Start main content
	draw_title_bar(prepare_breadcrumbs(array(_HOTELS_MANAGEMENT=>'',_SETTINGS=>'',_INTEGRATION=>'')));
	draw_message(_WIDGET_INTEGRATION_MESSAGE.'<br>'._INTEGRATION_MESSAGE.'<br>'._WIDGET_INTEGRATION_MESSAGE_HINT);
	
	draw_content_start();
?>
	<table>
	<tr><td colspan="2"><b><?php echo _SIDE_PANEL; ?>:</b></td></tr>
	<tr>
		<td>
			<textarea cols="60" style="height:160px;margin-top:5px;" onclick="this.select()" readonly="readonly"><?php
				echo '<script type="text/javascript">'."\n";
				echo 'var hsJsHost = "'.APPHP_BASE.'";'."\n";
				echo 'var hsJsKey = "'.INSTALLATION_KEY.'";'."\n";
				echo 'var hsHotelIDs = "";'."\n"; 
				echo 'document.write(unescape(\'%3Cscript src="\' + hsJsHost + \'widgets/ipanel-left/main.js" type="text/javascript"%3E%3C/script%3E\'));'."\n";
				echo '</script>'."\n";
			?></textarea>
			<br>			
		</td>		
		<td>
			<img src="templates/admin/images/integration-side.png" alt="" />
		</td>		
	</tr>
	<tr><td colspan="2" nowrap height="10px"></td></tr>
	<tr><td colspan="2"><b><?php echo _TOP_PANEL; ?>:</b></td></tr>
	<tr>
		<td>
			<textarea cols="60" style="height:160px;margin-top:5px;" onclick="this.select()" readonly="readonly"><?php
				echo '<script type="text/javascript">'."\n";
				echo 'var hsJsHost = "'.APPHP_BASE.'";'."\n";
				echo 'var hsJsKey = "'.INSTALLATION_KEY.'";'."\n";
				echo 'var hsHotelIDs = "";'."\n"; 
				echo 'document.write(unescape(\'%3Cscript src="\' + hsJsHost + \'widgets/ipanel-top/main.js" type="text/javascript"%3E%3C/script%3E\'));'."\n";
				echo '</script>'."\n";
			?></textarea>
			<br>			
		</td>		
		<td>
			<img src="templates/admin/images/integration-top.png" alt="" />
		</td>		
	</tr>
	</table>
	<br><br>

<?php
	draw_content_end();

}else{
	draw_title_bar(_ADMIN);
	draw_important_message(_NOT_AUTHORIZED);
}

?>