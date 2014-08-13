<?php
	function do_row( $id, $label, $desc, $type = 'text' )
	{
?>
		<tr class="form-field form-required">
			<th scope="row" valign="top"><label for="<?php echo $id; ?>"><?php echo $label; ?></label></th>
			<td><input id="<?php echo $id; ?>" name="<?php echo $id; ?>" type="<?php echo $type; ?>" required>
			<p class="description"><?php echo $desc; ?></p></td>
		</tr>
<?php
	}
?>
<div class="wrap">
	<h2>New Package</h2> <!-- FIXME: Edit? -->
	<form method="post" action="?page=<?php echo $_REQUEST['page']; ?>">
		<?php wp_nonce_field( 'new-' . $golangpkg_table->_args['singular'] ); ?>
		<input type="hidden" name="action" value="post-new">
		<table class="form-table">
<?php

	$desc = <<<DESC
The end of the URL where your package is from, eg <code>package</code> for <code>example.com/pkg/package</code><br>
This is used for importing (<code>import "example.com/pkg/package"</code>) and as an actual url <code>http://example.com/pkg/package</code>.
DESC;
	do_row( 'slug', __( 'Package Slug', 'golang_pkg' ), $desc );
?>
		<tr class="form-field form-required">
			<th scope="row" valign="top"><label for="type">Repository Type</label></th>
			<td>
				<select id="type" name="type"required>
					<option disabled selected> ---- </option>
					<option value="bzr">Bazaar</option>
					<option value="git">Git</option>
					<option value="hg">Mercurial</option>
					<option value="svn">Subversion</option>
				</select>
			<p class="description">The VCS your repository is using</p></td>
		</tr>
<?php
	$desc = <<<DESC
Where go get can download your package from.<br>
For instance <code>https://github.com/example/package</code> or <code>ssh://git@example.com/project/package.git</code>.<br>
Note that for SSH urls (<code>git@</code>) you <b>must</b> put <code>ssh://</code> and replace the first <code>:</code> with a <code>/</code>.<br>
EG if your clone command is <code>git clone git@example.com:project/package.git</code>, you replace the <code>:</code> after <code>example.com</code> with a <code>/</code> to end up with <code>ssh://git@example.com/project/package.git</code>.
DESC;
	do_row( 'url', __( 'Repository URL', 'golang_pkg' ), $desc );
?>
		<!-- This table does not handle enable / disable -->
		</table>
		<?php submit_button(
			__( 'Add Link', 'golang_pkg' ),
			'primary',
			'submit'
		); ?>
	</form>
</div>
