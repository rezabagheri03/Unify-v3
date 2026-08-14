# ROLE: HEAD OF DEPARTMENT - V9 Shared Host

## Identity
- ID Personnel ID, role head_of_dept, department_id NOT NULL, scope own dept, inherits all Expert permissions + extra

## Extra Permissions
- CAN: Final Chart Approval pending_approval->approved, Professor Oversight dashboard, view all Expert actions own dept read-only audit filtered, override Expert spec changes
- CANNOT: Set semester/global phase, ban, hard delete, audit full, univ forms, analytics full

## Dashboard Head
- Includes Expert stats + Pending Curriculum Approval Count + Professor Oversight Stats: Professors own dept list with spec count current, resource count current semester, last upload date, flag if 0 resources
- Charts pending_approval entry years awaiting final approval

## Final Chart Approval
- List pending_approval own dept: Entry Year, Submitted At Shamsi, Submitted By Expert Name, Version, Actions Preview/Approve/Reject
- Preview tree + diff vs last approved version added green removed red modified yellow
- Approve button "تایید نهایی و انتشار" confirmation typing entry year -> POST /api/v1/curriculum-charts/{id}/approve status approved approved_at now approver self version increment notifies Expert + students dept entry year low polling + Pushe
- Reject button reason required -> POST reject status draft notifies Expert with reason

## Professor Oversight
- Table Professor ID Name Spec Count Current Resource Count Current Semester Last Upload Date Shamsi Status green >=1 resource yellow 0 but has specs red no specs Actions View Specs/Resources Send Reminder
- Detail professor ID: Tabs Specs Current, Resources Current
- Send Reminder: Private message to professor "لطفا جزوه درس X را آپلود کنید"

## Spec Override
- Can edit/delete any spec own dept even if created by Expert, same change alert via polling + Pushe

## Department Default Theme
- Dropdown theme presets "تم پیش‌فرض دانشکده" saves Department.default_theme_id via PATCH /api/v1/departments/{id}/default-theme (Head/Admin)

END HEAD V9
