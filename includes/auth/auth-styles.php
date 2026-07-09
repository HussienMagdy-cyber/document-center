<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_render_auth_styles()
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;
?>
    <style>
        /* SINGLE COLUMN CLEAN AUTH FORM */
        .doccenter-auth-single {
            max-width: 420px !important;
            margin: 60px auto !important;
            padding: 32px 28px !important;
            display: block !important;
        }

        .doccenter-auth-single .doccenter-auth-aside {
            display: none !important;
        }

        .doccenter-auth-single .doccenter-auth-main {
            width: 100% !important;
        }

        .doccenter-auth-single h2 {
            font-size: 22px;
            margin-bottom: 4px;
        }

        .doccenter-auth-single .doccenter-auth-subtitle {
            margin-bottom: 22px;
            font-size: 14px;
        }

        .doccenter-auth-single .doccenter-auth-btn {
            width: 100%;
        }

        .doccenter-auth-single input {
            font-size: 14px !important;
        }

        @media (max-width: 480px) {
            .doccenter-auth-single {
                padding: 20px 16px !important;
                margin: 30px auto !important;
            }
        }

        .doccenter-auth-wrap {
            max-width: 880px;
            margin: 40px auto;
            padding: 32px 28px;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(148, 163, 184, 0.35);
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .doccenter-auth-main {
            flex: 1 1 260px;
            min-width: 0;
        }

        .doccenter-auth-aside {
            flex: 0 1 260px;
            min-width: 0;
            padding: 16px 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #111827, #1f2937);
            color: #e5e7eb;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .doccenter-auth-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .doccenter-auth-brand-title {
            font-size: 18px;
            font-weight: 600;
        }

        .doccenter-auth-brand-sub {
            font-size: 12px;
            color: #9ca3af;
        }

        .doccenter-auth-hint {
            font-size: 13px;
            line-height: 1.5;
            color: #d1d5db;
            margin-bottom: 10px;
        }

        .doccenter-auth-meta {
            font-size: 11px;
            color: #9ca3af;
        }

        .doccenter-auth-wrap h2 {
            margin-top: 0;
            margin-bottom: 6px;
            font-size: 20px;
            font-weight: 600;
            color: #111827;
        }

        .doccenter-auth-subtitle {
            margin: 0 0 18px;
            font-size: 13px;
            color: #6b7280;
        }

        .doccenter-auth-field {
            margin-bottom: 14px;
        }

        .doccenter-auth-field label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }

        .doccenter-auth-field input[type="text"],
        .doccenter-auth-field input[type="email"],
        .doccenter-auth-field input[type="password"] {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            padding: 9px 10px;
            font-size: 13px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .doccenter-auth-field input[type="text"]:focus,
        .doccenter-auth-field input[type="email"]:focus,
        .doccenter-auth-field input[type="password"]:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 1px rgba(79, 70, 229, 0.4);
        }

        .doccenter-auth-errors {
            margin-bottom: 12px;
            padding: 8px 10px;
            border-radius: 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-size: 13px;
        }

        .doccenter-btn-primary.doccenter-auth-btn {
            width: 100%;
            margin-top: 4px;
        }

        .doccenter-auth-footer {
            margin-top: 14px;
            font-size: 12px;
            color: #6b7280;
        }

        .doccenter-auth-footer a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .doccenter-auth-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .doccenter-auth-wrap {
                padding: 24px 20px;
            }
        }

        @media (max-width: 720px) {
            .doccenter-auth-wrap {
                flex-direction: column-reverse;
            }
        }

        @media (max-width: 480px) {
            .doccenter-auth-wrap {
                margin: 20px auto;
                border-radius: 12px;
                padding: 18px 14px;
            }

            .doccenter-auth-aside {
                border-radius: 10px;
            }
        }
    </style>
<?php
}


