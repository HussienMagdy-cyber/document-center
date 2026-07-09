<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_register_shortcode()
{
    if (is_user_logged_in()) {
        return '<p>You are already logged in. Go to <a href="' . esc_url(site_url('/dashboard')) . '">Dashboard</a>.</p>';
    }

    ob_start();
?>
    <?php doccenter_render_auth_styles(); ?>
    <div class="doccenter-auth-wrap doccenter-auth-single">
        <h2>Create Viewer Account</h2>
        <p class="doccenter-auth-subtitle">Register to access documents shared with you.</p>

        <form method="post">
            <div class="doccenter-auth-field">
                <label for="doc_username">Username</label>
                <input type="text" id="doc_username" name="doc_username" required>
            </div>
            <div class="doccenter-auth-field">
                <label for="doc_email">Email</label>
                <input type="email" id="doc_email" name="doc_email" required>
            </div>
            <div class="doccenter-auth-field">
                <label for="doc_password">Password</label>
                <input type="password" id="doc_password" name="doc_password" required>
            </div>

            <?php wp_nonce_field('doccenter_register', 'doccenter_register_nonce'); ?>
            <input type="hidden" name="doccenter_action" value="register">

            <button type="submit" class="doccenter-btn-primary doccenter-auth-btn">Register</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.querySelectorAll('.doccenter-alert').forEach(function(el) {
                    el.remove();
                });
            }, 3000);
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('doccenter_register', 'doccenter_register_shortcode');

