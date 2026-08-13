# P14 Expert Pages - V9 Shared Host (Laravel + MySQL + Cloud Host)

## Routes
/expert dashboard, /expert/courses CRUD, /expert/courses/new /{id}/edit, /expert/specifications CRUD, /expert/specifications/new /{id}/edit /import, /expert/prereq, /expert/curriculum list /{entryYear} editor, /expert/forms dept, /expert/tickets help desk /{id}, /expert/messaging/targeted, /expert/resources/pending, /expert/excel/import-export, /expert/settings

## Dashboard /expert
Stats Total Courses Own Dept Active Specs Current Pending Resources To Approve Open Tickets Curriculum Status Draft/Pending/Approved, Quick Actions Add Course Add Spec Import Excel View Pending Approvals View Tickets, Recent Activity Last 5 course/spec edits last 5 ticket replies, Data GET /api/v1/expert/stats?department_id=own, Polling for new tickets?

## Courses CRUD /expert/courses
List Table Code Name Credits Is Active Spec Count Actions Edit/Delete/View Specs Search Export Import Add Course button, Create/Edit Form Code unique Name Credits 0-6 Is Active toggle Dept auto own read-only Expert, Validation Code unique via API check, On save POST /api/v1/courses or PATCH, AuditLog

## Specifications CRUD /expert/specifications
List Table Code/Name Professor ID/Name Day Persian Time Start-End Location Exam Final Date Shamsi Is Active Enrolled Count Actions Edit/Delete/View Students/Resources, Filters Semester current+archive Course Professor Day multi-select Search Export Import Add Spec button, Create/Edit Form Course dropdown own dept required Professor dropdown own dept required Day sat-sun-mon-tue-wed-thu-fri required Time Start/End HH:MM end>start Location required Telegram Link URL optional https://t.me/ Final Exam Shamsi required Midterm optional Semester ID current+future required Is Active toggle default true, On save Validation professor belongs own dept course belongs own dept time overlap same professor warning "استاد {name} در {day} {time} قبلا کلاس دارد", On edit time/location/day confirmation modal "این تغییر باعث ارسال اعلان به دانشجویان ثبت‌نام شده می‌شود - ادامه؟" lists affected count, Change Alert polling + Pushe PHP curl to enrolled, On delete confirmation typing code archive enrollments notification, Import File drop Excel template download Preview table validation errors Confirm Import POST /api/v1/import/specifications multipart returns validation report or success

## Prerequisite Manager /expert/prereq
Select Course dropdown own dept, Shows Current Prerequisites list Codes/Names Current Co-requisites list, Add Prereq Search own dept not self cycle detection DFS PHP API, Add Co-req Search, Graph View optional nodes edges, Save POST /api/v1/courses/{id}/prerequisites {prereqIds, coReqIds}, Cycle detection error "ایجاد چرخه پیش‌نیاز ممنوع است: {cycle path}"

## Curriculum Editor /expert/curriculum
List Entry Year Status Version Last Updated Approver Actions Edit/View/Submit/Delete Draft Add New Entry Year, Editor Header Entry Year Status Version Last Updated Tree editor expandable semesters 1-12 each semester courses list drag-drop dnd-kit Course Row Code/Name Credits Is Required toggle Prerequisites multi-select Suggested Semester Remove Add Course Search Import Excel, Save as Draft PATCH, Submit for Approval POST pending_approval notifies Head via polling + Pushe, Diff View vs last approved added green removed red

## Forms Management /expert/forms
List own dept forms + univ read-only, CRUD own dept Title Description File PDF/DOCX 20MB Signature guide one-liner required Is Active toggle, On save file to /uploads/forms/{dept}/{uuid}.pdf

## Tickets Help Desk /expert/tickets
List Filters Status Assigned to me/unassigned/all own dept Department Search Sort, Table ID Student ID/Name Subject Dept Status Assigned Updated Escalated badge, Click detail, Detail Timeline student info sidebar Assign to me sets assigned_to self in_progress, Reply text + file any max20MB except exe, Close with reason, status answered on expert reply

## Targeted Messaging /expert/messaging/targeted
Input IDs comma Excel max 50 Add list ID resolved name status valid/invalid/banned Lookup debounced GET /api/v1/users/{id}?dept=own generic error "کاربر یافت نشد یا دسترسی ندارید" for not found or not in dept, If valid shows name + banned badge "بن شده - فقط ادمین می‌تواند پیام دهد" disables send for Expert, Compose Subject Body Send POST recipient_ids array Rate limit 10/min MySQL

## Pending Resources /expert/resources/pending
List pending own dept Title Uploader ID/Name Course+Prof Upload date Preview Approve/Reject

## Excel Import/Export /expert/excel/import-export
Tabs Import Type Courses Specs Curriculum Calendar File drop Template Download Preview Confirm, Export Type Dept auto own Semester Download GET /api/v1/export/{type}

## Settings /expert/settings Same

## Offline V9
View cached courses/specs/tickets cached, CRUD requires online must be server validated, Polling for new pending resources
