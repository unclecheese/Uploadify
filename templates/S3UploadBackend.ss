<div class="field UploadifyField backend">
	<div class="horizontal_tab_wrap">
	  <div class="tabNavigation clearfix">
		<label for="$id">$Title</label>
	  </div>
	  <div class="horizontal_tabs">
	      <div id="upload-$id" class="horizontal_tab upload">
			<div class="middleColumn">
				<div class="button_wrapper">
					<a class="uploadify_button upload">$ButtonText</a>
					<div class="object_wrapper">
						<input type="file" class="uploadify { $Metadata }" name="$Name" id="$id" />
					</div>
				</div>
				<% if CanSelectFolder %>
					<div class="folder_select_wrap" id="folder_select_wrap_{$id}">
						<% include FolderSelection %>
					</div>
				<% end_if %>			

				<div id="UploadifyFieldQueue_{$Name}" class="uploadifyfield_queue"></div>
			</div>
	      </div>
	  </div>
	</div>
	<div class="attached_files_wrap">
		<div class="middleColumn">
			<div id="upload_preview_{$id}" class="preview">
				<% include AttachedFiles %>
			</div>
		</div>
	</div>	
	<% if DebugMode %>
		$DebugOutput
	<% end_if %>
	
</div>