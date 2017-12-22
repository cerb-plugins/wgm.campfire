{if !empty($rooms)}
<b>Room:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[room]">
	{foreach from=$rooms key=room_id item=room_name}
	<option value="{$room_id}" {if $params.room == $room_id}selected="selected"{/if}>{$room_name}</option>
	{/foreach}
	</select>
</div>
{else}
<div class="ui-widget">
	<div class="ui-state-error ui-corner-all" style="padding:0 0.5em;margin:0.5em;"> 
		<p>
			<span class="ui-icon ui-icon-alert" style="float:left;margin-right:0.3em"></span> 
			<strong>Warning:</strong> The Campfire plugin is not configured.  Messages will not be sent.
		</p>
	</div>
</div>
{/if}

<b>Message:</b>
<label><input type="checkbox" name="{$namePrefix}[is_paste]" value="1" {if $params.is_paste}checked="checked"{/if}> Paste</label>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>
