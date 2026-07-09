<div class="doccenter-root" id="doccenter-root">
<?php
$custom_logo_id = get_theme_mod('custom_logo');
$site_logo_url  = wp_get_attachment_image_url($custom_logo_id, 'full');
?>
<div class="doccenter-shell">
<?php doccenter_get_template('dashboard/sidebar', get_defined_vars()); ?>
<main class="doccenter-main">
<?php doccenter_get_template('dashboard/header', get_defined_vars()); ?>
<?php doccenter_get_template('dashboard/overview', get_defined_vars()); ?>
<?php if ($is_admin) : doccenter_get_template('dashboard/upload', get_defined_vars()); endif; ?>
<?php doccenter_get_template('dashboard/documents', get_defined_vars()); ?>
<?php if ($is_admin) : ?>
<?php doccenter_get_template('dashboard/groups', get_defined_vars()); ?>
<?php doccenter_get_template('dashboard/users', get_defined_vars()); ?>
<?php endif; ?>
<?php doccenter_get_template('dashboard/requests', get_defined_vars()); ?>
<?php doccenter_get_template('dashboard/profile', get_defined_vars()); ?>
</main>
<div style="clear:both;"></div>
</div>
<?php doccenter_get_template('dashboard/modals', get_defined_vars()); ?>
</div>
