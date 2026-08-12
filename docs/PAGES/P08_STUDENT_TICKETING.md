# P08 Student Ticketing - V9 Shared Host (MySQL + Cron + Polling)

## Routes
/tickets list, /tickets/{id} detail, /tickets/new create

## List Page /tickets
UI AppBar Title "پشتیبانی و تیکت" + New Ticket Fab, Tabs Open Answered Closed All + counts, Filters Department education/technical/student_affairs dropdown search subject, List Virtualized tickets sorted updated_at desc infinite Ticket Row Status badge colors open gray in_progress blue answered green closed black Department badge Subject bold Last reply preview Updated Shamsi Assigned to name if assigned Escalated red badge if is_escalated Attachment icon if has student attachments, Empty No tickets illustration + button New Ticket

Data GET /api/v1/tickets?status=&department=&search=&page= student own only Workbox cache, Polling events ticket_updated ticket_replied ticket_closed via polling 15s

## Detail Page /tickets/{id}
UI Header Ticket ID short Subject Department badge Status badge Created Shamsi Assigned Escalated badge Close reason if closed, Description Student original description + student attachments images preview thumbnails clickable lightbox, Timeline Vertical timeline TicketReply sorted asc Each reply bubble Left avatar student or staff name badge is_staff blue Body text Attachments student images preview staff file download sent Shamsi small, Reply Section bottom If status closed banner "این تیکت بسته شده" + button "ثبت تیکت مرتبط" navigates /tickets/new?related_id={id} prefilled [مرتبط با #ID] old subject If open/answered/in_progress textarea + image picker max3 total per ticket Send POST /api/v1/tickets/{id}/reply body attachments, Actions No edit/delete for student replies only staff can close

Detail Data GET /api/v1/tickets/{id} includes replies array, POST /api/v1/tickets/{id}/reply body attachments multipart

Reply Flow Student Text required max2000 images optional max3 total per ticket each 5MB, On send student reply sets status open if was answered per state machine via Laravel model observer, Push to assigned staff or all experts dept if unassigned via polling + Pushe PHP curl

## Create Page /tickets/new
Form Department dropdown required education/technical/student_affairs with icons Subject text required max100 Description textarea required max2000 Attachments image picker preview thumbnails remove image button validation, Optional related_id query param If ?related_id present prefill subject "[مرتبط با #{id}] {old subject}" and description link old ticket, Submit button "ثبت تیکت" On success snackbar "تیکت ثبت شد" navigate detail
API POST /api/v1/tickets multipart department subject description attachments images, Validation Department required Subject max100 Description max2000 Images max3 each 5MB images only mime image/jpeg/png finfo, Rate limit 5 per day per student via MySQL table, 429 error "حداکثر 5 تیکت در روز"

## Offline V9
List cached Workbox, detail cached, create queued IndexedDB with local image staging path, reply queued

## Edge V9
Reply to closed ticket 403 error shows banner suggests related ticket, Image 6MB error "حجم هر تصویر حداکثر 5 مگابایت", 4th image error "حداکثر 3 تصویر", Escalated badge red tooltip "این تیکت به ادمین اسکلیشن شده", Staff file attachment download requires online if not cached Cache API, Cron escalation hourly Laravel command tickets:escalate checks 48h no staff reply is_escalated=1

## Notifications V9
On staff reply answered polling + Pushe to student, On student reply open polling + Pushe to staff
