<b>Target room:</b>

<select name="{$namePrefix}[room]">

{foreach $rooms as $room_id => $room_name}
<option value="{$room_id}"{if $params.room == $room_id} selected{/if}>{$room_name}</option>
{/foreach}
</select>
<br>

<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<textarea name="{$namePrefix}[content]" rows="10" cols="45" style="width:100%;">{$params.content}</textarea><br>

{if !empty($token_labels)}
<select onchange="$field=$(this).siblings('textarea');$field.focus().insertAtCursor($(this).val());$(this).val('');">
	<option value="">-- insert at cursor --</option>
	{foreach from=$token_labels key=k item=v}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
	{/foreach}
</select>
{/if}
