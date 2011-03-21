<% if Files %>
	<% if Multi %>
		<div class="import_input_select">
			<span><% _t('Uploadify.SELECT','Select') %>:
				<a class="import_input_select_all" onclick="jQuery.fn.uploadifyImportCheckboxToggle(true);"><% _t('Uploadify.ALL','all') %></a> /
				<a class="import_input_select_none" onclick="jQuery.fn.uploadifyImportCheckboxToggle(false);"><% _t('Uploadify.NONE','none') %></a> / 
				<a class="import_input_select_toggle" onclick="jQuery.fn.uploadifyImportCheckboxToggle('toggle');"><% _t('Uploadify.TOGGLE','toggle') %></a>
			</span>
		</div>
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
