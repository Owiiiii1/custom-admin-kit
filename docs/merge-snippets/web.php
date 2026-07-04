<?php

/*
| Owl Admin routes (v0.3)
|
| 1. Ensure routes/owl-admin-pages.php exists
|    (created by owl-admin:frontend-setup or copied from package stubs).
|
| 2. For preset=admin also ensure routes/owl-admin-auth.php exists.
|
| 3. Add these lines to routes/web.php (before auth.php if you use Breeze/Jetstream):
*/

require __DIR__.'/owl-admin-pages.php';
require __DIR__.'/owl-admin-auth.php';
