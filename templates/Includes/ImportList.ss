<% if Files %>
	<% if Multi %>
		<ul class="columns_{$ColumnCount}">
			<% control Files %>
				<li class="$EvenOdd <% if Disabled %>disabled<% end_if %>"><input <% if Disabled %>disabled="disabled"<% end_if %> type="checkbox" value="$ID" name="ImportFiles[]" id="import-input-$ID" /> 
				<% if Height %>$CroppedImage(32,32)<% else %><img src="$Icon" height="32"/><% end_if %>
				<label for="import-input-$ID">$Name.LimitCharacters</label></li>
			<% end_control %>
		</ul>
	<% else %>
		<ul class="columns_{$ColumnCount}">
			<% control Files %>
				<li class="$EvenOdd"><input type="radio" value="$ID" name="ImportFileID" id="import-input-$ID" /> 
				<% if Height %>$CroppedImage(32,32)<% else %><img src="$Icon" height="32"/><% end_if %>
				<label for="import-input-$ID">$Name.LimitCharacters(30)</label></li>
			<% end_control %>		
		</ul>
	<% end_if %>
<% else %>
	<% _t('Uploadify.NOFILESFOUND','There are no files in that folder') %>
<% end_if %>
