<?php http_response_code(404); die(1); // It is a template file, not a code to execute directly. This line is used to avoid to execute or read it. ?>
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    # based in Laravel .htaccess
    RewriteEngine On
    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To router
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    #RewriteRule ^ index.php [L]
    RewriteRule ^(.*)$ index.php?req=$1 [L]
</IfModule>
