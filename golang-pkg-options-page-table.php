<div class="wrap">
	<h2>Go Packages <a href="?page=<?= $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"> Add New </a></h2>
	<form method="get">
		<input type="hidden" name="page" value="<?= $_REQUEST['page']; ?>">
	<?php
		$golangpkg_table->search_box('search', 'search_id');
		$golangpkg_table->display();
	?>
	</form>
	<script type="text/javascript">
	(function($){
		$('span.delete a').on('click', function(event)
		{
			if ( ! confirm( commonL10n.warnDelete ) )
				event.preventDefault();
		} );
		$('#doaction,#doaction2').on('click', function(event)
		{
			var value;
			value = $('select[name=action]').val();
			if ( value !== "-1" ) {
				if ( value === 'delete' && ! confirm( commonL10n.warnDelete ) )
					event.preventDefault();
				return;
			}
			value = $('select[name=action2]').val();
			if ( value === 'delete' && ! confirm( commonL10n.warnDelete ) )
				event.preventDefault();
		} );
	})(jQuery);
	</script>
</div>
