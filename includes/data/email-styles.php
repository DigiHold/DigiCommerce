<?php
/**
 * Email styles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return <<<CSS
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    line-height: 1.6;
    color: #374151;
    margin: 0;
    padding: 0;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    background-color: #ffffff;
}

.header {
    text-align: center;
    padding: 20px 0;
    border-bottom: 1px solid #E5E7EB;
}

.content {
    padding: 30px 0;
}

h1, h2, h3, h4 {
    color: #111827;
    margin-top: 0;
}

h2 {
    font-size: 24px;
    margin-bottom: 20px;
}

h3 {
    font-size: 18px;
    margin: 25px 0 15px;
}

p {
    margin: 0 0 15px;
}

a {
    color: #2563EB;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

.button {
    display: inline-block;
    padding: 12px 24px;
    background-color: #09053A;
    color: #ffffff !important;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    margin: 15px 0;
}

.button:hover {
    background-color: #362F85;
    text-decoration: none;
}

.button-secondary {
    background-color: #6B7280;
}

.button-secondary:hover {
    background-color: #4B5563;
}

.button-container {
    text-align: center;
    margin: 30px 0;
}

.important-note {
    font-weight: 500;
    color: #DC2626;
}

.features-section {
    margin: 30px 0;
}

.features-section ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.features-section li {
    padding: 8px 0 8px 25px;
    position: relative;
}

.features-section li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #059669;
    font-weight: bold;
}

.order-info {
	background-color: #F3F4F6;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 20px;
}

.order-items {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.order-items th {
    background-color: #F3F4F6;
    padding: 12px;
    text-align: left;
    font-weight: 500;
}

.order-items td {
    padding: 12px;
    border-bottom: 1px solid #E5E7EB;
}

.order-items tfoot th,
.order-items tfoot td {
    border-top: 2px solid #E5E7EB;
    font-weight: 600;
}

.order-total {
	text-align: right;
	padding: 10px;
	background-color: #F3F4F6;
	border-radius: 8px;
}

.tracking-info {
    background-color: #F3F4F6;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.download-section {
    margin: 30px 0;
}

.download-list {
    list-style: none;
    padding: 0;
    margin: 15px 0;
}

.download-list li {
    padding: 10px;
    border: 1px solid #E5E7EB;
    border-radius: 6px;
    margin-bottom: 10px;
}

.download-link {
    display: block;
    font-weight: 500;
}

.expires {
    display: block;
    font-size: 14px;
    color: #6B7280;
    margin-top: 5px;
}

.credentials-box {
    background-color: #F3F4F6;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.credentials-box p:last-child {
    margin: 0;
}

.social-links {
    display: flex;
    justify-content: center;
    gap: .5rem;
    margin-bottom: 30px;
}

.social-links a {
    display: flex;
}

.social-links a img {
    max-width: 1.7rem;
    height: auto;
}

.footer {
    text-align: center;
    padding-top: 30px;
    border-top: 1px solid #E5E7EB;
    color: #6B7280;
    font-size: 14px;
}

.security-notice {
    color: #DC2626;
    font-size: 13px;
    margin-top: 15px;
}

.contact-info {
    margin-top: 15px;
    font-size: 13px;
}

@media screen and (max-width: 600px) {
    .container {
        padding: 10px;
    }
    
    .button {
        display: block;
        text-align: center;
    }
    
    .order-items {
        font-size: 14px;
    }
    
    .order-items td,
    .order-items th {
        padding: 8px;
    }
}
CSS;
