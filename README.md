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
Site administration -> Notifications
```

---

## Configuration

Navigate to:

```text
Site administration
-> Plugins
-> Local plugins
-> Course mirror
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
-> Server
-> Tasks
-> Scheduled tasks
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
-> Plugins
-> Local plugins
-> Run course mirror sync
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
-> Server
-> Web services
-> Manage protocols
```

has REST enabled.

---

## Privacy

This plugin stores **no personal user data**.

A synchronization log table is maintained for operational purposes.

---

## Remote Moodle Web Service Setup

The destination Moodle must expose a REST web service that allows this plugin to:

1. Search for existing courses
2. Create missing courses

The remote Moodle administrators must complete **all** of the following steps.

---

## Remote Moodle Setup Checklist

Before sending the token back, confirm that:

- Web services are enabled
- REST protocol is enabled
- A dedicated service user exists
- The service user has permission to create courses
- A custom external service exists
- The required functions are added to the service
- A token has been created for the service user
- The token is not expired
- The service is enabled

Yes, all of these matter. Moodle web services are not so much "configured" as "assembled from the corpses of previous admins."

---

## Step 1: Enable Web Services

On the **remote Moodle**, go to:

```text
Site administration
-> Advanced features
```

Find:

```text
Enable web services
```

Set it to:

```text
Yes
```

Save changes.

---

## Step 2: Enable the REST Protocol

Go to:

```text
Site administration
-> Server
-> Web services
-> Manage protocols
```

Find:

```text
REST protocol
```

Enable it.

Save changes.

---

## Step 3: Create a Dedicated Service User

Create a normal Moodle user account for the sync service.

Suggested username:

```text
svc_course_mirror
```

Suggested email:

```text
svc_course_mirror@example.edu
```

Do **not** use a real human administrator account.

Do **not** use someone's personal account.

Do **not** use the site admin account unless the goal is to be more like the Apple "Geniuses."

---

## Step 4: Create a Role for the Service User

Go to:

```text
Site administration
-> Users
-> Permissions
-> Define roles
```

Click:

```text
Add a new role
```

Use:

```text
No role archetype
```

Suggested role name:

```text
Course Mirror Web Service
```

Suggested short name:

```text
coursemirrorws
```

Set context type where this role may be assigned:

```text
System
```

Allow the capabilities needed to create courses.

At minimum, enable:

```text
moodle/course:create
moodle/course:view
moodle/category:viewcourselist
```

Depending on remote Moodle configuration, the service user may also need:

```text
moodle/course:update
moodle/course:visibility
moodle/category:manage
```

Only add broader permissions if the token fails due to missing capabilities.

Save the role.

---

## Step 5: Assign the Role to the Service User

Go to:

```text
Site administration
-> Users
-> Permissions
-> Assign system roles
```

Select the role:

```text
Course Mirror Web Service
```

Add the service user:

```text
svc_course_mirror
```

Save changes.

---

## Step 6: Create a Custom External Service

Go to:

```text
Site administration
-> Server
-> Web services
-> External services
```

Click:

```text
Add
```

Use:

```text
Name: Course Mirror Service
Short name: coursemirror
Enabled: Yes
Authorised users only: Yes
```

Recommended:

```text
Can download files: No
Can upload files: No
```

Save changes.

---

## Step 7: Add Required Functions to the Service

After creating the service, find:

```text
Course Mirror Service
```

Click:

```text
Functions
```

Add these functions:

```text
core_course_get_courses_by_field
core_course_create_courses
```

These are required.

Without the first function, the plugin cannot check whether courses already exist.

Without the second function, the plugin cannot create courses.

This is the kind of dependency chain that should be obvious, but you're apparently not an Apple "Genius."

---

## Step 8: Add the Service User as an Authorized User

Return to:

```text
Site administration
-> Server
-> Web services
-> External services
```

Find:

```text
Course Mirror Service
```

Click:

```text
Authorised users
```

Add:

```text
svc_course_mirror
```

Save changes.

---

## Step 9: Create the Token

Go to:

```text
Site administration
-> Server
-> Web services
-> Manage tokens
```

Click:

```text
Add
```

Set:

```text
User: svc_course_mirror
Service: Course Mirror Service
Valid until: Optional, but recommended
IP restriction: Optional
```

If using an IP restriction, enter the public IP address of the source Moodle server.

Save changes.

Copy the generated token.

Send the token securely to the source Moodle administrator.

Do **not** paste it into email, Slack, Teams, a spreadsheet, or whatever other cursed place people store secrets because typing "vault" felt like too much work.

---

## Step 10: Configure the Source Moodle Plugin

On the **source Moodle**, go to:

```text
Site administration
-> Plugins
-> Local plugins
-> Course mirror
```

Enter:

```text
Remote Moodle URL: https://remote-moodle.example.edu
Remote web service token: the token from the remote Moodle
Academic periods: select the periods to sync
Create courses as visible: enabled or disabled as desired
```

Save changes.

---

## Step 11: Test the Sync Manually

On the **source Moodle**, go to:

```text
Site administration
-> Plugins
-> Local plugins
-> Run course mirror sync
```

Click:

```text
Run sync now
```

Review the output.

Expected results:

```text
Created remote course: COURSESHORTNAME
Skipped existing remote course: COURSESHORTNAME
Failed syncing COURSESHORTNAME: error message
```

---

## Troubleshooting Remote Web Services

### Error: Access control exception

Likely causes:

- the service user is not authorized for the external service
- the service user does not have the required role
- the role is missing course creation permissions
- the external service is disabled

Check:

```text
Site administration
-> Server
-> Web services
-> External services
```

Then check:

```text
Authorised users
Functions
```

---

### Error: Function not found or unavailable

The external service is missing one or both required functions.

Add:

```text
core_course_get_courses_by_field
core_course_create_courses
```

to the custom external service.

---

### Error: Invalid token

Likely causes:

- token copied incorrectly
- token expired
- token belongs to the wrong service
- token belongs to the wrong user
- service is disabled

Create a new token if needed.

Tiny tragedy, but at least it is fixable.

---

### Error: REST protocol disabled

Enable REST here:

```text
Site administration
-> Server
-> Web services
-> Manage protocols
```

---

### Error: Course category does not exist

This plugin uses the source course category ID:

```php
$course->category
```

The remote Moodle must have a matching course category ID.

If the category IDs do not match, the remote Moodle may reject the course creation request.

Fix options:

1. Align category IDs between systems
2. Add a category mapping layer to this plugin
3. Use a fallback destination category

Option 2 is the correct long-term answer. Option 3 is the "dump everything into a bucket and apologize later" answer.

---

### Error: Required parameter missing

Usually this means the course object is missing a required value for remote course creation.

Verify that the local course has:

```text
fullname
shortname
category
```

Moodle requires these when creating a course.

---

## Minimal Remote Admin Summary

For remote admins who just need the short version:

1. Enable web services
2. Enable REST
3. Create service user
4. Create system role with course creation permissions
5. Assign role to service user
6. Create external service
7. Add these functions:

```text
core_course_get_courses_by_field
core_course_create_courses
```

8. Authorize the service user
9. Generate token
10. Send token securely (figure this out yourself)

That is the whole maze. Try not to feed the demon that lurks in your soul.

---

## License

GNU GPL v3 or later

<http://www.gnu.org/copyleft/gpl.html>

---

Built for Moodle administrators who apparently enjoy moving courses between Moodles instead of experiencing peace.
