# Azurite Properties Rental System 
This is the modern, fully styled version of the Azurite case study system.

- Landing hero with search bar & featured cards
- Tenant registration with CAPTCHA + ID upload (JPG/PNG)
- MD5 password hashing 
- Admin / Host / Tenant roles
- Booking with reference number, KSh pricing
- Modern dashboard cards & tables

Setup is the same as the earlier version:

1. Create database `azurite_rental`.
2. Import `database/migrations.sql`.
3. Import `database/seed_admin.sql`.
4. Place folder in your web root (`htdocs/Azurite-Rental-System-Modern`).
5. visit `/public/`.

Admin login: `admin` / `admin123` (password stored with MD5).



Azurite-Rental-System/
│
├── database.sql
├── README.md               (optional)
│
├── includes/
│   ├── db.php
│   ├── helpers.php
│   ├── calendar_single.php
│   ├── admin_header_public.php      (optional)
│   ├── admin_footer_public.php      (optional)
│
├── assets/
│   ├── css/
│   │   ├── modern.css
│   │   ├── style.css
│   │   ├── select2.min.css
│   │   ├── jquery.datetimepicker.min.css
│   │   └── material.css
│   │
│   ├── js/
│   │   ├── jquery.min.js
│   │   ├── select2.min.js
│   │   ├── jquery.datetimepicker.full.js
│   │   ├── calendar.js         (your front-end calendar script)
│   │   └── main.js
│   │
│   └── img/
│       ├── logo.png
│       ├── sample-house.jpg
│       └── other images...
│
├── uploads/
│   ├── avatars/
│   ├── id_cards/
│   ├── room_photos/
│   └── temp/
│
├── public/ - DONE 
│   ├── index.php
│   ├── rooms.php
│   ├── room.php
│   ├── book.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   ├── profile.php
│   ├── my_bookings.php
│   ├── room_availability.php
│
│   ├── host/ -DONE
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   ├── footer.php
│   │   ├── index.php
│   │   ├── rooms.php
│   │   ├── room_view.php
│   │   ├── room_gallery_upload.php
│   │   ├── bookings.php
│   │   ├── booking_view.php
│   │   ├── bookings_update.php
│   │   ├── availability.php
│   │   ├── calendar.php
│   │   ├── toggle_day.php
│   │   └── gallery_delete.php (optional)
│
│   ├── admin/ - DONE 
│       ├── header.php
│       ├── sidebar.php
│       ├── footer.php
│       ├── index.php
│       ├── rooms.php
│       ├── room_view.php
│       ├── bookings.php
│       ├── booking_view.php
│       ├── users.php
│       ├── user_view.php
│       ├── calendar.php
│       ├── reports.php
│       ├── categories.php
│       └── settings.php
│
└── vendor/                (only if using composer)
