# Instructor Registration Setup

## Flow

- Instructor registers with course type, full name, country code, mobile, email, gender, date of birth, qualification, career started, students trained, address, profile picture, password, and confirm password.
- New instructors are saved with `role = instructor` and `status = pending`.
- Admin approves, rejects, or deletes instructors from `admin/instructors.php`.
- Approved instructors can log in through `/instructor-login` and access the instructor dashboard.
- Pending/rejected instructors receive clear login messages.
- Forgot password sends a reset link by email and stores a hashed reset token with expiry.

## Database

The backend auto-creates or updates the `instructors` table, but the expected structure is:

```sql
CREATE TABLE instructors (
  id CHAR(36) PRIMARY KEY,
  full_name VARCHAR(255) NOT NULL,
  mobile VARCHAR(30) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NULL,
  course_type VARCHAR(60) NULL,
  country_code VARCHAR(10) NULL,
  gender VARCHAR(30) NULL,
  date_of_birth DATE NULL,
  qualification VARCHAR(255) NULL,
  career_started VARCHAR(100) NULL,
  students_trained VARCHAR(100) NULL,
  address TEXT NULL,
  profile_picture TEXT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  role VARCHAR(30) NOT NULL DEFAULT 'instructor',
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  reset_token VARCHAR(64) NULL,
  reset_expiry DATETIME NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Email

Forgot password uses PHPMailer when installed. Install it on the backend server with:

```bash
cd backend
php composer.phar require phpmailer/phpmailer
```

If PHPMailer is not installed, the backend falls back to the existing Brevo/API/SMTP sender.

Minimum email `.env`:

```env
EMAIL_ENABLED=true
EMAIL_TIMEOUT_SECONDS=10
EMAIL_FROM="Simple Abacus <simpleabacuspune@gmail.com>"
EMAIL_HOST=smtp-relay.brevo.com
EMAIL_PORT=587
EMAIL_USER=ab4de2001@smtp-brevo.com
EMAIL_PASS=your-xsmtpsib-smtp-key
EMAIL_ALLOW_PHP_MAIL_FALLBACK=false
```

## Admin

Open:

```text
admin/instructors.php
```

Use:

- Approve: lets the instructor log in immediately.
- Reject: blocks login with a rejected message.
- Delete: removes the instructor registration.
