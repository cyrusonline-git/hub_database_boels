# Boels CORE PLATFORM — Database Ontwerp

**Versie:** 0.1
**Engine:** MySQL 8.x (utf8mb4 / utf8mb4_unicode_ci)
**Database:** deb2003831_hub_database_boels

---

## Conventies

- Alle tabellen: `engine=InnoDB`, `charset=utf8mb4`, `collation=utf8mb4_unicode_ci`
- Primary key: `id BIGINT UNSIGNED AUTO_INCREMENT`
- Timestamps: `created_at`, `updated_at` (Laravel standaard)
- Soft delete: `deleted_at NULL` waar `deleted` vermeld staat
- FK naming: `{tabel_enkelvoud}_id`
- Index naming: Laravel default

---

## A. Identity & Access

### users
| Kolom | Type | Null | Default | Opmerking |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | nee | AI | PK |
| name | VARCHAR(150) | nee | | |
| email | VARCHAR(190) | nee | | UNIQUE |
| email_verified_at | TIMESTAMP | ja | NULL | |
| password | VARCHAR(255) | nee | | bcrypt hash |
| employee_id | BIGINT UNSIGNED | ja | NULL | FK → employees.id |
| is_super_admin | TINYINT(1) | nee | 0 | bypass alle permissies |
| active | TINYINT(1) | nee | 1 | |
| last_login_at | TIMESTAMP | ja | NULL | |
| remember_token | VARCHAR(100) | ja | | |
| created_at, updated_at, deleted_at | | | | |

**Indexen:** `email` UNIQUE, `employee_id` FK, `active` index

### roles
| Kolom | Type | Null | Opmerking |
|---|---|---|---|
| id | BIGINT UNSIGNED | nee | PK |
| name | VARCHAR(100) | nee | UNIQUE — bv. "Fleet Manager" |
| slug | VARCHAR(100) | nee | UNIQUE — bv. "fleet-manager" |
| description | TEXT | ja | |
| is_system | TINYINT(1) | nee | system rollen niet verwijderbaar |
| created_at, updated_at, deleted_at | | | |

### permissions
| Kolom | Type | Null | Opmerking |
|---|---|---|---|
| id | BIGINT UNSIGNED | nee | PK |
| application_id | BIGINT UNSIGNED | ja | FK — NULL = platform-permissie |
| key | VARCHAR(150) | nee | bv. "machines.view" |
| name | VARCHAR(150) | nee | display naam |
| description | TEXT | ja | |
| created_at, updated_at | | | |

**Indexen:** UNIQUE(`application_id`, `key`)

### role_permissions
Pivot: `role_id`, `permission_id`, `created_at`. PK = (role_id, permission_id).

### user_roles
Pivot: `user_id`, `role_id`, `created_at`. PK = (user_id, role_id).

### applications
| Kolom | Type | Null | Opmerking |
|---|---|---|---|
| id | BIGINT UNSIGNED | nee | PK |
| name | VARCHAR(150) | nee | |
| slug | VARCHAR(100) | nee | UNIQUE — bv. "fleet" |
| description | TEXT | ja | |
| url | VARCHAR(255) | ja | bv. "https://fleet.sorai.nl" |
| icon | VARCHAR(100) | ja | bv. "bi-truck" (Bootstrap Icons) |
| color | VARCHAR(20) | ja | hex, default Boels-oranje |
| sort_order | INT | nee | 0 |
| active | TINYINT(1) | nee | 1 |
| created_at, updated_at, deleted_at | | | |

---

## B. Organisatie

### departments
`id`, `name VARCHAR(150)`, `code VARCHAR(50) NULL`, timestamps, soft delete.

### employees
| Kolom | Type | Null | Opmerking |
|---|---|---|---|
| id | BIGINT UNSIGNED | nee | PK |
| employee_number | VARCHAR(50) | nee | UNIQUE |
| name | VARCHAR(200) | nee | |
| email | VARCHAR(190) | ja | UNIQUE waar NOT NULL |
| phone | VARCHAR(50) | ja | |
| department_id | BIGINT UNSIGNED | ja | FK → departments.id |
| function | VARCHAR(150) | ja | bv. "Monteur" |
| active | TINYINT(1) | nee | 1 |
| external_id | VARCHAR(100) | ja | |
| source_system | VARCHAR(50) | ja | |
| created_at, updated_at, deleted_at | | | |

---

## C. Klanten & CRM

### customers
| Kolom | Type | Opmerking |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| customer_number | VARCHAR(50) | UNIQUE |
| customer_name | VARCHAR(255) | |
| status | VARCHAR(50) | bv. "active", "prospect", "blocked" |
| kvk_number | VARCHAR(20) NULL | |
| vat_number | VARCHAR(30) NULL | |
| address_street, address_number, address_postal, address_city, address_country | VARCHAR | |
| email, phone, website | VARCHAR NULL | |
| external_id, source_system | VARCHAR NULL | |
| owner_employee_id | BIGINT UNSIGNED NULL | FK → employees.id (accountmanager) |
| timestamps, soft delete | | |

### contacts
`customer_id` FK, `name`, `function`, `email`, `phone`, `mobile`, `is_primary TINYINT(1)`, timestamps, soft delete.

### leads
`lead_number`, `name`, `source`, `status`, `customer_id NULL`, `assigned_to BIGINT UNSIGNED NULL` (FK → employees.id), `expected_value DECIMAL(12,2) NULL`, `description`, timestamps, soft delete.

### opportunities
`opportunity_number`, `customer_id` FK, `name`, `stage` (varchar), `amount DECIMAL(12,2)`, `probability TINYINT`, `close_date DATE`, `owner_employee_id` FK, `description`, timestamps, soft delete.

### customer_visits
`customer_id` FK, `contact_id NULL` FK, `employee_id` FK, `visit_date DATETIME`, `purpose VARCHAR(255)`, `outcome TEXT`, `next_action TEXT`, timestamps, soft delete.

---

## D. Projecten & Werk

### projects
| Kolom | Type | Opmerking |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| project_number | VARCHAR(50) | UNIQUE |
| project_name | VARCHAR(255) | |
| customer_id | BIGINT UNSIGNED | FK |
| status | VARCHAR(50) | "planning","active","completed","cancelled" |
| description | TEXT | |
| start_date, end_date | DATE NULL | |
| project_manager_id | BIGINT UNSIGNED NULL | FK → employees.id |
| external_id, source_system | VARCHAR NULL | |
| timestamps, soft delete | | |

### work_orders
`work_order_number` UNIQUE, `project_id NULL` FK, `customer_id NULL` FK, `machine_id NULL` FK, `assigned_employee_id NULL` FK, `status`, `description`, `planned_date DATETIME NULL`, `completed_at DATETIME NULL`, timestamps, soft delete.

### tasks
`title VARCHAR(255)`, `description TEXT`, `taskable_id BIGINT UNSIGNED`, `taskable_type VARCHAR(150)` (polymorf — kan aan project/customer/machine hangen), `assigned_to BIGINT UNSIGNED NULL` (FK → employees.id), `status`, `priority`, `due_date DATETIME NULL`, `completed_at DATETIME NULL`, timestamps, soft delete.

---

## E. Vloot & Materieel

### machine_groups
`group_number VARCHAR(50) UNIQUE`, `group_name VARCHAR(150)`, `description TEXT`, timestamps, soft delete.

### machine_subgroups
`group_id` FK, `subgroup_number VARCHAR(50)`, `subgroup_name VARCHAR(150)`, `description TEXT`, timestamps, soft delete.
UNIQUE(`group_id`,`subgroup_number`).

### machines
| Kolom | Type | Opmerking |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| machine_number | VARCHAR(50) | UNIQUE |
| description | VARCHAR(255) | |
| subgroup_id | BIGINT UNSIGNED | FK |
| brand, model, serial_number | VARCHAR NULL | |
| year | YEAR NULL | |
| status | VARCHAR(50) | "available","rented","maintenance","damaged" |
| location | VARCHAR(150) NULL | |
| external_id, source_system | VARCHAR NULL | |
| timestamps, soft delete | | |

### damages
`damage_number`, `machine_id` FK, `reported_by BIGINT UNSIGNED NULL` (FK → employees.id), `customer_id NULL` FK, `project_id NULL` FK, `damage_date DATE`, `description TEXT`, `estimated_cost DECIMAL(10,2) NULL`, `actual_cost DECIMAL(10,2) NULL`, `status`, timestamps, soft delete.

---

## F. Content (polymorf)

### notes
`notable_id`, `notable_type`, `user_id` FK → users.id, `body TEXT`, `pinned TINYINT(1)`, timestamps, soft delete.
Index op (`notable_type`,`notable_id`).

### documents
`documentable_id`, `documentable_type`, `title VARCHAR(255)`, `category VARCHAR(100)`, `uploaded_by` FK → users.id, `file_path VARCHAR(500)`, `mime_type VARCHAR(100)`, `size_bytes BIGINT`, timestamps, soft delete.

### attachments
`attachable_id`, `attachable_type`, `file_path`, `original_filename`, `mime_type`, `size_bytes`, `uploaded_by` FK → users.id, timestamps, soft delete.

---

## G. Platform

### field_aliases
| Kolom | Type | Opmerking |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| entity | VARCHAR(100) | bv. "customer" |
| alias | VARCHAR(190) | bv. "Debiteurnaam" |
| field | VARCHAR(100) | bv. "customer_name" |
| created_by | BIGINT UNSIGNED NULL | FK → users.id |
| timestamps | | |

UNIQUE(`entity`,`alias`).

### custom_fields
| Kolom | Type | Opmerking |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| entity | VARCHAR(100) | bv. "machine", "project" |
| key | VARCHAR(100) | bv. "atex_certification" |
| label | VARCHAR(150) | bv. "ATEX certificering" |
| type | VARCHAR(30) | "text","number","date","boolean","select" |
| options | JSON NULL | voor select |
| required | TINYINT(1) | 0 |
| sort_order | INT | 0 |
| timestamps, soft delete | | |

UNIQUE(`entity`,`key`).

### custom_field_values
`custom_field_id` FK, `valuable_id`, `valuable_type`, `value TEXT`, timestamps.
UNIQUE(`custom_field_id`,`valuable_type`,`valuable_id`).

### import_profiles
`name VARCHAR(150)`, `entity VARCHAR(100)`, `description TEXT`, `default_mapping JSON`, `created_by` FK → users.id, timestamps, soft delete.

### import_jobs
`profile_id` FK, `user_id` FK, `original_filename`, `file_path`, `status` ("pending","mapping","processing","completed","failed"), `mapping JSON NULL`, `total_rows INT`, `imported_rows INT`, `failed_rows INT`, `error_log TEXT NULL`, `started_at`, `finished_at`, timestamps.

### import_job_rows
`import_job_id` FK, `row_number INT`, `raw_data JSON`, `status` ("pending","imported","skipped","error"), `error_message TEXT NULL`, `created_entity_id BIGINT UNSIGNED NULL`, `created_entity_type VARCHAR(150) NULL`, timestamps.

### audit_logs
| Kolom | Type | Opmerking |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED NULL | FK |
| auditable_id | BIGINT UNSIGNED | polymorf |
| auditable_type | VARCHAR(150) | polymorf |
| event | VARCHAR(50) | "created","updated","deleted","restored" |
| old_values | JSON NULL | |
| new_values | JSON NULL | |
| ip_address | VARCHAR(45) NULL | |
| user_agent | VARCHAR(500) NULL | |
| created_at | TIMESTAMP | |

Index op (`auditable_type`,`auditable_id`), (`user_id`), (`created_at`).

---

## Foreign Key gedrag

- `ON DELETE RESTRICT` voor harde relaties (customer → projects)
- `ON DELETE SET NULL` voor optionele relaties (project → project_manager_id)
- `ON DELETE CASCADE` alleen voor pivots (user_roles, role_permissions) en child-content (custom_field_values)

---

## Seed data (eerste deploy)

- 1 Super Admin user (email vanuit `.env`)
- Default rollen: Super Admin, Administrator, User
- Default applicaties: Boels CORE (deze admin), Fleet App, Project App, Sales App, AI Assistant
- Default permissions per applicatie: `{slug}.view`, `{slug}.manage`
- Voorbeeld field_aliases voor Nederlandse + Engelse veldnamen
