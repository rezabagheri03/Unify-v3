# P15 Head of Department Pages - V9 Shared Host

## Inherits Expert +

## Dashboard /head
Extra Stats vs Expert Pending Curriculum Approval Count Professor Oversight Stats Professors own dept count 0 resources flag red, UI same Expert + sections Pending Approvals list entry years pending quick Approve/Reject Professor Oversight Table Professors ID Name Spec Count Current Resource Count Current Semester Last Upload Date Shamsi Status green >=1 resource yellow 0 but has specs red no specs Actions View Specs/Resources Send Reminder, All Expert stats included

## Final Chart Approval Queue /head/curriculum/pending-approval
List pending_approval own dept Entry Year Submitted At Shamsi Submitted By Expert Name Version Actions Preview/Approve/Reject, Preview tree + diff vs last approved version added green removed red modified yellow, Approve button "تایید نهایی و انتشار" confirmation typing entry year POST /api/v1/curriculum-charts/{id}/approve status approved approved_at now approver self version increment notifies Expert + students dept entry year low polling + Pushe, Reject button reason required POST reject status draft notifies Expert with reason

## Professor Oversight /head/oversight/professors
List Table Search filter status green/yellow/red, Detail /head/oversight/professors/{profId} Header Name ID Dept Tabs Specs Current Resources Current Action Send Reminder Message prefilled recipient professor, Spec Override edit/delete any spec own dept even if created by Expert same change alert polling + Pushe, Other pages all Expert pages available, Settings same Expert + Department Default Theme dropdown saves Department.default_theme_id PATCH

## Settings /head/settings Same Expert + Department Default Theme
