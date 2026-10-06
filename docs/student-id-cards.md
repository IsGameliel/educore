# Temporary student ID cards

Students open **Temporary ID Card** under **Account** in the sidebar, or **View ID card** on the dashboard. The page displays the Mudiame University crest, stored profile photo, student name and matric number. PDF and PNG downloads are available after all four items are present. Students can update their photo through My Profile; student administration manages missing matric numbers and corrections to identification details.

The card also shows department, current level and expiry. Required identification fields must be present before downloading. Validity from first download: 100 level = 6 years; 200 = 4; 300 = 3; 400 = 2; 500 and 600 = 1. First issuance and expiry are saved on the account, so later downloads and level changes do not extend validity. Expired cards cannot be downloaded; the page directs students to administration.

The PNG uses the browser's image and canvas APIs, without external rendering services. The PDF uses the project's existing Dompdf installation. Card endpoints require an authenticated, verified student and use only that student's account, with private/no-store responses.

For deployment, upload the controller, updated User model, `resources/views/student/id-card` directory, `public/dash/assets/js/student-id-card.js`, `public/asset/images/student-id-verified.svg`, and the updated routes, student dashboard and student sidebar. Include `database/migrations/2026_10_05_150000_add_temporary_id_dates_to_users.php` and run `php artisan migrate --path=database/migrations/2026_10_05_150000_add_temporary_id_dates_to_users.php --force`. Retain `public/asset/images/euvion.png` as the school crest and the configured profile-photo storage disk. Run `php artisan view:clear` and `php artisan route:clear` after deploying.
