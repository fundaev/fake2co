 INSTALLATION
------------------------------------
1. Create new database
2. Import the install.sql file into created database
3. Copy config.ini.example.php to config.ini.php and fill in the options in this file



 USAGE
------------------------------------
There are 2 entry points:
1. index.php - entry point for checkout. Your script (e-commerce shopping cart software or something else) should send the data to this script to create new order in "2checkout emulator".
2. cp.php - admin back-end. Here you can see the orders list, manage the invoices of selected order and send new callback requests.



The simplified 2Checkout emulator was created in according to this guide https://www.2checkout.com/documentation/Advanced_User_Guide.pdf
