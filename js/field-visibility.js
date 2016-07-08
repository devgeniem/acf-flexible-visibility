jQuery(document).ready(function($) {
	$("div[data-type=flexible_content]").each(function() {
		$(this).find("tr[data-name=fc_layout]").each(function() {
			handle_tr( this );
		});
	});

	function handle_tr( $el ) {
		var id = $($el).closest("div[data-type=flexible_content]").data("id");
		var layout_id = $($el).data("id");

		$.ajax({
			method: "POST",
			url: ajaxurl,
			data: {
				action: "acf_fv_get_visibility_value",
				id: id,
				layout_id: layout_id
			},
			dataType: "json"
		}).done(function( response ) {			
			var field = '<li class="acf-fc-meta-visibility"><div class="acf-input-prepend">' + acf_flexible_visibility.page_templates_to_ignore + '</div><div class="acf-input-wrap"><select multiple id="acf_fields-' + id + '-layouts-' + layout_id + '-visibility" class="acf-is-prepended acf-fv-' + layout_id + ' acf-flexible-visibility-select" name="acf_fields[' + id + '][layouts][' + layout_id + '][visibilities]" style="width: 100%;"><option>' + acf_flexible_visibility.choose_template + '</option><option></option>';

			$.each(response.data.templates, function( key, value ) {
				field = field + '<option value="'+ value +'"';

				var visibility = response.data.visibility;

				if ( "undefined" !== typeof visibility ) {
					if ( -1 !== visibility.indexOf( value ) ) {
						field = field + ' selected="selected"';
					}
				}

				field = field + '>' + key + '</option>';
			});

			var visibility = response.data.visibility;

			if ( "undefined" !== typeof visibility ) {
				var visibility_string = visibility.join(",");
			}
			else {
				var visibility_string = "";
			}

			field = field + '</select><input type="hidden" class="acf-fv-hidden-' + layout_id + '" name="acf_fields[' + id + '][layouts][' + layout_id + '][visibility]" value="'+ visibility_string +'"></div></li>';

			$($el).find("ul.acf-fc-meta").append( field );

			$("select.acf-flexible-visibility-select").select2();

			$("select.acf-flexible-visibility-select.acf-fv-" + layout_id ).on("change", function(e) {
				var values = e.val;

				$("input.acf-fv-hidden-" + layout_id).val( values.join(",") );
			});
		});
	}
});