{*
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{if $ipdebug}
	<button type="button" 
		class="add-ip btn btn-primary" 
		data-my-ip="{$my_ip_address|escape:'htmlall':'UTF-8'}"
		style="display:none;margin-top: 10px" 
		>
		{l s='Add my IP' mod='ipdebug'}
	</button>
	<script type="text/javascript">
		$(document).ready(function(){
			if($('#module_allow_debug_ip_form input[name="IPD_ENABLE_IP_LIST"]:checked').val() == 1){
				$('#module_allow_debug_ip_form > #fieldset_0  > .form-wrapper .form-group:nth-child(2)').show();
			} else {
				$('#module_allow_debug_ip_form > #fieldset_0  > .form-wrapper .form-group:nth-child(2)').hide();
			}

			$('.add-ip').insertAfter('#module_allow_debug_ip_form input[name="IPD_IP"]').show();
			
			$(document).on('change','#module_allow_debug_ip_form input[name="IPD_ENABLE_IP_LIST"]',function(e){
				var value = $(this).val();
				if(value == 1){
					$('#module_allow_debug_ip_form > #fieldset_0  > .form-wrapper .form-group:nth-child(2)').show();
				} else {
					$('#module_allow_debug_ip_form > #fieldset_0  > .form-wrapper .form-group:nth-child(2)').hide();
				}
			});

			$(document).on('click','.add-ip',function(e){
				var data = $('#IPD_IP').val();
				var my_ip_address = $(this).data('my-ip');
				if(data != ''){
					data = data.split(",");
					if(jQuery.inArray( my_ip_address, data ) == -1){
						data.push(my_ip_address);
					}
					var ips = data.join();
					$('#IPD_IP').val(data);
				} else {
					$('#IPD_IP').val(my_ip_address);
				}
			});
		});
	</script>
{else}
	{if $ps_version >= '1.7'}
		<a 
		class="btn btn-outline-secondary"
		id="adv_debug"
		href="{$url_debug_setting|escape:'htmlall':'UTF-8'}"
		title="{l s='Setting Debug mode based on IP address' mod='ipdebug'}"
		target="_blank"
		style="display:none"
		>
			{l s='Advaced Debug Mode' mod='ipdebug'}
		</a>

		<script type="text/javascript">
			$(document).ready(function(){
				if($('body').hasClass('adminperformance')){
				 	$('#adv_debug').insertBefore("#page-header-desc-configuration-clear_cache").show();
				}
			});
		</script>
	{else}
		<li class="adv_debug" style="display:none">
			<a 
				class="toolbar_btn  pointer"
				id="adv_debug"
				href="{$url_debug_setting|escape:'htmlall':'UTF-8'}"
				title="{l s='Setting Debug mode based on IP address' mod='ipdebug'}"
				target="_blank"
			>
				<i class="process-icon-configure"></i>
				<div>{l s='Advaced Debug Mode' mod='ipdebug'}</div>
			</a>
		<li>

		<script type="text/javascript">
			$(document).ready(function(){
				if($('body').hasClass('adminperformance')){
				 	$('.adv_debug').insertBefore(".adminperformance #toolbar-nav >li:first-child").show();
				}
			});
		</script>
	{/if}
	
{/if}
