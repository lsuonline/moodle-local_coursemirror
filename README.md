# local_coursemirror

Mirror selected Moodle courses to an external Moodle instance.

`local_coursemirror` is a Moodle local plugin that synchronizes courses from a source Moodle instance to a remote Moodle instance using Moodle REST web services.

Courses are selected based on administratively configured **academic periods** from `enrol_wds_periods`.

Only courses that **do not already exist** in the remote Moodle are created.

A course is considered existing if **any** of the following fields match:

- `shortname`
- `fullname`
- `idnumber`

This prevents accidental duplicate course creation.

---

## Features

- Synchronize courses to an external Moodle instance
- Select academic periods using a configurable multi-select
- Scheduled task support
- Manual "Run Sync Now" UI
- Synchronization logging
- Course existence checks by:
  - shortname
  - fullname
  - idnumber
- Preserves source course category
- Preserves selected course metadata:
  - summary
  - course format
  - language
  - visibility
  - start date
  - end date
  - grade visibility
  - news items count
  - max upload size
  - group mode
  - forced group mode
  - completion tracking

---

## Requirements

### Moodle

- Moodle **4.5+**

### Required plugin dependency

This plugin expects the following tables to exist:

```text
enrol_wds_sections
enrol_wds_periods
```

These are provided by LSU Workday Student enrollment integrations.

### Remote Moodle requirements

The remote Moodle instance must:

1. Have **Web Services** enabled
2. Have **REST protocol** enabled
3. Have a valid token for a service user with permissions to:

```text
core_course_get_courses_by_field
core_course_create_courses
```

---

## Installation

Copy the plugin into:

```text
/local/coursemirror
```

Then complete installation from:

```text
Site administration → Notifications
```

---

## Configuration

Navigate to:

```text
Site administration
→ Plugins
→ Local plugins
→ Course mirror
```

Configure:

### Remote Moodle URL

Base URL of the destination Moodle instance.

Example:

```text
https://moodle.example.edu
```

---

### Remote Web Service Token

Token for a remote Moodle account that has permission to:

- search for courses
- create courses

---

### Academic Periods

Select one or more academic periods from `enrol_wds_periods`.

Only courses associated with selected academic periods will be considered for synchronization.

---

### Create Courses as Visible

When enabled:

- newly created remote courses are visible

When disabled:

- newly created remote courses are hidden

This setting only affects **newly created** courses.

---

## How Course Selection Works

Courses are selected using the following query:

```sql
SELECT DISTINCT c.*
FROM mdl_course c
INNER JOIN mdl_enrol_wds_sections sec
    ON sec.moodle_status = c.id
INNER JOIN mdl_enrol_wds_periods per
    ON sec.academic_period_id = per.academic_period_id
WHERE per.academic_period_id IN (...)
ORDER BY c.shortname ASC
```

The selected academic periods are determined from plugin configuration.

---

## How Course Matching Works

Before creating a course remotely, the plugin checks whether a matching course already exists.

The following fields are checked independently:

1. `shortname`
2. `fullname`
3. `idnumber`

If **any** field matches, the course is treated as already existing and will **not** be created.

Example:

| shortname | fullname | idnumber | Result |
|------------|------------|------------|--------|
| no match | no match | match | skipped |
| no match | match | no match | skipped |
| match | no match | no match | skipped |
| no matches | no matches | no matches | created |

This conservative approach helps avoid duplicate course creation.

---

## Course Category Behavior

Courses are created in the **same category ID** as the source Moodle course.

Example:

```php
$course->category
```

### Important

This assumes that **course category IDs are aligned between both Moodle instances**.

If category IDs differ between systems, courses may fail to create or may be created in unintended categories.

If category structures differ, a category mapping layer should be implemented.

---

## Scheduled Task

The plugin installs a scheduled task:

```text
Course mirror
```

Default schedule:

```text
Every 15 minutes
```

Modify the schedule at:

```text
Site administration
→ Server
→ Tasks
→ Scheduled tasks
```

Search for:

```text
Course mirror
```

---

## Manual Synchronization

A manual synchronization page is available at:

```text
Site administration
→ Plugins
→ Local plugins
→ Run course mirror sync
```

This page:

- runs synchronization immediately
- shows execution trace output
- displays a recent synchronization log

---

## Logging

Synchronization events are written to:

```text
local_coursemirror_log
```

Statuses include:

```text
created
skipped
failed
```

Stored information includes:

- Moodle course ID
- shortname
- fullname
- idnumber
- status
- message
- timestamp

---

## Plugin Structure

```text
local/coursemirror/
├── classes/
│   ├── external_client.php
│   ├── local/
│   │   └── sync.php
│   ├── privacy/
│   │   └── provider.php
│   └── task/
│       └── sync_courses.php
├── db/
│   ├── access.php
│   ├── install.xml
│   └── tasks.php
├── lang/
│   └── en/
│       └── local_coursemirror.php
├── index.php
├── settings.php
├── version.php
└── README.md
```

---

## Security Considerations

The remote Moodle token should:

- belong to a dedicated service account
- have only the required web service permissions
- not have unnecessary administrative access

Treat the token like a password.

Because Moodle web service tokens are basically "what if passwords had less supervision."

---

## Troubleshooting

### No courses sync

Check:

- remote Moodle URL
- token validity
- academic periods selected
- scheduled task execution
- remote web services enabled

Also verify that selected academic periods actually return courses.

---

### Courses appear skipped unexpectedly

A course is skipped if **any** of these match remotely:

- shortname
- fullname
- idnumber

Review the remote Moodle for naming collisions.

---

### Courses created in the wrong category

This plugin uses:

```php
$course->category
```

Ensure category IDs match between both Moodles.

---

### Remote Moodle errors

Verify the token user can execute:

```text
core_course_get_courses_by_field
core_course_create_courses
```

Also ensure:

```text
Site administration
→ Server
→ Web services
→ Manage protocols
```

has REST enabled.

---

## Privacy

This plugin stores **no personal user data**.

A synchronization log table is maintained for operational purposes.

---

## License

GNU GPL v3 or later

<http://www.gnu.org/copyleft/gpl.html>

---

Built for Moodle administrators who apparently enjoy moving courses between Moodles instead of experiencing peace.
