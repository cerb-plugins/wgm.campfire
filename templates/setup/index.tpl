<h2>{'wgm.campfire.common'|devblocks_translate}</h2>

<form action="javascript:;" method="post" id="frmSetupCampfire" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="campfire">
<input type="hidden" name="action" value="saveJson">

<fieldset>
	<legend>Campfire Settings</legend>
	
	<b>API Auth Token:</b><br>
	<input type="password" name="api_token" value="{$params.api_token}" size="45"><br>
	<br>
	<b>Campfire Subdomain:</b><br>
	https://<input type="text" name="url" value="{$params.url}" size="45">.campfirenow.com<br>
	<br>
	<div class="status"></div>	

	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	
</fieldset>

</form>

<script type="text/javascript">
$('#frmSetupCampfire BUTTON.submit')
	.click(function(e) {
		genericAjaxPost('frmSetupCampfire','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#frmSetupCampfire div.status',$o.error);
			} else {
				Devblocks.showSuccess('#frmSetupCampfire div.status',$o.message);
			}
		});
	})
;
</script>