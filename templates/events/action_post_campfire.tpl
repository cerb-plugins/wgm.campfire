<b>Room:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[room]">
	{foreach from=$rooms key=room_id item=room_name}
	<option value="{$room_id}"{if $params.room == $room_id} selected{/if}>{$room_name}</option>
	{/foreach}
	</select>
</div>

<b>Speak text:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea name="{$namePrefix}[content]" rows="10" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>
