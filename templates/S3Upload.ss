<div class="field UploadifyField">
	<label for="$id">$Title</label>
	<div class="middleColumn">
		<div class="button_wrapper">
			<a class="uploadify_button upload">$ButtonText</a>
			<div class="object_wrapper">
				<input type="file" class="uploadify { $Metadata }" name="$Name" id="$id" />
			</div>
		</div>
		<div id="upload_preview_{$id}" class="preview">
			<% include AttachedFiles %>
		</div>
		<div id="UploadifyFieldQueue_{$Name}" class="uploadifyfield_queue"></div>
	</div>
	<% if DebugMode %>
		$DebugOutput
	<% end_if %>
	
</div>