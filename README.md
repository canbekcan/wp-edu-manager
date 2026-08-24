# WP EDU Manager (Host)

A WordPress-based academic content tracking, automated grading, and student management platform. It allows you to monitor student-generated content, revision histories, SEO metrics, and production speeds through a centralized server.

> **Important Note:** This plugin acts as the **Host (Teacher)** and is designed to work in integration with the **[BEKCAN Institute (Student) - WP EDU Client](https://github.com/canbekcan/wp-edu-client)** plugin. The instructor must install the `wp-edu-manager` plugin on their main site, while each participating student must install the `wp-edu-client` plugin on their independent WordPress sites.

---

## Features

* **Algorithmic Grading:** Automatically calculates grades based on word count, internal/external links, images, missing ALT tags, and post-publication modifications.
* **Asynchronous Content Sync (WP-Cron):** Fetches content and revisions from student sites asynchronously in the background without overloading the server.
* **Single Sign-On (SSO):** Allows students to log in to the Host dashboard seamlessly using a secure, hash-verified token with a 24-hour Time-to-Live (TTL).
* **Live Update Tracking:** Monitors core, plugin, and theme updates across all connected student sites from a single dashboard.
* **Direct Notice Delivery:** Pushes admin messages and alerts directly to the WordPress dashboards of student sites.
* **Multilingual Ready (i18n):** Built with English as the primary language, including full translation support.

---

## Architecture & System Requirements

* **Teacher / Main Site:** WordPress 5.8+, PHP 7.4+, MySQL 5.7+ (Requires `wp-edu-manager`).
* **Student Sites:** WordPress 5.8+, PHP 7.4+ (Requires `wp-edu-client`).
* **REST API:** WordPress REST API endpoints must be accessible on both servers.

---

## Installation & Usage Instructions

### 1. Instructor Setup (Host)
1. Download this repository, rename the folder to `wp-edu-manager`, and upload it to your `/wp-content/plugins/` directory.
2. Activate the **WP EDU Manager (Host)** plugin via the WordPress Admin Dashboard.
3. Navigate to **LMS Manager > Semesters** from the left menu.
4. Create a new semester by defining a semester name (e.g., *Fall 2026*), registration code (e.g., *NEWS-F26*), expiration date, and grading weights.

### 2. Student Setup (Client)
1. The student installs and activates the **WP EDU Client** plugin on their own WordPress site.
2. In the student dashboard settings, the student enters the instructor's site address (`Host URL`) and the `Registration Code` provided by the instructor.
3. Once matched, a unique `API Token` is generated for the student, and a `Contributor` account is automatically created on the Host site.

### 3. Content Sync & Audit
* **Automated Scan:** The system runs automatically every night at 23:50, fetching new content, typing speeds (WPM), and revision statuses from all student sites.
* **Manual Scan:** Synchronization can be triggered instantly via the *Fetch Data Now* button under **LMS Manager > Dashboard**.
* **Audit Dashboard:** Review SEO compliance, modification flags (Modified/Original), and calculated grades for all posts under the **LMS Manager > Content Audit** tab.

---

## License

This project is licensed under the **MIT License**.

```text
MIT License

Copyright (c) 2026 BEKCAN Institute

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```