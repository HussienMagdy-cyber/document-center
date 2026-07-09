<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_login_shortcode()
{
    if (is_user_logged_in()) {
        return '<p>You are already logged in. Go to <a href="' . esc_url(site_url('/dashboard')) . '">Dashboard</a>.</p>';
    }

    ob_start();
?>
    <?php doccenter_render_auth_styles(); ?>


    <div class="doccenter-auth-wrap doccenter-auth-single">
        <h2>Sign in</h2>
        <p class="doccenter-auth-subtitle">Access your Document Center dashboard.</p>

        <form method="post">
            <div class="doccenter-auth-field">
                <label for="doc_login_username">Username or Email</label>
                <input type="text" id="doc_login_username" name="doc_login_username" required>
            </div>
            <div class="doccenter-auth-field">
                <label for="doc_login_password">Password</label>
                <input type="password" id="doc_login_password" name="doc_login_password" required>
            </div>
            <div class="doccenter-auth-field" style="display:flex;align-items:center;gap:6px;">
                <input type="checkbox" id="doc_login_remember" name="doc_login_remember" value="1" style="width:auto;">
                <label for="doc_login_remember" style="margin:0;font-size:12px;color:#4b5563;">Remember me</label>
            </div>

            <?php wp_nonce_field('doccenter_login', 'doccenter_login_nonce'); ?>
            <input type="hidden" name="doccenter_action" value="login">

            <button type="submit" class="doccenter-btn-primary doccenter-auth-btn">Login</button>
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
add_shortcode('doccenter_login', 'doccenter_login_shortcode');

