## TalentHub API – Job Portal Backend

A RESTful backend API for a mini job portal (TalentHub), built with Laravel. The system enables Employers to post jobs and review applications, Applicants to view and apply for jobs and review thier application status, and Admins to review applications.

## 🚀 Features

JWT-based Authentication(With refresh token) & Authorization

Role Management: Admin, Employer, Applicant

Job posting and management (Employers)

Job applications (Applicants)

Application review and status updates (Admin/Employer)

Secure password hashing & validation

Environment-based configuration

## 🛠️ Tech Stack

Backend Framework: Laravel 11 (PHP 8.2+)

Database: MySQL (configurable)

Authentication: JWT (JSON Web Token)

Deployment Options: Heroku(in progress)

## 📂 Project Structure
Job-Portal-API/

│── app/               # Application logic (Controllers, Models, Middleware)

│── config/            # Configuration files

│── database/          # Migrations & Seeders

│── routes/            # API routes (api.php)

│── tests/             # Feature & Unit tests

│── .env.example       # Example environment variables

│── composer.json      # PHP dependencies

│── README.md          # Documentation

## ⚙️ Installation & Setup

### 1.Clone Repository

git clone https://github.com/kenaolb/Job-portal.git

cd Job-portal

### 2.Install Dependencies
   
composer install

### 3.Environment Configuration

Copy .env.example → .env

### 4.Generate application key:

php artisan key:generate

### 5.Configure your database in the .env file to the below code:

DB_CONNECTION=mysql

DB_HOST=127.0.0.1

DB_PORT=3306

DB_DATABASE=talenthub

DB_USERNAME=root

DB_PASSWORD=

### 6.Generate JWT secret key:

php artisan jwt:secret

### 7.Run database migrations:

php artisan migrate

### 8.(Optional) Seed the database with sample data:

php artisan db:seed

### 9.Start the development server:

php artisan serve

The API will be available at http://localhost:8000 .

## by Kenaol Bekele

