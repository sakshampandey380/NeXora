# 🛒 E-Commerce Web Application (PHP + MySQL)

A fully functional **E-Commerce Web Application** built using **Core PHP, MySQL, HTML, CSS, and JavaScript**.
This project includes both **User and Admin panels**, complete authentication system with OTP verification, product management, cart system, and order tracking.

---

## 🚀 Features

### 👤 User Features

* User Signup & Login (OTP Verification)
* Secure Authentication System
* Browse Products by Category
* Search Products
* Add to Cart / Buy Now
* Manage Cart (Update, Remove, Clear)
* Place Orders
* View Order History & Status
* Profile Management (Edit Profile, Address, Password)

---

### 🔐 Admin Features

* Admin Signup & Login (OTP Verification)
* Admin Dashboard
* Add / Edit / Delete Products
* Add Categories
* View All Products
* Manage Orders
* Admin Profile Management

---

### 🛍️ Core Functionalities

* Dynamic Product Listing
* Category Filtering
* Search System
* Cart Management System
* Order Tracking System
* OTP-based Authentication
* File Upload (Product Images, Profile Images)

---

## 🧰 Tech Stack

* **Frontend:** HTML, CSS, JavaScript
* **Backend:** PHP (Core PHP)
* **Database:** MySQL
* **Server:** Apache (XAMPP / WAMP recommended)

---

## 📁 Project Structure

```
ecommerce/
│
├── admin/                 # Admin panel (product & category management)
├── auth/
│   ├── admin/            # Admin authentication
│   └── user/             # User authentication
│
├── cart/                 # Cart operations
├── config/               # Database & OTP configuration
├── products/             # Product listing, search, category pages
├── profile/              # User profile & orders
│
├── assets/
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   ├── images/           # UI images
│   └── indexes/          # HTML templates
│
├── uploads/              # Uploaded images (products & profiles)
├── database.sql          # Database file
├── index.php             # Entry point
└── debug.php             # Debugging file
```

---

## ⚙️ Installation & Setup

### 1️⃣ Clone the Repository

```bash
git clone https://github.com/your-username/ecommerce-project.git
```

---

### 2️⃣ Move Project to Server Directory

* For XAMPP:

```
C:/xampp/htdocs/
```

---

### 3️⃣ Start Server

* Start **Apache** and **MySQL** from XAMPP Control Panel

---

### 4️⃣ Import Database

1. Open **phpMyAdmin**
2. Create a new database (e.g., `ecommerce`)
3. Import:

```
database.sql
```

---

### 5️⃣ Configure Database Connection

Go to:

```
config/db.php
```

Update credentials:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "ecommerce";
```

---

### 6️⃣ Configure OTP (Important ⚠️)

Edit:

```
config/otp_delivery_config.php
```

* Add your OTP service configuration (API or SMTP)
* Or modify logic for testing (local OTP display)

---

### 7️⃣ Run the Project

Open in browser:

```
http://localhost/ecommerce/
```

---

## 🔑 Default Access (Optional Setup)

You may need to create:

* Admin account via signup
* Or manually insert into database

---

## 📸 Screens Included

* User Signup & Login UI
* Admin Dashboard
* Product Pages
* Cart & Orders
* Profile Section

---

## 🧪 Testing Routes

* `/index.php` → Homepage
* `/auth/user/login.php` → User Login
* `/auth/admin/login.php` → Admin Login
* `/products/` → Product Listings
* `/cart/view.php` → Cart

---

## ⚠️ Known Improvements

* Payment Gateway Integration (Razorpay/Stripe)
* Better UI/UX enhancements
* API-based architecture (for scaling)
* Security improvements (prepared statements, validation)
* Mobile responsiveness optimization

---

## 💡 Future Enhancements

* Wishlist Feature
* Product Reviews & Ratings
* Real-time Notifications
* Admin Analytics Dashboard
* Multi-vendor support

---

## 🤝 Contributing

Pull requests are welcome!
For major changes, please open an issue first.

---

## 📄 License

This project is open-source and available under the **MIT License**.

---

## 👨‍💻 Author

**Saksham**

* Passionate Developer 🚀
* Focused on building scalable web applications

---

## ⭐ Support

If you like this project:

* ⭐ Star the repository
* 🍴 Fork it
* 🧠 Share ideas & improvements

---

🔥 *Happy Coding!*
made by Saksham Pandey
